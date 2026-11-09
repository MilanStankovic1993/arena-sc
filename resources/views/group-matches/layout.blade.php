@extends('layouts.site', [
    'title' => $title.' | Sportski centar Arena',
    'metaRobots' => 'noindex,follow',
    'showQuickActions' => false,
])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/group-matches.css') }}?v=5">
    <meta name="referrer" content="no-referrer">
@endpush

@section('content')
    <section class="site-grid group-match-page">
        <div class="booking-intro-card">
            <div>
                <span class="dark-eyebrow-chip">Padel i basket 3×3</span>
                <h1 class="hero-title-dark booking-intro-card__title">{{ $title }}</h1>
                @isset($intro)<p class="group-match-intro">{{ $intro }}</p>@endisset
            </div>
            <div class="group-match-actions">
                @yield('intro-actions')
            </div>
        </div>

        @if (session('group_match_status'))
            <div class="group-match-notice" role="status">{{ session('group_match_status') }}</div>
        @endif
        @if ($errors->any())
            <div class="group-match-notice group-match-error" role="alert">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('matches-content')
    </section>
@endsection
