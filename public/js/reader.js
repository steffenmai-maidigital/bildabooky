/* BilDaBooky — Leseansicht: blättern per Knopf, Pfeiltasten oder Wischen

   BilDaBooky — reader view: turn pages by button, arrow keys or swiping */
(function () {
    'use strict';
    const BDB = window.BDB;
    const book = window.BOOK;
    if (!book) return;

    const stage = document.getElementById('reader-stage');
    const counter = document.getElementById('reader-counter');
    const back = document.getElementById('reader-prev');
    const next = document.getElementById('reader-next');
    const h = BDB.height(book.format);
    let nr = Math.max(0, Math.min(book.pages.length - 1, (parseInt(location.hash.slice(1), 10) || 1) - 1));

    function show(direction) {
        BDB.stopAudio();
        const spaceW = stage.clientWidth - 16;
        const spaceH = stage.clientHeight - 16;
        const width = Math.max(120, Math.min(spaceW, spaceH * BDB.WIDTH / h));
        const frame = document.createElement('div');
        frame.className = 'reader-page' + (direction ? ' enter-' + direction : '');
        BDB.page(frame, book.pages[nr], book.format, { width: Math.floor(width), play: true });
        stage.textContent = '';
        stage.appendChild(frame);
        counter.textContent = (nr + 1) + ' / ' + book.pages.length;
        back.disabled = nr === 0;
        next.disabled = nr === book.pages.length - 1;
        if (!book.standalone) history.replaceState(null, '', '#' + (nr + 1));

        // Fuchs-Helfer (falls da): Ton auf der Seite, Ende des Buchs
        // Fox helper (if present): sound on the page, end of the book
        if (window.FoxEvents) {
            if (nr === book.pages.length - 1 && book.pages.length > 1) window.FoxEvents.event('end');
            else if ((book.pages[nr].elements || []).some((e) => e.type === 'audio')) window.FoxEvents.event('page_audio');
        }
    }

    function turn(d) {
        const target = nr + d;
        if (target < 0 || target >= book.pages.length) return;
        nr = target;
        show(d > 0 ? 'right' : 'left');
    }

    back.addEventListener('click', () => turn(-1));
    next.addEventListener('click', () => turn(1));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); turn(1); }
        if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); turn(-1); }
    });

    let start = null;
    stage.addEventListener('pointerdown', (e) => { start = { x: e.clientX, y: e.clientY, t: Date.now() }; });
    stage.addEventListener('pointerup', (e) => {
        if (!start) return;
        const dx = e.clientX - start.x, dy = e.clientY - start.y;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5 && Date.now() - start.t < 800) turn(dx < 0 ? 1 : -1);
        start = null;
    });

    const full = document.getElementById('reader-fullscreen');
    if (full) {
        if (!document.fullscreenEnabled) full.hidden = true;
        full.addEventListener('click', () => {
            if (document.fullscreenElement) document.exitFullscreen();
            else document.documentElement.requestFullscreen().catch(() => {});
        });
    }

    let t;
    window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(() => show(), 120); });
    show();
})();
