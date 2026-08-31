<x-app-layout>
    <x-slot name="header">{{ auth()->user()->isMaster() ? 'Pagos / Master' : 'Activación de torneos' }}</x-slot>
    <x-slot name="subheader">1er torneo gratis · siguientes y renovaciones: $70.000 COP</x-slot>

    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Torneo gratis</p>
            <p class="mt-2 text-2xl font-semibold text-arena-navy">{{ $freeQuota > 0 ? 'Disponible' : 'Usado' }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">Créditos pagos</p>
            <p class="mt-2 text-2xl font-semibold text-arena-navy">{{ auth()->user()->tournament_credits }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">¿Podés crear / renovar?</p>
            <p class="mt-2 text-2xl font-semibold {{ $canCreate ? 'text-arena-limeDark' : 'text-rose-600' }}">
                {{ $canCreate ? 'Sí' : 'No' }}
            </p>
        </div>
    </div>

    @unless (auth()->user()->isMaster())
        <section class="card p-6 mb-8">
            <h2 class="font-semibold mb-2">Solicitar activación ($70.000 COP)</h2>
            <p class="text-sm text-slate-600 mb-4">
                Delegados y jugadores entran sin costo. El cobro aplica solo al organizador al crear un 2º torneo o renovar.
                El master aprueba el pago y te acredita 1 torneo.
            </p>
            @error('billing')
                <p class="mb-4 text-sm text-rose-600">{{ $message }}</p>
            @enderror
            <form method="POST" action="{{ route('billing.store') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="text-sm text-slate-600">Motivo</label>
                    <select name="purpose" class="field">
                        <option value="create">Crear torneo nuevo</option>
                        <option value="renew">Renovar torneo</option>
                    </select>
                </div>
                <div class="min-w-[220px] flex-1">
                    <label class="text-sm text-slate-600">Nota / comprobante (opcional)</label>
                    <input name="notes" class="field" placeholder="Transferencia, referencia, etc.">
                </div>
                <button class="btn-accent">Solicitar activación</button>
            </form>
        </section>
    @endunless

    <section class="card p-6">
        <h2 class="font-semibold mb-4">{{ auth()->user()->isMaster() ? 'Solicitudes' : 'Mis solicitudes' }}</h2>
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        @if (auth()->user()->isMaster())
                            <th>Organizador</th>
                        @endif
                        <th>Monto</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        @if (auth()->user()->isMaster())
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                            @if (auth()->user()->isMaster())
                                <td>{{ $payment->user?->name }}<br><span class="text-xs text-slate-500">{{ $payment->user?->email }}</span></td>
                            @endif
                            <td class="font-semibold">{{ $payment->amountLabel() }}</td>
                            <td>{{ $payment->purpose === 'renew' ? 'Renovar' : 'Crear' }}</td>
                            <td>{{ $payment->statusLabel() }}</td>
                            <td class="text-sm text-slate-600">{{ $payment->notes ?: '—' }}</td>
                            @if (auth()->user()->isMaster() && $payment->status === 'pending')
                                <td class="space-x-2 whitespace-nowrap">
                                    <form method="POST" action="{{ route('billing.approve', $payment) }}" class="inline">
                                        @csrf
                                        <button class="btn-accent text-xs px-3 py-1.5">Aprobar</button>
                                    </form>
                                    <form method="POST" action="{{ route('billing.reject', $payment) }}" class="inline">
                                        @csrf
                                        <button class="btn-danger text-xs px-3 py-1.5">Rechazar</button>
                                    </form>
                                </td>
                            @elseif (auth()->user()->isMaster())
                                <td class="text-xs text-slate-500">{{ $payment->approver?->name }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-slate-500">Sin solicitudes todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </section>
</x-app-layout>
