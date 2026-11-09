(() => {
    const dialog = document.querySelector('[data-group-match-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const sideInput = dialog.querySelector('[name="side"]');
    const positionInput = dialog.querySelector('[name="position"]');
    const teamLabel = dialog.querySelector('[data-selected-team]');
    const form = dialog.querySelector('form');
    const roster = form.querySelector('[data-wizard-roster]');
    const review = form.querySelector('[data-wizard-review]');
    const consent = form.querySelector('[data-wizard-consent]');
    const progress = form.querySelector('[data-wizard-progress]');
    const stage = form.querySelector('[data-wizard-stage]');
    const caption = form.querySelector('[data-wizard-caption]');
    const summary = form.querySelector('[data-wizard-summary]');
    const back = form.querySelector('[data-wizard-back]');
    const next = form.querySelector('[data-wizard-next]');
    const submit = form.querySelector('[data-wizard-submit]');
    const playerPanels = new Map(Array.from(form.querySelectorAll('[data-wizard-player]'), panel => [
        panel.dataset.wizardPlayer, panel,
    ]));
    const ownName = roster.querySelector('[name="name"]');
    const ownEmail = roster.querySelector('[name="email"]');
    const guestSeats = Array.from(roster.querySelectorAll('[data-guest-key]'), fields => ({
        fields,
        slot: fields.closest('details'),
        name: fields.querySelector('[data-wizard-guest-name]'),
        email: fields.querySelector('[data-wizard-guest-email]'),
        panel: playerPanels.get(fields.dataset.guestKey),
    }));
    let players = [];
    let questions = [];
    let step = -1;

    form.dataset.wizardReady = '';
    form.noValidate = true;
    form.querySelector('[name="group_questionnaire"]').disabled = false;

    for (const guest of guestSeats) {
        guest.name.addEventListener('input', () => {
            guest.slot.querySelector('[data-wizard-guest-label]').textContent = guest.name.value.trim() || 'Slobodno';
        });
    }
    for (const email of [ownEmail, ...guestSeats.map(guest => guest.email)]) {
        email.addEventListener('input', () => email.setCustomValidity(''));
    }

    function buildQuestions() {
        players = [];
        questions = [];
        const people = [{ name: ownName, email: ownEmail, panel: playerPanels.get('self') }];

        for (const guest of guestSeats) {
            const participating = !guest.fields.disabled && (guest.name.value.trim() || guest.email.value.trim());
            guest.panel.disabled = !participating;
            guest.name.required = !!participating;
            guest.email.required = !!participating;
            if (participating) people.push(guest);
        }

        const addresses = new Set();
        for (const person of people) {
            person.name.value = person.name.value.trim();
            const address = person.email.value.trim().toLowerCase();
            person.email.setCustomValidity(addresses.has(address) ? 'Unesi različit mejl za svakog igrača.' : '');
            if (!person.name.checkValidity() || !person.email.checkValidity()) {
                if (person.slot) person.slot.open = true;
                if (!person.name.reportValidity()) return false;
                person.email.reportValidity();
                return false;
            }
            addresses.add(address);
        }

        players = people;
        questions = players.flatMap((person, playerIndex) => {
            if (!person.panel) return [];
            const items = Array.from(person.panel.querySelectorAll('[data-wizard-question]'));
            return items.map((question, index) => ({ person, playerIndex, question, index, total: items.length }));
        });

        return true;
    }

    function renderStep(focus = true) {
        const current = questions[step];
        const reviewing = step >= 0 && !current;
        roster.hidden = step >= 0;
        review.hidden = !reviewing;
        consent.hidden = !reviewing;
        back.hidden = step < 0;
        next.hidden = reviewing;
        submit.hidden = !reviewing;
        progress.hidden = false;

        for (const panel of playerPanels.values()) {
            panel.hidden = panel !== current?.person.panel;
            for (const question of panel.querySelectorAll('[data-wizard-question]')) {
                question.hidden = question !== current?.question;
            }
        }

        let target = ownName;
        if (current) {
            stage.textContent = current.person.name.value + ' · igrač ' + (current.playerIndex + 1) + ' od ' + players.length;
            caption.textContent = 'Pitanje ' + (current.index + 1) + ' od ' + current.total;
            next.textContent = step === questions.length - 1
                ? 'Pregled prijave' : 'Sledeće pitanje';
            target = current.question.querySelector('legend');
        } else if (reviewing) {
            stage.textContent = 'Korak 3 od 3 · Slanje prijave';
            caption.textContent = players.length === 1 ? '1 igrač' : players.length + ' igrača';
            submit.textContent = players.length === 1 ? 'Pošalji mejl za potvrdu' : 'Pošalji mejlove za potvrdu';
            summary.replaceChildren(...players.map(player => {
                const entry = document.createElement('li');
                entry.textContent = player.name.value;
                return entry;
            }));
            target = review.querySelector('h3');
        } else {
            stage.textContent = 'Korak 1 od 3 · Igrači';
            caption.textContent = 'Unesi sebe i saigrače koje prijavljuješ.';
            next.textContent = 'Idi dalje';
        }

        dialog.scrollTop = 0;
        if (focus) {
            if (current) target.tabIndex = -1;
            target.focus({ preventScroll: true });
        }
    }

    function advance() {
        if (step < 0 && !buildQuestions()) return;
        const current = questions[step];
        if (current && !current.question.querySelector('input:checked')) {
            current.question.querySelector('input').reportValidity();
            return;
        }
        step++;
        renderStep();
    }

    next.addEventListener('click', advance);
    back.addEventListener('click', () => {
        step--;
        renderStep();
    });
    form.addEventListener('click', event => {
        if (event.target.matches('input[type="radio"]') && questions[step]?.question.contains(event.target)) {
            advance();
        }
    });
    form.addEventListener('submit', event => {
        if (step !== questions.length) {
            event.preventDefault();
            advance();
        }
    });

    function selectSeat(side, position) {
        sideInput.value = side;
        positionInput.value = position;
        teamLabel.textContent = 'Ekipa ' + side;

        for (const guest of guestSeats) {
            const isOwnSeat = guest.fields.dataset.guestSide === side && guest.fields.dataset.guestPosition === position;
            guest.slot.hidden = isOwnSeat;
            guest.fields.disabled = isOwnSeat;
            guest.panel.disabled = true;
        }

        step = -1;
        players = [];
        questions = [];
        renderStep(false);
        dialog.showModal();
        ownName.focus({ preventScroll: true });
    }

    for (const seat of document.querySelectorAll('[data-group-seat]')) {
        seat.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            selectSeat(seat.dataset.side, seat.dataset.position);
        });
    }

    dialog.querySelector('[data-close-group-dialog]').addEventListener('click', (event) => {
        event.preventDefault();
        dialog.close();
    });

    if (dialog.open) {
        dialog.close();
        selectSeat(sideInput.value, positionInput.value);
    }
})();
