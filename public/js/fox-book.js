/*
 * BilDaBooky — Rua im Buch (Editor und Leseansicht). Ergänzt den gemeinsamen
 * Baustein fox.js um Hinweise passend zum Geschehen: window.FoxEvents.event(key).
 * Jeder Hinweis kommt je Sitzung und Buch nur einmal (außer „viele Seiten“),
 * mit mindestens 25 s Abstand (beim Lesen 8 s). Die Begrüßung im Buch kommt
 * aus fox.js (Fox::data setzt sie auf den passenden Einstieg).
 *
 * BilDaBooky — Rua inside a book (editor and reader). Adds hints that fit what is
 * happening to the shared building block fox.js: window.FoxEvents.event(key).
 * Each hint comes only once per session and book (except "many pages"), at
 * least 25 s apart (8 s while reading). The greeting inside a book comes from
 * fox.js (Fox::data sets it to the fitting introduction).
 */
(function () {
    'use strict';
    const fox = document.getElementById('fox');
    if (!fox) return;
    const d = JSON.parse(fox.dataset.fox);
    const E = d.editor;
    if (!E) return;
    const bubble = fox.querySelector('.fox-bubble');
    const text = fox.querySelector('.fox-text');
    // beim Lesen blättert man schnell — dort kürzerer Abstand zwischen Hinweisen
    // when reading, pages are turned quickly — so a shorter gap between hints there
    const GAP = fox.classList.contains('on-reading') ? 8000 : 25000;
    const deepAsleep = () => fox.classList.contains('deep-sleep');
    const session = {
        get(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* egal / ignore */ } },
    };
    let lastHint = 0, timer = null;

    function hop() {
        fox.classList.remove('hopping');
        void fox.offsetWidth;   // Animation neu starten / restart the animation
        fox.classList.add('hopping');
    }

    function say(t, duration, values) {
        if (values) Object.keys(values).forEach((k) => { t = t.split(':' + k).join(values[k]); });
        text.textContent = t;
        bubble.hidden = false;
        fox.classList.remove('asleep');
        fox.classList.add('speaking');
        clearTimeout(timer);
        timer = setTimeout(() => { const c = fox.querySelector('.fox-close'); if (c && !bubble.hidden) c.click(); }, duration || 9000);
    }

    window.FoxEvents = {
        event(key, values, repeatable) {
            if (!E[key] || fox.hidden || deepAsleep()) return;
            const mark = (fox.dataset.prefix || 'bdb-fox') + '-' + d.book + '-' + key + (repeatable && values ? '-' + Object.values(values).join('-') : '');
            if (session.get(mark)) return;
            if (Date.now() - lastHint < GAP) return;
            session.set(mark, '1');
            lastHint = Date.now();
            hop();
            say(E[key], 9000, values);
        },
    };

    // nach 3 Minuten ohne Aktion eine Idee anbieten
    // after 3 minutes without action, offer an idea
    let quiet;
    const calm = () => { clearTimeout(quiet); quiet = setTimeout(() => window.FoxEvents.event('idle'), 180000); };
    ['pointerdown', 'keydown'].forEach((e) => window.addEventListener(e, calm, { passive: true }));
    calm();
})();
