<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const PURPOSE_CREATE = 'create';
    public const PURPOSE_RENEW = 'renew';

    public const FEE_AMOUNT = 70000;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'purpose',
        'status',
        'reference_tournament_id',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referenceTournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'reference_tournament_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function amountLabel(): string
    {
        return '$'.number_format($this->amount, 0, ',', '.').' '.$this->currency;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            default => 'Pendiente',
        };
    }
}
