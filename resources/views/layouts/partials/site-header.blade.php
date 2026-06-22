<header class="premium-header">
    <div class="premium-nav-shell">
        <div class="site-grid premium-nav-layout">
            <a href="{{ route('home') }}" class="premium-brand">
                <img src="{{ asset('brand/arena-sc-mark.webp') }}" alt="Sportski centar Arena logo" width="640" height="360" decoding="async" class="brand-logo brand-logo--header">
            </a>

            <nav class="premium-nav-links premium-nav-links--center" aria-label="Glavna navigacija">
                <a href="{{ route('home') }}" class="premium-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Pocetna</a>
                <a href="{{ route('about') }}" class="premium-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}">O nama</a>
                <a href="{{ route('sports.index') }}" class="premium-nav-link {{ request()->routeIs('sports.*') || request()->routeIs('courts.*') ? 'is-active' : '' }}">Tereni</a>
                <a href="{{ route('equipment.index') }}" class="premium-nav-link {{ request()->routeIs('equipment.*') ? 'is-active' : '' }}">Oprema</a>
                <a href="{{ route('price-list.index') }}" class="premium-nav-link {{ request()->routeIs('price-list.*') ? 'is-active' : '' }}">Cenovnik</a>
                <a href="{{ route('events.index') }}" class="premium-nav-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}">Dogadjaji</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="premium-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Moj nalog</a>
                @endauth
            </nav>

            <div class="premium-nav-actions">
                <div class="hidden items-center gap-3 xl:flex">
                    @auth
                        @if(auth()->user()?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                            <a href="{{ url('/admin') }}" class="arena-button-secondary">Admin panel</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="arena-button-primary">Odjavi me</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="site-link">Prijava</a>
                        <a href="{{ route('register') }}" class="arena-button-primary">Registracija</a>
                    @endauth
                </div>

                <details class="relative xl:hidden">
                    <summary class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-[color:var(--arena-sand-glow)] bg-[rgba(245,245,242,0.08)] text-[color:var(--arena-sand)] marker:content-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </summary>

                    <div class="mobile-sheet absolute right-0 top-[calc(100%+0.9rem)] z-20 w-[min(18rem,86vw)]">
                        <div class="grid gap-3">
                            <a href="{{ route('home') }}" class="site-link">Pocetna</a>
                            <a href="{{ route('about') }}" class="site-link">O nama</a>
                            <a href="{{ route('sports.index') }}" class="site-link">Tereni</a>
                            <a href="{{ route('equipment.index') }}" class="site-link">Oprema</a>
                            <a href="{{ route('price-list.index') }}" class="site-link">Cenovnik</a>
                            <a href="{{ route('events.index') }}" class="site-link">Dogadjaji</a>

                            @auth
                                <a href="{{ route('dashboard') }}" class="site-link">Moj nalog</a>
                                @if(auth()->user()?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                                    <a href="{{ url('/admin') }}" class="site-link">Admin panel</a>
                                @endif
                            @endauth
                        </div>

                        <div class="mt-5 grid gap-3">
                            @auth
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="arena-button-primary w-full">Odjavi me</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="arena-button-secondary w-full">Prijava</a>
                                <a href="{{ route('register') }}" class="arena-button-primary w-full">Registracija</a>
                            @endauth
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</header>
