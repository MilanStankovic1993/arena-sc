<x-filament-widgets::widget class="fi-analytics-summary-widget">
    <div class="analytics-stack">
        <x-filament::section>
            <div class="analytics-heading-row">
                <div>
                    <p class="analytics-kicker">Analitika sajta</p>
                    <h2 class="analytics-page-title">Pregled kljucnih metrika i trendova.</h2>
                </div>

                <div class="analytics-filter-pill">
                    Period: {{ $filterSummary }}
                    @if ($selectedUser)
                        | Korisnik: {{ $selectedUser }}
                    @endif
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="analytics-section-title">Danas</div>

            <div class="analytics-card-grid analytics-card-grid--four">
                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Rezervacija danas</span>
                    <strong class="analytics-metric-value">{{ number_format($today['reservations'], 0, ',', '.') }}</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Prihod danas</span>
                    <strong class="analytics-metric-value analytics-metric-value--accent">{{ number_format($today['revenue'], 0, ',', '.') }} RSD</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Aktivnih terena</span>
                    <strong class="analytics-metric-value">{{ number_format($today['activeCourts'], 0, ',', '.') }}</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Korisnika ukupno</span>
                    <strong class="analytics-metric-value">{{ number_format($today['customers'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="analytics-section-title">Rezervacije - izabrani period</div>

            <div class="analytics-card-grid analytics-card-grid--compact" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Ukupno rezervacija</span>
                    <strong class="analytics-metric-value">{{ number_format($period['total'], 0, ',', '.') }}</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Rezervisane</span>
                    <strong class="analytics-metric-value analytics-metric-value--blue">{{ number_format($period['reserved'], 0, ',', '.') }}</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Otkazanih</span>
                    <strong class="analytics-metric-value analytics-metric-value--danger">{{ number_format($period['cancelled'], 0, ',', '.') }}</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Ucesnickih termina</span>
                    <strong class="analytics-metric-value analytics-metric-value--blue">{{ number_format($period['participantVisits'], 0, ',', '.') }}</strong>
                    <span class="analytics-metric-meta">{{ number_format($period['uniqueParticipants'], 0, ',', '.') }} razlicitih korisnika</span>
                </div>

                <div class="analytics-metric-card analytics-metric-card--wide">
                    <span class="analytics-metric-label">Ukupan prihod</span>
                    <strong class="analytics-metric-value analytics-metric-value--accent">{{ number_format($period['revenue'], 0, ',', '.') }} RSD</strong>
                </div>

                <div class="analytics-metric-card analytics-metric-card--wide">
                    <span class="analytics-metric-label">Prosecna cena po terminu</span>
                    <strong class="analytics-metric-value">{{ number_format($period['averagePrice'], 0, ',', '.') }} RSD</strong>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="analytics-section-title">Zdravlje sistema</div>

            <div class="analytics-card-grid analytics-card-grid--three">
                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Stopa otkazivanja</span>
                    <strong class="analytics-metric-value analytics-metric-value--blue">{{ number_format($health['cancellationRate'], 1, ',', '.') }}%</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Neotkazanih termina</span>
                    <strong class="analytics-metric-value">{{ number_format($health['retentionRate'], 1, ',', '.') }}%</strong>
                </div>

                <div class="analytics-metric-card">
                    <span class="analytics-metric-label">Novi korisnici u periodu</span>
                    <strong class="analytics-metric-value analytics-metric-value--blue">+{{ number_format($health['newUsers'], 0, ',', '.') }}</strong>
                    <span class="analytics-metric-meta">od {{ number_format($health['totalUsers'], 0, ',', '.') }} ukupno registrovanih</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
