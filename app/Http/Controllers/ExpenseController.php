<?php

namespace App\Http\Controllers;

use App\Actions\Operations\RecordExpenseAction;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Party;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RecordExpenseAction $recordExpenseAction,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به بخش هزینه‌ها فقط برای مدیر و حسابدار مجاز است.');
        }

        $query = Expense::with(['financialAccount', 'party', 'creator'])->latest('date');

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $expenses = $query->paginate(15)->withQueryString();

        return view('expenses.index', compact('expenses'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به ثبت هزینه فقط برای مدیر و حسابدار مجاز است.');
        }

        $accounts = FinancialAccount::where('is_active', true)->get();
        $parties = Party::where('is_active', true)->orderBy('name')->get();

        $categories = [
            'اجاره محل فروشگاه',
            'حقوق و دستمزد پرسنل',
            'قبوض آب، برق، گاز و تلفن',
            'تبلیغات و بازاریابی',
            'بسته‌بندی و ارسال (پیک/پست)',
            'ملزومات مصرفی و اداری',
            'تعمیرات و نگهداری تجهیزات',
            'پذیرایی و تشریفات',
            'سایر هزینه‌های عمومی',
        ];

        return view('expenses.create', compact('accounts', 'parties', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به ثبت هزینه فقط برای مدیر و حسابدار مجاز است.');
        }

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'party_id' => ['nullable', 'exists:parties,id'],
            'amount_toman' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $amountRials = (int) ($validated['amount_toman'] * 10);

        $expense = $this->recordExpenseAction->execute([
            'category' => $validated['category'],
            'financial_account_id' => (int) $validated['financial_account_id'],
            'party_id' => ! empty($validated['party_id']) ? (int) $validated['party_id'] : null,
            'amount_rials' => $amountRials,
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
        ], $user);

        $this->auditLogger->record(
            'expense.created',
            $expense,
            [],
            [
                'expense_number' => $expense->expense_number,
                'category' => $expense->category,
                'amount_rials' => $expense->amount_rials,
            ],
            $user
        );

        return redirect()->route('expenses.index')
            ->with('status', "هزینه با شماره سند {$expense->expense_number} با موفقیت ثبت گردید.");
    }
}
