<?php

namespace Tests\Feature\Catalog;

use App\Actions\Catalog\RegisterDeviceAction;
use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\Ownership;
use App\Enums\ProductType;
use App\Enums\RegistryStatus;
use App\Enums\UserRole;
use App\Models\DeviceIdentifier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeviceImeiValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'iPhone 13 128GB',
            'type' => ProductType::Serialized,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IP13-128-BLK',
            'selling_price_rials' => 450000000,
            'min_selling_price_rials' => 420000000,
        ]);
    }

    /**
     * Requirement 4:
     * دو دستگاه هم‌مدل با IMEI متفاوت پذیرفته شوند؛ تکرار IMEI حتی میان جایگاه اول و دوم رد شود.
     */
    public function test_two_devices_with_different_imeis_accepted(): void
    {
        $action = app(RegisterDeviceAction::class);

        $device1 = $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234567',
        ]);

        $device2 = $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234568',
        ]);

        $this->assertDatabaseHas('device_identifiers', ['value' => '352094101234567']);
        $this->assertDatabaseHas('device_identifiers', ['value' => '352094101234568']);
        $this->assertNotEquals($device1->id, $device2->id);
    }

    public function test_duplicate_imei_across_primary_and_secondary_is_rejected(): void
    {
        $action = app(RegisterDeviceAction::class);

        // First device has IMEI as secondary
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234567',
            'secondary_imei' => '352094101234568',
        ]);

        // Attempting to register second device with same IMEI as primary must be rejected
        $this->expectException(ValidationException::class);
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234568', // Collides with existing secondary IMEI
        ]);
    }

    public function test_same_device_cannot_have_identical_primary_and_secondary_imei(): void
    {
        $action = app(RegisterDeviceAction::class);

        $this->expectException(ValidationException::class);
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234567',
            'secondary_imei' => '352094101234567',
        ]);
    }

    /**
     * Requirement 5:
     * ارقام فارسی استاندارد و صفرهای ابتدا حفظ شوند؛ سلامت باتری خارج از دامنه رد شود.
     */
    public function test_persian_digits_converted_and_leading_zeros_preserved(): void
    {
        $action = app(RegisterDeviceAction::class);

        // IMEI with leading zero and Persian digits: ۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
        $persianImei = '۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴';
        $expectedAscii = '012345678901234';

        $device = $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => $persianImei,
            'battery_health' => 95,
        ]);

        $this->assertEquals($expectedAscii, $device->primary_imei);
        $this->assertDatabaseHas('device_identifiers', [
            'device_id' => $device->id,
            'value' => $expectedAscii,
        ]);
    }

    public function test_battery_health_outside_range_is_rejected(): void
    {
        $action = app(RegisterDeviceAction::class);

        // Greater than 100
        $this->expectException(ValidationException::class);
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234569',
            'battery_health' => 105,
        ]);
    }

    public function test_negative_battery_health_is_rejected(): void
    {
        $action = app(RegisterDeviceAction::class);

        $this->expectException(ValidationException::class);
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094101234569',
            'battery_health' => -5,
        ]);
    }

    public function test_invalid_length_imei_is_rejected(): void
    {
        $action = app(RegisterDeviceAction::class);

        // 14 digits
        $this->expectException(ValidationException::class);
        $action->execute([
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '12345678901234',
        ]);
    }

    public function test_device_creation_via_http_form(): void
    {
        $response = $this->actingAs($this->manager)->post(route('devices.store'), [
            'product_variant_id' => $this->variant->id,
            'primary_imei' => '352094109876543',
            'physical_condition' => 'new',
            'battery_health' => '100',
            'registry_status' => 'registered',
            'ownership' => 'shop',
            'operational_status' => 'available',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('device_identifiers', [
            'value' => '352094109876543',
        ]);
    }
}
