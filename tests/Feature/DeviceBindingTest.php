<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeviceBindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStudentApi;
use Tests\TestCase;

class DeviceBindingTest extends TestCase
{
    use InteractsWithStudentApi, RefreshDatabase;

    public function test_registration_binds_the_registering_device(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'علي حسن',
            'phone' => '+964 770 123 4567',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_id' => 'phone-device-001',
            'device_name' => 'Galaxy S24',
            'platform' => 'android',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'phone', 'watermark']])
            ->assertJsonPath('user.phone', '07701234567');

        $student = User::where('phone', '07701234567')->firstOrFail();
        $this->assertTrue($student->isStudent());
        $this->assertSame('phone-device-001', $student->activeDevice->device_id);
    }

    public function test_registration_validates_phone_and_uniqueness(): void
    {
        User::factory()->create(['phone' => '07801112233']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'طالب',
            'phone' => '0780 111 2233',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_id' => 'another-device',
            'platform' => 'ios',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'طالب',
            'phone' => '12345',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_id' => 'another-device',
            'platform' => 'ios',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_first_login_binds_device_and_same_device_can_log_in_again(): void
    {
        $student = User::factory()->create();

        $this->loginStudent($student, 'device-one');
        $this->loginStudent($student, 'device-one');

        $this->assertSame(1, $student->devices()->count());
        $this->assertSame('device-one', $student->fresh()->activeDevice->device_id);
    }

    public function test_login_from_a_second_device_is_rejected(): void
    {
        $student = User::factory()->create();
        $this->loginStudent($student, 'device-one');

        $this->postJson('/api/v1/auth/login', [
            'phone' => $student->phone,
            'password' => 'password',
            'device_id' => 'device-two',
            'platform' => 'ios',
        ])->assertStatus(409)->assertJsonPath('code', 'device_mismatch');

        $this->assertSame('device-one', $student->fresh()->activeDevice->device_id);
    }

    public function test_requests_must_come_from_the_bound_device(): void
    {
        $student = User::factory()->create();
        $token = $this->loginStudent($student, 'device-one');

        $this->getJson('/api/v1/me', $this->studentHeaders($token, 'device-one'))->assertOk();

        // Token replayed from another device.
        $this->getJson('/api/v1/me', $this->studentHeaders($token, 'device-two'))
            ->assertStatus(409)->assertJsonPath('code', 'device_mismatch');

        // Missing header.
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])->assertStatus(409);
    }

    public function test_admin_reset_releases_binding_and_revokes_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $oldToken = $this->loginStudent($student, 'old-phone');

        app(DeviceBindingService::class)->reset($student, $admin);

        $this->assertNull($student->fresh()->activeDevice);
        $this->assertSame(0, $student->tokens()->count());
        $this->assertSame($admin->id, $student->devices()->first()->released_by);

        // Old token no longer works.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $this->studentHeaders($oldToken, 'old-phone'))->assertUnauthorized();

        // A new device can now be bound.
        $newToken = $this->loginStudent($student, 'new-phone');
        $this->getJson('/api/v1/me', $this->studentHeaders($newToken, 'new-phone'))->assertOk();
        $this->assertSame('new-phone', $student->fresh()->activeDevice->device_id);
    }

    public function test_logging_in_again_invalidates_previous_tokens(): void
    {
        $student = User::factory()->create();
        $first = $this->loginStudent($student, 'device-one');
        $second = $this->loginStudent($student, 'device-one');

        $this->assertSame(1, $student->tokens()->count());
        $this->getJson('/api/v1/me', $this->studentHeaders($second, 'device-one'))->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $this->studentHeaders($first, 'device-one'))->assertUnauthorized();
    }

    public function test_wrong_password_and_disabled_accounts_cannot_log_in(): void
    {
        $student = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'phone' => $student->phone, 'password' => 'wrong', 'device_id' => 'device-one', 'platform' => 'web',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->assertSame(0, $student->devices()->count());

        $student->update(['is_active' => false]);
        $this->postJson('/api/v1/auth/login', [
            'phone' => $student->phone, 'password' => 'password', 'device_id' => 'device-one', 'platform' => 'web',
        ])->assertUnprocessable();
    }

    public function test_staff_cannot_use_the_student_api(): void
    {
        $editor = User::factory()->editor()->create(['phone' => '07900000000']);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '07900000000', 'password' => 'password', 'device_id' => 'device-one', 'platform' => 'web',
        ])->assertUnprocessable();

        $this->assertSame(0, $editor->devices()->count());
    }
}
