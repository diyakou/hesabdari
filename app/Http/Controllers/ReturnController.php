<?php

namespace App\Http\Controllers;

use App\Actions\Invoicing\ProcessSaleReturnAction;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ProcessSaleReturnAction $processSaleReturnAction,
    ) {}

    public function create(Request $request): View
    {
        $user = $request->user();
        if (! $user->is_active || $user->isWarehouseKeeper()) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        $invoiceId = $request->query('invoice_id');
        $invoice = null;

        if ($invoiceId) {
            $invoice = Invoice::with(['lines.variant.product', 'lines.device', 'party'])
                ->where('type', 'sale')
                ->where('status', 'finalized')
                ->findOrFail($invoiceId);
        }

        return view('returns.create', compact('invoice'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->is_active || $user->isWarehouseKeeper()) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        $validated = $request->validate([
            'reference_invoice_id' => ['required', 'exists:invoices,id'],
            'reference_line_id' => ['required', 'exists:invoice_lines,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $returnInvoice = $this->processSaleReturnAction->execute($validated, $user);

        $this->auditLogger->record(
            'sale_return.created',
            $returnInvoice,
            [],
            [
                'return_invoice_number' => $returnInvoice->invoice_number,
                'reference_invoice_id' => $validated['reference_invoice_id'],
                'line_id' => $validated['reference_line_id'],
                'quantity' => $validated['quantity'],
            ],
            $user
        );

        return redirect()->route('sales.show', $returnInvoice)
            ->with('status', 'فاکتور برگشت از فروش با موفقیت صادر و ثبت شد.');
    }
}
