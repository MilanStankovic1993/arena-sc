<div class="analytics-stack">
    <x-filament::section>
        <div class="analytics-heading-row">
            <div>
                <p class="analytics-kicker">Profil korisnika</p>
                <h2 class="analytics-page-title">{{ $record->name }}</h2>
            </div>

            <div class="analytics-filter-pill">
                {{ $record->email }}
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="analytics-section-title">Osnovne metrike</div>

        <div class="analytics-card-grid analytics-card-grid--four analytics-card-grid--compact">
            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Ukupno rezervacija</span>
                <strong class="analytics-metric-value">{{ number_format($stats['total'], 0, ',', '.') }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Ukupna potrosnja</span>
                <strong class="analytics-metric-value analytics-metric-value--accent">{{ number_format($stats['revenue'], 0, ',', '.') }} RSD</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Prosecna cena po terminu</span>
                <strong class="analytics-metric-value">{{ number_format($stats['averageSpend'], 0, ',', '.') }} RSD</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Ukupno sati</span>
                <strong class="analytics-metric-value">{{ number_format($stats['durationHours'], 1, ',', '.') }} h</strong>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="analytics-section-title">Status rezervacija</div>

        <div class="analytics-card-grid analytics-card-grid--compact" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Rezervisane</span>
                <strong class="analytics-metric-value analytics-metric-value--blue">{{ number_format($stats['reserved'], 0, ',', '.') }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Otkazane</span>
                <strong class="analytics-metric-value analytics-metric-value--danger">{{ number_format($stats['cancelled'], 0, ',', '.') }}</strong>
                <span class="analytics-metric-meta">Stopa: {{ number_format($stats['cancellationRate'], 1, ',', '.') }}%</span>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="analytics-section-title">Navike korisnika</div>

        <div class="analytics-card-grid analytics-card-grid--three">
            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Najcesci sport</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $stats['favoriteSport'] ?? 'Nema podataka' }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Najcesci teren</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $stats['favoriteCourt'] ?? 'Nema podataka' }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Omiljeni termin</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $stats['favoriteTime'] ?? 'Nema podataka' }}</strong>
                <span class="analytics-metric-meta">{{ $stats['favoriteWeekday'] ?? 'Nema podataka o danu' }}</span>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="analytics-section-title">Vremenski pregled</div>

        <div class="analytics-card-grid analytics-card-grid--three">
            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Prva rezervacija</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $stats['firstReservationAt']?->format('d.m.Y H:i') ?? 'Nema podataka' }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Poslednja rezervacija</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $stats['lastReservationAt']?->format('d.m.Y H:i') ?? 'Nema podataka' }}</strong>
            </div>

            <div class="analytics-metric-card">
                <span class="analytics-metric-label">Telefon</span>
                <strong class="analytics-metric-value" style="font-size: 1.3rem;">{{ $record->phone ?: 'Nije unet' }}</strong>
            </div>
        </div>
    </x-filament::section>
</div>
