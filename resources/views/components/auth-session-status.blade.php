@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'auth-status']) }}>
        {{ $status }}
    </div>
@endif
