import { Controller } from '@hotwired/stimulus';

const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
const smootherstep = (t) => t * t * t * (t * (t * 6 - 15) + 10);

/**
 * Hero strony głównej: sekwencja klatek rysowana na <canvas> i sterowana scrollem,
 * z czarnym intro, napisami pojawiającymi się wraz z przewijaniem oraz resztkowym
 * przyciemnieniem sceny.
 */
export default class extends Controller {
    static targets = [
        'track', 'canvas', 'scrim', 'vignette', 'caption',
        'veil', 'introBrand', 'introHint', 'bar',
    ];

    static values = {
        frameCount: { type: Number, default: 192 },
        framePath:  { type: String, default: '/frames/frame-%.jpg' }, // % → numer z zerami
        revealEnd:  { type: Number, default: 0.12 },                  // % scrolla, na którym podnosi się intro
        scrimLevel: { type: Number, default: 0.42 },                  // resztkowe przyciemnienie sceny
        introFade:  { type: Number, default: 0.06 },                  // % scrolla na zanik intro
    };

    connect() {
        this.ctx = this.canvasTarget.getContext('2d');
        this.images = [];
        this.loaded = 0;
        this.ready = false;
        this.currentIndex = -1;
        this.lastProgress = 0;

        this.onResize = this.resize.bind(this);
        this.frame = this.tick.bind(this);

        window.addEventListener('resize', this.onResize);
        this.resize();
        this.rafId = requestAnimationFrame(this.frame);
        this.preload();
    }

    disconnect() {
        cancelAnimationFrame(this.rafId);
        window.removeEventListener('resize', this.onResize);
    }

    // --- ładowanie klatek ---
    preload() {
        for (let i = 1; i <= this.frameCountValue; i++) {
            const image = new Image();
            image.onload = image.onerror = () => this.onImageSettled();
            image.src = this.framePathValue.replace('%', String(i).padStart(3, '0'));
            this.images.push(image);
        }
    }

    onImageSettled() {
        this.loaded += 1;
        this.barTarget.style.width = `${Math.round((this.loaded / this.frameCountValue) * 100)}%`;

        if (this.loaded === this.frameCountValue) {
            this.ready = true;
            this.barTarget.parentElement.style.display = 'none';
        }
    }

    // --- canvas ---
    resize() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        this.canvasTarget.width = Math.round(this.canvasTarget.clientWidth * dpr);
        this.canvasTarget.height = Math.round(this.canvasTarget.clientHeight * dpr);
        // telefon: klatki wypełniają cały ekran (cover); desktop: mieszczą się w całości (contain)
        this.cover = window.matchMedia('(max-width: 600px)').matches;
        this.currentIndex = -1;
        if (this.ready) this.draw(this.lastProgress);
    }

    draw(progress) {
        const last = this.frameCountValue - 1;
        const index = clamp(Math.round(progress * last), 0, last);
        if (index === this.currentIndex) return;

        const image = this.images[index];
        if (!image || !image.naturalWidth) {
            this.currentIndex = index;
            return;
        }
        this.currentIndex = index;

        const { width, height } = this.canvasTarget;
        const fit = this.cover ? Math.max : Math.min; // cover wypełnia kadr, contain mieści w całości
        const scale = fit(width / image.naturalWidth, height / image.naturalHeight);
        const w = image.naturalWidth * scale;
        const h = image.naturalHeight * scale;

        this.ctx.fillStyle = '#0c0c0c'; // letterbox w kolorze tła klatek
        this.ctx.fillRect(0, 0, width, height);
        this.ctx.drawImage(image, (width - w) / 2, (height - h) / 2, w, h);
    }

    // --- pętla renderu ---
    tick() {
        const pinned = this.trackTarget.offsetHeight - window.innerHeight;
        const full = this.trackTarget.offsetHeight;
        const scrolled = -this.trackTarget.getBoundingClientRect().top;

        const progress = pinned > 0 ? clamp(scrolled / pinned, 0, 1) : 0;       // napisy / czerń
        const frameProgress = full > 0 ? clamp(scrolled / full, 0, 1) : 0;      // klatki — aż hero zjedzie z ekranu

        this.lastProgress = frameProgress;
        this.draw(frameProgress);
        this.renderVeil(progress, scrolled, pinned);
        this.renderCaptions(progress);

        this.rafId = requestAnimationFrame(this.frame);
    }

    renderVeil(progress, scrolled, pinned) {
        let veil;
        let scrim;

        if (!this.ready) {
            veil = 1;
            scrim = 0;
        } else {
            const revealed = smootherstep(clamp(progress / this.revealEndValue, 0, 1));
            veil = scrolled > pinned ? 0 : 1 - revealed; // poza hero intro znika i nie zaciemnia katalogu
            scrim = this.scrimLevelValue * revealed;     // przyciemnienie sceny zostaje do końca hero
        }

        this.veilTarget.style.opacity = veil.toFixed(3);
        this.scrimTarget.style.opacity = scrim.toFixed(3);

        const intro = (1 - smootherstep(clamp(progress / this.introFadeValue, 0, 1))).toFixed(3);
        this.introBrandTarget.style.opacity = intro;
        this.introHintTarget.style.opacity = intro;
    }

    renderCaptions(progress) {
        let strongest = 0;

        for (const caption of this.captionTargets) {
            const { a, b, c, d } = caption.dataset;
            const fadeIn = smootherstep(clamp((progress - a) / (b - a), 0, 1));
            const fadeOut = smootherstep(clamp((progress - c) / (d - c), 0, 1));
            const opacity = fadeIn * (1 - fadeOut);

            caption.style.opacity = opacity.toFixed(3);
            caption.style.transform = `translateY(${((1 - fadeIn) * 18).toFixed(2)}px)`;
            strongest = Math.max(strongest, opacity);
        }

        this.vignetteTarget.style.opacity = (strongest * 0.85).toFixed(3);
    }
}
