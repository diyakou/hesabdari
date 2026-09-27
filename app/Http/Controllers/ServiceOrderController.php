<?php

namespace App\Http\Controllers;

use App\Actions\Services\CreateServiceOrderAction;
use App\Actions\Services\TransitionServiceOrderStatusAction;
use App\Models\Party;
use App\Models\ServiceDefinition;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceOrderController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CreateServiceOrderAction $createServiceOrderAction,
        private readonly TransitionServiceOrderStatusAction $transitionServiceOrderStatusAction,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeAccess($request->user());

        $query = ServiceOrder::with(['party', 'serviceDefinition', 'technician', 'creator'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->where('order_number', 'like', "%{$asciiSearch}%")
                    ->orWhereHas('party', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('services.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAccess($request->user());

        $customers = Party::whereHas('roles', fn ($q) => $q->where('role', 'customer'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $services = ServiceDefinition::where('is_active', true)->with('latestFormVersion')->get();
        $technicians = User::where('is_active', true)->get();

        return view('services.create', compact('customers', 'services', 'technicians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request->user());

        $validated = $request->validate([
            'party_id' => ['required', 'exists:parties,id'],
            'service_definition_id' => ['required', 'exists:service_definitions,id'],
            'form_data' => ['required', 'array'],
            'direct_cost_toman' => ['nullable', 'numeric', 'min:0'],
            'promised_date' => ['nullable', 'date'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $directCostRials = ! empty($validated['direct_cost_toman'])
            ? (int) ($validated['direct_cost_toman'] * 10)
            : 0;

        $order = $this->createServiceOrderAction->execute([
            'party_id' => (int) $validated['party_id'],
            'service_definition_id' => (int) $validated['service_definition_id'],
            'form_data' => $validated['form_data'],
            'direct_cost_rials' => $directCostRials,
            'promised_date' => $validated['promised_date'] ?? null,
            'technician_id' => ! empty($validated['technician_id']) ? (int) $validated['technician_id'] : null,
            'notes' => $validated['notes'] ?? null,
        ], $request->user());

        $this->auditLogger->record(
            'service_order.created',
            $order,
            [],
            ['order_number' => $order->order_number],
            $request->user(),
        );

        return redirect()->route('services.show', $order)
            ->with('status', 'سفارش خدمت با موفقیت ثبت گردید.');
    }

    public function show(ServiceOrder $service): View
    {
        $this->authorizeAccess(request()->user());

        $service->load(['party', 'serviceDefinition', 'formVersion', 'technician', 'creator', 'invoice']);

        return view('services.show', ['order' => $service]);
    }

    public function updateStatus(Request $request, ServiceOrder $service): RedirectResponse
    {
        $this->authorizeAccess($request->user());

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:in_progress,ready,delivered,cancelled'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $service->status;

        $this->transitionServiceOrderStatusAction->execute(
            $service,
            $validated['status'],
            $validated['cancellation_reason'] ?? null,
            $request->user()
        );

        $this->auditLogger->record(
            'service_order.status_updated',
            $service,
            ['status' => $previousStatus],
            ['status' => $validated['status']],
            $request->user(),
        );

        return redirect()->route('services.show', $service)
            ->with('status', 'وضعیت سفارش خدمت با موفقیت به‌روزرسانی شد.');
    }

    private function authorizeAccess(User $user): void
    {
        if (! $user->is_active || $user->isWarehouseKeeper()) {
            abort(403, 'دسترسی غیرمجاز.');
        }
    }
}
