<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Thrown when a student signs in from a device other than the one bound to the account. */
class DeviceMismatchException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('هذا الحساب مرتبط بجهاز آخر. يرجى التواصل مع الإدارة لإعادة تعيين الجهاز.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'device_mismatch',
        ], 409);
    }
}
