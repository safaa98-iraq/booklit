<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

trait HasDeviceFields
{
    protected function deviceRules(): array
    {
        return [
            'device_id' => ['required', 'string', 'min:8', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:191'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
        ];
    }

    /** @return array{device_id: string, device_name: ?string, platform: string, ip_address: ?string} */
    public function device(): array
    {
        return [
            'device_id' => (string) $this->validated('device_id'),
            'device_name' => $this->validated('device_name'),
            'platform' => (string) $this->validated('platform'),
            'ip_address' => $this->ip(),
        ];
    }
}
