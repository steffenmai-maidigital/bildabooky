/*
 * BilDaBooky — Cara im Buch (Editor und Leseansicht). Ergänzt companion.js um
 * Antworten auf Aktionen: window.CompanionEvents.event(key) – aufgerufen von
 * editor.js und reader.js, wenn jemand etwas tut (Text gewählt, Foto, neue
 * Seite, abgegeben, letzte Seite …). Jeder Hinweis kommt je Sitzung und Buch
 * nur einmal (außer „viele Seiten“ je Zahl), mit mindestens 25 s Abstand
 * (beim Lesen 8 s). Schläft Cara tief („Schlafen legen“), sagt sie nichts.
 * Nichts kommt nach Zeit; der Erklär-Hinweis ist ein gewöhnlicher Tipp.
 *
 * BilDaBooky — Cara inside a book (editor and reader). Adds replies to actions
 * to companion.js: window.CompanionEvents.event(key) – called by editor.js and
 * reader.js when someone does something (text selected, photo, new page,
 * handed in, last page …). Each hint comes only once per session and book
 * (except "many pages" per number), at least 25 s apart (8 s while reading).
 * If Cara is put to sleep she says nothing. Nothing comes after a time; the
 * explanation hint is an ordinary tip.
 */
(function () {
    'use strict';
    const root = document.getElementById('companion');
    if (!root || !window.Companion) return;
    const d = JSON.parse(root.dataset.companion);
    const E = d.events;
    if (!E) return;
    const P = (root.dataset.prefix || 'bdb-companion') + '-';
    // beim Lesen blättert man schnell — dort kürzerer Abstand zwischen Hinweisen
    // when reading, pages are turned quickly — so a shorter gap between hints there
    const GAP = d.mode === 'reading' ? 8000 : 25000;
    const session = {
        get(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* egal / ignore */ } },
    };

    // Erklär-Hinweis passend zum Gerät als Tipp in die Liste (kommt nur nach Antippen)
    // Explanation hint suited to the device as a tip in the list (only after a tap)
    const MOUSE = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    window.Companion.addTip(E[MOUSE ? 'explain_mouse' : 'explain_touch']);

    let lastHint = 0;
    window.CompanionEvents = {
        event(key, values, repeatable) {
            let t = E[key];
            if (!t || key.indexOf('explain_') === 0) return;
            const mark = P + d.book + '-' + key + (repeatable && values ? '-' + Object.values(values).join('-') : '');
            if (session.get(mark) || Date.now() - lastHint < GAP) return;
            if (values) Object.keys(values).forEach((k) => { t = t.split(':' + k).join(values[k]); });
            if (!window.Companion.respond(t, 9000)) return;
            session.set(mark, '1');
            lastHint = Date.now();
        },
    };
})();
