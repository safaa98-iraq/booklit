<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Media
{
    public static function disk(): string
    {
        return config('booklit.media_disk');
    }

    /** Public (or temporary signed) URL for a stored media path. */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = Storage::disk(self::disk());

        if (config('booklit.media_signed_urls')) {
            return $disk->temporaryUrl($path, now()->addMinutes((int) config('booklit.media_url_ttl', 60)));
        }

        return $disk->url($path);
    }
}
