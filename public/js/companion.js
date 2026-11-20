/*
 * Cara, die Begleiterin (Figur und Ton: docs/CARA.md). Sie schläft, bis man
 * sie antippt: Kein Text erscheint nach Zeit. Von sich aus spricht sie nur
 * als direkte Antwort auf eine Handlung – entweder steht die Antwort schon
 * in den Daten (reply: ein Satz nach dem Speichern oder zu einer
 * Fehlermeldung) oder sie kommt aus companion-book.js (Aktionen im Buch).
 * Antippen weckt sie: das erste Mal auf einer Seite das Passende zur Seite
 * oder die Begrüßung (first), danach der nächste Tipp. Nach dem Schließen der
 * Blase und kurzer Ruhe rollt sie sich wieder ein. „Schlafen legen“ merkt
 * sich der Browser: Dann reagiert sie auf nichts mehr, bis man sie antippt.
 * Verschieben per Ziehen oder mit den Pfeiltasten; die Lage merkt sich der
 * Browser. Die einzigen Zeitgeber blenden die Blase aus oder legen sie
 * schlafen – keiner lässt sie sprechen.
 *
 * Cara, the companion (character and tone: docs/CARA.md). She sleeps until
 * she is tapped: no text appears after a time. She only speaks on her own in
 * direct reply to an action – either the reply is already in the data
 * (reply: one line after saving or about an error message) or it comes from
 * companion-book.js (actions inside a book). A tap wakes her: the first time
 * on a page what fits the page or the greeting (first), then the next tip.
 * After the bubble is closed and a short rest she curls up again. The browser
 * remembers “Schlafen legen” (put to sleep): then she reacts to nothing until
 * she is tapped. Move her by dragging or with the arrow keys; the browser
 * remembers the position. The only timers hide the bubble or put her back to
 * sleep – none of them makes her speak.
 */
(function () {
    'use strict';
    const root = document.getElementById('companion');
    if (!root) return;
    const P = (root.dataset.prefix || 'bdb-companion') + '-';
    const d = JSON.parse(root.dataset.companion);
    const figure = root.querySelector('.companion-figure');
    const bubble = root.querySelector('.companion-bubble');
    const text = root.querySelector('.companion-text');

    const store = {
        get(k) { try { return localStorage.getItem(P + k); } catch (e) { return null; } },
        set(k, v) { try { localStorage.setItem(P + k, v); } catch (e) { /* egal / ignore */ } },
        remove(k) { try { localStorage.removeItem(P + k); } catch (e) { /* egal / ignore */ } },
    };

    // Schlüssel des Fuchs-Helfers (bdb-fox-*, davor bdb-fuchs-*): Schlaf und Lage einmal
    // übernehmen, alles andere (z. B. „zuletzt gesprochen“) löschen – auch im sessionStorage
    // Keys of the fox helper (bdb-fox-*, before that bdb-fuchs-*): take over sleep and position
    // once, delete everything else (e.g. "last spoken") – also in sessionStorage
    try {
        const take = { sleep: ['bdb-fox-sleep', 'bdb-fuchs-tiefschlaf'], position: ['bdb-fox-position', 'bdb-fuchs-lage'] };
        Object.keys(take).forEach((k) => take[k].forEach((old) => {
            const v = localStorage.getItem(old);
            if (v !== null && store.get(k) === null) store.set(k, v);
        }));
        [localStorage, sessionStorage].forEach((st) => {
            for (let i = st.length - 1; i >= 0; i--) {
                const k = st.key(i);
                if (k && (k.indexOf('bdb-fox-') === 0 || k.indexOf('bdb-fuchs-') === 0)) st.removeItem(k);
            }
        });
    } catch (e) { /* ohne Speicher (privates Fenster) / without storage (private window) */ }

    // Lage je Ansicht: Seiten, Editor, Leseansicht / position per view: pages, editor, reader
    const POSITION = d.mode === 'editor' || d.mode === 'reading' ? 'position-' + d.mode : 'position';
    const deepAsleep = () => store.get('sleep') === '1';

    // ---- Haltung: eingerollt oder wach / posture: curled up or awake ----
    let restTimer = null;
    function label() {
        figure.setAttribute('aria-label', root.classList.contains('asleep') ? d.texts.asleep : d.texts.awake);
    }
    function wake() {
        clearTimeout(restTimer);
        root.classList.remove('asleep');
        label();
    }
    function curl() {
        clearTimeout(restTimer);
        root.classList.add('asleep');
        label();
    }
    // nach dem Schließen kurz sitzen bleiben, dann einrollen / after closing sit for a moment, then curl up
    function restSoon() {
        clearTimeout(restTimer);
        restTimer = setTimeout(() => { if (bubble.hidden && !drag) curl(); }, 4000);
    }

    // ---- Blase / bubble ----
    let hideTimer = null, holdFor = 0, holding = false;
    function hold(ms) {
        holdFor = ms;
        clearTimeout(hideTimer);
        if (!holding) hideTimer = setTimeout(hush, ms);
    }
    function say(t, ms) {
        if (!t) return;
        const wasHidden = bubble.hidden;
        wake();
        bubble.hidden = false;
        text.textContent = t;
        // Bildschirmleser lesen eine eben sichtbar gewordene Statuszeile nicht immer vor:
        // den Text kurz danach noch einmal setzen (derselbe Text, kein neuer Satz)
        // Screen readers do not always read a status line that just became visible:
        // set the text once more right after (the same text, no new line)
        if (wasHidden) setTimeout(() => { if (!bubble.hidden && text.textContent === t) { text.textContent = ''; text.textContent = t; } }, 60);
        hold(ms || 20000);
    }
    function hush() {
        clearTimeout(hideTimer);
        const hadFocus = bubble.contains(document.activeElement);
        bubble.hidden = true;
        if (hadFocus) figure.focus();
        restSoon();
    }
    // Zeigt oder tippt jemand in die Blase, bleibt sie stehen / pointing at or using the bubble keeps it open
    ['pointerenter', 'focusin'].forEach((type) => bubble.addEventListener(type, () => { holding = true; clearTimeout(hideTimer); }));
    ['pointerleave', 'focusout'].forEach((type) => bubble.addEventListener(type, (e) => {
        if (e.relatedTarget && bubble.contains(e.relatedTarget)) return;
        holding = false;
        if (!bubble.hidden) hold(Math.max(6000, holdFor / 2));
    }));

    // ---- Tipps: erst das Passende zur Seite, dann gemischt / tips: first what fits the page, then shuffled ----
    const tips = (d.tips || []).slice();
    let queue = [], firstDone = false;
    const shuffle = (a) => a.map((x) => [Math.random(), x]).sort((x, y) => x[0] - y[0]).map((x) => x[1]);
    function nextTip() {
        if (!queue.length) queue = shuffle(tips);
        return queue.shift();
    }

    // ---- Verschieben / moving ----
    // Bereich: das Fenster (position: fixed) oder der Rahmen (position: absolute, Editor)
    // area: the window (position: fixed) or the frame (position: absolute, editor)
    function area() {
        if (getComputedStyle(root).position === 'fixed' || !root.offsetParent) {
            return { left: 0, top: 0, width: window.innerWidth, height: window.innerHeight };
        }
        return root.offsetParent.getBoundingClientRect();
    }
    // l, t in Fensterkoordinaten / l, t in viewport coordinates
    function place(l, t) {
        const a = area(), w = root.offsetWidth, h = root.offsetHeight;
        l = Math.max(a.left + 4, Math.min(a.left + a.width - w - 4, l));
        t = Math.max(a.top + 4, Math.min(a.top + a.height - h - 4, t));
        root.style.left = (l - a.left) + 'px';
        root.style.top = (t - a.top) + 'px';
        root.style.right = 'auto';
        root.style.bottom = 'auto';
        // die Blase klappt zur freien Seite / the bubble flips to the free side
        root.classList.toggle('at-left', l + w / 2 < a.left + a.width / 2);
        root.classList.toggle('at-top', t - a.top < 200);
    }
    function savePosition() {
        const a = area(), r = root.getBoundingClientRect();
        store.set(POSITION, JSON.stringify([
            (r.left - a.left) / Math.max(1, a.width - r.width),
            (r.top - a.top) / Math.max(1, a.height - r.height),
        ]));
    }
    function loadPosition() {
        try {
            const p = JSON.parse(store.get(POSITION) || 'null');
            if (!Array.isArray(p) || p.length !== 2) return;
            const a = area(), w = root.offsetWidth || 96, h = root.offsetHeight || 122;
            place(a.left + p[0] * (a.width - w), a.top + p[1] * (a.height - h));
        } catch (e) { /* egal / ignore */ }
    }
    // Erst ab 6 Pixeln Bewegung ist es Ziehen, kürzer ein Antippen
    // Only 6 pixels of movement or more count as dragging, less is a tap
    let drag = null, afterDrag = false;
    figure.addEventListener('pointerdown', (e) => {
        if (e.button) return;
        const r = root.getBoundingClientRect();
        drag = { id: e.pointerId, x: e.clientX, y: e.clientY, l: r.left, t: r.top, moved: false };
    });
    window.addEventListener('pointermove', (e) => {
        if (!drag || e.pointerId !== drag.id) return;
        const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
        if (!drag.moved && Math.hypot(dx, dy) < 6) return;
        if (!drag.moved) {
            drag.moved = true;
            root.classList.add('dragging');
            try { figure.setPointerCapture(e.pointerId); } catch (x) { /* egal / ignore */ }
        }
        place(drag.l + dx, drag.t + dy);
        e.preventDefault();
    }, { passive: false });
    const release = (e) => {
        if (!drag || e.pointerId !== drag.id) return;
        if (drag.moved) {
            root.classList.remove('dragging');
            savePosition();
            afterDrag = true;
            if (bubble.hidden) restSoon();
        }
        drag = null;
    };
    window.addEventListener('pointerup', release);
    window.addEventListener('pointercancel', release);
    window.addEventListener('resize', loadPosition);

    // ---- Antippen / tapping ----
    figure.addEventListener('click', (e) => {
        // nach dem Ziehen kein Antippen hinterher / no tap after dragging
        if (afterDrag) { afterDrag = false; e.preventDefault(); return; }
        if (deepAsleep()) {
            store.remove('sleep');
            root.classList.remove('deep-sleep');
        }
        if (!firstDone) { firstDone = true; say(d.first); } else say(nextTip());
    });
    root.querySelector('.companion-next').addEventListener('click', () => say(nextTip()));
    root.querySelector('.companion-close').addEventListener('click', hush);
    root.querySelector('.companion-sleep').addEventListener('click', () => {
        store.set('sleep', '1');
        root.classList.add('deep-sleep');
        hush();
        curl();
    });

    // ---- Tastatur / keyboard ----
    // Pfeiltasten verschieben, Enter und Leertaste wecken (der Knopf selbst), Escape schließt.
    // Die Tasten gehen nicht weiter an die Seite (Leseansicht: Leertaste blättert, Editor: Pfeile schieben).
    // Arrow keys move, Enter and space wake (the button itself), Escape closes.
    // The keys do not travel on to the page (reader: space turns pages, editor: arrows move parts).
    figure.addEventListener('keydown', (e) => {
        const step = { ArrowLeft: [-24, 0], ArrowRight: [24, 0], ArrowUp: [0, -24], ArrowDown: [0, 24] }[e.key];
        if (!step) return;
        e.preventDefault();
        const r = root.getBoundingClientRect();
        place(r.left + step[0], r.top + step[1]);
        savePosition();
    });
    root.addEventListener('keydown', (e) => {
        if ([' ', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Delete', 'Backspace'].includes(e.key)) e.stopPropagation();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !bubble.hidden) hush(); });
    // Wer etwas eintippt (Anmeldung, Formular …), bekommt nichts verdeckt
    // Anyone typing (sign-in, form …) gets nothing covered up
    const isField = (e) => e.target.closest && !root.contains(e.target) && e.target.closest('input, textarea, select, [contenteditable]');
    ['focusin', 'input'].forEach((type) => document.addEventListener(type, (e) => { if (!bubble.hidden && isField(e)) hush(); }));

    // ---- Schnittstelle für companion-book.js / interface for companion-book.js ----
    window.Companion = {
        // Antwort auf eine Handlung; schläft sie tief, sagt sie nichts (false)
        // reply to an action; if she is put to sleep she says nothing (false)
        respond(t, ms) {
            if (!t || root.hidden || deepAsleep()) return false;
            say(t, ms || 9000);
            return true;
        },
        // weiterer Tipp in der Liste (nur nach Antippen) / another tip in the list (only after a tap)
        addTip(t) {
            if (!t) return;
            tips.push(t);
            queue = [];
        },
    };

    // ---- Start: schlafend, still / start: asleep, quiet ----
    root.hidden = false;
    if (deepAsleep()) root.classList.add('deep-sleep');
    label();
    loadPosition();
    // Antwort auf die eben abgeschickte Handlung (Lob oder Fehlersatz) – nie nach Zeit
    // Reply to the action just submitted (praise or error line) – never after a time
    if (d.reply && !deepAsleep()) say(d.reply, 7000);
})();
