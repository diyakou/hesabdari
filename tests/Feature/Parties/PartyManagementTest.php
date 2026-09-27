<?php

namespace Tests\Feature\Parties;

use App\Actions\Parties\UpsertPartyAction;
use App\Enums\PartyRoleType;
use App\Enums\UserRole;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $salesperson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->salesperson = User::factory()->create([
            'role' => UserRole::Salesperson,
            'is_active' => true,
        ]);
    }

    public function test_party_can_have_multiple_roles_customer_and_supplier(): void
    {
        $action = app(UpsertPartyAction::class);

        $party = $action->execute([
            'name' => 'علی احمدی',
            'roles' => [PartyRoleType::Customer, PartyRoleType::Supplier],
            'mobile' => '۰۹۱۲۱۱۱۱۱۱۱',
            'credit_limit_rials' => 500000000,
        ]);

        $this->assertTrue($party->isCustomer());
        $this->assertTrue($party->isSupplier());
        $this->assertEquals('09121111111', $party->mobile);
        $this->assertDatabaseHas('party_roles', ['party_id' => $party->id, 'role' => 'customer']);
        $this->assertDatabaseHas('party_roles', ['party_id' => $party->id, 'role' => 'supplier']);
    }

    public function test_salesperson_can_view_and_create_parties(): void
    {
        $response = $this->actingAs($this->salesperson)->get(route('parties.index'));
        $response->assertOk();

        $postResponse = $this->actingAs($this->salesperson)->post(route('parties.store'), [
            'name' => 'فروشگاه تستی',
            'type' => 'company',
            'roles' => ['customer'],
            'mobile' => '09351234567',
            'credit_limit_toman' => '20000000',
        ]);

        $postResponse->assertRedirect();
        $this->assertDatabaseHas('parties', ['name' => 'فروشگاه تستی', 'credit_limit_rials' => 200000000]);
    }
}
