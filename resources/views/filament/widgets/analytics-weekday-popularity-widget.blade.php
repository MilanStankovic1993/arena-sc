<x-filament-widgets::widget class="fi-analytics-weekday-popularity-widget">
    <x-filament::section>
        <div class="analytics-section-title">Popularnost po danu</div>

        <div class="analytics-list">
            @forelse ($days as $day)
                <div class="analytics-list-row">
                    <div class="analytics-list-header">
                        <div class="analytics-list-title">{{ $day['label'] }}</div>
                        <div class="analytics-list-values">
                            <span>{{ number_format($day['count'], 0, ',', '.') }} term.</span>
                        </div>
                    </div>

                    <div class="analytics-progress">
                        <span class="analytics-progress-bar" style="width: {{ $day['progress'] }}%"></span>
                    </div>
                </div>
            @empty
                <p class="analytics-empty-state">Nema podataka za prikaz po danima.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
