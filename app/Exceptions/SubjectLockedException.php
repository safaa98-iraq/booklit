<?php

namespace App\Exceptions;

use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Thrown when a student opens ministerial content without an active subscription. */
class SubjectLockedException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $settings = app(SettingsService::class);

        // Web site: back to the home page, which shows the "locked" dialog.
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return redirect()->route('student.library')->with('locked', $settings->get('locked_subject_message'));
        }

        return response()->json([
            'message' => $settings->get('locked_subject_message'),
            'code' => 'subject_locked',
            'telegram_url' => $settings->get('telegram_url'),
            'button_label' => $settings->get('locked_subject_button'),
        ], 403);
    }
}
