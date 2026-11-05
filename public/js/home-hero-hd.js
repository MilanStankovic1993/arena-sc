(() => {
    const video = document.getElementById('home-hero-video');
    const control = document.querySelector('.home-hero__video-control');
    if (!video || !control) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const { tourHdr, tour, padel } = video.dataset;
    const supportsHdrClip = video.canPlayType('video/mp4; codecs="hvc1.2.4.L120.B0"');
    const clips = [supportsHdrClip ? tourHdr : tour, padel];
    let currentClip = 0;

    function play() {
        video.hidden = false;
        video.muted = true;
        if (video.getAttribute('src') !== clips[currentClip]) video.src = clips[currentClip];
        video.play().catch((error) => {
            if (error.name !== 'AbortError' && video.paused) {
                control.textContent = 'Pokreni video';
            }
        });
    }

    video.addEventListener('playing', () => {
        control.textContent = 'Pauziraj video';
    });
    video.addEventListener('pause', () => {
        control.textContent = 'Pokreni video';
    });
    video.addEventListener('ended', () => {
        currentClip = (currentClip + 1) % clips.length;
        play();
    });
    video.addEventListener('error', () => {
        if (currentClip === 0 && clips[0] === tourHdr) {
            clips[0] = tour;
            play();
            return;
        }
        video.hidden = true;
        control.hidden = true;
    });
    control.addEventListener('click', () => {
        if (video.paused) play();
        else video.pause();
    });
    reducedMotion.addEventListener('change', ({ matches }) => {
        if (matches) {
            video.pause();
            video.hidden = true;
        }
    });

    control.hidden = false;
    if (!reducedMotion.matches && !navigator.connection?.saveData) play();
})();
