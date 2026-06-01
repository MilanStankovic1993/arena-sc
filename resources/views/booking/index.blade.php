@extends('layouts.site', ['title' => 'Rezervisi termin | Arena SC'])

@section('content')
    @php
        $sportsPayload = $sports->map(fn ($sport) => [
            'name' => $sport->name,
            'slug' => $sport->slug,
            'courts' => $sport->courts->map(fn ($court) => [
                'name' => $court->name,
                'slug' => $court->slug,
            ])->values(),
        ])->values();
    @endphp

    <section
        class="site-grid py-10 sm:py-12"
        data-booking-app
        data-sports='@json($sportsPayload)'
        data-initial='@json($initialState)'
        data-availability-url="{{ route('booking.availability') }}"
        data-dashboard-url="{{ route('dashboard') }}"
        data-login-url="{{ route('login') }}"
        data-register-url="{{ route('register') }}"
        data-authenticated="{{ auth()->check() ? '1' : '0' }}"
    >
        <div class="page-stack">
            <div class="page-hero-dark overflow-hidden">
                <div class="grid gap-8 xl:grid-cols-[1.08fr_0.92fr] xl:items-end">
                    <div>
                        <span class="dark-eyebrow-chip">Rezervacija bez cekanja</span>
                        <h1 class="hero-title-dark mt-6 max-w-4xl text-4xl sm:text-5xl lg:text-6xl">Brzo biras sport, teren i slobodno vreme, a termin je odmah rezervisan.</h1>
                        <p class="hero-copy-dark mt-5 max-w-3xl">
                            Rezervacija je sada jednostavna i direktna. Nema dodatnog odobravanja, nema osvezavanja stranice na svaki izbor i nema ogromnih tabela koje zbunjuju korisnika.
                        </p>
                    </div>
                    <div class="metric-ribbon sm:grid-cols-3 xl:grid-cols-1">
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">1</p>
                            <p class="mt-2 text-xl font-black text-white">Sport i teren</p>
                        </div>
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">2</p>
                            <p class="mt-2 text-xl font-black text-white">Datum i trajanje</p>
                        </div>
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">3</p>
                            <p class="mt-2 text-xl font-black text-white">Potvrda i oprema</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="premium-card booking-shell p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="eyebrow-chip">Pametna rezervacija</span>
                        <h2 class="hero-title mt-4 max-w-3xl text-3xl sm:text-4xl">Bez refresh-a stranice i bez komplikovanog pregleda termina.</h2>
                        <p class="hero-copy mt-4 max-w-3xl text-sm">
                            Izaberi sport, teren, datum i trajanje, pa klikni na dugme da prikazemo samo slobodna vremena za taj teren.
                        </p>
                    </div>

                    <span class="info-chip">
                        {{ auth()->check() ? 'Rezervacija se potvrduje odmah' : 'Pregled je javan, rezervacija zahteva nalog' }}
                    </span>
                </div>

                <div class="booking-planner-grid mt-8">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Sport</label>
                        <select class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3" data-booking-sport>
                            <option value="">Izaberi sport</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Teren</label>
                        <select class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3" data-booking-court disabled>
                            <option value="">Izaberi teren</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Datum</label>
                        <input type="date" class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3" data-booking-date min="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Trajanje</label>
                        <select class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3" data-booking-duration>
                            <option value="60">1h</option>
                            <option value="90">1,5h</option>
                            <option value="120">2h</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="button" class="arena-button-primary w-full xl:w-auto" data-booking-load>
                            Prikazi slobodna vremena
                        </button>
                    </div>
                </div>
            </div>

            <div class="booking-side-grid">
                <div class="space-y-6">
                    <div class="premium-card p-6 sm:p-7" data-booking-court-card hidden>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]" data-court-sport></p>
                                <h2 class="mt-3 text-3xl font-black text-[color:var(--arena-forest)]" data-court-name></h2>
                                <p class="mt-3 text-sm leading-7 text-[color:var(--arena-muted)]" data-court-description></p>
                            </div>
                        </div>

                        <div class="mt-6 premium-grid sm:grid-cols-3">
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Lokacija</p>
                                <p class="mt-2 font-semibold text-[color:var(--arena-forest)]" data-court-location></p>
                            </div>
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Podloga</p>
                                <p class="mt-2 font-semibold text-[color:var(--arena-forest)]" data-court-surface></p>
                            </div>
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Podrska</p>
                                <p class="mt-2 font-semibold text-[color:var(--arena-forest)]">Brza potvrda termina</p>
                            </div>
                        </div>
                    </div>

                    <div class="premium-card p-6 sm:p-7" data-booking-pricing hidden>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="eyebrow-chip">Cenovnik</span>
                                <h3 class="hero-title mt-4 text-2xl sm:text-3xl">Pregled cena za izabrani sport</h3>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4" data-pricing-list></div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="premium-card p-6 sm:p-7">
                        <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Slobodni termini</p>
                        <h2 class="hero-title mt-4 text-3xl sm:text-4xl">Izaberi jedno slobodno vreme iz padajuce liste.</h2>
                        <p class="hero-copy mt-4 text-sm">
                            Prikazujemo samo slobodna vremena za izabrani teren i izabrani datum.
                        </p>

                        <div class="site-table-shell mt-6 p-5">
                            <label class="text-sm font-semibold text-slate-700">Slobodno vreme</label>
                            <select class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3" data-booking-slot disabled>
                                <option value="">Prvo prikazi slobodna vremena</option>
                            </select>

                            <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                                <span class="text-[color:var(--arena-muted)]" data-slot-helper>Izaberi sport, teren, datum i trajanje.</span>
                                <span class="rounded-full bg-white px-3 py-1 font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-forest)]" data-slot-count hidden></span>
                            </div>
                        </div>

                        <div class="mt-5" data-booking-feedback hidden></div>
                    </div>

                    <div class="premium-card p-6 sm:p-7" data-booking-selection hidden>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Izabrani termin</p>
                                <h3 class="hero-title mt-4 text-3xl sm:text-4xl" data-selected-slot-label></h3>
                                <p class="hero-copy mt-4 text-sm" data-selected-slot-copy></p>
                            </div>
                            <span class="info-chip" data-selected-duration></span>
                        </div>

                        @if (! auth()->check())
                            <div class="soft-message mt-6">
                                <p class="text-sm leading-7 text-[color:var(--arena-ink)]">Za potvrdu rezervacije potrebno je da imas nalog i da budes prijavljen.</p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="{{ route('register') }}" class="arena-button-primary">Registruj se</a>
                                    <a href="{{ route('login') }}" class="arena-button-secondary">Prijavi se</a>
                                </div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('reservations.store') }}" class="mt-6 space-y-5" data-booking-form>
                                @csrf
                                <input type="hidden" name="court_id" value="" data-reservation-court-id>
                                <input type="hidden" name="starts_at" value="" data-reservation-starts-at>
                                <input type="hidden" name="duration_minutes" value="" data-reservation-duration>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Cena rezervacije</label>
                                    <div class="mt-2 rounded-[1.35rem] border border-[color:var(--arena-border)] bg-[color:var(--arena-paper)] px-4 py-3 font-bold text-[color:var(--arena-forest)]" data-selected-price></div>
                                </div>

                                <div class="site-table-shell p-4" data-equipment-box hidden>
                                    <div>
                                        <p class="text-sm font-extrabold uppercase tracking-[0.26em] text-[color:var(--arena-forest-glow)]">Opciona oprema</p>
                                        <p class="mt-2 text-sm text-[color:var(--arena-muted)]">Oprema nije obavezna. Dodaj samo ono sto stvarno zelis da rezervises.</p>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2" data-equipment-list></div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Napomena</label>
                                    <textarea name="customer_note" rows="4" class="mt-2 w-full rounded-[1.35rem] border-[color:var(--arena-border)] bg-white px-4 py-3">{{ old('customer_note') }}</textarea>
                                </div>

                                <button class="arena-button-primary">Potvrdi rezervaciju</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const app = document.querySelector('[data-booking-app]');

            if (!app) {
                return;
            }

            const sports = JSON.parse(app.dataset.sports ?? '[]');
            const initial = JSON.parse(app.dataset.initial ?? '{}');
            const availabilityUrl = app.dataset.availabilityUrl;
            const isAuthenticated = app.dataset.authenticated === '1';

            const sportSelect = app.querySelector('[data-booking-sport]');
            const courtSelect = app.querySelector('[data-booking-court]');
            const dateInput = app.querySelector('[data-booking-date]');
            const durationSelect = app.querySelector('[data-booking-duration]');
            const loadButton = app.querySelector('[data-booking-load]');
            const slotSelect = app.querySelector('[data-booking-slot]');
            const slotHelper = app.querySelector('[data-slot-helper]');
            const slotCount = app.querySelector('[data-slot-count]');
            const feedbackBox = app.querySelector('[data-booking-feedback]');

            const courtCard = app.querySelector('[data-booking-court-card]');
            const pricingCard = app.querySelector('[data-booking-pricing]');
            const pricingList = app.querySelector('[data-pricing-list]');
            const selectionCard = app.querySelector('[data-booking-selection]');
            const selectedSlotLabel = app.querySelector('[data-selected-slot-label]');
            const selectedSlotCopy = app.querySelector('[data-selected-slot-copy]');
            const selectedDuration = app.querySelector('[data-selected-duration]');
            const selectedPrice = app.querySelector('[data-selected-price]');

            const courtSport = app.querySelector('[data-court-sport]');
            const courtName = app.querySelector('[data-court-name]');
            const courtDescription = app.querySelector('[data-court-description]');
            const courtLocation = app.querySelector('[data-court-location]');
            const courtSurface = app.querySelector('[data-court-surface]');
            const courtCapacity = app.querySelector('[data-court-capacity]');

            const courtIdInput = app.querySelector('[data-reservation-court-id]');
            const startsAtInput = app.querySelector('[data-reservation-starts-at]');
            const durationInput = app.querySelector('[data-reservation-duration]');
            const equipmentBox = app.querySelector('[data-equipment-box]');
            const equipmentList = app.querySelector('[data-equipment-list]');

            let latestPayload = null;

            const formatMoney = (amount) => `${new Intl.NumberFormat('sr-RS').format(Number(amount || 0))} RSD`;

            const resetSlots = (message = 'Prvo prikazi slobodna vremena.') => {
                slotSelect.innerHTML = '<option value="">Prvo prikazi slobodna vremena</option>';
                slotSelect.disabled = true;
                slotHelper.textContent = message;
                slotCount.hidden = true;
                latestPayload = null;
                selectionCard.hidden = true;
                feedbackBox.hidden = true;
                feedbackBox.innerHTML = '';
            };

            const resetDetails = () => {
                courtCard.hidden = true;
                pricingCard.hidden = true;
                pricingList.innerHTML = '';
                selectionCard.hidden = true;
                if (equipmentBox) {
                    equipmentBox.hidden = true;
                }
                if (equipmentList) {
                    equipmentList.innerHTML = '';
                }
            };

            const fillSports = () => {
                sportSelect.innerHTML = '<option value="">Izaberi sport</option>';

                sports.forEach((sport) => {
                    const option = document.createElement('option');
                    option.value = sport.slug;
                    option.textContent = sport.name;

                    if (initial.sport === sport.slug) {
                        option.selected = true;
                    }

                    sportSelect.appendChild(option);
                });
            };

            const fillCourts = (selectedSportSlug, selectedCourtSlug = '') => {
                const sport = sports.find((item) => item.slug === selectedSportSlug);

                courtSelect.innerHTML = '<option value="">Izaberi teren</option>';
                courtSelect.disabled = !sport;

                if (!sport) {
                    return;
                }

                sport.courts.forEach((court) => {
                    const option = document.createElement('option');
                    option.value = court.slug;
                    option.textContent = court.name;

                    if (selectedCourtSlug === court.slug) {
                        option.selected = true;
                    }

                    courtSelect.appendChild(option);
                });
            };

            const renderCourt = (court) => {
                courtCard.hidden = false;
                courtSport.textContent = court.sport;
                courtName.textContent = court.name;
                courtDescription.textContent = court.description || 'Opis ce uskoro biti dodat.';
                courtLocation.textContent = court.location || 'Lokacija nije uneta';
                courtSurface.textContent = court.surface || 'Podloga nije uneta';
                courtCapacity.textContent = court.capacity || '-';
            };

            const renderPricing = (rules) => {
                pricingList.innerHTML = '';
                pricingCard.hidden = rules.length === 0;

                rules.forEach((rule) => {
                    const item = document.createElement('div');
                    item.className = 'premium-card bg-white p-4';
                    item.innerHTML = `
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-[color:var(--arena-forest)]">${rule.name}</p>
                                <p class="mt-1 text-sm text-slate-500">${rule.days}</p>
                            </div>
                            <span class="info-chip">${rule.time}</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
                            <div class="premium-card bg-[color:var(--arena-paper)] px-4 py-3"><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">1h</span><strong class="mt-2 block text-[color:var(--arena-forest)]">${formatMoney(rule.price60)}</strong></div>
                            <div class="premium-card bg-[color:var(--arena-paper)] px-4 py-3"><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">1,5h</span><strong class="mt-2 block text-[color:var(--arena-forest)]">${formatMoney(rule.price90)}</strong></div>
                            <div class="premium-card bg-[color:var(--arena-paper)] px-4 py-3"><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">2h</span><strong class="mt-2 block text-[color:var(--arena-forest)]">${formatMoney(rule.price120)}</strong></div>
                        </div>
                    `;
                    pricingList.appendChild(item);
                });
            };

            const renderEquipment = (items) => {
                if (!equipmentBox || !equipmentList) {
                    return;
                }

                equipmentList.innerHTML = '';
                equipmentBox.hidden = items.length === 0;

                items.forEach((item, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'premium-card bg-white p-4';
                    wrapper.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-[color:var(--arena-forest)]">${item.name}</p>
                                <p class="mt-1 text-sm text-slate-500">${item.description || 'Oprema je spremna za izdavanje.'}</p>
                            </div>
                            <span class="info-chip">${formatMoney(item.price)}</span>
                        </div>
                        <input type="hidden" name="equipment[${index}][equipment_id]" value="${item.id}">
                        <label class="mt-4 block text-sm font-semibold text-slate-700">Kolicina</label>
                        <input type="number" min="0" max="10" name="equipment[${index}][quantity]" value="0" class="mt-2 w-full rounded-[1.2rem] border-[color:var(--arena-border)] bg-white px-4 py-3">
                    `;
                    equipmentList.appendChild(wrapper);
                });
            };

            const renderSlots = (payload) => {
                latestPayload = payload;
                slotSelect.innerHTML = '<option value="">Izaberi slobodno vreme</option>';

                payload.slots.forEach((slot, index) => {
                    const option = document.createElement('option');
                    option.value = String(index);
                    option.textContent = `${slot.label} | ${formatMoney(slot.price)}`;
                    slotSelect.appendChild(option);
                });

                slotSelect.disabled = payload.slots.length === 0;
                slotHelper.textContent = payload.slots.length > 0
                    ? 'Izaberi vreme iz liste da otvorimo potvrdu rezervacije.'
                    : 'Nema slobodnih vremena za izabrani teren i datum.';
                slotCount.hidden = payload.slots.length === 0;
                slotCount.textContent = `${payload.slots.length} termina`;
            };

            const renderSelection = (slotIndex) => {
                if (!latestPayload || slotIndex === '') {
                    selectionCard.hidden = true;
                    return;
                }

                const slot = latestPayload.slots[Number(slotIndex)];

                if (!slot) {
                    selectionCard.hidden = true;
                    return;
                }

                selectionCard.hidden = false;
                selectedSlotLabel.textContent = `${latestPayload.selectedDayLabel} | ${slot.label}`;
                selectedSlotCopy.textContent = `Cena za ${latestPayload.court.name} iznosi ${formatMoney(slot.price)}. Po potrebi mozes da dodas i opremu.`;
                selectedDuration.textContent = latestPayload.durationLabel;

                if (selectedPrice) {
                    selectedPrice.textContent = formatMoney(slot.price);
                }

                if (courtIdInput) {
                    courtIdInput.value = latestPayload.court.id;
                }

                if (startsAtInput) {
                    startsAtInput.value = slot.starts_at;
                }

                if (durationInput) {
                    durationInput.value = durationSelect.value;
                }
            };

            const showMessage = (message, type = 'info') => {
                feedbackBox.hidden = false;
                const classes = type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-[color:var(--arena-border)] bg-[color:var(--arena-cream)] text-[color:var(--arena-ink)]';

                feedbackBox.innerHTML = `<div class="rounded-[1.25rem] border px-4 py-3 text-sm ${classes}">${message}</div>`;
            };

            const loadAvailability = async () => {
                const sport = sportSelect.value;
                const court = courtSelect.value;
                const date = dateInput.value;
                const duration = durationSelect.value;

                if (!sport || !court || !date || !duration) {
                    showMessage('Izaberi sport, teren, datum i trajanje pre prikaza slobodnih termina.', 'error');
                    return;
                }

                loadButton.disabled = true;
                loadButton.textContent = 'Ucitivanje...';
                resetSlots('Ucitavamo slobodna vremena...');
                resetDetails();

                try {
                    const url = new URL(availabilityUrl, window.location.origin);
                    url.searchParams.set('sport', sport);
                    url.searchParams.set('court', court);
                    url.searchParams.set('date', date);
                    url.searchParams.set('duration', duration);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Ne mozemo trenutno da ucitamo slobodne termine.');
                    }

                    const payload = await response.json();
                    renderCourt(payload.court);
                    renderPricing(payload.pricingRules);
                    renderSlots(payload);
                    renderEquipment(payload.equipment);
                    renderSelection('');

                    if (payload.slots.length === 0) {
                        showMessage('Za izabrani datum i trajanje nema slobodnih termina na ovom terenu.');
                    } else {
                        showMessage('Slobodna vremena su ucitana. Izaberi jedno vreme iz liste da nastavis.');
                    }
                } catch (error) {
                    resetSlots('Doslo je do greske pri ucitavanju termina.');
                    showMessage(error.message || 'Doslo je do greske pri ucitavanju termina.', 'error');
                } finally {
                    loadButton.disabled = false;
                    loadButton.textContent = 'Prikazi slobodna vremena';
                }
            };

            fillSports();
            fillCourts(initial.sport, initial.court);
            dateInput.value = initial.date;
            durationSelect.value = String(initial.duration || 60);

            sportSelect.addEventListener('change', () => {
                fillCourts(sportSelect.value);
                resetSlots('Izaberi teren i klikni na dugme za prikaz termina.');
                resetDetails();
            });

            courtSelect.addEventListener('change', () => {
                resetSlots('Klikni na dugme za prikaz termina.');
                resetDetails();
            });

            dateInput.addEventListener('change', () => {
                resetSlots('Klikni na dugme za prikaz termina za novi datum.');
                selectionCard.hidden = true;
            });

            durationSelect.addEventListener('change', () => {
                resetSlots('Klikni na dugme za prikaz termina za novo trajanje.');
                selectionCard.hidden = true;
            });

            slotSelect.addEventListener('change', () => {
                renderSelection(slotSelect.value);
            });

            loadButton.addEventListener('click', loadAvailability);

            if (initial.sport && initial.court) {
                loadAvailability();
            } else {
                resetSlots();
            }

            if (isAuthenticated && durationInput) {
                durationInput.value = durationSelect.value;
            }
        })();
    </script>
@endsection
