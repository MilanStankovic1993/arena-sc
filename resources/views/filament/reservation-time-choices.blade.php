<div style="display: grid; gap: 1rem">
    @if ($shifts !== [])
        <div style="display: grid; gap: .5rem">
            <p style="font-size: .875rem; font-weight: 600">Pomeri početak</p>
            <div role="group" aria-label="Brzi pomak početka" style="display: flex; flex-wrap: wrap; gap: .5rem">
                @foreach ($shifts as $hours => $target)
                    <x-filament::button
                        type="button"
                        size="sm"
                        color="gray"
                        :disabled="$target === null"
                        wire:loading.attr="disabled"
                        wire:click="$set(@js($timePath), @js($target))"
                    >
                        {{ $hours < 0 ? '−' : '+' }}{{ abs($hours) }} {{ abs($hours) === 1 ? 'sat' : 'sata' }}
                    </x-filament::button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($slots->isNotEmpty())
        <div style="display: grid; gap: .5rem">
            <p style="font-size: .875rem; font-weight: 600">Početak na izabranom terenu</p>
            <div role="group" aria-label="Počeci termina" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(4rem, 1fr)); gap: .375rem">
                @foreach ($slots as $slot)
                    @php($start = $slot['starts_at']->format('H:i'))
                    <x-filament::button
                        type="button"
                        size="sm"
                        :color="$start === $time ? 'primary' : 'gray'"
                        :disabled="! $slot['available']"
                        :aria-label="$start.($slot['available'] ? '' : ', zauzeto')"
                        :aria-pressed="$start === $time ? 'true' : 'false'"
                        wire:loading.attr="disabled"
                        wire:click="$set(@js($timePath), @js($start))"
                    >
                        {{ $start }}
                    </x-filament::button>
                @endforeach
            </div>
            <p style="font-size: .75rem">Zauzeta vremena su onemogućena.</p>
        </div>
    @endif
</div>
