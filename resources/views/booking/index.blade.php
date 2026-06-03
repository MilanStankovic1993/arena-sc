@extends('layouts.site', ['title' => 'Rezervisi termin | Arena SC'])

@section('content')
    @php
        $sportsPayload = $sports->map(fn ($sport) => [
            'name' => $sport->name,
            'slug' => $sport->slug,
        ])->values();
    @endphp

    <section
        class="site-grid py-10 sm:py-12"
        data-booking-app
        data-sports='@json($sportsPayload)'
        data-initial='@json($initialState)'
        data-availability-url="{{ route('booking.availability') }}"
        data-authenticated="{{ auth()->check() ? '1' : '0' }}"
    >
        <div class="page-stack booking-page-stack">
            <div class="booking-intro-card">
                <div>
                    <span class="dark-eyebrow-chip">Rezervisi termin</span>
                    <h1 class="hero-title-dark booking-intro-card__title">IZABERI SPORT, DAN I VREME. BRZO I JASNO.</h1>
                </div>

                <div class="booking-intro-card__chips">
                    <span class="info-chip-soft-dark">Padel</span>
                    <span class="info-chip-soft-dark">Basket 3x3</span>
                    <span class="info-chip-soft-dark">Oprema uz termin</span>
                </div>
            </div>

            <div class="booking-summary-grid">
                <div class="booking-stage">
                    <div class="booking-panel">
                        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <span class="eyebrow-chip">Rezervacija</span>
                                <h2 class="mt-4 text-[2rem] font-semibold leading-none text-[color:var(--arena-forest)] sm:text-[2.8rem]">Rezervisi termin.</h2>
                            </div>

                            <div class="booking-compact-field min-w-[15rem] max-w-[22rem] flex-1 sm:flex-none">
                                <label class="text-sm font-semibold text-slate-700">Sport</label>
                                <select class="booking-select" data-booking-sport>
                                    <option value="">Izaberi sport</option>
                                </select>
                            </div>
                        </div>

                        <div class="booking-day-strip">
                            <button type="button" class="booking-nav-button self-center" data-window-prev aria-label="Prethodni dani">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                                </svg>
                            </button>

                            <div class="booking-day-cards" data-day-list></div>

                            <button type="button" class="booking-nav-button self-center" data-window-next aria-label="Sledeci dani">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-3 text-center text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]" data-window-label>
                            Termin prozor
                        </div>

                        <div class="mt-7 flex items-center justify-between gap-3">
                            <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Vreme</p>
                            <span class="info-chip-soft">Samo slobodni slotovi</span>
                        </div>

                        <div class="mt-5 booking-time-grid-compact" data-time-list></div>

                        <div class="mt-5" data-booking-feedback hidden></div>

                        <div class="booking-chooser-grid" data-chooser-row hidden>
                            <div class="booking-mini-card">
                                <label class="text-xs font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)]">Trajanje</label>
                                <select class="booking-select mt-3" data-duration-select disabled>
                                    <option value="">Izaberi trajanje</option>
                                </select>
                            </div>

                            <div class="booking-mini-card lg:col-span-2">
                                <label class="text-xs font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)]">Teren</label>
                                <select class="booking-select mt-3" data-court-select disabled>
                                    <option value="">Izaberi teren</option>
                                </select>
                            </div>
                        </div>

                        <div class="booking-inline-summary" data-summary-card hidden>
                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_8rem] lg:items-start">
                                <div>
                                    <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)]">Izabrani termin</p>
                                    <h2 class="mt-3 text-2xl font-black text-[color:var(--arena-forest)]" data-summary-title></h2>
                                    <p class="mt-2 text-sm leading-7 text-[color:var(--arena-muted)]" data-summary-copy></p>
                                </div>

                                <div class="booking-mini-card text-center">
                                    <span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-muted)]">Cena</span>
                                    <div class="mt-3 text-2xl font-black text-[color:var(--arena-forest)]" data-selected-price></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="booking-sidebar">
                    <div class="premium-card p-6 sm:p-7 booking-section" data-summary-box hidden>
                        <div class="booking-mini-card" data-court-preview hidden>
                            <div class="grid gap-4 sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-center">
                                <div class="overflow-hidden rounded-[1.1rem] border border-white/20 bg-[rgba(15,42,31,0.08)]" data-court-preview-image></div>
                                <div>
                                    <p class="text-xl font-black text-[color:var(--arena-forest)]" data-court-preview-name></p>
                                    <p class="mt-1 text-sm text-[color:var(--arena-muted)]" data-court-preview-meta></p>
                                </div>
                            </div>
                        </div>

                        @if (! auth()->check())
                            <div class="soft-message">
                                <p class="text-sm leading-7 text-[color:var(--arena-ink)]">Za potvrdu termina prijavi se ili napravi nalog.</p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="{{ route('register') }}" class="arena-button-primary">Registruj se</a>
                                    <a href="{{ route('login') }}" class="arena-button-secondary">Prijavi se</a>
                                </div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('reservations.store') }}" class="space-y-5" data-booking-form>
                                @csrf
                                <input type="hidden" name="court_id" value="" data-reservation-court-id>
                                <input type="hidden" name="starts_at" value="" data-reservation-starts-at>
                                <input type="hidden" name="duration_minutes" value="" data-reservation-duration>

                                <div class="site-table-shell p-4" data-equipment-box hidden>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-extrabold uppercase tracking-[0.26em] text-[color:var(--arena-forest-glow)]">Iznajmljivanje opreme</p>
                                            <p class="mt-2 text-sm text-[color:var(--arena-muted)]">Dodaj opremu uz termin.</p>
                                        </div>
                                        <span class="info-chip-soft">Opcionalno</span>
                                    </div>

                                    <div class="mt-4 grid gap-3" data-equipment-list></div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Napomena</label>
                                    <textarea name="customer_note" rows="3" class="mt-2 w-full rounded-[1.25rem] border-[color:var(--arena-border)] bg-white px-4 py-3">{{ old('customer_note') }}</textarea>
                                </div>

                                <button class="arena-button-primary w-full justify-center">Potvrdi rezervaciju</button>
                            </form>
                        @endif
                    </div>

                    <div class="premium-card p-6 sm:p-7 booking-section" data-pricing-card hidden>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="eyebrow-chip">Cenovnik</span>
                                <h3 class="section-title mt-4 text-[2.15rem] sm:text-[2.8rem]">Cene</h3>
                            </div>
                            <span class="info-chip-soft">Za izabrani sport</span>
                        </div>

                        <div class="space-y-4" data-pricing-list></div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const app = document.querySelector('[data-booking-app]');

            if (!app) return;

            const sports = JSON.parse(app.dataset.sports ?? '[]');
            const initial = JSON.parse(app.dataset.initial ?? '{}');
            const availabilityUrl = app.dataset.availabilityUrl;

            const sportSelect = app.querySelector('[data-booking-sport]');
            const prevWindowButton = app.querySelector('[data-window-prev]');
            const nextWindowButton = app.querySelector('[data-window-next]');
            const windowLabel = app.querySelector('[data-window-label]');
            const feedbackBox = app.querySelector('[data-booking-feedback]');
            const dayList = app.querySelector('[data-day-list]');
            const timeList = app.querySelector('[data-time-list]');
            const chooserRow = app.querySelector('[data-chooser-row]');
            const durationSelect = app.querySelector('[data-duration-select]');
            const courtSelect = app.querySelector('[data-court-select]');
            const pricingCard = app.querySelector('[data-pricing-card]');
            const pricingList = app.querySelector('[data-pricing-list]');
            const summaryCard = app.querySelector('[data-summary-card]');
            const summaryBox = app.querySelector('[data-summary-box]');
            const summaryTitle = app.querySelector('[data-summary-title]');
            const summaryCopy = app.querySelector('[data-summary-copy]');
            const selectedPrice = app.querySelector('[data-selected-price]');
            const equipmentBox = app.querySelector('[data-equipment-box]');
            const equipmentList = app.querySelector('[data-equipment-list]');
            const courtIdInput = app.querySelector('[data-reservation-court-id]');
            const startsAtInput = app.querySelector('[data-reservation-starts-at]');
            const durationInput = app.querySelector('[data-reservation-duration]');
            const courtPreview = app.querySelector('[data-court-preview]');
            const courtPreviewImage = app.querySelector('[data-court-preview-image]');
            const courtPreviewName = app.querySelector('[data-court-preview-name]');
            const courtPreviewMeta = app.querySelector('[data-court-preview-meta]');

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const initialDate = initial.date ? new Date(`${initial.date}T00:00:00`) : new Date(today);
            initialDate.setHours(0, 0, 0, 0);

            let availabilityPayload = null;
            let windowStart = initialDate < today ? new Date(today) : initialDate;
            let selectedDayIndex = 0;
            let selectedDay = null;
            let selectedTime = null;
            let selectedDuration = null;
            let selectedCourt = null;

            const formatMoney = (amount) => `${new Intl.NumberFormat('sr-RS').format(Number(amount || 0))} RSD`;

            const toIsoDate = (date) => {
                const year = date.getFullYear();
                const month = `${date.getMonth() + 1}`.padStart(2, '0');
                const day = `${date.getDate()}`.padStart(2, '0');

                return `${year}-${month}-${day}`;
            };

            const resetSelect = (select, placeholder) => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.value = '';
                select.disabled = true;
            };

            const setFeedback = (message, type = 'info') => {
                feedbackBox.hidden = false;
                const classes = type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-[color:var(--arena-border)] bg-[color:var(--arena-cream)] text-[color:var(--arena-ink)]';

                feedbackBox.innerHTML = `<div class="rounded-[1.25rem] border px-4 py-3 text-sm ${classes}">${message}</div>`;
            };

            const clearFeedback = () => {
                feedbackBox.hidden = true;
                feedbackBox.innerHTML = '';
            };

            const updateWindowLabel = () => {
                const end = new Date(windowStart);
                end.setDate(end.getDate() + 4);

                windowLabel.textContent = `${windowStart.toLocaleDateString('sr-RS')} - ${end.toLocaleDateString('sr-RS')}`;
                prevWindowButton.disabled = windowStart <= today;
            };

            const clearFlow = () => {
                selectedDay = null;
                selectedTime = null;
                selectedDuration = null;
                selectedCourt = null;
                dayList.innerHTML = '';
                timeList.innerHTML = '';
                resetSelect(durationSelect, 'Izaberi trajanje');
                resetSelect(courtSelect, 'Izaberi teren');
                chooserRow.hidden = true;
                summaryCard.hidden = true;
                summaryBox.hidden = true;
                if (courtPreview) courtPreview.hidden = true;
            };

            const fillSports = () => {
                sportSelect.innerHTML = '<option value="">Izaberi sport</option>';

                sports.forEach((sport) => {
                    const option = document.createElement('option');
                    option.value = sport.slug;
                    option.textContent = sport.name;

                    if (initial.sport === sport.slug) option.selected = true;

                    sportSelect.appendChild(option);
                });
            };

            const renderPricing = (rules) => {
                pricingList.innerHTML = '';
                pricingCard.hidden = rules.length === 0;

                rules.forEach((rule) => {
                    const item = document.createElement('div');
                    item.className = 'booking-mini-card';
                    item.innerHTML = `
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-[color:var(--arena-forest)]">${rule.name}</p>
                                <p class="mt-1 text-sm text-slate-500">${rule.days}</p>
                            </div>
                            <span class="info-chip">${rule.time}</span>
                        </div>
                        <div class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                            <div><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">1h</span><strong class="mt-1 block text-[color:var(--arena-forest)]">${formatMoney(rule.price60)}</strong></div>
                            <div><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">1,5h</span><strong class="mt-1 block text-[color:var(--arena-forest)]">${formatMoney(rule.price90)}</strong></div>
                            <div><span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500">2h</span><strong class="mt-1 block text-[color:var(--arena-forest)]">${formatMoney(rule.price120)}</strong></div>
                        </div>
                    `;
                    pricingList.appendChild(item);
                });
            };

            const renderEquipment = (items) => {
                if (!equipmentBox || !equipmentList) return;

                equipmentList.innerHTML = '';
                equipmentBox.hidden = items.length === 0;

                items.forEach((item, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'booking-mini-card booking-equipment-item';
                    wrapper.innerHTML = `
                        <div class="booking-equipment-item__grid">
                            <div class="booking-equipment-item__image-shell">
                                ${item.image_url
                                    ? `<img src="${item.image_url}" alt="${item.name}" class="booking-equipment-item__image">`
                                    : `<div class="booking-equipment-item__image-fallback"><img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Arena SC" class="h-10 w-10 opacity-80"></div>`
                                }
                            </div>
                            <div class="booking-equipment-item__copy">
                                <div class="booking-equipment-item__top">
                                    <p class="booking-equipment-item__name">${item.name}</p>
                                    <span class="booking-equipment-item__price">${formatMoney(item.price)}</span>
                                </div>
                                <p class="booking-equipment-item__description">${item.description || 'Oprema za termin.'}</p>
                                <input type="hidden" name="equipment[${index}][equipment_id]" value="${item.id}">
                            </div>
                            <div class="booking-equipment-item__quantity">
                                <label class="booking-equipment-item__quantity-label">Kolicina</label>
                                <input type="number" min="0" max="10" name="equipment[${index}][quantity]" value="0" class="booking-equipment-item__input">
                            </div>
                        </div>
                    `;
                    equipmentList.appendChild(wrapper);
                });
            };

            const renderSummary = () => {
                if (!selectedDay || !selectedTime || !selectedDuration || !selectedCourt) {
                    summaryCard.hidden = true;
                    summaryBox.hidden = true;
                    if (courtPreview) courtPreview.hidden = true;
                    return;
                }

                summaryCard.hidden = false;
                summaryBox.hidden = false;
                summaryTitle.textContent = `${selectedDay.full_label} | ${selectedTime.time}`;
                summaryCopy.textContent = `${selectedDuration.label} | ${selectedCourt.name} | ${selectedCourt.location || 'Arena SC'} | ${selectedCourt.surface || 'Spreman teren'}`;
                selectedPrice.textContent = formatMoney(selectedCourt.price);

                if (courtIdInput) courtIdInput.value = selectedCourt.id;
                if (startsAtInput) startsAtInput.value = selectedCourt.starts_at;
                if (durationInput) durationInput.value = selectedDuration.minutes;

                if (courtPreview) {
                    courtPreview.hidden = false;
                    courtPreviewName.textContent = selectedCourt.name;
                    courtPreviewMeta.textContent = `${selectedCourt.location || 'Arena SC'} | ${selectedCourt.surface || 'Standard'}`;
                    courtPreviewImage.innerHTML = selectedCourt.image_url
                        ? `<img src="${selectedCourt.image_url}" alt="${selectedCourt.name}" class="h-24 w-full object-cover">`
                        : `<div class="flex h-24 items-center justify-center bg-[linear-gradient(145deg,rgba(15,42,31,0.96),rgba(26,26,26,0.92))]"><img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Arena SC" class="h-10 w-10 opacity-80"></div>`;
                }

                renderEquipment(availabilityPayload?.equipment ?? []);
            };

            const renderCourts = () => {
                resetSelect(courtSelect, 'Izaberi teren');
                selectedCourt = null;
                summaryCard.hidden = true;
                summaryBox.hidden = true;

                if (!selectedDuration) return;

                courtSelect.disabled = false;

                selectedDuration.courts.forEach((court, index) => {
                    const option = document.createElement('option');
                    option.value = `${index}`;
                    option.textContent = `${court.name} - ${formatMoney(court.price)}`;
                    courtSelect.appendChild(option);
                });
            };

            const renderDurations = () => {
                chooserRow.hidden = false;
                resetSelect(durationSelect, 'Izaberi trajanje');
                resetSelect(courtSelect, 'Izaberi teren');
                selectedDuration = null;
                selectedCourt = null;
                summaryCard.hidden = true;
                summaryBox.hidden = true;

                if (!selectedTime) return;

                durationSelect.disabled = false;

                selectedTime.durations.forEach((duration, index) => {
                    const option = document.createElement('option');
                    option.value = `${index}`;
                    option.textContent = `${duration.label} - od ${formatMoney(duration.price_from)}`;
                    durationSelect.appendChild(option);
                });
            };

            const renderTimes = () => {
                timeList.innerHTML = '';
                resetSelect(durationSelect, 'Izaberi trajanje');
                resetSelect(courtSelect, 'Izaberi teren');
                chooserRow.hidden = true;
                selectedTime = null;
                selectedDuration = null;
                selectedCourt = null;
                summaryCard.hidden = true;
                summaryBox.hidden = true;

                if (!selectedDay) {
                    setFeedback('Izaberi dan da prikazemo slobodna vremena.', 'info');
                    return;
                }

                clearFeedback();

                if (!selectedDay.times.length) {
                    setFeedback('Za izabrani dan trenutno nema slobodnih termina. Probaj sledeci dan.', 'error');
                    return;
                }

                selectedDay.times.forEach((time, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'booking-time-pill';
                    button.textContent = time.time;

                    button.addEventListener('click', () => {
                        selectedTime = selectedDay.times[index];
                        Array.from(timeList.children).forEach((child) => child.classList.remove('is-active'));
                        button.classList.add('is-active');
                        renderDurations();
                    });

                    timeList.appendChild(button);
                });
            };

            const renderDays = (days) => {
                dayList.innerHTML = '';
                selectedDay = null;
                selectedTime = null;
                selectedDuration = null;
                selectedCourt = null;
                timeList.innerHTML = '';
                resetSelect(durationSelect, 'Izaberi trajanje');
                resetSelect(courtSelect, 'Izaberi teren');
                chooserRow.hidden = true;
                summaryCard.hidden = true;
                summaryBox.hidden = true;

                const visibleDays = days.slice(0, 3);

                visibleDays.forEach((day, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'booking-day-card';
                    button.innerHTML = `
                        <span class="block text-xs font-extrabold uppercase tracking-[0.22em] opacity-70">${day.day_label}</span>
                        <strong class="mt-2 block text-4xl font-black">${day.date_label}</strong>
                        <span class="mt-1 block text-sm opacity-80">${day.month_label}</span>
                    `;

                    button.addEventListener('click', () => {
                        selectedDayIndex = index;
                        selectedDay = visibleDays[index];
                        Array.from(dayList.children).forEach((child) => child.classList.remove('is-active'));
                        button.classList.add('is-active');
                        renderTimes();
                    });

                    dayList.appendChild(button);

                    if (index === 0) {
                        button.click();
                    }
                });
            };

            const loadAvailability = async () => {
                const sport = sportSelect.value;

                if (!sport) {
                    clearFlow();
                    setFeedback('Prvo izaberi sport da bismo ucitali slobodne termine.', 'error');
                    return;
                }

                prevWindowButton.disabled = true;
                nextWindowButton.disabled = true;
                clearFlow();
                pricingCard.hidden = true;

                try {
                    const url = new URL(availabilityUrl, window.location.origin);
                    url.searchParams.set('sport', sport);
                    url.searchParams.set('date', toIsoDate(windowStart));

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Ne mozemo trenutno da ucitamo slobodne termine.');
                    }

                    availabilityPayload = await response.json();
                    renderPricing(availabilityPayload.pricingRules ?? []);
                    renderDays(availabilityPayload.days ?? []);
                    setFeedback('Izaberi dan, vreme, trajanje i teren.', 'info');
                } catch (error) {
                    setFeedback(error.message || 'Doslo je do greske pri ucitavanju termina.', 'error');
                } finally {
                    updateWindowLabel();
                    prevWindowButton.disabled = windowStart <= today;
                    nextWindowButton.disabled = false;
                }
            };

            fillSports();
            updateWindowLabel();

            sportSelect.addEventListener('change', () => {
                availabilityPayload = null;
                pricingCard.hidden = true;
                clearFlow();

                if (sportSelect.value) {
                    loadAvailability();
                } else {
                    clearFeedback();
                }
            });

            durationSelect.addEventListener('change', () => {
                selectedDuration = selectedTime?.durations?.[Number(durationSelect.value)] ?? null;
                renderCourts();
            });

            courtSelect.addEventListener('change', () => {
                selectedCourt = selectedDuration?.courts?.[Number(courtSelect.value)] ?? null;
                renderSummary();
            });

            prevWindowButton.addEventListener('click', () => {
                const candidate = new Date(windowStart);
                candidate.setDate(candidate.getDate() - 5);
                candidate.setHours(0, 0, 0, 0);

                windowStart = candidate < today ? new Date(today) : candidate;
                updateWindowLabel();

                if (sportSelect.value) {
                    loadAvailability();
                }
            });

            nextWindowButton.addEventListener('click', () => {
                windowStart = new Date(windowStart);
                windowStart.setDate(windowStart.getDate() + 5);
                windowStart.setHours(0, 0, 0, 0);
                updateWindowLabel();

                if (sportSelect.value) {
                    loadAvailability();
                }
            });

            if (initial.sport) {
                loadAvailability();
            } else {
                setFeedback('Izaberi sport da odmah prikazemo naredne slobodne termine.', 'info');
            }
        })();
    </script>
@endsection
