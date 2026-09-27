<?php

namespace App\Actions\Catalog;

use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\IdentifierType;
use App\Enums\Ownership;
use App\Enums\RegistryStatus;
use App\Models\Device;
use App\Models\DeviceIdentifier;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterDeviceAction
{
    /**
     * @param array{
     *     product_variant_id: int,
     *     warehouse_id?: int|null,
     *     physical_condition?: DeviceCondition|string,
     *     battery_health?: int|null,
     *     registry_status?: RegistryStatus|string,
     *     ownership?: Ownership|string,
     *     operational_status?: DeviceStatus|string,
     *     notes?: string|null,
     *     primary_imei: string,
     *     secondary_imei?: string|null,
     *     serial_number?: string|null
     * } $data
     */
    public function execute(array $data): Device
    {
        $primaryImei = trim((string) LocalizedDigits::toAscii($data['primary_imei'] ?? ''));
        $secondaryImei = ! empty($data['secondary_imei']) ? trim((string) LocalizedDigits::toAscii($data['secondary_imei'])) : null;
        $serialNumber = ! empty($data['serial_number']) ? trim((string) LocalizedDigits::toAscii($data['serial_number'])) : null;

        // Validation for 15-digit IMEI
        if (! preg_match('/^[0-9]{15}$/', $primaryImei)) {
            throw ValidationException::withMessages([
                'primary_imei' => 'شناسه IMEI اول باید دقیقاً ۱۵ رقم عددی باشد.',
            ]);
        }

        if ($secondaryImei !== null) {
            if (! preg_match('/^[0-9]{15}$/', $secondaryImei)) {
                throw ValidationException::withMessages([
                    'secondary_imei' => 'شناسه IMEI دوم باید دقیقاً ۱۵ رقم عددی باشد.',
                ]);
            }

            if ($primaryImei === $secondaryImei) {
                throw ValidationException::withMessages([
                    'secondary_imei' => 'شناسه IMEI دوم نمی‌تواند با IMEI اول یکسان باشد.',
                ]);
            }
        }

        // Validate battery health: 0 to 100
        $batteryHealth = isset($data['battery_health']) && $data['battery_health'] !== '' && $data['battery_health'] !== null
            ? (int) LocalizedDigits::toAscii($data['battery_health'])
            : null;

        if ($batteryHealth !== null && ($batteryHealth < 0 || $batteryHealth > 100)) {
            throw ValidationException::withMessages([
                'battery_health' => 'درصد سلامت باتری باید عددی بین ۰ تا ۱۰۰ باشد.',
            ]);
        }

        // Check global uniqueness of IMEIs across all positions and devices
        $existingPrimary = DeviceIdentifier::where('value', $primaryImei)->exists();
        if ($existingPrimary) {
            throw ValidationException::withMessages([
                'primary_imei' => 'این شناسه IMEI قبلاً در سامانه ثبت شده است.',
            ]);
        }

        if ($secondaryImei !== null) {
            $existingSecondary = DeviceIdentifier::where('value', $secondaryImei)->exists();
            if ($existingSecondary) {
                throw ValidationException::withMessages([
                    'secondary_imei' => 'شناسه IMEI دوم قبلاً در سامانه ثبت شده است.',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $primaryImei, $secondaryImei, $serialNumber, $batteryHealth) {
            $device = Device::create([
                'product_variant_id' => $data['product_variant_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'physical_condition' => $data['physical_condition'] ?? DeviceCondition::New,
                'battery_health' => $batteryHealth,
                'registry_status' => $data['registry_status'] ?? RegistryStatus::Unknown,
                'ownership' => $data['ownership'] ?? Ownership::Shop,
                'operational_status' => $data['operational_status'] ?? DeviceStatus::PendingReceipt,
                'notes' => $data['notes'] ?? null,
            ]);

            DeviceIdentifier::create([
                'device_id' => $device->id,
                'type' => IdentifierType::Imei,
                'position' => 'primary',
                'value' => $primaryImei,
            ]);

            if ($secondaryImei !== null) {
                DeviceIdentifier::create([
                    'device_id' => $device->id,
                    'type' => IdentifierType::Imei,
                    'position' => 'secondary',
                    'value' => $secondaryImei,
                ]);
            }

            if ($serialNumber !== null) {
                DeviceIdentifier::create([
                    'device_id' => $device->id,
                    'type' => IdentifierType::Serial,
                    'position' => 'primary',
                    'value' => $serialNumber,
                ]);
            }

            return $device->load(['identifiers', 'variant.product']);
        });
    }
}
