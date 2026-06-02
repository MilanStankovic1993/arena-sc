<x-filament-widgets::widget class="fi-analytics-time-popularity-widget">
    <x-filament::section>
        <div class="analytics-section-title">Najpopularnije vreme</div>

        <div class="analytics-list">
            @forelse ($hours as $hour)
                <div class="analytics-list-row">
                    <div class="analytics-list-header">
                        <div class="analytics-list-title">{{ $hour['label'] }}</div>
                        <div class="analytics-list-values">
                            <span>{{ number_format($hour['count'], 0, ',', '.') }} {{ $hour['count'] === 1 ? 'termin' : 'termina' }}</span>
                        </div>
                    </div>

                    <div class="analytics-progress">
                        <span class="analytics-progress-bar" style="width: {{ $hour['progress'] }}%"></span>
                    </div>
                </div>
            @empty
                <p class="analytics-empty-state">Nema podataka za prikaz po terminima.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
