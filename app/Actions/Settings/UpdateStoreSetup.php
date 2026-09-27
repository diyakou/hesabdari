<?php

namespace App\Actions\Settings;

use App\Models\Branch;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateStoreSetup
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, string|null>  $data
     */
    public function handle(array $data, User $actor): StoreSetting
    {
        return DB::transaction(function () use ($data, $actor): StoreSetting {
            $settings = StoreSetting::query()->firstOrNew(['key' => 'primary']);
            $settingsBefore = $settings->exists ? $settings->getOriginal() : [];
            $settings->fill([
                'name' => $data['store_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'phone' => $data['store_phone'] ?? null,
                'address' => $data['store_address'] ?? null,
                'timezone' => 'Asia/Tehran',
                'storage_currency' => 'IRR',
                'display_currency' => 'IRT',
            ])->save();

            $branch = Branch::query()->firstOrNew(['code' => 'MAIN']);
            $branchBefore = $branch->exists ? $branch->getOriginal() : [];
            $branch->fill([
                'name' => $data['branch_name'],
                'phone' => $data['branch_phone'] ?? null,
                'address' => $data['branch_address'] ?? null,
                'is_active' => true,
                'is_default' => true,
            ])->save();

            $warehouse = Warehouse::query()->firstOrNew(['code' => 'MAIN-WH']);
            $warehouseBefore = $warehouse->exists ? $warehouse->getOriginal() : [];
            $warehouse->fill([
                'branch_id' => $branch->getKey(),
                'name' => $data['warehouse_name'],
                'is_active' => true,
                'is_default' => true,
            ])->save();

            $this->auditLogger->record('store_settings.updated', $settings, $settingsBefore, $settings->getAttributes(), $actor);
            $this->auditLogger->record('branch.updated', $branch, $branchBefore, $branch->getAttributes(), $actor);
            $this->auditLogger->record('warehouse.updated', $warehouse, $warehouseBefore, $warehouse->getAttributes(), $actor);

            return $settings;
        }, attempts: 3);
    }
}
