<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\RegisterDeviceAction;
use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\Ownership;
use App\Enums\ProductType;
use App\Enums\RegistryStatus;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(private readonly \App\Services\Auditing\AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Device::class);

        $query = Device::with(['variant.product', 'warehouse', 'identifiers'])->latest();

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->whereHas('identifiers', fn ($iq) => $iq->where('value', 'like', "%{$asciiSearch}%"))
                    ->orWhereHas('variant.product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('operational_status', $status);
        }

        if ($ownership = $request->input('ownership')) {
            $query->where('ownership', $ownership);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        $devices = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('devices.index', compact('devices', 'warehouses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Device::class);

        $variants = ProductVariant::whereHas('product', fn ($q) => $q->where('type', ProductType::Serialized))
            ->with('product')
            ->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('devices.create', compact('variants', 'warehouses'));
    }

    public function store(Request $request, RegisterDeviceAction $action): RedirectResponse
    {
        Gate::authorize('create', Device::class);

        $validated = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'primary_imei' => ['required', 'string', 'max:50'],
            'secondary_imei' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:50'],
            'physical_condition' => ['required', 'string', 'in:new,used'],
            'battery_health' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'registry_status' => ['required', 'string', 'in:unknown,registered,unregistered'],
            'ownership' => ['required', 'string', 'in:shop,customer'],
            'operational_status' => ['required', 'string', 'in:pending_receipt,available,sold,quarantine,returned_to_supplier'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $device = $action->execute([
            'product_variant_id' => (int) $validated['product_variant_id'],
            'warehouse_id' => ! empty($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
            'primary_imei' => $validated['primary_imei'],
            'secondary_imei' => $validated['secondary_imei'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'physical_condition' => DeviceCondition::from($validated['physical_condition']),
            'battery_health' => $validated['battery_health'] ?? null,
            'registry_status' => RegistryStatus::from($validated['registry_status']),
            'ownership' => Ownership::from($validated['ownership']),
            'operational_status' => DeviceStatus::from($validated['operational_status']),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditLogger->record(
            'device.registered',
            $device,
            [],
            ['primary_imei' => $device->primary_imei],
            $request->user(),
        );

        return redirect()->route('devices.show', $device)
            ->with('status', 'دستگاه فیزیکی با موفقیت ثبت شد.');
    }

    public function show(Device $device): View
    {
        Gate::authorize('view', $device);

        $device->load(['variant.product', 'warehouse', 'identifiers']);

        return view('devices.show', compact('device'));
    }
}
