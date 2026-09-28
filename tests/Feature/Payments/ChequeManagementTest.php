<?php

namespace Tests\Feature\Payments;

use App\Enums\UserRole;
use App\Models\Cheque;
use App\Models\FinancialAccount;
use App\Models\Party;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\StoreStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChequeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private FinancialAccount $account;
    private Party $customer;
    private Party $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StoreStructureSeeder::class, ChartOfAccountsSeeder::class]);
        $this->accountant = User::factory()->create(['role' => UserRole::Accountant, 'is_active' => true]);
        $this->account = FinancialAccount::firstOrFail();
        $this->customer = Party::create(['name' => 'مشتری چکی', 'is_active' => true]);
        $this->supplier = Party::create(['name' => 'فروشنده چکی', 'is_active' => true]);
    }

    public function test_receiving_and_issuing_cheques_create_treasury_records(): void
    {
        $base = [
            'financial_account_id' => $this->account->id, 'amount_toman' => 2_500_000,
            'payment_method' => 'cheque', 'check_number' => 'CH-100', 'sayad_id' => '1234567890123456',
            'bank_name' => 'ملت', 'account_owner' => 'علی رضایی', 'due_date' => '2026-10-20',
            'date' => '2026-09-27',
        ];

        $this->actingAs($this->accountant)->post(route('payments.store'), $base + ['type' => 'receipt', 'party_id' => $this->customer->id])->assertRedirect();
        $this->assertDatabaseHas('cheques', ['direction' => 'received', 'status' => 'on_hand', 'check_number' => 'CH-100', 'amount_rials' => 25_000_000]);

        $this->actingAs($this->accountant)->post(route('payments.store'), array_merge($base, ['type' => 'payment', 'party_id' => $this->supplier->id, 'check_number' => 'CH-200', 'sayad_id' => '6543210987654321']))->assertRedirect();
        $this->assertDatabaseHas('cheques', ['direction' => 'issued', 'status' => 'scheduled', 'check_number' => 'CH-200']);
    }

    public function test_received_cheque_can_be_endorsed_only_once(): void
    {
        $cheque = Cheque::create([
            'direction' => 'received', 'status' => 'on_hand', 'party_id' => $this->customer->id,
            'check_number' => 'CH-300', 'bank_name' => 'ملی', 'amount_rials' => 18_000_000, 'due_date' => '2026-11-01',
        ]);
        $payload = [
            'type' => 'payment', 'party_id' => $this->supplier->id, 'financial_account_id' => $this->account->id,
            'payment_method' => 'endorsed_cheque', 'cheque_id' => $cheque->id, 'date' => '2026-09-27',
        ];

        $this->actingAs($this->accountant)->post(route('payments.store'), $payload)->assertRedirect();
        $cheque->refresh();
        $this->assertSame('endorsed', $cheque->status);
        $this->assertSame($this->supplier->id, $cheque->endorsed_to_party_id);
        $this->assertSame(18_000_000, Payment::findOrFail($cheque->endorsed_payment_id)->amount_rials);

        $this->actingAs($this->accountant)->from(route('payments.create'))->post(route('payments.store'), $payload)
            ->assertSessionHasErrors('cheque_id');
        $this->assertSame(1, Payment::where('payment_method', 'endorsed_cheque')->count());
    }
}
