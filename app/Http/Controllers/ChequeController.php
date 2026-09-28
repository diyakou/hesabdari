<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChequeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);
        $query = Cheque::with(['party', 'endorsedToParty', 'payment'])->latest();
        if ($request->filled('direction')) $query->where('direction', $request->string('direction'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));

        $cheques = $query->paginate(20)->withQueryString();
        return view('cheques.index', compact('cheques'));
    }
}
