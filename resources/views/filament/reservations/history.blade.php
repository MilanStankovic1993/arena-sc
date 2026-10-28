@php
    $labels = ['id' => 'Rezervacija #', 'user_id' => 'Nalog korisnika #', 'guest_name' => 'Ime gosta', 'guest_phone' => 'Telefon', 'guest_email' => 'Mejl', 'court_id' => 'Teren #', 'sport_id' => 'Sport #', 'status' => 'Status', 'starts_at' => 'Početak', 'ends_at' => 'Kraj', 'duration_minutes' => 'Trajanje (min)', 'court_price' => 'Cena terena', 'equipment_price' => 'Cena opreme', 'total_price' => 'Ukupna cena', 'is_gift' => 'Poklon', 'customer_note' => 'Napomena korisnika', 'admin_note' => 'Interna napomena', 'cancellation_reason' => 'Razlog otkazivanja', 'cancelled_at' => 'Otkazano', 'deleted_at' => 'Obrisano', 'created_at' => 'Kreirano', 'updated_at' => 'Izmenjeno', 'series_id' => 'Serija', 'players_count' => 'Broj igrača', 'participant_ids' => 'Nalozi učesnika #', 'equipment' => 'Stavka opreme'];
    $format = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (is_bool($value) ? ($value ? 'Da' : 'Ne') : ($value ?? '—'));
@endphp
<div style="max-width:100%;min-width:0;display:grid;gap:1rem">
    @forelse ($audits as $audit)
        <section style="border:1px solid #d1d5db;border-radius:0.75rem;padding:1rem;min-width:0">
            <p><strong>{{ $audit->event_label }} · {{ $audit->created_at->format('d.m.Y H:i:s') }}</strong></p>
            <p>Izvršilac: {{ $audit->actor_name }} @if ($audit->actor_id) (nalog #{{ $audit->actor_id }}) @endif · {{ ['user' => 'Prijavljeni korisnik', 'guest' => 'Gost', 'system' => 'Sistem / konzola'][$audit->actor_type] ?? $audit->actor_type }}</p>
            <div style="overflow-x:auto;max-width:100%;margin-top:0.75rem">
                <table style="width:100%;min-width:420px;text-align:left;border-collapse:collapse">
                    <thead><tr><th style="padding:0.5rem">Polje</th><th style="padding:0.5rem">Pre</th><th style="padding:0.5rem">Posle</th></tr></thead>
                    <tbody>
                    @foreach (array_unique(array_merge(array_keys($audit->old_values), array_keys($audit->new_values))) as $field)
                        <tr style="border-top:1px solid #d1d5db">
                            <td style="padding:0.5rem;vertical-align:top">{{ $labels[$field] ?? $field }}</td>
                            <td style="padding:0.5rem;vertical-align:top;white-space:pre-wrap;overflow-wrap:anywhere">{{ $format($audit->old_values[$field] ?? null) }}</td>
                            <td style="padding:0.5rem;vertical-align:top;white-space:pre-wrap;overflow-wrap:anywhere">{{ $format($audit->new_values[$field] ?? null) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p>Nema zabeleženih promena. Evidencija počinje od uvođenja istorije rezervacija; ranije promene nisu dostupne.</p>
    @endforelse
</div>
