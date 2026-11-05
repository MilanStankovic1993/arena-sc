(() => {
    const tour = document.getElementById('home-hero-tour');
    const padel = document.getElementById('home-hero-padel');
    const control = document.querySelector('.home-hero__video-control');
    if (!tour || !padel || !control) return;

    const videos = [tour, padel];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const supportsHdr = tour.canPlayType('video/mp4; codecs="hvc1.2.4.L120.B0"');
    const preloadLeadSeconds = 12;
    const returnLeadSeconds = 1.5;
    let current = tour;
    let requested = tour;
    let playing = false;
    let tourPrimed = false;
    let tourWarmup = null;

    function prepare(video) {
        if (video.hasAttribute('src')) return;

        video.preload = 'auto';
        video.src = video === tour && supportsHdr ? tour.dataset.hdrSrc : video.dataset.src;
    }

    function pause() {
        playing = false;
        control.textContent = 'Pokreni video';
        videos.forEach((video) => video.pause());
    }

    function show(video) {
        video.classList.add('is-current');
        if (current === video) return;

        current.classList.remove('is-current');
        current.currentTime = 0;
        current = video;
        tourPrimed = false;
    }

    function primeTour() {
        if (tourPrimed || tourWarmup || tour.error) return;

        // Start the decoder, then hold the first frame until padel finishes.
        tourWarmup = tour.play().then(() => {
            if (playing && requested === padel) {
                tour.pause();
                tourPrimed = true;
            }
        }).catch(() => {}).finally(() => {
            tourWarmup = null;
        });
    }

    function play(video) {
        prepare(video);
        if (video.error) video.load();
        requested = video;
        playing = true;
        control.textContent = 'Pauziraj video';

        if (video === tour && tourPrimed && !tour.seeking && tour.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            show(tour);
        }

        const source = video.getAttribute('src');
        video.play().catch((error) => {
            if (requested === video && video.getAttribute('src') === source && error.name !== 'AbortError') {
                pause();
            }
        });
    }

    for (const [video, next] of [[tour, padel], [padel, tour]]) {
        video.addEventListener('timeupdate', () => {
            if (!playing || video !== current) return;

            const remaining = video.duration - video.currentTime;
            if (remaining <= preloadLeadSeconds) prepare(next);
            if (video === padel && remaining <= returnLeadSeconds) primeTour();
        });

        video.addEventListener('ended', () => {
            if (video === current) {
                requested = next;
                if (playing) play(next);
            }
        });

        video.addEventListener('playing', () => {
            if (playing && requested === video) show(video);
        });

        video.addEventListener('error', () => {
            if (video === tour && video.getAttribute('src') === tour.dataset.hdrSrc) {
                tourPrimed = false;
                video.src = tour.dataset.src;
                if (playing && requested === video) play(video);
            } else if (playing && requested === video) {
                pause();
            }
        });
    }

    control.addEventListener('click', () => {
        if (playing) pause();
        else play(requested);
    });
    reducedMotion.addEventListener('change', ({ matches }) => {
        if (matches) {
            pause();
            current.classList.remove('is-current');
        }
    });

    control.hidden = false;
    if (!reducedMotion.matches && !navigator.connection?.saveData) play(tour);
})();
