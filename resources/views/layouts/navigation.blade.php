<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="text-lg font-black tracking-[0.2em] text-[var(--arena-blue)]">ARENA SC</a>

                    <div class="hidden items-center gap-6 sm:flex">
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-nav-link>
                        <x-nav-link :href="route('about')" :active="request()->routeIs('about')">O nama</x-nav-link>
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Moje rezervacije</x-nav-link>
                        <x-nav-link :href="route('sports.index')" :active="request()->routeIs('sports.*') || request()->routeIs('courts.*')">Tereni</x-nav-link>
                        <x-nav-link :href="route('equipment.index')" :active="request()->routeIs('equipment.*')">Oprema</x-nav-link>
                        <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">Dogadjaji</x-nav-link>
                    </div>
                </div>

        <div class="hidden sm:flex sm:items-center sm:gap-4">
            @if(auth()->user()?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                <a href="{{ url('/admin') }}" class="rounded-full border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700">Admin panel</a>
            @endif

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">
                        <div>{{ Auth::user()->name }}</div>

                        <div class="ms-1">
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Odjava
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <div class="flex sm:hidden">
            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 bg-white sm:hidden">
        <div class="space-y-1 px-4 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Moje rezervacije</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('about')" :active="request()->routeIs('about')">O nama</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sports.index')" :active="request()->routeIs('sports.*')">Sportovi</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('equipment.index')" :active="request()->routeIs('equipment.*')">Oprema</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">Dogadjaji</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
        </div>
    </div>
</nav>
