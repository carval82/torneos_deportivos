<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentPayment;
use App\Services\TournamentBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentPaymentController extends Controller
{
    public function __construct(private readonly TournamentBillingService $billing) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isMaster()) {
            $payments = TournamentPayment::with(['user', 'referenceTournament', 'approver'])
                ->latest()
                ->paginate(30);
        } else {
            abort_unless($user->isOrganizer(), 403);
            $payments = TournamentPayment::query()
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(20);
        }

        return view('billing.index', [
            'payments' => $payments,
            'canCreate' => $this->billing->canCreateOrRenew($user),
            'freeQuota' => $this->billing->remainingFreeQuota($user),
            'fee' => TournamentPayment::FEE_AMOUNT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOrganizer() || $user->isMaster(), 403);

        $data = $request->validate([
            'purpose' => ['required', 'in:create,renew'],
            'reference_tournament_id' => ['nullable', 'exists:tournaments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reference = null;
        if (! empty($data['reference_tournament_id'])) {
            $reference = Tournament::findOrFail($data['reference_tournament_id']);
            abort_unless($user->isMaster() || $reference->user_id === $user->id, 403);
        }

        $payment = $this->billing->requestPayment(
            $user,
            $data['purpose'],
            $reference,
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('billing.index')
            ->with('status', 'Solicitud enviada por '.$payment->amountLabel().'. El master la revisará y te acreditará 1 torneo.');
    }

    public function approve(Request $request, TournamentPayment $payment): RedirectResponse
    {
        abort_unless($request->user()->isMaster(), 403);
        $this->billing->approve($payment, $request->user());

        return back()->with('status', 'Pago aprobado. Se acreditó 1 torneo a '.$payment->user->name.'.');
    }

    public function reject(Request $request, TournamentPayment $payment): RedirectResponse
    {
        abort_unless($request->user()->isMaster(), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->billing->reject($payment, $request->user(), $data['notes'] ?? null);

        return back()->with('status', 'Solicitud rechazada.');
    }
}
