<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection('pgsql')->select('select 1');

            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            // Only the exception class is logged. The message can contain host or port details.
            Log::error('health check failed', ['exception' => $e::class]);

            return response()->json(['status' => 'unavailable'], 503);
        }
    }
}
