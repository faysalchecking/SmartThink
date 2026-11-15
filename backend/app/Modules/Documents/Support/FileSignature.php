<?php

namespace App\Modules\Documents\Support;

/**
 * Detects the real file type from its first bytes. The extension and the browser's
 * content type are both ignored, because either can be faked.
 */
final class FileSignature
{
    public const MAX_BYTES = 10485760;

    public static function detect(string $path): ?string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $head = fread($handle, 8);
        fclose($handle);

        if (! is_string($head)) {
            return null;
        }

        if (str_starts_with($head, '%PDF-')) {
            return 'application/pdf';
        }

        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }

        return null;
    }
}
