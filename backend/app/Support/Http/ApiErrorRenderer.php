<?php

namespace App\Support\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class ApiErrorRenderer
{
    private const HTTP_SERVER_ERROR = 500;

    public static function render(Throwable $e): Response
    {
        // A middleware (for example the rate limiter) already built the response. Pass it through unchanged.
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if (self::isServerError($e)) {
            // The client only sees a generic message. The server log keeps the real cause.
            Log::error('api request failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
        }

        return match (true) {
            $e instanceof ValidationException => self::error(422, 'VALIDATION_FAILED', 'The given data was invalid.', [
                'fields' => $e->errors(),
            ]),
            $e instanceof AuthenticationException => self::error(401, 'UNAUTHENTICATED', 'Authentication is required.'),
            $e instanceof AuthorizationException => self::error(403, 'FORBIDDEN', 'You are not allowed to do this.'),
            $e instanceof ModelNotFoundException => self::error(404, 'NOT_FOUND', 'Resource not found.'),
            $e instanceof HttpException => self::fromHttpException($e),
            default => self::error(self::HTTP_SERVER_ERROR, 'INTERNAL', 'Internal server error.'),
        };
    }

    private static function isServerError(Throwable $e): bool
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode() >= self::HTTP_SERVER_ERROR;
        }

        return ! $e instanceof ValidationException
            && ! $e instanceof AuthenticationException
            && ! $e instanceof AuthorizationException
            && ! $e instanceof ModelNotFoundException;
    }

    private static function fromHttpException(HttpException $e): JsonResponse
    {
        $status = $e->getStatusCode();

        if ($status >= self::HTTP_SERVER_ERROR) {
            return self::error(self::HTTP_SERVER_ERROR, 'INTERNAL', 'Internal server error.');
        }

        return match ($status) {
            403 => self::error(403, 'FORBIDDEN', 'You are not allowed to do this.'),
            404 => self::error(404, 'NOT_FOUND', 'Resource not found.'),
            405 => self::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed.'),
            429 => self::error(429, 'RATE_LIMITED', 'Too many requests. Try again later.'),
            default => self::error($status, 'REQUEST_FAILED', 'Request could not be processed.'),
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function error(int $status, string $code, string $message, array $extra = []): JsonResponse
    {
        return response()->json([
            'error' => array_merge(['code' => $code, 'message' => $message], $extra),
        ], $status);
    }
}
