/*
 * BilDaBooky — Darstellung einer Buchseite.
 * Wird von Editor, Leser, Druckansicht und der Datei zum Mitnehmen benutzt.
 *
 * Eine Seite ist immer 1000 Einheiten breit; die Höhe hängt vom Format ab.
 * Alles wird in diesen Einheiten gespeichert und beim Anzeigen skaliert.
 * Inhalte gelangen nur per textContent bzw. Style-Eigenschaften ins DOM —
 * nie als HTML.
 *
 * BilDaBooky — rendering of a book page.
 * Used by the editor, reader, print view and the take-away file.
 *
 * A page is always 1000 units wide; the height depends on the format.
 * Everything is stored in these units and scaled for display.
 * Content reaches the DOM only via textContent or style properties —
 * never as HTML.
 */
(function () {
    'use strict';
    const BDB = window.BDB = window.BDB || {};

    BDB.WIDTH = 1000;
    BDB.FORMATS = { portrait: 1414, square: 1000, landscape: 707 };
    // Werte vor Standard 10 (z. B. aus einem noch offenen Editor) / values from before standard 10 (e.g. from an editor still open)
    BDB.LEGACY = { hoch: 'portrait', quadrat: 'square', quer: 'landscape', rund: 'rounded', dick: 'heavy', rechteck: 'rectangle', kreis: 'circle' };
    BDB.height = (format) => BDB.FORMATS[BDB.LEGACY[format] || format] || BDB.FORMATS.portrait;
    BDB.base = BDB.base || '';

    // Übersetzung: deutsche Texte sind die Schlüssel; fehlt eine Übersetzung, bleibt Deutsch
    // Translation: German strings are the keys; if a translation is missing, German stays
    BDB.texts = window.BDB_TEXTS || {};
    BDB.t = (s, values) => {
        let r = BDB.texts[s] || s;
        if (values) Object.keys(values).forEach((k) => { r = r.split(':' + k).join(values[k]); });
        return r;
    };
    // kurzer Name, damit die Übersetzungsprüfung die Schlüssel findet / short name so that the translation check finds the keys
    const t = BDB.t;
    /**
     * Gespeicherter Wert im Browser: neuer Schlüssel, sonst den alten (vor Standard 10)
     * einmal übernehmen und löschen. Ohne Speicher (privates Fenster) null.
     *
     * Value stored in the browser: the new key, otherwise take over the old one
     * (before standard 10) once and delete it. Without storage (private window) null.
     */
    BDB.stored = (key, oldKey) => {
        try {
            let v = localStorage.getItem(key);
            if (v === null && oldKey && localStorage.getItem(oldKey) !== null) {
                v = localStorage.getItem(oldKey);
                localStorage.setItem(key, v);
                localStorage.removeItem(oldKey);
            }
            return v;
        } catch (e) { return null; }
    };
    BDB.store = (key, value) => { try { localStorage.setItem(key, value); } catch (e) { /* egal / ignore */ } };

    BDB.media = BDB.media || null;   // { id: dataUri } in der Datei zum Mitnehmen / in the take-away file
    BDB.mediaUrl = (id) => (BDB.media ? (BDB.media[id] || '') : BDB.base + '/media/' + id);

    // Sticker als Twemoji-Grafik, damit sie auf jedem Gerät gleich aussehen.
    // Dateiname wie bei Twemoji: Codepunkte in hex, FE0F nur in ZWJ-Folgen.
    // Stickers as Twemoji images so they look the same on every device.
    // File name as in Twemoji: code points in hex, FE0F only in ZWJ sequences.
    BDB.emoji = BDB.emoji || null;     // { code: dataUri } in der Datei zum Mitnehmen / in the take-away file
    BDB.emojiCode = (character) => {
        const cps = Array.from(character, (z) => z.codePointAt(0).toString(16));
        return (cps.includes('200d') ? cps : cps.filter((c) => c !== 'fe0f')).join('-');
    };
    BDB.emojiUrl = (character) => {
        const code = BDB.emojiCode(character);
        return BDB.emoji ? (BDB.emoji[code] || '') : BDB.base + '/img/emoji/' + code + '.svg';
    };
    /**
     * Grafik für ein Emoji; fehlt sie, bleibt das Schriftzeichen stehen.
     *
     * Image for an emoji; if it is missing, the character stays.
     */
    BDB.emojiImage = (character, target) => {
        const url = BDB.emojiUrl(character);
        // eigener Platzhalter: der Editor hängt Anfasser an dasselbe Element,
        // die dürfen beim Austausch nicht verschwinden
        // own placeholder: the editor attaches handles to the same element,
        // they must not disappear when it is swapped
        const slot = document.createElement('span');
        slot.className = 'emoji-image';
        slot.textContent = character;
        target.appendChild(slot);
        if (!url) return;
        // bis die Grafik da ist, bleibt der Platz leer — sonst blitzt kurz das Geräte-Emoji auf
        // until the image is there, the space stays empty — otherwise the device emoji flashes briefly
        slot.style.visibility = 'hidden';
        const img = document.createElement('img');
        img.alt = character;
        img.className = 'emoji-image';
        img.draggable = false;
        img.decoding = 'async';
        img.addEventListener('load', () => { if (slot.parentNode) slot.replaceWith(img); });
        img.addEventListener('error', () => { slot.style.visibility = ''; });
        img.src = url;
    };

    // Tonknopf: immer ein Lautsprecher; beim Abspielen pulsiert er und die Wellen laufen
    // Sound button: always a speaker; while playing it pulses and the waves run
    const SVG_AUDIO = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 9.2h3.7l5-4.2v14l-5-4.2H3.5z" fill="currentColor" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>'
        + '<path class="bdb-wave bdb-wave1" d="M15.5 9a4.2 4.2 0 0 1 0 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
        + '<path class="bdb-wave bdb-wave2" d="M18.3 6.2a8.2 8.2 0 0 1 0 11.6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

    function geometry(d, el) {
        d.style.left = el.x + 'px';
        d.style.top = el.y + 'px';
        d.style.width = el.w + 'px';
        d.style.height = el.h + 'px';
        d.style.transform = el.r ? 'rotate(' + el.r + 'deg)' : '';
        // der Lautsprecher im runden Tonknopf bleibt immer aufrecht
        // the speaker in the round sound button always stays upright
        if (el.type === 'audio') d.style.setProperty('--counter-rotate', (-(el.r || 0)) + 'deg');
    }
    BDB.geometry = geometry;

    function textStyle(content, el) {
        content.className = 'bdb-content bdb-f-' + (BDB.LEGACY[el.font] || el.font || 'rounded');
        content.style.fontSize = (el.size || 48) + 'px';
        content.style.color = el.color || '#222';
        content.style.fontWeight = el.bold ? '800' : '';
        content.style.fontStyle = el.italic ? 'italic' : '';
        content.style.textAlign = el.align || 'left';
        content.style.background = el.bg || '';
    }
    BDB.textStyle = textStyle;

    let playingAudio = null;
    function playAudio(node, el) {
        if (playingAudio) {
            const was = playingAudio;
            was.audio.pause();
            was.node.classList.remove('playing');
            was.node.querySelector('button').setAttribute('aria-label', t('Abspielen'));
            playingAudio = null;
            if (was.node === node) return;
        }
        const audio = new Audio(BDB.mediaUrl(el.media));
        const end = () => {
            node.classList.remove('playing');
            node.querySelector('button').setAttribute('aria-label', t('Abspielen'));
            if (playingAudio && playingAudio.audio === audio) playingAudio = null;
        };
        audio.addEventListener('ended', end);
        audio.addEventListener('error', end);
        audio.play().catch(end);
        node.classList.add('playing');
        node.querySelector('button').setAttribute('aria-label', t('Anhalten'));
        playingAudio = { audio, node };
    }
    BDB.playAudio = playAudio;
    BDB.stopAudio = () => { if (playingAudio) playAudio(playingAudio.node, null); };

    /**
     * Ein Element als DOM-Knoten. opts.play: Tonknöpfe reagieren auf Klick.
     *
     * An element as a DOM node. opts.play: sound buttons react to clicks.
     */
    BDB.element = function (el, opts) {
        opts = opts || {};
        const d = document.createElement('div');
        d.className = 'bdb-el bdb-' + el.type;
        d.dataset.id = el.id;
        geometry(d, el);

        if (el.type === 'text') {
            const content = document.createElement('div');
            textStyle(content, el);
            content.textContent = el.text || '';
            if (el.bg) d.classList.add('with-bg');
            d.appendChild(content);
        } else if (el.type === 'image') {
            const img = document.createElement('img');
            img.alt = '';
            img.decoding = 'async';
            img.src = BDB.mediaUrl(el.media);
            if (el.flip) d.classList.add('flipped');
            d.appendChild(img);
        } else if (el.type === 'sticker') {
            BDB.emojiImage(el.char, d);
            d.style.fontSize = (Math.min(el.w, el.h) * 0.86) + 'px';
        } else if (el.type === 'shape') {
            const f = document.createElement('div');
            f.className = 'bdb-shape-fill';
            f.style.background = el.fill || '#ffd166';
            const shape = BDB.LEGACY[el.shape] || el.shape;
            f.style.borderRadius = shape === 'circle' ? '50%' : shape === 'rounded' ? '40px' : '0';
            d.appendChild(f);
        } else if (el.type === 'audio') {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'bdb-audio-button';
            b.setAttribute('aria-label', t('Abspielen'));
            b.style.background = el.color || '#ff7a3d';
            b.innerHTML = SVG_AUDIO;
            if (opts.play) {
                b.addEventListener('click', (e) => { e.stopPropagation(); playAudio(d, el); });
            } else {
                b.tabIndex = -1;
            }
            d.appendChild(b);
        }
        // Beim Lesen nimmt nur der Tonknopf Klicks an — sonst verdeckt ein
        // darüberliegender Sticker oder Text ihn mit seinem leeren Rand
        // When reading, only the sound button accepts clicks — otherwise a
        // sticker or text lying above it covers it with its empty margin
        if (opts.play && el.type !== 'audio') d.style.pointerEvents = 'none';
        return d;
    };

    /**
     * Zeichnet eine Seite in einen Rahmen. Der Rahmen bekommt die gewünschte
     * Breite von außen (CSS) — oder opts.width in Pixeln. Gibt das innere
     * Seitenelement (1000 Einheiten breit) zurück.
     *
     * Draws a page into a frame. The frame gets the desired
     * width from outside (CSS) — or opts.width in pixels. Returns the inner
     * page element (1000 units wide).
     */
    BDB.page = function (frame, page, format, opts) {
        opts = opts || {};
        const h = BDB.height(format);
        frame.classList.add('bdb-frame');
        frame.textContent = '';
        const inner = document.createElement('div');
        // no-symbols: Sticker der Buchseite bleiben, wie sie sind (symbols.js) / stickers on the book page stay as they are (symbols.js)
        inner.className = 'bdb-page no-symbols';
        inner.style.height = h + 'px';
        inner.style.background = page.background || '#fff';
        (page.elements || []).forEach((el) => inner.appendChild(BDB.element(el, opts)));
        frame.appendChild(inner);

        const rescale = () => {
            const b = opts.width || frame.clientWidth || BDB.WIDTH;
            const s = b / BDB.WIDTH;
            if (opts.width) frame.style.width = b + 'px';
            frame.style.height = (h * s) + 'px';
            inner.style.transform = 'scale(' + s + ')';
            inner.dataset.scale = s;
        };
        rescale();
        if (!opts.width && window.ResizeObserver) {
            if (frame._bdbObserver) frame._bdbObserver.disconnect();
            frame._bdbObserver = new ResizeObserver(rescale);
            frame._bdbObserver.observe(frame);
        }
        inner._rescale = rescale;
        return inner;
    };

    /** Vorschaubild: passt die Seite in eine quadratische Box / Preview image: fits the page into a square box */
    BDB.preview = function (box, page, format) {
        const h = BDB.height(format);
        const size = box.clientWidth || 180;
        const width = h > BDB.WIDTH ? size * BDB.WIDTH / h : size;
        const frame = document.createElement('div');
        box.textContent = '';
        box.appendChild(frame);
        BDB.page(frame, page, format, { width: Math.round(width * 0.92) });
    };

    // Alle Vorschaubilder auf der Seite: <div data-bdb-preview='{"format":..,"page":..}'>
    // All preview images on the page: <div data-bdb-preview='{"format":..,"page":..}'>
    BDB.drawPreviews = function () {
        document.querySelectorAll('[data-bdb-preview]').forEach((box) => {
            try {
                const d = JSON.parse(box.dataset.bdbPreview);
                BDB.preview(box, d.page || { elements: [] }, d.format);
            } catch (e) { /* kaputte Vorschau überspringen / skip broken preview */ }
        });
        BDB.observePreviews();
    };

    // Vorschauen nachladen: <a data-preview-id="12" data-format="portrait"> — erst wenn die
    // Karte in die Nähe des sichtbaren Bereichs kommt, gebündelt über /previews
    // Load previews lazily: <a data-preview-id="12" data-format="portrait"> — only when the
    // card gets close to the visible area, batched via /previews
    const loaded = new Map();      // id → Daten / id → data
    const waiting = new Set();       // Ids, die im nächsten Bündel geholt werden / ids fetched in the next batch
    const requested = new Set();    // schon unterwegs / already on the way
    let batchTimer = null, observer = null;
    function drawFor(id) {
        const d = loaded.get(id);
        if (!d) return;
        document.querySelectorAll('[data-preview-id="' + id + '"]:not([data-drawn])').forEach((box) => {
            if (!box.offsetParent) return;   // (noch) unsichtbar, z. B. eingeklappt / (still) invisible, e.g. collapsed
            BDB.preview(box, d.page || { elements: [] }, d.format || box.dataset.format);
            box.dataset.drawn = '1';
        });
    }
    async function fetchBatch() {
        batchTimer = null;
        const ids = [...waiting].slice(0, 60);
        ids.forEach((id) => waiting.delete(id));
        if (!ids.length) return;
        // vorausschauend die nächsten aufgeklappten Karten mitnehmen (bis 40 je Abruf) — spart viele kleine Abrufe
        // look ahead and take the next expanded cards along (up to 40 per request) — saves many small requests
        for (const box of document.querySelectorAll('[data-preview-id]:not([data-drawn])')) {
            if (ids.length >= 40) break;
            const id = box.dataset.previewId;
            if (box.offsetParent && !loaded.has(id) && !requested.has(id) && !ids.includes(id)) ids.push(id);
        }
        ids.forEach((id) => requested.add(id));
        try {
            const r = await fetch(BDB.base + '/previews?ids=' + ids.join(','), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = r.ok ? await r.json() : {};
            ids.forEach((id) => { loaded.set(id, data[id] || { page: null }); drawFor(id); });
        } catch (e) { /* offline: Vorschau bleibt leer / offline: preview stays empty */ }
        if (waiting.size) batchTimer = setTimeout(fetchBatch, 30);
    }
    function request(id) {
        if (loaded.has(id)) { drawFor(id); return; }
        if (requested.has(id)) return;   // kommt mit dem laufenden Abruf / comes with the running request
        waiting.add(id);
        // kurz sammeln: beim Scrollen kommen viele Karten fast gleichzeitig in Sicht
        // collect briefly: when scrolling, many cards come into view almost at once
        if (!batchTimer) batchTimer = setTimeout(fetchBatch, 150);
    }
    BDB.observePreviews = function () {
        const boxes = document.querySelectorAll('[data-preview-id]:not([data-drawn])');
        if (!boxes.length) return;
        if (!('IntersectionObserver' in window)) { boxes.forEach((b) => request(b.dataset.previewId)); return; }
        if (!observer) {
            observer = new IntersectionObserver((entries) => entries.forEach((e) => {
                if (!e.isIntersecting) return;
                observer.unobserve(e.target);
                request(e.target.dataset.previewId);
            }), { rootMargin: '900px 0px' });
        }
        boxes.forEach((b) => { observer.unobserve(b); observer.observe(b); });
    };
})();
