<?php

namespace App\Exceptions;

use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Thrown when a student opens ministerial content without an active subscription. */
class SubjectLockedException extends RuntimeException
{
    public function render(): JsonResponse
    {
        $settings = app(SettingsService::class);

        return response()->json([
            'message' => $settings->get('locked_subject_message'),
            'code' => 'subject_locked',
            'telegram_url' => $settings->get('telegram_url'),
            'button_label' => $settings->get('locked_subject_button'),
        ], 403);
    }
}
