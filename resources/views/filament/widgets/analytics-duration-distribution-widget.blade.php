<x-filament-widgets::widget class="fi-analytics-duration-distribution-widget">
    <x-filament::section>
        <div class="analytics-section-title">Raspodela trajanja</div>

        <div class="analytics-donut-grid">
            @forelse ($durations as $duration)
                <div class="analytics-donut-card">
                    <div class="analytics-donut-ring" style="--analytics-percent: {{ $duration['percentage'] }}%">
                        <span>{{ number_format($duration['percentage'], 0, ',', '.') }}%</span>
                    </div>

                    <div class="analytics-donut-label">{{ $duration['label'] }}</div>
                    <div class="analytics-donut-meta">{{ number_format($duration['count'], 0, ',', '.') }} {{ $duration['count'] === 1 ? 'termin' : 'termina' }}</div>
                </div>
            @empty
                <p class="analytics-empty-state">Nema podataka za trajanja rezervacija.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
