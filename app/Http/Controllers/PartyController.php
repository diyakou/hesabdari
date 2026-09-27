<?php

namespace App\Http\Controllers;

use App\Actions\Parties\UpsertPartyAction;
use App\Enums\PartyRoleType;
use App\Enums\PartyType;
use App\Models\AuditLog;
use App\Models\Party;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PartyController extends Controller
{
    public function __construct(private readonly \App\Services\Auditing\AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Party::class);

        $query = Party::with('roles')->latest();

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$asciiSearch}%")
                    ->orWhere('phone', 'like', "%{$asciiSearch}%")
                    ->orWhere('national_id', 'like', "%{$asciiSearch}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('role', $role));
        }

        $parties = $query->paginate(15)->withQueryString();

        return view('parties.index', compact('parties'));
    }

    public function create(): View
    {
        Gate::authorize('create', Party::class);

        return view('parties.create');
    }

    public function store(Request $request, UpsertPartyAction $action): RedirectResponse
    {
        Gate::authorize('create', Party::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:individual,company'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:customer,supplier'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'credit_limit_toman' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $creditLimitRials = ! empty($validated['credit_limit_toman'])
            ? (int) ($validated['credit_limit_toman'] * 10)
            : null;

        $party = $action->execute([
            'name' => $validated['name'],
            'type' => PartyType::from($validated['type']),
            'roles' => $validated['roles'],
            'mobile' => $validated['mobile'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_limit_rials' => $creditLimitRials,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            'party.created',
            $party,
            [],
            ['name' => $party->name, 'roles' => $validated['roles']],
            $request->user(),
        );

        return redirect()->route('parties.show', $party)
            ->with('status', 'طرف‌حساب جدید با موفقیت ثبت شد.');
    }

    public function show(Party $party): View
    {
        Gate::authorize('view', $party);

        $party->load('roles');

        return view('parties.show', compact('party'));
    }

    public function edit(Party $party): View
    {
        Gate::authorize('update', $party);

        $party->load('roles');

        return view('parties.edit', compact('party'));
    }

    public function update(Request $request, Party $party, UpsertPartyAction $action): RedirectResponse
    {
        Gate::authorize('update', $party);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:individual,company'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:customer,supplier'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'credit_limit_toman' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $creditLimitRials = ! empty($validated['credit_limit_toman'])
            ? (int) ($validated['credit_limit_toman'] * 10)
            : null;

        $action->execute([
            'name' => $validated['name'],
            'type' => PartyType::from($validated['type']),
            'roles' => $validated['roles'],
            'mobile' => $validated['mobile'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_limit_rials' => $creditLimitRials,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ], $party);

        $this->auditLogger->record(
            'party.updated',
            $party,
            [],
            ['name' => $party->name],
            $request->user(),
        );

        return redirect()->route('parties.show', $party)
            ->with('status', 'مشخصات طرف‌حساب به‌روزرسانی شد.');
    }
}
