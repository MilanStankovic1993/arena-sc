@php
    $flashStatus = session('status');
    $flashErrors = $errors->any() ? $errors->all() : [];
    $flashType = $flashErrors !== [] ? 'error' : ($flashStatus ? 'success' : null);
    $flashErrorKeys = method_exists($errors->getBag('default'), 'keys') ? $errors->getBag('default')->keys() : [];
    $flashCopy = mb_strtolower((string) $flashStatus.' '.implode(' ', $flashErrors));
    $reservationErrorFields = [
        'court_id',
        'starts_at',
        'duration_minutes',
        'guest_name',
        'guest_phone',
        'guest_email',
        'equipment',
        'reservation',
    ];
    $isReservationFlash = Illuminate\Support\Str::contains($flashCopy, ['rezerv', 'termin', 'teren'])
        || collect($flashErrorKeys)->contains(fn (string $key): bool => collect($reservationErrorFields)->contains(
            fn (string $field): bool => $key === $field || Illuminate\Support\Str::startsWith($key, $field.'.')
        ));
    $flashTitle = $flashType === 'error'
        ? ($isReservationFlash ? 'Rezervacija nije prosla' : 'Zahtev nije prosao')
        : ($isReservationFlash ? 'Rezervacija je evidentirana' : 'Uspesno evidentirano');
@endphp

@if ($flashType)
    <div class="arena-flash-dialog" data-flash-dialog role="alertdialog" aria-modal="true" aria-labelledby="arena-flash-title" aria-describedby="arena-flash-message">
        <div class="arena-flash-dialog__shade" data-flash-close></div>
        <div class="arena-flash-dialog__panel arena-flash-dialog__panel--{{ $flashType }}" tabindex="-1">
            <button type="button" class="arena-flash-dialog__close" data-flash-close aria-label="Zatvori obavestenje">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>

            <span class="arena-flash-dialog__eyebrow">
                {{ $flashType === 'error' ? 'Potrebna je provera' : 'Potvrda' }}
            </span>

            <h2 id="arena-flash-title" class="arena-flash-dialog__title">
                {{ $flashTitle }}
            </h2>

            <div id="arena-flash-message" class="arena-flash-dialog__body">
                @if ($flashType === 'error')
                    <p>Proveri poruke ispod i pokusaj ponovo.</p>
                    <ul class="arena-flash-dialog__list">
                        @foreach ($flashErrors as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>{{ $flashStatus }}</p>
                @endif
            </div>

            <button type="button" class="arena-button-primary arena-flash-dialog__action" data-flash-close>
                Razumem
            </button>
        </div>
    </div>

    @once
        <script>
            (() => {
                const closeDialog = (dialog) => {
                    dialog.hidden = true;
                    document.body.classList.remove('arena-flash-dialog-open');
                };

                document.querySelectorAll('[data-flash-dialog]').forEach((dialog) => {
                    const panel = dialog.querySelector('.arena-flash-dialog__panel');
                    document.body.classList.add('arena-flash-dialog-open');
                    panel?.focus({ preventScroll: true });

                    dialog.querySelectorAll('[data-flash-close]').forEach((button) => {
                        button.addEventListener('click', () => closeDialog(dialog));
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && !dialog.hidden) {
                            closeDialog(dialog);
                        }
                    });
                });
            })();
        </script>
    @endonce
@endif
