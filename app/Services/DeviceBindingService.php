<?php

namespace App\Services;

use App\Exceptions\DeviceMismatchException;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Enforces "one active device per student account".
 *
 * The first successful login binds the device. Logins from any other device
 * are rejected until an admin resets the binding.
 */
class DeviceBindingService
{
    /**
     * @param  array{device_id: string, device_name?: ?string, platform?: ?string, ip_address?: ?string}  $device
     *
     * @throws DeviceMismatchException
     */
    public function bind(User $user, array $device): Device
    {
        return DB::transaction(function () use ($user, $device) {
            // Serialise concurrent first-logins for the same account.
            User::whereKey($user->getKey())->lockForUpdate()->first();

            $active = $user->devices()->whereNull('released_at')->latest('bound_at')->first();

            if ($active && ! hash_equals($active->device_id, $device['device_id'])) {
                throw new DeviceMismatchException;
            }

            $attributes = [
                'device_name' => $device['device_name'] ?? $active?->device_name,
                'platform' => $device['platform'] ?? $active?->platform,
                'ip_address' => $device['ip_address'] ?? null,
                'last_seen_at' => now(),
            ];

            if ($active) {
                $active->update($attributes);

                return $active;
            }

            return $user->devices()->create($attributes + [
                'device_id' => $device['device_id'],
                'bound_at' => now(),
            ]);
        });
    }

    public function isBoundTo(User $user, ?string $deviceId): bool
    {
        if (blank($deviceId)) {
            return false;
        }

        $active = $user->devices()->whereNull('released_at')->latest('bound_at')->first();

        return $active !== null && hash_equals($active->device_id, $deviceId);
    }

    public function touch(User $user, string $deviceId): void
    {
        // Throttled to one write every 5 minutes per device.
        $user->devices()->whereNull('released_at')->where('device_id', $deviceId)
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes(5)))
            ->update(['last_seen_at' => now()]);
    }

    /**
     * Release the current binding and revoke every API token, so the student
     * can sign in again from a new device.
     */
    public function reset(User $user, ?User $by = null): void
    {
        DB::transaction(function () use ($user, $by) {
            $user->devices()->whereNull('released_at')->update([
                'released_at' => now(),
                'released_by' => $by?->getKey(),
            ]);

            $user->tokens()->delete();
        });
    }
}
