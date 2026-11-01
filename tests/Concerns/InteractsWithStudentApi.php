<?php

namespace Tests\Concerns;

use App\Models\User;

trait InteractsWithStudentApi
{
    protected string $deviceId = 'device-AAAA-1111';

    /** Log a student in through the API and return the bearer token. */
    protected function loginStudent(User $student, ?string $deviceId = null, string $password = 'password'): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => $student->phone,
            'password' => $password,
            'device_id' => $deviceId ?? $this->deviceId,
            'device_name' => 'Pixel 8',
            'platform' => 'android',
        ])->assertOk();

        return $response->json('token');
    }

    /** Headers for an authenticated request from the given device. */
    protected function studentHeaders(string $token, ?string $deviceId = null): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Device-Id' => $deviceId ?? $this->deviceId,
            'Accept' => 'application/json',
        ];
    }

    /** Create a student, log in, and return [student, headers]. */
    protected function actingAsStudentOnDevice(?User $student = null): array
    {
        $student ??= User::factory()->create();
        $token = $this->loginStudent($student);

        return [$student, $this->studentHeaders($token)];
    }
}
