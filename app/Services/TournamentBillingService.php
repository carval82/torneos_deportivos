<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentPayment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TournamentBillingService
{
    public function canCreateOrRenew(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->free_tournament_used) {
            return true;
        }

        return $user->tournament_credits > 0;
    }

    public function remainingFreeQuota(User $user): int
    {
        if ($user->isAdmin()) {
            return 999;
        }

        return $user->free_tournament_used ? 0 : 1;
    }

    /**
     * Consume free slot or paid credit. Returns billing type used.
     */
    public function consumeForCreate(User $user): string
    {
        if ($user->isAdmin()) {
            return 'free';
        }

        if (! $user->free_tournament_used) {
            $user->update(['free_tournament_used' => true]);

            return 'free';
        }

        if ($user->tournament_credits < 1) {
            throw ValidationException::withMessages([
                'billing' => 'Ya usaste tu torneo gratis. Solicitá la activación de $'.number_format(TournamentPayment::FEE_AMOUNT, 0, ',', '.').' COP para crear o renovar otro.',
            ]);
        }

        $user->decrement('tournament_credits');

        return 'paid';
    }

    public function requestPayment(User $user, string $purpose = TournamentPayment::PURPOSE_CREATE, ?Tournament $reference = null, ?string $notes = null): TournamentPayment
    {
        $pending = TournamentPayment::query()
            ->where('user_id', $user->id)
            ->where('status', TournamentPayment::STATUS_PENDING)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'billing' => 'Ya tenés una solicitud de pago pendiente. Esperá la aprobación del master.',
            ]);
        }

        return TournamentPayment::create([
            'user_id' => $user->id,
            'amount' => TournamentPayment::FEE_AMOUNT,
            'currency' => 'COP',
            'purpose' => $purpose,
            'status' => TournamentPayment::STATUS_PENDING,
            'reference_tournament_id' => $reference?->id,
            'notes' => $notes,
        ]);
    }

    public function approve(TournamentPayment $payment, User $master): void
    {
        if (! $master->isAdmin()) {
            abort(403);
        }

        if ($payment->status === TournamentPayment::STATUS_APPROVED) {
            return;
        }

        $payment->update([
            'status' => TournamentPayment::STATUS_APPROVED,
            'approved_by' => $master->id,
            'approved_at' => now(),
        ]);

        $payment->user()->increment('tournament_credits');
    }

    public function reject(TournamentPayment $payment, User $master, ?string $notes = null): void
    {
        if (! $master->isAdmin()) {
            abort(403);
        }

        $payment->update([
            'status' => TournamentPayment::STATUS_REJECTED,
            'approved_by' => $master->id,
            'approved_at' => now(),
            'notes' => $notes ?: $payment->notes,
        ]);
    }
}
