@extends('layouts.site', [
    'title' => 'Rezervisi termin | Padel i Basket 3x3 | Arena Kraljevo',
    'metaDescription' => 'Online rezervacija termina za padel i basket 3x3 u Sportski centar Arena Kraljevo. Proverite slobodne slotove i cene termina.',
    'metaKeywords' => 'rezervisi termin, online rezervacija, padel kraljevo, basket 3x3 kraljevo, sportski centar arena',
    'metaImage' => asset('media/home/hero-exterior.webp'),
])

@section('content')
    @php
        $sportsPayload = $sports->map(fn ($sport) => [
            'name' => $sport->name,
            'slug' => $sport->slug,
            'supports_online_booking' => (bool) $sport->supports_online_booking,
        ])->values();
    @endphp

    <section
        class="site-grid py-10 sm:py-12"
        data-booking-app
        data-sports='@json($sportsPayload)'
        data-initial='@json($initialState)'
        data-availability-url="{{ route('booking.availability') }}"
        data-authenticated="{{ auth()->check() ? '1' : '0' }}"
        data-contact-phone="{{ config('arena.contact.phone') }}"
        data-contact-email="{{ config('arena.contact.email') }}"
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

            <div class="booking-summary-grid booking-summary-grid--single">
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

                        <div class="booking-day-strip" data-day-strip>
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

                        <div class="mt-3 text-center text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]" data-window-label-row>
                            <span data-window-label>
                                Termin prozor
                            </span>
                        </div>

                        <div class="mt-7 flex items-center justify-between gap-3" data-time-header>
                            <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Vreme</p>
                            <span class="info-chip-soft">Samo slobodni slotovi</span>
                        </div>

                        <div class="mt-5 booking-time-grid-compact" data-time-list></div>

                        <div class="mt-5" data-booking-feedback hidden></div>

                        <div class="soft-message mt-5" data-contact-inline hidden></div>
                    </div>
                </div>

                <aside class="booking-sidebar">
                    <div class="premium-card p-6 sm:p-7 booking-section" data-contact-card hidden>
                        <div class="space-y-5">
                            <div>
                                <span class="eyebrow-chip">Kontakt rezervacija</span>
                                <h3 class="section-title mt-4 text-[2rem] sm:text-[2.5rem]" data-contact-title>Kontaktirajte nas</h3>
                                <p class="mt-3 text-sm leading-7 text-[color:var(--arena-muted)]" data-contact-copy></p>
                            </div>

                            <div class="booking-mini-card">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('arena.contact.phone')) }}" class="arena-button-secondary-light w-full justify-center text-center leading-tight" data-contact-phone-link>
                                        {{ config('arena.contact.phone') }}
                                    </a>
                                    <a href="mailto:{{ config('arena.contact.email') }}" class="arena-button-secondary-light w-full justify-center break-words text-center leading-tight" data-contact-email-link>
                                        {{ config('arena.contact.email') }}
                                    </a>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="redirect_to" value="booking">

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Ime i prezime</label>
                                        <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" class="mt-2 booking-select" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Email</label>
                                        <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="mt-2 booking-select" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Telefon</label>
                                    <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" class="mt-2 booking-select">
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Poruka</label>
                                    <textarea name="message" rows="4" class="booking-textarea mt-2" data-contact-message required>{{ old('message') }}</textarea>
                                </div>

                                <button class="arena-button-primary w-full justify-center">Posalji upit</button>
                            </form>
                        </div>
                    </div>

                    <div class="booking-modal" data-booking-modal hidden>
                        <button type="button" class="booking-modal__shade" data-booking-modal-close aria-label="Zatvori detalje termina"></button>

                        <div class="premium-card p-6 sm:p-7 booking-section booking-modal__panel" data-summary-box hidden role="dialog" aria-modal="true" aria-label="Potvrda rezervacije">
                        <button type="button" class="booking-modal__close" data-booking-modal-close aria-label="Zatvori">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                            </svg>
                        </button>

                        <div class="booking-option-list booking-option-list--modal" data-option-list hidden></div>

                        <div class="booking-confirm-stack" data-booking-confirm hidden>
                            <div class="booking-confirm-topline">
                                <button type="button" class="booking-back-button" data-booking-back-to-options>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                                    </svg>
                                    Promeni izbor
                                </button>
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

                            <div class="booking-mini-card" data-court-preview hidden>
                                <div class="grid gap-4 sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-center">
                                    <div class="overflow-hidden rounded-[1.1rem] border border-white/20 bg-[rgba(15,42,31,0.08)]" data-court-preview-image></div>
                                    <div>
                                        <p class="text-xl font-black text-[color:var(--arena-forest)]" data-court-preview-name></p>
                                        <p class="mt-1 text-sm text-[color:var(--arena-muted)]" data-court-preview-meta></p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('reservations.store') }}" class="space-y-5" data-booking-form>
                                @csrf
                                <input type="hidden" name="court_id" value="" data-reservation-court-id>
                                <input type="hidden" name="starts_at" value="" data-reservation-starts-at>
                                <input type="hidden" name="duration_minutes" value="" data-reservation-duration>

                                @guest
                                    <div class="site-table-shell p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-extrabold uppercase tracking-[0.26em] text-[color:var(--arena-forest-glow)]">Podaci za rezervaciju</p>
                                                <p class="mt-2 text-sm text-[color:var(--arena-muted)]">Nalog nije obavezan.</p>
                                            </div>
                                            <span class="info-chip-soft">Guest</span>
                                        </div>

                                        <div class="mt-4 grid gap-4">
                                            <div>
                                                <label class="text-sm font-semibold text-slate-700">Ime i prezime</label>
                                                <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="mt-2 booking-select" required>
                                                @error('guest_name')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="text-sm font-semibold text-slate-700">Telefon</label>
                                                <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="mt-2 booking-select" required>
                                                @error('guest_phone')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="text-sm font-semibold text-slate-700">Email <span class="font-normal text-slate-500">(opciono)</span></label>
                                                <input type="email" name="guest_email" value="{{ old('guest_email') }}" class="mt-2 booking-select">
                                                @error('guest_email')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endguest

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
                                    <textarea name="customer_note" rows="3" class="booking-textarea mt-2">{{ old('customer_note') }}</textarea>
                                </div>

                                <div class="booking-submit-bar">
                                    <button type="submit" class="arena-button-primary w-full justify-center">Potvrdi rezervaciju</button>
                                </div>
                            </form>
                        </div>
                        </div>
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
            const contactPhone = app.dataset.contactPhone;
            const contactEmail = app.dataset.contactEmail;

            const sportSelect = app.querySelector('[data-booking-sport]');
            const prevWindowButton = app.querySelector('[data-window-prev]');
            const nextWindowButton = app.querySelector('[data-window-next]');
            const windowLabel = app.querySelector('[data-window-label]');
            const windowLabelRow = app.querySelector('[data-window-label-row]');
            const dayStrip = app.querySelector('[data-day-strip]');
            const timeHeader = app.querySelector('[data-time-header]');
            const feedbackBox = app.querySelector('[data-booking-feedback]');
            const contactInline = app.querySelector('[data-contact-inline]');
            const dayList = app.querySelector('[data-day-list]');
            const timeList = app.querySelector('[data-time-list]');
            const optionList = app.querySelector('[data-option-list]');
            const pricingCard = app.querySelector('[data-pricing-card]');
            const pricingList = app.querySelector('[data-pricing-list]');
            const summaryCard = app.querySelector('[data-summary-card]');
            const summaryBox = app.querySelector('[data-summary-box]');
            const confirmSection = app.querySelector('[data-booking-confirm]');
            const bookingModal = app.querySelector('[data-booking-modal]');
            const bookingModalCloseButtons = app.querySelectorAll('[data-booking-modal-close]');
            const bookingBackToOptionsButton = app.querySelector('[data-booking-back-to-options]');
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
            const contactCard = app.querySelector('[data-contact-card]');
            const contactTitle = app.querySelector('[data-contact-title]');
            const contactCopy = app.querySelector('[data-contact-copy]');
            const contactPhoneLink = app.querySelector('[data-contact-phone-link]');
            const contactEmailLink = app.querySelector('[data-contact-email-link]');
            const contactMessage = app.querySelector('[data-contact-message]');

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
            let selectedSportMeta = null;

            const formatMoney = (amount) => `${new Intl.NumberFormat('sr-RS').format(Number(amount || 0))} RSD`;

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character]);

            const safeImageUrl = (value) => {
                if (!value) return '';

                try {
                    const url = new URL(value, window.location.origin);

                    return url.origin === window.location.origin && ['http:', 'https:'].includes(url.protocol)
                        ? url.href
                        : '';
                } catch {
                    return '';
                }
            };

            const normalizeList = (value) => {
                if (Array.isArray(value)) return value;
                if (value && typeof value === 'object') return Object.values(value);

                return [];
            };

            const toIsoDate = (date) => {
                const year = date.getFullYear();
                const month = `${date.getMonth() + 1}`.padStart(2, '0');
                const day = `${date.getDate()}`.padStart(2, '0');

                return `${year}-${month}-${day}`;
            };

            const openBookingModal = () => {
                if (!bookingModal) return;
                bookingModal.hidden = false;
                document.body.classList.add('booking-modal-open');
            };

            const closeBookingModal = () => {
                if (!bookingModal) return;
                bookingModal.hidden = true;
                document.body.classList.remove('booking-modal-open');
            };

            const showOptionStep = () => {
                optionList.hidden = false;
                if (confirmSection) confirmSection.hidden = true;
                summaryCard.hidden = true;
                if (courtPreview) courtPreview.hidden = true;
                if (equipmentBox) equipmentBox.hidden = true;
            };

            const showConfirmStep = () => {
                optionList.hidden = true;
                if (confirmSection) confirmSection.hidden = false;
            };

            const setFeedback = (message, type = 'info') => {
                feedbackBox.hidden = false;
                const classes = type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-[color:var(--arena-border)] bg-[color:var(--arena-cream)] text-[color:var(--arena-ink)]';
                const feedback = document.createElement('div');
                feedback.className = `rounded-[1.25rem] border px-4 py-3 text-sm ${classes}`;
                feedback.textContent = String(message ?? '');
                feedbackBox.replaceChildren(feedback);
            };

            const clearFeedback = () => {
                feedbackBox.hidden = true;
                feedbackBox.innerHTML = '';
            };

            const hideContactMode = () => {
                contactInline.hidden = true;
                contactInline.innerHTML = '';
                if (contactCard) {
                    contactCard.hidden = true;
                }
            };

            const showOnlineMode = () => {
                dayStrip.hidden = false;
                windowLabelRow.hidden = false;
                timeHeader.hidden = false;
                timeList.hidden = false;
                hideContactMode();
            };

            const showContactMode = (sport, message) => {
                clearFlow();
                clearFeedback();
                pricingCard.hidden = true;
                summaryCard.hidden = true;
                summaryBox.hidden = true;
                dayStrip.hidden = true;
                windowLabelRow.hidden = true;
                timeHeader.hidden = true;
                timeList.hidden = true;
                optionList.hidden = true;

                const copy = message || `Za sport ${sport.name} online rezervacija trenutno nije dostupna. Posaljite upit ili nas pozovite i zakazacemo termin.`;

                contactInline.hidden = false;
                const contactParagraph = document.createElement('p');
                contactParagraph.className = 'text-sm leading-7 text-[color:var(--arena-ink)]';
                contactParagraph.textContent = copy;
                contactInline.replaceChildren(contactParagraph);

                if (contactCard) {
                    contactCard.hidden = false;
                    contactTitle.textContent = `Kontakt za ${sport.name}`;
                    contactCopy.textContent = copy;
                    contactPhoneLink.textContent = contactPhone;
                    contactPhoneLink.href = `tel:${contactPhone.replace(/[^0-9+]/g, '')}`;
                    contactEmailLink.textContent = contactEmail;
                    contactEmailLink.href = `mailto:${contactEmail}`;
                    if (contactMessage && !contactMessage.value.trim()) {
                        contactMessage.value = `Zelim da rezervisem termin za sport ${sport.name}. Molim vas da me kontaktirate sa vise informacija.`;
                    }
                }
            };

            const updateWindowLabel = () => {
                const end = new Date(windowStart);
                end.setDate(end.getDate() + 2);

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
                optionList.innerHTML = '';
                optionList.hidden = true;
                summaryCard.hidden = true;
                summaryBox.hidden = true;
                if (confirmSection) confirmSection.hidden = true;
                closeBookingModal();
                if (courtPreview) courtPreview.hidden = true;
                if (equipmentBox) equipmentBox.hidden = true;
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

            const renderPricing = () => {
                pricingCard.hidden = true;
                pricingList.replaceChildren();
            };

            const renderEquipment = (items) => {
                items = normalizeList(items);
                if (!equipmentBox || !equipmentList) return;

                equipmentList.innerHTML = '';
                equipmentBox.hidden = items.length === 0;

                items.forEach((item, index) => {
                    const wrapper = document.createElement('div');
                    const imageUrl = safeImageUrl(item.image_url);
                    const itemName = escapeHtml(item.name);
                    const itemDescription = escapeHtml(item.description || 'Oprema za termin.');
                    const itemId = Number.isInteger(Number(item.id)) ? Number(item.id) : '';
                    wrapper.className = 'booking-mini-card booking-equipment-item';
                    wrapper.innerHTML = `
                        <div class="booking-equipment-item__grid">
                            <div class="booking-equipment-item__image-shell">
                                ${imageUrl
                                    ? `<img src="${escapeHtml(imageUrl)}" alt="${itemName}" loading="lazy" decoding="async" class="booking-equipment-item__image">`
                                    : `<div class="booking-equipment-item__image-fallback"><img src="{{ asset('brand/arena-sc-mark.webp') }}" alt="Sportski centar Arena" width="640" height="360" loading="lazy" decoding="async" class="h-10 w-10 opacity-80"></div>`
                                }
                            </div>
                            <div class="booking-equipment-item__copy">
                                <div class="booking-equipment-item__top">
                                    <p class="booking-equipment-item__name">${itemName}</p>
                                    <span class="booking-equipment-item__price">${formatMoney(item.price)}</span>
                                </div>
                                <p class="booking-equipment-item__description">${itemDescription}</p>
                                <input type="hidden" name="equipment[${index}][equipment_id]" value="${itemId}">
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
                    if (confirmSection) confirmSection.hidden = true;
                    closeBookingModal();
                    if (courtPreview) courtPreview.hidden = true;
                    return;
                }

                showConfirmStep();
                summaryCard.hidden = false;
                summaryBox.hidden = false;
                summaryTitle.textContent = `${selectedDay.full_label} | ${selectedTime.time}`;
                summaryCopy.textContent = `${selectedDuration.label} | ${selectedCourt.name} | ${selectedCourt.location || 'Sportski centar Arena'} | ${selectedCourt.surface || 'Spreman teren'}`;
                selectedPrice.textContent = formatMoney(selectedCourt.price);

                if (courtIdInput) courtIdInput.value = selectedCourt.id;
                if (startsAtInput) startsAtInput.value = selectedCourt.starts_at;
                if (durationInput) durationInput.value = selectedDuration.minutes;

                if (courtPreview) {
                    const courtImageUrl = safeImageUrl(selectedCourt.image_url);
                    courtPreview.hidden = false;
                    courtPreviewName.textContent = selectedCourt.name;
                    courtPreviewMeta.textContent = `${selectedCourt.location || 'Sportski centar Arena'} | ${selectedCourt.surface || 'Standard'}`;
                    courtPreviewImage.innerHTML = courtImageUrl
                        ? `<img src="${escapeHtml(courtImageUrl)}" alt="${escapeHtml(selectedCourt.name)}" decoding="async" class="h-24 w-full object-cover">`
                        : `<div class="flex h-24 items-center justify-center bg-[linear-gradient(145deg,rgba(15,42,31,0.96),rgba(26,26,26,0.92))]"><img src="{{ asset('brand/arena-sc-mark.webp') }}" alt="Sportski centar Arena" width="640" height="360" decoding="async" class="h-10 w-10 opacity-80"></div>`;
                }

                renderEquipment(availabilityPayload?.equipment ?? []);
                openBookingModal();
            };

            const renderOptions = () => {
                optionList.innerHTML = '';
                selectedDuration = null;
                selectedCourt = null;
                summaryBox.hidden = false;
                showOptionStep();
                openBookingModal();

                if (!selectedTime) return;

                const header = document.createElement('div');
                header.className = 'booking-option-list__header';
                header.innerHTML = `
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.26em] text-[color:var(--arena-forest-glow)]">${escapeHtml(selectedDay.full_label)} | ${escapeHtml(selectedTime.time)}</p>
                        <p class="mt-1 text-sm text-[color:var(--arena-muted)]">Izaberi teren, pa trajanje termina.</p>
                    </div>
                    <span class="info-chip-soft">Slobodni tereni</span>
                `;
                optionList.appendChild(header);

                const grid = document.createElement('div');
                grid.className = 'booking-court-option-grid';
                const courtGroups = new Map();

                normalizeList(selectedTime.durations).forEach((duration) => {
                    normalizeList(duration.courts).forEach((court) => {
                        const courtMeta = availabilityPayload?.courts?.[court.id] ?? {};
                        const group = courtGroups.get(court.id) ?? {
                            court: { ...courtMeta, ...court },
                            durations: [],
                        };

                        group.durations.push({
                            ...duration,
                            court: { ...courtMeta, ...court },
                        });

                        courtGroups.set(court.id, group);
                    });
                });

                courtGroups.forEach((group) => {
                    const card = document.createElement('div');
                    const courtName = escapeHtml(group.court.name);
                    const courtLocation = escapeHtml(group.court.location || 'Sportski centar Arena');
                    const courtSurface = group.court.surface ? ` | ${escapeHtml(group.court.surface)}` : '';
                    card.className = 'booking-court-option-card';
                    card.innerHTML = `
                        <div class="booking-court-option-card__header">
                            <div>
                                <p class="booking-court-option-card__title">${courtName}</p>
                                <p class="booking-court-option-card__meta">${courtLocation}${courtSurface}</p>
                            </div>
                            <span class="info-chip-soft">${group.durations.length} opcije</span>
                        </div>
                    `;

                    const durationGrid = document.createElement('div');
                    durationGrid.className = 'booking-duration-choice-grid';

                    group.durations.forEach((duration) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'booking-duration-choice';
                        option.innerHTML = `
                            <span>${escapeHtml(duration.label)}</span>
                            <strong>${formatMoney(duration.court.price)}</strong>
                        `;

                        option.addEventListener('click', () => {
                            selectedDuration = duration;
                            selectedCourt = duration.court;
                            Array.from(optionList.querySelectorAll('.booking-duration-choice')).forEach((child) => child.classList.remove('is-active'));
                            option.classList.add('is-active');
                            renderSummary();
                        });

                        durationGrid.appendChild(option);
                    });

                    card.appendChild(durationGrid);
                    grid.appendChild(card);
                });

                if (!courtGroups.size) {
                    setFeedback('Za izabrano vreme nema slobodnih terena. Probaj drugo vreme.', 'error');
                    optionList.hidden = true;
                    summaryBox.hidden = true;
                    closeBookingModal();
                    return;
                }

                optionList.appendChild(grid);
            };

            const renderTimes = () => {
                timeList.innerHTML = '';
                optionList.innerHTML = '';
                optionList.hidden = true;
                selectedTime = null;
                selectedDuration = null;
                selectedCourt = null;
                summaryCard.hidden = true;
                summaryBox.hidden = true;
                if (confirmSection) confirmSection.hidden = true;
                closeBookingModal();

                if (!selectedDay) {
                    setFeedback('Izaberi dan da prikazemo slobodna vremena.', 'info');
                    return;
                }

                clearFeedback();

                selectedDay.times = normalizeList(selectedDay.times);

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
                        renderOptions();
                    });

                    timeList.appendChild(button);
                });
            };

            const renderDateShell = () => {
                dayList.innerHTML = '';
                selectedDay = null;

                Array.from({ length: 3 }).forEach((_, index) => {
                    const date = new Date(windowStart);
                    date.setDate(date.getDate() + index);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `booking-day-card${index === 0 ? ' is-active' : ''}`;
                    button.disabled = true;
                    button.innerHTML = `
                        <span class="block text-xs font-extrabold uppercase tracking-[0.22em] opacity-70">${date.toLocaleDateString('sr-RS', { weekday: 'short' })}</span>
                        <strong class="mt-2 block text-4xl font-black">${String(date.getDate()).padStart(2, '0')}</strong>
                        <span class="mt-1 block text-sm opacity-80">${date.toLocaleDateString('sr-RS', { month: 'short' })}</span>
                    `;

                    dayList.appendChild(button);
                });
            };

            const renderDays = (days) => {
                days = normalizeList(days);
                dayList.innerHTML = '';
                selectedDay = null;
                selectedTime = null;
                selectedDuration = null;
                selectedCourt = null;
                timeList.innerHTML = '';
                optionList.innerHTML = '';
                optionList.hidden = true;
                summaryCard.hidden = true;
                summaryBox.hidden = true;
                if (confirmSection) confirmSection.hidden = true;
                closeBookingModal();

                const visibleDays = days.slice(0, 3);

                if (!visibleDays.length) {
                    setFeedback('Za izabrani sport trenutno nema dostupnih dana za online rezervaciju.', 'error');
                    return;
                }

                visibleDays.forEach((day, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'booking-day-card';
                    button.innerHTML = `
                        <span class="block text-xs font-extrabold uppercase tracking-[0.22em] opacity-70">${escapeHtml(day.day_label)}</span>
                        <strong class="mt-2 block text-4xl font-black">${escapeHtml(day.date_label)}</strong>
                        <span class="mt-1 block text-sm opacity-80">${escapeHtml(day.month_label)}</span>
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
                selectedSportMeta = sports.find((item) => item.slug === sport) ?? null;

                if (!sport) {
                    clearFlow();
                    hideContactMode();
                    showOnlineMode();
                    setFeedback('Prvo izaberi sport da bismo ucitali slobodne termine.', 'error');
                    return;
                }

                if (selectedSportMeta && !selectedSportMeta.supports_online_booking) {
                    showContactMode(selectedSportMeta);
                    return;
                }

                showOnlineMode();

                prevWindowButton.disabled = true;
                nextWindowButton.disabled = true;
                clearFlow();
                renderDateShell();
                setFeedback('Ucitavamo slobodne termine...', 'info');
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

                    if (availabilityPayload.contact_only) {
                        showContactMode(availabilityPayload.sport, availabilityPayload.contact_message);
                        return;
                    }

                    renderPricing(availabilityPayload.pricingRules ?? []);
                    renderDays(availabilityPayload.days ?? []);
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
                    showOnlineMode();
                    clearFeedback();
                }
            });

            bookingModalCloseButtons.forEach((button) => {
                button.addEventListener('click', closeBookingModal);
            });

            if (bookingBackToOptionsButton) {
                bookingBackToOptionsButton.addEventListener('click', () => {
                    selectedDuration = null;
                    selectedCourt = null;
                    Array.from(optionList.querySelectorAll('.booking-duration-choice')).forEach((child) => child.classList.remove('is-active'));
                    showOptionStep();
                });
            }

            const bookingForm = app.querySelector('[data-booking-form]');

            bookingForm?.addEventListener('submit', () => {
                const submitButton = bookingForm.querySelector('button[type="submit"], button:not([type])');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                    submitButton.textContent = 'Saljemo rezervaciju...';
                }

                setFeedback('Saljemo rezervaciju. Molimo sacekaj potvrdu.', 'info');
            });

            prevWindowButton.addEventListener('click', () => {
                const candidate = new Date(windowStart);
                candidate.setDate(candidate.getDate() - 3);
                candidate.setHours(0, 0, 0, 0);

                windowStart = candidate < today ? new Date(today) : candidate;
                updateWindowLabel();

                if (sportSelect.value) {
                    loadAvailability();
                }
            });

            nextWindowButton.addEventListener('click', () => {
                windowStart = new Date(windowStart);
                windowStart.setDate(windowStart.getDate() + 3);
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
