(() => {
    const video = document.getElementById('home-hero-video');
    const control = document.querySelector('.home-hero__video-control');
    if (!video || !control) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function play() {
        video.hidden = false;
        video.muted = true;
        if (!video.hasAttribute('src')) video.src = video.dataset.src;
        video.play().catch(() => {
            control.textContent = 'Pokreni video';
        });
    }

    video.addEventListener('playing', () => {
        control.textContent = 'Pauziraj video';
    });
    video.addEventListener('pause', () => {
        control.textContent = 'Pokreni video';
    });
    video.addEventListener('error', () => {
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
