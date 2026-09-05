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

        return $user->tournament_credits > 0 && $this->hasValidIdentity($user);
    }

    public function remainingFreeQuota(User $user): int
    {
        return $user->isAdmin() ? 999 : 0;
    }

    public function hasValidIdentity(User $user): bool
    {
        $document = User::normalizeDocument($user->document_number);

        if (! $document) {
            return false;
        }

        return ! User::query()
            ->where('id', '!=', $user->id)
            ->where('document_number', $document)
            ->exists();
    }

    public function assertIdentity(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $document = User::normalizeDocument($user->document_number);
        if (! $document) {
            throw ValidationException::withMessages([
                'billing' => 'Antes de crear o renovar un torneo cargá tu cédula en el perfil. Así no se evaden pagos con otra cuenta.',
            ]);
        }

        $other = User::query()
            ->where('id', '!=', $user->id)
            ->where('document_number', $document)
            ->first();

        if ($other) {
            throw ValidationException::withMessages([
                'billing' => 'Esta cédula ya está en otra cuenta ('.$other->email.'). No se puede crear ni renovar torneos con un documento duplicado.',
            ]);
        }
    }

    /**
     * Consume 1 paid credit. Every tournament costs a credit (no free slot).
     */
    public function consumeForCreate(User $user): string
    {
        if ($user->isAdmin()) {
            return 'paid';
        }

        $this->assertIdentity($user);

        if ($user->tournament_credits < 1) {
            throw ValidationException::withMessages([
                'billing' => 'Cada torneo o renovación cuesta '.TournamentPayment::feeLabel().'. Solicitá la activación y esperá la aprobación del master.',
            ]);
        }

        $user->decrement('tournament_credits');

        return 'paid';
    }

    public function requestPayment(User $user, string $purpose = TournamentPayment::PURPOSE_CREATE, ?Tournament $reference = null, ?string $notes = null): TournamentPayment
    {
        $this->assertIdentity($user);

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
