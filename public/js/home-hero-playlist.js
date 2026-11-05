(() => {
    const tour = document.getElementById('home-hero-tour');
    const padel = document.getElementById('home-hero-padel');
    const control = document.querySelector('.home-hero__video-control');
    if (!tour || !padel || !control) return;

    const videos = [tour, padel];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const supportsHdr = tour.canPlayType('video/mp4; codecs="hvc1.2.4.L120.B0"');
    const preloadLeadSeconds = 12;
    let current = tour;
    let requested = tour;
    let playing = false;

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

    function play(video) {
        prepare(video);
        if (video.error) video.load();
        requested = video;
        playing = true;
        control.textContent = 'Pauziraj video';

        const source = video.getAttribute('src');
        video.play().catch((error) => {
            if (requested === video && video.getAttribute('src') === source && error.name !== 'AbortError') {
                pause();
            }
        });
    }

    for (const [video, next] of [[tour, padel], [padel, tour]]) {
        video.addEventListener('timeupdate', () => {
            if (playing && video === current && video.duration - video.currentTime <= preloadLeadSeconds) {
                prepare(next);
            }
        });

        video.addEventListener('ended', () => {
            if (video === current) {
                requested = next;
                if (playing) play(next);
            }
        });

        video.addEventListener('playing', () => {
            if (!playing || requested !== video) return;

            video.classList.add('is-current');
            if (current !== video) {
                current.classList.remove('is-current');
                current.currentTime = 0;
                current = video;
            }
        });

        video.addEventListener('error', () => {
            if (video === tour && video.getAttribute('src') === tour.dataset.hdrSrc) {
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
