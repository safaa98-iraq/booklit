<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\DeviceMismatchException;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly DeviceBindingService $devices) {}

    /**
     * @param  array{name: string, phone: string, password: string}  $data
     * @param  array{device_id: string, device_name?: ?string, platform?: ?string, ip_address?: ?string}  $device
     * @return array{user: User, token: string}
     */
    public function register(array $data, array $device): array
    {
        return DB::transaction(function () use ($data, $device) {
            $user = $this->createStudent($data);

            return $this->issueToken($user, $device);
        });
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException|DeviceMismatchException
     */
    public function login(string $phone, string $password, array $device): array
    {
        return $this->issueToken($this->verifyCredentials($phone, $password), $device);
    }

    /**
     * Shared by the API and the web site: checks phone/password and account status.
     *
     * @throws ValidationException
     */
    public function verifyCredentials(string $phone, string $password): User
    {
        $user = User::students()->where('phone', Phone::normalize($phone))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => 'تم إيقاف هذا الحساب. يرجى التواصل مع الإدارة.',
            ]);
        }

        return $user;
    }

    /** Create a student account (web or API). */
    public function createStudent(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'phone' => Phone::normalize($data['phone']),
            'password' => $data['password'],
            'role' => UserRole::Student,
            'is_active' => true,
        ]);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /** @return array{user: User, token: string} */
    private function issueToken(User $user, array $device): array
    {
        $this->devices->bind($user, $device);

        // One device → one live token: drop tokens issued before this login.
        $user->tokens()->delete();
        $token = $user->createToken($device['device_id'], ['student'])->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return ['user' => $user, 'token' => $token];
    }
}
