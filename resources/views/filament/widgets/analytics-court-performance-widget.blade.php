<x-filament-widgets::widget class="fi-analytics-court-performance-widget">
    <x-filament::section>
        <div class="analytics-section-title">Zauzetost i prihod po terenu</div>

        <div class="analytics-list">
            @forelse ($courts as $court)
                <div class="analytics-list-row">
                    <div class="analytics-list-header">
                        <div>
                            <div class="analytics-list-title">{{ $court['name'] }}</div>
                            <div class="analytics-list-meta">
                                {{ $court['sport'] ?? 'Bez sporta' }}
                                @unless($court['is_active'])
                                    <span class="analytics-status-badge">Neaktivan</span>
                                @endunless
                            </div>
                        </div>

                        <div class="analytics-list-values">
                            <span>{{ number_format($court['reservations'], 0, ',', '.') }} term.</span>
                            <strong>{{ number_format($court['revenue'], 0, ',', '.') }} RSD</strong>
                        </div>
                    </div>

                    <div class="analytics-progress">
                        <span class="analytics-progress-bar" style="width: {{ $court['progress'] }}%"></span>
                    </div>
                </div>
            @empty
                <p class="analytics-empty-state">Nema dovoljno podataka za prikaz po terenima.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
