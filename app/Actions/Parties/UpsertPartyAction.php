<?php

namespace App\Actions\Parties;

use App\Enums\PartyRoleType;
use App\Enums\PartyType;
use App\Models\Party;
use App\Models\PartyRole;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertPartyAction
{
    /**
     * @param array{
     *     name: string,
     *     type?: PartyType|string,
     *     roles: array<string|PartyRoleType>,
     *     mobile?: string|null,
     *     phone?: string|null,
     *     national_id?: string|null,
     *     address?: string|null,
     *     credit_limit_rials?: int|null,
     *     notes?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data, ?Party $party = null): Party
    {
        $mobile = ! empty($data['mobile']) ? trim((string) LocalizedDigits::toAscii($data['mobile'])) : null;
        $phone = ! empty($data['phone']) ? trim((string) LocalizedDigits::toAscii($data['phone'])) : null;
        $nationalId = ! empty($data['national_id']) ? trim((string) LocalizedDigits::toAscii($data['national_id'])) : null;

        if (empty($data['roles'])) {
            throw ValidationException::withMessages([
                'roles' => 'انتخاب حداقل یک نقش (مشتری یا تأمین‌کننده) الزامی است.',
            ]);
        }

        return DB::transaction(function () use ($data, $party, $mobile, $phone, $nationalId) {
            $party = $party ?? new Party();
            $party->fill([
                'name' => trim($data['name']),
                'type' => $data['type'] ?? PartyType::Individual,
                'mobile' => $mobile,
                'phone' => $phone,
                'national_id' => $nationalId,
                'address' => $data['address'] ?? null,
                'credit_limit_rials' => $data['credit_limit_rials'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $party->save();

            // Sync roles
            $roleValues = array_map(
                fn ($r) => $r instanceof PartyRoleType ? $r->value : $r,
                $data['roles']
            );

            $party->roles()->delete();
            foreach (array_unique($roleValues) as $role) {
                PartyRole::create([
                    'party_id' => $party->id,
                    'role' => $role,
                ]);
            }

            return $party->load('roles');
        });
    }
}
