<?php

namespace App\Actions\Services;

use App\Models\Party;
use App\Models\ServiceDefinition;
use App\Models\ServiceFormVersion;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateServiceOrderAction
{
    /**
     * @param array{
     *     party_id: int,
     *     service_definition_id: int,
     *     form_data: array<string, mixed>,
     *     direct_cost_rials?: int,
     *     promised_date?: string|null,
     *     technician_id?: int|null,
     *     notes?: string|null
     * } $data
     */
    public function execute(array $data, ?User $actor = null): ServiceOrder
    {
        $party = Party::findOrFail($data['party_id']);
        $service = ServiceDefinition::findOrFail($data['service_definition_id']);

        $latestVersion = ServiceFormVersion::where('service_definition_id', $service->id)
            ->latest('version')
            ->firstOrFail();

        // Validate form data against schema
        $formData = $data['form_data'] ?? [];
        $schema = $latestVersion->fields_schema;

        foreach ($schema as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label'] ?? $fieldName;
            $isRequired = $field['required'] ?? false;
            $val = $formData[$fieldName] ?? null;

            $isEmpty = $val === null || $val === '' || (($field['type'] ?? '') === 'boolean' && ! $val);

            if ($isRequired && $isEmpty) {
                throw ValidationException::withMessages([
                    "form_data.{$fieldName}" => "تکمیل فیلد «{$fieldLabel}» الزامی است.",
                ]);
            }
        }

        return DB::transaction(function () use ($data, $party, $service, $latestVersion, $formData, $actor) {
            $countToday = ServiceOrder::whereDate('created_at', now()->toDateString())->count();
            $orderNumber = 'SRV-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            return ServiceOrder::create([
                'order_number' => $orderNumber,
                'party_id' => $party->id,
                'service_definition_id' => $service->id,
                'service_form_version_id' => $latestVersion->id,
                'form_data' => $formData,
                'status' => 'queued',
                'direct_cost_rials' => (int) ($data['direct_cost_rials'] ?? 0),
                'promised_date' => $data['promised_date'] ?? null,
                'technician_id' => $data['technician_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id ?? 1,
            ]);
        });
    }
}
