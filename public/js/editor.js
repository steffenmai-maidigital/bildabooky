/*
 * BilDaBooky — Editor.
 * Seiten gestalten mit Text, Fotos, Kamera, Malen, Tonaufnahmen, Stickern
 * und Formen. Speichert automatisch (je Seite, verzögert, der Reihe nach).
 *
 * BilDaBooky — editor.
 * Design pages with text, photos, camera, painting, sound recordings, stickers
 * and shapes. Saves automatically (per page, delayed, one after another).
 */
(function () {
    'use strict';
    const BDB = window.BDB;
    const E = window.EDITOR;
    const t = BDB.t;
    const book = E.book;
    let format = book.format;

    const $ = (id) => document.getElementById(id);
    const frame = $('ed-page-frame');
    const stage = $('ed-stage');
    const pageBar = $('ed-page-bar');
    const props = $('ed-properties');
    const statusEl = $('ed-status');
    const undoBtn = $('ed-undo');
    const dialog = $('ed-dialog');
    const dialogContent = $('ed-dialog-content');

    const COLOURS = ['#26324a', '#ffffff', '#e63946', '#ff7a3d', '#ffd166', '#2a9d8f', '#3d7bff', '#8e5cf7', '#ff6fb5', '#7a4b2a', '#8d99ae', '#000000'];
    const BACKGROUNDS = ['#ffffff', '#fff6e5', '#ffe7da', '#ffe3ec', '#f3e8ff', '#e8f6ff', '#e3f6ef', '#eefbe9', '#fffbd6', '#f2ede4', '#26324a', '#111111'];
    // Sticker in Gruppen: [Name, Reiterbild, Liste]. Nur Emojis bis Unicode 12,
    // damit auch ältere Tablets sie kennen.
    // Stickers in groups: [name, tab image, list]. Only emojis up to Unicode 12,
    // so that older tablets know them too.
    // Werkzeugleiste: dieselben Twemoji-Grafiken wie die Sticker, auf jedem Gerät gleich
    // Toolbar: the same Twemoji images as the stickers, identical on every device
    document.querySelectorAll('.ed-tools [data-emoji]').forEach((b) => {
        b.textContent = '';
        BDB.emojiImage(b.dataset.emoji, b);
    });

    const STICKER_GROUPS = [
        [t('Gesichter'), '😀', '😀 😃 😄 😁 😆 😅 🤣 😂 🙂 🙃 😉 😊 😇 🥰 😍 🤩 😘 😗 😚 😋 😛 😜 🤪 😝 🤑 🤗 🤭 🤫 🤔 🤐 🤨 😐 😑 😶 😏 😒 🙄 😬 😌 😔 😪 🤤 😴 😷 🤒 🤕 🤧 🥵 🥶 🥴 😵 🤯 🤠 🥳 😎 🤓 🧐 😕 😟 🙁 😮 😯 😲 😳 🥺 😦 😧 😨 😰 😥 😢 😭 😱 😖 😣 😞 😓 😩 😫 🥱 😤 😠 😡 🤡 👻 👽 👾 🤖 💩 😺 😸 😹 😻 😼 😽 🙀 😿 😾 🙈 🙉 🙊'],
        [t('Menschen'), '👋', '👋 🤚 ✋ 🖐️ 👌 🤏 ✌️ 🤞 🤟 🤙 👈 👉 👆 👇 ☝️ 👍 👎 ✊ 👊 👏 🙌 👐 🤲 🤝 🙏 ✍️ 💪 🦵 🦶 👂 👃 🧠 🦷 👀 👅 👄 👶 🧒 👦 👧 🧑 👱 👨 👩 🧓 👴 👵 👮 👷 💂 🕵️ 👩‍⚕️ 👨‍⚕️ 👩‍🎓 👨‍🎓 👩‍🏫 👨‍🏫 👩‍🍳 👨‍🍳 👩‍🌾 👨‍🌾 👩‍🔧 👨‍🔧 👩‍🔬 👨‍🔬 👩‍🎨 👨‍🎨 👩‍💻 👨‍💻 👩‍🚀 👨‍🚀 👩‍🚒 👨‍🚒 👸 🤴 🦸 🦹 🧙 🧚 🧜 🧝 🧞 🎅 🤶 🙋 🙆 🙅 🤷 🙇 🤦 🧍 🏃 🚶 💃 🕺 🧘 👪 👫 👭 👬 🧑‍🤝‍🧑'],
        [t('Tiere'), '🦊', '🐶 🐕 🐩 🐺 🦊 🦝 🐱 🐈 🦁 🐯 🐅 🐆 🐴 🐎 🦄 🦓 🦌 🐮 🐂 🐃 🐄 🐷 🐖 🐗 🐽 🐏 🐑 🐐 🐪 🐫 🦙 🦒 🐘 🦏 🦛 🐭 🐁 🐀 🐹 🐰 🐇 🐿️ 🦔 🦇 🐻 🐨 🐼 🦥 🦦 🦨 🦘 🦡 🐾 🦃 🐔 🐓 🐣 🐤 🐥 🐦 🐧 🕊️ 🦅 🦆 🦢 🦉 🦩 🦚 🦜 🐸 🐊 🐢 🦎 🐍 🐲 🐉 🦕 🦖 🐳 🐋 🐬 🐟 🐠 🐡 🦈 🐙 🐚 🦀 🦞 🦐 🦑 🐌 🦋 🐛 🐜 🐝 🐞 🦗 🕷️ 🕸️ 🦂 🦠'],
        [t('Natur & Wetter'), '🌻', '💐 🌸 💮 🏵️ 🌹 🌺 🌻 🌼 🌷 🌱 🌲 🌳 🌴 🌵 🌾 🌿 ☘️ 🍀 🍁 🍂 🍃 🍄 🌰 🌍 🌎 🌏 🌑 🌒 🌓 🌔 🌕 🌙 🌛 🌜 ⭐ 🌟 🌠 ☀️ 🌤️ ⛅ 🌥️ ☁️ 🌦️ 🌧️ ⛈️ 🌩️ 🌨️ ❄️ ☃️ ⛄ 🌬️ 💨 🌪️ 🌫️ 🌈 ☂️ ☔ ⚡ 🔥 💧 🌊 ⛰️ 🏔️ 🌋 🏝️ 🏜️ 🪐 🌌 ☄️'],
        [t('Essen'), '🍎', '🍏 🍎 🍐 🍊 🍋 🍌 🍉 🍇 🍓 🍈 🍒 🍑 🥭 🍍 🥥 🥝 🍅 🍆 🥑 🥦 🥬 🥒 🌶️ 🌽 🥕 🧄 🧅 🥔 🍠 🥐 🥯 🍞 🥖 🥨 🧀 🥚 🍳 🧈 🥞 🧇 🥓 🍗 🍖 🌭 🍔 🍟 🍕 🥪 🌮 🌯 🥗 🍝 🍜 🍲 🍛 🍣 🍱 🥟 🍙 🍚 🍘 🍢 🍡 🍧 🍨 🍦 🥧 🧁 🍰 🎂 🍮 🍭 🍬 🍫 🍿 🍩 🍪 🥜 🍯 🥛 🧃 ☕ 🍵 🧊 🥤 🍼 🥄 🍴 🥣'],
        [t('Orte & Fahrzeuge'), '🚗', '🏠 🏡 🏘️ 🏢 🏫 🏥 🏦 🏪 🏬 🏰 🏯 🏟️ 🗼 🗽 ⛪ 🕌 🕍 ⛩️ 🎡 🎢 🎠 ⛲ ⛺ 🏕️ 🌁 🌃 🏙️ 🌄 🌅 🌆 🌇 🌉 🗺️ 🧭 🚗 🚕 🚙 🚌 🚎 🏎️ 🚓 🚑 🚒 🚐 🚚 🚛 🚜 🛵 🏍️ 🚲 🛴 🛹 🚂 🚃 🚄 🚅 🚆 🚇 🚊 🚝 🚞 🚋 🚠 🚡 🚁 ✈️ 🛩️ 🛫 🛬 🪂 🚀 🛸 🛰️ ⛵ 🚤 🛥️ 🛳️ ⛴️ 🚢 ⚓ 🚦 🚧 ⛽ 🎪'],
        [t('Sport & Spiel'), '⚽', '⚽ 🏀 🏈 ⚾ 🥎 🎾 🏐 🏉 🥏 🎱 🏓 🏸 🏒 🏑 🥍 🏏 ⛳ 🎣 🤿 🥊 🥋 🎽 🛷 ⛸️ 🥌 🎿 ⛷️ 🏂 🏋️ 🤸 ⛹️ 🤾 🏌️ 🏇 🧗 🚴 🏊 🏄 🚣 🤽 🏆 🥇 🥈 🥉 🏅 🎖️ 🎮 🕹️ 🎲 🧩 ♟️ 🃏 🎯 🎳 🪁 🧸 🪀 🎭 🎨 🖌️ 🖍️ 🎤 🎧 🎼 🎵 🎶 🎹 🥁 🎷 🎺 🎸 🪕 🎻 🎬 📷 🎞️'],
        [t('Schule & Dinge'), '✏️', '📚 📖 📕 📗 📘 📙 📓 📒 📔 📝 ✏️ ✒️ 🖊️ 🖋️ 🖍️ 📏 📐 ✂️ 📎 🖇️ 📌 📍 🗂️ 📁 📂 📅 📆 🗓️ 📋 📊 📈 📉 🎒 🧮 🔍 🔬 🔭 🧪 🧫 🧬 🌡️ 🧲 💡 🔦 🕯️ 🔋 🔌 💻 🖥️ ⌨️ 🖱️ 🖨️ 📱 ☎️ 📺 📻 ⏰ ⏱️ ⌛ ⏳ 🕰️ 🔔 📣 📢 ✉️ 📮 📦 🏷️ 🔑 🗝️ 🔒 🔓 🧰 🔧 🔨 ⚙️ 🧱 🧹 🧺 🧻 🧼 🧽 🛁 🚿 🪑 🛏️ 🚪 🧳 👓 🕶️ 🥽 👕 👖 🧣 🧤 🧥 🧦 👗 👚 👛 👜 🎓 🧢 👒 🎩 👑 💍 💎 👟 👞 🥾 👢'],
        [t('Feste'), '🎉', '🎉 🎊 🎈 🎁 🎀 🎂 🕯️ 🎄 🎃 🎆 🎇 ✨ 🎋 🎍 🎎 🎏 🎐 🎑 🏮 🎗️ 🎟️ 🎫 🥚 🐣 🐰 🌷 🦃 🍂 ❄️ ⛄ 🎅 🤶 🦌 🔔 ⭐ 🌟 👑 🦄 🧚 💝 💖 💗 💓 💞 💕 💌 💘 🍬 🍭 🧁 🍰'],
        [t('Zeichen'), '❤️', '❤️ 🧡 💛 💚 💙 💜 🤎 🖤 🤍 💔 ❣️ 💯 💢 💥 💫 💦 💤 💬 💭 🗯️ ✅ ☑️ ✔️ ❌ ❎ ➕ ➖ ➗ ✖️ ❓ ❔ ❗ ❕ ‼️ ⁉️ ⭕ 🚫 ⛔ ⚠️ ♻️ 🔰 ❇️ ✳️ 🆗 🆒 🆕 🆓 🆙 🆘 🔝 🔙 🔜 ➡️ ⬅️ ⬆️ ⬇️ ↗️ ↘️ ↙️ ↖️ ↕️ ↔️ 🔄 ▶️ ⏸️ ⏹️ ⏩ ⏪ 🔀 🔁 0️⃣ 1️⃣ 2️⃣ 3️⃣ 4️⃣ 5️⃣ 6️⃣ 7️⃣ 8️⃣ 9️⃣ 🔟 #️⃣ 🔢 🔤 🔴 🟠 🟡 🟢 🔵 🟣 🟤 ⚫ ⚪ 🟥 🟧 🟨 🟩 🟦 🟪 🟫 ⬛ ⬜ 🔶 🔷 🔸 🔹 🔺 🔻 💠 🔘 ☮️ ☯️'],
    ].map(([name, image, list]) => ({ name, image, list: list.split(' ') }));

    let nr = 0;               // aktuelle Seite (Index) / current page (index)
    let selected = null;       // ID des gewählten Elements / ID of the selected element
    let editing = null;    // { id, before, isNew } während der Texteingabe / { id, before, isNew } during text input
    let inner = null;         // gezeichnete Seite (1000 Einheiten breit) / rendered page (1000 units wide)
    let scale = 1;
    let drag = null;           // laufendes Ziehen/Skalieren/Drehen / running drag/scale/rotate
    let painting = null;         // laufender Malmodus / running paint mode

    const R = E.rights || {};
    const undoHistory = new Map();  // Seiten-ID → Schnappschüsse (Rückgängig) / page ID → snapshots (undo)
    // Seiten-ID → zuletzt mit dem Server abgeglichener Stand (für das Zusammenführen)
    // page ID → state last synced with the server (for merging)
    const baseOf = new Map();
    const inFlight = new Set(); // Seiten, deren Speichern gerade läuft / pages currently being saved
    const planned = new Map();  // Seiten-ID → Timer / page ID → timer
    const chains = new Map();   // Seiten-ID → laufendes Speichern (der Reihe nach) / page ID → running save (in order)
    let running = 0;
    let error = false;

    const page = () => book.pages[nr];
    // Fuchs-Helfer (falls eingeschaltet) über das Geschehen informieren
    // Tell the fox helper (if switched on) what is happening
    const fox = (k, values, repeatable) => { if (window.FoxEvents) window.FoxEvents.event(k, values, repeatable); };
    const element = (id) => page().elements.find((e) => e.id === id);
    const newId = () => 'e' + Math.random().toString(36).slice(2, 10);
    const round = (v) => Math.round(v * 10) / 10;
    const nodeOf = (id) => inner && inner.querySelector('[data-id="' + CSS.escape(id) + '"]');

    // ---- Server ---------------------------------------------------------

    async function request(url, method, body, keepalive) {
        const opts = { method, credentials: 'same-origin', keepalive: !!keepalive,
            headers: { 'X-CSRF-TOKEN': E.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
        if (body instanceof FormData) opts.body = body;
        else if (body !== undefined) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const r = await fetch(url, opts);
        if (r.status === 419 || r.status === 401) throw new Error(t('Du bist nicht mehr angemeldet. Bitte Seite neu laden.'));
        if (!r.ok) {
            let data = null;
            try { data = await r.json(); } catch (e) { /* kein JSON / no JSON */ }
            const error = new Error((data && data.message) || ('HTTP ' + r.status));
            error.status = r.status;
            error.data = data;
            throw error;
        }
        return r.json().catch(() => null);
    }

    function status(kind, text) {
        statusEl.className = 'ed-status ' + kind;
        statusEl.textContent = text || { pending: '…', saving: t('Speichert …'), ok: '✓ ' + t('Gespeichert'), error: '⚠️ ' + t('Nicht gespeichert') }[kind] || '';
        if (kind === 'error' && text) statusEl.title = text;
    }

    function scheduleSave(s) {
        clearTimeout(planned.get(s.id));
        planned.set(s.id, setTimeout(() => save(s), 700));
        status('pending');
    }

    function save(s, keepalive) {
        clearTimeout(planned.get(s.id));
        planned.delete(s.id);
        running++;
        status('saving');
        const before = chains.get(s.id) || Promise.resolve();
        let sent;
        const p = before.then(() => {
            inFlight.add(s.id);
            sent = { b: s.background, e: JSON.parse(JSON.stringify(s.elements)) };
            return request(E.urls.pages + '/' + s.id, 'PUT',
                { background: sent.b, elements: sent.e, version: s.version }, keepalive);
        })
            .then((r) => {
                error = false;
                if (r && r.version) s.version = r.version;
                baseOf.set(s.id, sent);
            })
            .catch((e) => {
                if (e.status === 409 && e.data && e.data.page) {
                    // jemand anderes hat diese Seite inzwischen gespeichert → zusammenführen, erneut speichern
                    // someone else has saved this page in the meantime → merge, save again
                    merge(s, e.data.page);
                    scheduleSave(s);
                    return;
                }
                if (e.status === 404) {
                    // Seite wurde gelöscht (z. B. von der Lehrkraft im Klassenbuch)
                    // Page was deleted (e.g. by the teacher in the class book)
                    clearTimeout(planned.get(s.id));
                    planned.delete(s.id);
                    return;
                }
                error = true;
                status('error', e.message);
                // später erneut versuchen (z. B. kurz kein WLAN)
                // try again later (e.g. no Wi-Fi for a moment)
                if (!planned.has(s.id)) planned.set(s.id, setTimeout(() => save(s), 5000));
            })
            .finally(() => {
                inFlight.delete(s.id);
                running--;
                if (!running && !planned.size && !error) status('ok');
            });
        chains.set(s.id, p);
        return p;
    }

    function saveAll(keepalive) {
        const pending = [...planned.keys()];
        return Promise.all(pending.map((id) => {
            const s = book.pages.find((x) => x.id === id);
            return s ? save(s, keepalive) : null;
        }));
    }

    window.addEventListener('beforeunload', (e) => {
        if (editing) finishText();
        if (planned.size || running) { saveAll(true); e.preventDefault(); e.returnValue = ''; }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') { if (editing) finishText(); saveAll(true); }
    });

    // ---- Gemeinsam arbeiten / Working together --------------------------------

    /**
     * JSON mit sortierten Schlüsseln — zum Vergleichen von Elementen
     *
     * JSON with sorted keys — for comparing elements
     */
    function stable(o) {
        if (Array.isArray(o)) return '[' + o.map(stable).join(',') + ']';
        if (o && typeof o === 'object') return '{' + Object.keys(o).sort().map((k) => JSON.stringify(k) + ':' + stable(o[k])).join(',') + '}';
        return JSON.stringify(o);
    }

    /**
     * Drei-Wege-Zusammenführung je Element: Ausgangspunkt ist der Stand des
     * Servers; darauf werden die EIGENEN Änderungen seit dem letzten Abgleich
     * angewendet (hinzugefügt, geändert, gelöscht). Was andere geändert haben,
     * bleibt so erhalten. Bei derselben Stelle gewinnt die eigene Änderung.
     *
     * Three-way merge per element: the starting point is the server's state;
     * on top of it the OWN changes since the last sync are applied
     * (added, changed, deleted). What others have changed is kept that way.
     * If the same spot was changed, the own change wins.
     */
    function merge(s, server) {
        const base = baseOf.get(s.id) || { b: null, e: [] };
        const baseMap = new Map(base.e.map((e) => [e.id, stable(e)]));
        const localIds = new Set(s.elements.map((e) => e.id));
        const result = server.elements.slice();
        const indexOf = (id) => result.findIndex((e) => e.id === id);

        baseMap.forEach((_, id) => {
            if (!localIds.has(id)) { const i = indexOf(id); if (i >= 0) result.splice(i, 1); }
        });
        s.elements.forEach((e) => {
            const b = baseMap.get(e.id);
            const i = indexOf(e.id);
            if (b === undefined) { if (i < 0) result.push(e); }
            else if (b !== stable(e)) { if (i >= 0) result[i] = e; else result.push(e); }
        });

        s.background = (base.b !== null && s.background !== base.b) ? s.background : server.background;
        s.elements = result;
        s.version = server.version;
        baseOf.set(s.id, { b: server.background, e: server.elements });
        // Rückgängig würde sonst Änderungen der anderen zurückdrehen
        // otherwise undo would revert the others' changes
        undoHistory.delete(s.id);
        if (s === page() && !editing && !drag && !painting) draw();
        previewLater(s);
    }

    function busy(s) {
        return planned.has(s.id) || inFlight.has(s.id)
            || (s === page() && (editing || drag || painting));
    }

    /**
     * Klassenbuch: alle paar Sekunden die Änderungen der anderen holen
     *
     * Class book: fetch the others' changes every few seconds
     */
    async function sync() {
        if (!R.collaborative || document.hidden) return;
        const v = book.pages.map((x) => x.id + ':' + x.version).join(',');
        let r;
        try { r = await request(E.urls.state + '?v=' + encodeURIComponent(v) + '&page=' + page().id, 'GET'); }
        catch (e) { return; }

        const beforeIds = book.pages.map((x) => x.id).join(',');
        const currentId = page().id;
        const merged = r.pages.map((info) => {
            let s = book.pages.find((x) => x.id === info.id);
            const g = r.changed.find((x) => x.id === info.id);
            if (!s) {
                baseOf.set(g.id, { b: g.background, e: JSON.parse(JSON.stringify(g.elements)) });
                return g;
            }
            if (g && g.version !== s.version && !busy(s)) {
                s.elements = g.elements;
                s.background = g.background;
                s.version = g.version;
                baseOf.set(s.id, { b: g.background, e: JSON.parse(JSON.stringify(g.elements)) });
                undoHistory.delete(s.id);
                if (s.id === currentId) {
                    if (selected && !element(selected)) selected = null;
                    draw();
                }
                previewLater(s);
            }
            return s;
        });
        book.pages.splice(0, book.pages.length, ...merged);

        if (merged.map((x) => x.id).join(',') !== beforeIds) {
            const i = book.pages.findIndex((x) => x.id === currentId);
            if (i < 0) { selected = null; nr = Math.min(nr, book.pages.length - 1); draw(); }
            else nr = i;
            drawPageBar();
        }
        presence(r.others || []);
        const title = $('ed-title');
        if (r.title && document.activeElement !== title && title.value !== r.title) title.value = r.title;
    }

    let others = [];
    function presence(list) {
        others = list;
        const header = $('ed-others');
        if (header) header.textContent = list.length ? '👀 ' + list.map((a) => a.name).join(' · ') : '';
        pageBar.querySelectorAll('.ed-mini').forEach((b) => {
            const old = b.querySelector('.ed-mini-who');
            if (old) old.remove();
            const here = list.filter((a) => String(a.page) === b.dataset.page);
            if (!here.length) return;
            const w = document.createElement('span');
            w.className = 'ed-mini-who';
            w.textContent = here.map((a) => a.name.slice(0, 1).toUpperCase()).join('');
            w.title = here.map((a) => a.name).join(', ');
            b.appendChild(w);
        });
    }

    if (R.collaborative) {
        setInterval(sync, 4000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) sync(); });
    }

    // ---- Rückgängig / Undo ------------------------------------------------

    function snapshot(s) { return JSON.stringify({ b: s.background, e: s.elements }); }

    function remember(snap) {
        const s = page();
        const v = undoHistory.get(s.id) || [];
        v.push(snap || snapshot(s));
        if (v.length > 60) v.shift();
        undoHistory.set(s.id, v);
        undoBtn.disabled = false;
    }

    function undo() {
        if (editing) finishText();
        const s = page();
        const v = undoHistory.get(s.id);
        if (!v || !v.length) return;
        const z = JSON.parse(v.pop());
        s.background = z.b;
        s.elements = z.e;
        if (selected && !element(selected)) selected = null;
        draw();
        changed();
        fox('undo');
    }

    /**
     * Eine Änderung am gewählten Element: merken, ändern, neu zeichnen, speichern
     *
     * A change to the selected element: remember, change, redraw, save
     */
    function change(fn) {
        if (editing) finishText();
        const x = element(selected);
        if (!x) return;
        remember();
        fn(x);
        draw();
        changed();
    }

    function changed(s) {
        s = s || page();
        scheduleSave(s);
        previewLater(s);
    }

    // ---- Zeichnen / Drawing -------------------------------------------------

    function fittingWidth() {
        const b = stage.clientWidth - 24;
        const h = stage.clientHeight - 24;
        return Math.max(160, Math.floor(Math.min(b, h * BDB.WIDTH / BDB.height(format))));
    }

    function draw() {
        const s = page();
        inner = BDB.page(frame, s, format, { width: fittingWidth() });
        scale = parseFloat(inner.dataset.scale) || 1;
        // 1 Bildschirmpixel in Seiteneinheiten
        // 1 screen pixel in page units
        inner.style.setProperty('--px', (1 / scale) + 'px');
        if (selected && !element(selected)) selected = null;
        if (selected) mark();
        properties();
        undoBtn.disabled = !(undoHistory.get(s.id) || []).length;
    }

    function mark() {
        const k = nodeOf(selected);
        if (!k) return;
        k.classList.add('ed-selected');
        const r = document.createElement('div');
        r.className = 'ed-selection';
        r.innerHTML = '<span class="ed-handle ed-rotate" title="' + t('Drehen') + '">↻</span>'
            + '<span class="ed-handle ed-resize" title="' + t('Größe') + '"></span>';
        k.appendChild(r);
    }

    function select(id) {
        if (editing && editing.id !== id) finishText();
        selected = id;
        draw();
    }

    // ---- Ziehen, Größe, Drehen / Drag, size, rotate -----------------------------

    function point(e) {
        const r = inner.getBoundingClientRect();
        return { x: (e.clientX - r.left) / scale, y: (e.clientY - r.top) / scale };
    }

    const fixedRatio = (x) => x.type === 'image' || x.type === 'sticker' || x.type === 'audio';

    frame.addEventListener('pointerdown', (e) => {
        if (painting) return;
        // Ziel selbst bestimmen: direkt nach dem Neuzeichnen meldet der Browser
        // sonst teils noch den alten (inzwischen ersetzten) Knoten
        // Determine the target ourselves: right after redrawing, the browser
        // sometimes still reports the old (since replaced) node
        const findTarget = () => document.elementFromPoint(e.clientX, e.clientY) || e.target;
        let target = findTarget();
        if (editing) {
            if (target.closest('.bdb-content[contenteditable]')) return;   // Cursor im Text setzen / set text cursor
            finishText();
            target = findTarget();
        }
        const node = target.closest('.bdb-el');
        if (!node) { if (selected) { selected = null; draw(); } return; }
        const id = node.dataset.id;
        const x = element(id);
        if (!x) return;
        e.preventDefault();
        const handle = target.closest('.ed-handle');
        const alreadySelected = selected === id;
        if (!alreadySelected) { select(id); fox('select_' + x.type); }
        drag = {
            kind: handle ? (handle.classList.contains('ed-rotate') ? 'rotate' : 'resize') : 'move',
            id, start: point(e), orig: Object.assign({}, x), before: snapshot(page()),
            alreadySelected, moved: false, node: nodeOf(id), pointer: e.pointerId,
        };
        try { frame.setPointerCapture(e.pointerId); } catch (err) { /* egal / ignore */ }
    });

    frame.addEventListener('pointermove', (e) => {
        if (!drag || e.pointerId !== drag.pointer) return;
        const p = point(e);
        const x = element(drag.id);
        const o = drag.orig;
        const dx = p.x - drag.start.x, dy = p.y - drag.start.y;
        if (!drag.moved) {
            if (Math.hypot(dx, dy) * scale < 5) return;
            drag.moved = true;
            remember(drag.before);
        }
        const cx = o.x + o.w / 2, cy = o.y + o.h / 2;
        if (drag.kind === 'move') {
            let nx = o.x + dx, ny = o.y + dy;
            // an der Seitenmitte einrasten
            // snap to the page centre
            if (Math.abs(nx + o.w / 2 - BDB.WIDTH / 2) < 10 / scale) nx = BDB.WIDTH / 2 - o.w / 2;
            if (Math.abs(ny + o.h / 2 - BDB.height(format) / 2) < 10 / scale) ny = BDB.height(format) / 2 - o.h / 2;
            x.x = round(nx); x.y = round(ny);
        } else if (drag.kind === 'resize') {
            let w, h;
            if (fixedRatio(x)) {
                const f = Math.hypot(p.x - cx, p.y - cy) / Math.max(1, Math.hypot(drag.start.x - cx, drag.start.y - cy));
                w = Math.max(40, o.w * f);
                h = w * o.h / o.w;
            } else {
                const a = (o.r || 0) * Math.PI / 180;
                const vx = p.x - cx, vy = p.y - cy;
                w = Math.max(60, 2 * Math.abs(vx * Math.cos(a) + vy * Math.sin(a)));
                h = Math.max(40, 2 * Math.abs(-vx * Math.sin(a) + vy * Math.cos(a)));
            }
            x.w = round(w); x.h = round(h);
            x.x = round(cx - w / 2); x.y = round(cy - h / 2);
            if (x.type === 'sticker') drag.node.style.fontSize = (Math.min(x.w, x.h) * 0.86) + 'px';
        } else {
            let w = Math.atan2(p.y - cy, p.x - cx) * 180 / Math.PI + 90;
            if (w > 180) w -= 360;
            for (const z of [-180, -90, 0, 90, 180]) if (Math.abs(w - z) < 6) w = z;
            x.r = Math.round(w);
        }
        BDB.geometry(drag.node, x);
    });

    function dragEnd(e, cancel) {
        if (!drag || (e && e.pointerId !== drag.pointer)) return;
        const z = drag;
        drag = null;
        if (z.moved) { draw(); changed(); return; }
        if (cancel) return;
        const x = element(z.id);
        if (z.alreadySelected && x && x.type === 'text') editText(z.id);
        // nochmal Antippen spielt den Ton ab (oder hält ihn an)
        // tapping again plays the sound (or stops it)
        if (z.alreadySelected && x && x.type === 'audio' && z.node) BDB.playAudio(z.node, x);
    }
    // Tippen auf die freie Fläche neben der Seite hebt die Auswahl auf
    // Tapping the empty area next to the page clears the selection
    stage.addEventListener('pointerdown', (e) => {
        // nur die freie Fläche selbst — Knoten der Seite können beim Neuzeichnen
        // schon ersetzt sein und gehören dann nicht mehr zum Rahmen
        // only the empty area itself — nodes of the page may already have been
        // replaced when redrawing and then no longer belong to the frame
        if (painting || e.target !== stage) return;
        if (editing) finishText();
        if (selected) { selected = null; draw(); }
    });
    frame.addEventListener('pointerup', (e) => dragEnd(e, false));
    frame.addEventListener('pointercancel', (e) => dragEnd(e, true));
    frame.addEventListener('dblclick', (e) => {
        const k = e.target.closest('.bdb-text');
        if (k && !editing) editText(k.dataset.id);
    });

    // ---- Text -----------------------------------------------------------------

    function editText(id, isNew) {
        const k = nodeOf(id);
        if (!k) return;
        const content = k.querySelector('.bdb-content');
        editing = { id, before: snapshot(page()), isNew: !!isNew };
        try { content.contentEditable = 'plaintext-only'; } catch (err) { /* älterer Browser / older browser */ }
        if (content.contentEditable !== 'plaintext-only') content.contentEditable = 'true';
        content.dataset.placeholder = t('Tippe hier …');
        k.classList.add('ed-typing');
        content.focus();
        const r = document.createRange();
        r.selectNodeContents(content);
        r.collapse(false);
        const sel = getSelection();
        sel.removeAllRanges();
        sel.addRange(r);

        content.addEventListener('input', () => {
            const x = element(id);
            if (!x) return;
            if (content.scrollHeight > x.h + 1) { x.h = Math.ceil(content.scrollHeight); k.style.height = x.h + 'px'; }
        });
        content.addEventListener('paste', (ev) => {
            ev.preventDefault();
            document.execCommand('insertText', false, (ev.clipboardData || window.clipboardData).getData('text/plain'));
        });
        content.addEventListener('blur', () => setTimeout(() => { if (editing && editing.id === id && document.activeElement !== content) finishText(); }, 0));
        content.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') { ev.preventDefault(); finishText(); } });
    }

    function finishText() {
        if (!editing) return;
        const b = editing;
        editing = null;
        const x = element(b.id);
        const k = nodeOf(b.id);
        if (!x || !k) { draw(); return; }
        const text = k.querySelector('.bdb-content').innerText.replace(/\n$/, '');
        if (!text.trim()) {
            page().elements = page().elements.filter((e) => e.id !== b.id);
            if (selected === b.id) selected = null;
            if (b.isNew) { (undoHistory.get(page().id) || []).pop(); draw(); return; }
            remember(b.before);
        } else if (text !== x.text || b.isNew) {
            if (!b.isNew) remember(b.before);
            x.text = text;
        } else {
            draw();
            return;
        }
        draw();
        changed();
    }

    // ---- Neue Elemente / New elements --------------------------------------------

    function centre(w, h) {
        const ph = BDB.height(format);
        const offset = (page().elements.length % 5) * 24;
        return { x: round((BDB.WIDTH - w) / 2 + offset), y: round((ph - h) / 2 + offset) };
    }

    function add(x) {
        remember();
        page().elements.push(x);
        selected = x.id;
        draw();
        changed();
    }

    function newText() {
        const w = 760, h = 110;
        const x = Object.assign({ id: newId(), type: 'text', w, h, r: 0, text: '', size: 56, color: '#26324a', bg: '',
            font: 'rounded', bold: false, italic: false, align: 'center' }, centre(w, h));
        remember();
        page().elements.push(x);
        selected = x.id;
        draw();
        editText(x.id, true);
        fox('new_text');
    }

    function newShape() {
        const w = 420, h = 280;
        add(Object.assign({ id: newId(), type: 'shape', shape: 'rounded', fill: '#ffd166', w, h, r: 0 }, centre(w, h)));
    }

    function newSticker(char) {
        const g = 220;
        add(Object.assign({ id: newId(), type: 'sticker', char, w: g, h: g, r: 0 }, centre(g, g)));
    }

    async function upload(blob, kind, name) {
        const fd = new FormData();
        fd.append('file', blob, name);
        fd.append('type', kind);
        return request(E.urls.media, 'POST', fd);
    }

    /**
     * Fotos im Browser verkleinern: spart Platz und entfernt Standortdaten (EXIF)
     *
     * Shrink photos in the browser: saves space and removes location data (EXIF)
     */
    async function shrinkImage(file) {
        let image;
        try { image = await createImageBitmap(file, { imageOrientation: 'from-image' }); }
        catch (err) {
            image = await new Promise((ok, no) => {
                const i = new Image();
                i.onload = () => ok(i);
                i.onerror = () => no(new Error(t('Dieses Bild kann nicht gelesen werden.')));
                i.src = URL.createObjectURL(file);
            });
        }
        const bw = image.width, bh = image.height;
        const f = Math.min(1, 1600 / Math.max(bw, bh));
        const c = document.createElement('canvas');
        c.width = Math.round(bw * f);
        c.height = Math.round(bh * f);
        c.getContext('2d').drawImage(image, 0, 0, c.width, c.height);
        const png = /png|gif|webp/.test(file.type);
        const blob = await new Promise((ok) => c.toBlob(ok, png ? 'image/png' : 'image/jpeg', 0.85));
        return { blob, w: c.width, h: c.height, extension: png ? 'png' : 'jpg' };
    }

    async function newPhoto(file) {
        if (!file) return;
        const s = page();
        try {
            status('saving', t('Foto wird hochgeladen …'));
            const b = await shrinkImage(file);
            const m = await upload(b.blob, 'image', 'photo.' + b.extension);
            const max = Math.min(640, BDB.height(format) * 0.6);
            const f = Math.min(max / b.w, max / b.h);
            const w = round(b.w * f), h = round(b.h * f);
            if (page() !== s) return;
            add(Object.assign({ id: newId(), type: 'image', media: m.id, flip: false, w, h, r: 0 }, centre(w, h)));
            fox('photo');
        } catch (e) {
            status('error', e.message);
            alert(t('Das Foto konnte nicht hochgeladen werden.') + '\n' + e.message);
        }
    }

    // ---- Malen / Painting --------------------------------------------------

    function startPainting() {
        if (editing) finishText();
        selected = null;
        draw();
        const q = 1.5;                       // Auflösung der Zeichnung je Seiteneinheit / drawing resolution per unit
        const ph = BDB.height(format);
        const c = document.createElement('canvas');
        c.className = 'ed-canvas';
        c.width = BDB.WIDTH * q;
        c.height = ph * q;
        c.style.width = BDB.WIDTH + 'px';
        c.style.height = ph + 'px';
        inner.appendChild(c);
        const ctx = c.getContext('2d');
        ctx.scale(q, q);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        painting = { c, ctx, q, strokes: [], colour: '#26324a', width: 12, eraser: false, active: null };
        fox('paint');

        const bar = document.createElement('div');
        bar.className = 'ed-paint-bar';
        const colourButtons = COLOURS.map((f) => '<button class="ed-colour" data-colour="' + f + '" style="background:' + f + '" aria-label="' + f + '"></button>').join('');
        bar.innerHTML = '<div class="ed-paint-group">' + colourButtons + '</div>'
            + '<div class="ed-paint-group">'
            + [6, 12, 26, 48].map((b) => '<button class="ed-width" data-width="' + b + '"><i style="width:' + (b / 2 + 4) + 'px;height:' + (b / 2 + 4) + 'px"></i></button>').join('')
            + '<button class="ed-eraser" title="' + t('Radierer') + '">🧽</button>'
            + '<button class="ed-stroke-undo" title="' + t('Rückgängig') + '">↶</button></div>'
            + '<div class="ed-paint-group"><button class="ed-paint-cancel">' + t('Abbrechen') + '</button>'
            + '<button class="ed-paint-done primary">✓ ' + t('Fertig') + '</button></div>';
        document.body.appendChild(bar);
        painting.bar = bar;
        document.getElementById('ed').classList.add('ed-painting');

        const refresh = () => {
            bar.querySelectorAll('.ed-colour').forEach((b) => b.classList.toggle('on', !painting.eraser && b.dataset.colour === painting.colour));
            bar.querySelectorAll('.ed-width').forEach((b) => b.classList.toggle('on', +b.dataset.width === painting.width));
            bar.querySelector('.ed-eraser').classList.toggle('on', painting.eraser);
        };
        refresh();
        bar.addEventListener('click', (e) => {
            const b = e.target.closest('button');
            if (!b) return;
            if (b.dataset.colour) { painting.colour = b.dataset.colour; painting.eraser = false; }
            else if (b.dataset.width) painting.width = +b.dataset.width;
            else if (b.classList.contains('ed-eraser')) painting.eraser = !painting.eraser;
            else if (b.classList.contains('ed-stroke-undo')) { painting.strokes.pop(); repaint(); }
            else if (b.classList.contains('ed-paint-cancel')) { if (!painting.strokes.length || confirm(t('Zeichnung verwerfen?'))) endPainting(false); return; }
            else if (b.classList.contains('ed-paint-done')) { endPainting(true); return; }
            refresh();
        });

        const pos = (e) => {
            const r = c.getBoundingClientRect();
            return [round((e.clientX - r.left) / scale), round((e.clientY - r.top) / scale)];
        };
        c.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            c.setPointerCapture(e.pointerId);
            const s = { colour: painting.colour, width: painting.width, eraser: painting.eraser, points: [pos(e)], pointer: e.pointerId };
            painting.strokes.push(s);
            painting.active = s;
            stroke(s, 0);
        });
        c.addEventListener('pointermove', (e) => {
            const s = painting && painting.active;
            if (!s || e.pointerId !== s.pointer) return;
            const list = e.getCoalescedEvents ? e.getCoalescedEvents() : [e];
            for (const ev of list) {
                s.points.push(pos(ev));
                stroke(s, s.points.length - 2);
            }
        });
        const end = () => { if (painting) painting.active = null; };
        c.addEventListener('pointerup', end);
        c.addEventListener('pointercancel', end);
    }

    function stroke(s, from) {
        const ctx = painting.ctx;
        ctx.globalCompositeOperation = s.eraser ? 'destination-out' : 'source-over';
        ctx.strokeStyle = s.colour;
        ctx.fillStyle = s.colour;
        ctx.lineWidth = s.eraser ? s.width * 2 : s.width;
        const p = s.points;
        if (p.length === 1) {
            ctx.beginPath();
            ctx.arc(p[0][0], p[0][1], ctx.lineWidth / 2, 0, Math.PI * 2);
            ctx.fill();
            return;
        }
        ctx.beginPath();
        ctx.moveTo(p[Math.max(0, from)][0], p[Math.max(0, from)][1]);
        for (let i = Math.max(1, from + 1); i < p.length; i++) ctx.lineTo(p[i][0], p[i][1]);
        ctx.stroke();
    }

    function repaint() {
        painting.ctx.clearRect(0, 0, BDB.WIDTH, BDB.height(format));
        painting.strokes.forEach((s) => stroke(s, 0));
    }

    async function endPainting(apply) {
        const m = painting;
        painting = null;
        m.bar.remove();
        document.getElementById('ed').classList.remove('ed-painting');
        const painted = m.strokes.filter((s) => !s.eraser);
        if (!apply || !painted.length) { draw(); return; }

        // nur den bemalten Ausschnitt speichern
        // save only the painted section
        let x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
        painted.forEach((s) => s.points.forEach(([x, y]) => {
            const r = s.width / 2 + 4;
            x1 = Math.min(x1, x - r); y1 = Math.min(y1, y - r); x2 = Math.max(x2, x + r); y2 = Math.max(y2, y + r);
        }));
        x1 = Math.max(0, Math.floor(x1)); y1 = Math.max(0, Math.floor(y1));
        x2 = Math.min(BDB.WIDTH, Math.ceil(x2)); y2 = Math.min(BDB.height(format), Math.ceil(y2));
        const w = Math.max(10, x2 - x1), h = Math.max(10, y2 - y1);
        const out = document.createElement('canvas');
        out.width = Math.round(w * m.q);
        out.height = Math.round(h * m.q);
        out.getContext('2d').drawImage(m.c, x1 * m.q, y1 * m.q, out.width, out.height, 0, 0, out.width, out.height);
        draw();
        try {
            status('saving', t('Zeichnung wird gespeichert …'));
            const blob = await new Promise((ok) => out.toBlob(ok, 'image/png'));
            const media = await upload(blob, 'image', 'drawing.png');
            add({ id: newId(), type: 'image', media: media.id, flip: false, x: x1, y: y1, w, h, r: 0 });
        } catch (e) {
            status('error', e.message);
            alert(t('Die Zeichnung konnte nicht gespeichert werden.') + '\n' + e.message);
        }
    }

    // ---- Tonaufnahme / Sound recording ----------------------------------------------

    async function recordAudio() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
            alert(t('Tonaufnahmen funktionieren nur über eine sichere Verbindung (https) und in einem aktuellen Browser.'));
            return;
        }
        let stream;
        try { stream = await navigator.mediaDevices.getUserMedia({ audio: true }); }
        catch (e) { alert(t('Kein Zugriff auf das Mikrofon. Bitte im Browser erlauben.')); return; }

        const type = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/webm', 'audio/ogg'].find((m) => MediaRecorder.isTypeSupported(m)) || '';
        let rec = null, chunks = [], blob = null, start = 0, clock = null;

        const box = document.createElement('div');
        box.className = 'ed-audio';
        box.innerHTML = '<h2>' + t('Ton aufnehmen') + '</h2>'
            + '<button class="ed-audio-button" aria-label="' + t('Aufnahme starten') + '"><i></i></button>'
            + '<div class="ed-audio-time">0:00</div>'
            + '<p class="hint ed-audio-hint">' + t('Tippe auf den roten Knopf und sprich.') + '</p>'
            + '<audio controls hidden></audio>'
            + '<div class="actions"><button class="ed-audio-ok primary" disabled>' + t('Einfügen') + '</button>'
            + '<button class="ed-audio-cancel">' + t('Abbrechen') + '</button></div>';
        const button = box.querySelector('.ed-audio-button');
        const time = box.querySelector('.ed-audio-time');
        const hint = box.querySelector('.ed-audio-hint');
        const player = box.querySelector('audio');
        const ok = box.querySelector('.ed-audio-ok');

        const stop = () => { if (rec && rec.state === 'recording') rec.stop(); };
        const cleanUp = () => { stop(); clearInterval(clock); stream.getTracks().forEach((tr) => tr.stop()); };

        button.addEventListener('click', () => {
            if (rec && rec.state === 'recording') { stop(); return; }
            chunks = [];
            blob = null;
            ok.disabled = true;
            player.hidden = true;
            rec = new MediaRecorder(stream, type ? { mimeType: type } : undefined);
            rec.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };
            rec.onstop = () => {
                clearInterval(clock);
                button.classList.remove('recording');
                blob = new Blob(chunks, { type: rec.mimeType || type || 'audio/webm' });
                player.src = URL.createObjectURL(blob);
                player.hidden = false;
                ok.disabled = false;
                hint.textContent = t('Anhören, nochmal aufnehmen oder einfügen.');
            };
            rec.start(250);
            start = Date.now();
            button.classList.add('recording');
            hint.textContent = t('Aufnahme läuft … zum Beenden nochmal tippen.');
            clock = setInterval(() => {
                const s = Math.floor((Date.now() - start) / 1000);
                time.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
                if (s >= 300) stop();   // höchstens 5 Minuten / at most 5 minutes
            }, 250);
        });
        box.querySelector('.ed-audio-cancel').addEventListener('click', () => { cleanUp(); dialog.close(); });
        ok.addEventListener('click', async () => {
            if (!blob) return;
            ok.disabled = true;
            ok.textContent = t('Speichert …');
            try {
                const extension = /mp4|aac/.test(blob.type) ? 'm4a' : /ogg/.test(blob.type) ? 'ogg' : 'webm';
                const m = await upload(blob, 'audio', 'recording.' + extension);
                cleanUp();
                dialog.close();
                const g = 140;
                add(Object.assign({ id: newId(), type: 'audio', media: m.id, color: '#ff7a3d', w: g, h: g, r: 0 }, centre(g, g)));
                fox('audio_done');
            } catch (e) {
                ok.disabled = false;
                ok.textContent = t('Einfügen');
                alert(t('Die Aufnahme konnte nicht gespeichert werden.') + '\n' + e.message);
            }
        });
        dialog.onclose = cleanUp;
        showDialog(box);
    }

    // ---- Dialoge / Dialogs ---------------------------------------------------

    function showDialog(content) {
        dialogContent.textContent = '';
        dialogContent.appendChild(content);
        dialog.showModal();
    }
    dialog.addEventListener('close', () => { dialog.onclose = null; });
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });

    const RECENT_KEY = 'bdb-sticker-recent';
    function recentStickers() {
        // alter Schlüssel „bw_sticker_zuletzt“ (vor Standard 10) wird übernommen / old key "bw_sticker_zuletzt" (before standard 10) is taken over
        try { const l = JSON.parse(BDB.stored(RECENT_KEY, 'bw_sticker_zuletzt') || '[]'); return Array.isArray(l) ? l.slice(0, 32) : []; }
        catch (e) { return []; }
    }
    function rememberSticker(s) {
        const l = [s].concat(recentStickers().filter((x) => x !== s)).slice(0, 32);
        try { localStorage.setItem(RECENT_KEY, JSON.stringify(l)); } catch (e) { /* egal / ignore */ }
    }
    let stickerTab = null;

    function stickerPicker() {
        const box = document.createElement('div');
        box.className = 'ed-sticker-picker';
        box.innerHTML = '<h2 style="margin-top:0">' + t('Sticker') + '</h2>'
            + '<div class="ed-sticker-tabs" role="tablist"></div>'
            + '<div class="ed-sticker-title"></div><div class="ed-sticker-grid"></div>';
        const tabs = box.querySelector('.ed-sticker-tabs');
        const title = box.querySelector('.ed-sticker-title');
        const grid = box.querySelector('.ed-sticker-grid');
        const recent = recentStickers();
        const groups = (recent.length ? [{ name: t('Zuletzt benutzt'), image: '🕘', list: recent }] : []).concat(STICKER_GROUPS);

        function showGroup(g) {
            stickerTab = g.name;
            tabs.querySelectorAll('button').forEach((b) => b.setAttribute('aria-selected', b.dataset.name === g.name ? 'true' : 'false'));
            title.textContent = g.name;
            grid.textContent = '';
            g.list.forEach((s) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.setAttribute('aria-label', s);
                BDB.emojiImage(s, b);
                b.addEventListener('click', () => { rememberSticker(s); dialog.close(); newSticker(s); });
                grid.appendChild(b);
            });
            grid.scrollTop = 0;
        }
        groups.forEach((g) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('role', 'tab');
            b.dataset.name = g.name;
            b.title = g.name;
            b.setAttribute('aria-label', g.name);
            BDB.emojiImage(g.image, b);
            b.addEventListener('click', () => showGroup(g));
            tabs.appendChild(b);
        });
        showGroup(groups.find((g) => g.name === stickerTab) || groups[0]);
        showDialog(box);
    }

    function backgroundPicker() {
        const box = document.createElement('div');
        box.innerHTML = '<h2 style="margin-top:0">' + t('Seitenfarbe') + '</h2><div class="ed-colour-grid"></div>'
            + '<label class="check" style="margin-top:1rem">' + t('Eigene Farbe') + ' <input type="color"></label>'
            + '<div class="actions"><button class="primary">' + t('Fertig') + '</button></div>';
        const grid = box.querySelector('.ed-colour-grid');
        const setColour = (f) => {
            remember();
            page().background = f;
            draw();
            changed();
        };
        BACKGROUNDS.forEach((f) => {
            const b = document.createElement('button');
            b.className = 'ed-colour gross' + (page().background === f ? ' an' : '');
            b.style.background = f;
            b.setAttribute('aria-label', f);
            b.addEventListener('click', () => { setColour(f); grid.querySelectorAll('.on').forEach((x) => x.classList.remove('on')); b.classList.add('on'); });
            grid.appendChild(b);
        });
        const custom = box.querySelector('input[type=color]');
        custom.value = /^#[0-9a-f]{6}$/i.test(page().background) ? page().background : '#ffffff';
        custom.addEventListener('change', () => setColour(custom.value));
        box.querySelector('.primary').addEventListener('click', () => dialog.close());
        showDialog(box);
    }

    // ---- Eigenschaften des gewählten Elements / Properties of the selected element ----

    function button(content, title, fn, on) {
        const b = document.createElement('button');
        b.type = 'button';
        b.innerHTML = content;
        b.title = title;
        b.setAttribute('aria-label', title);
        if (on) b.classList.add('on');
        // Fokus nicht aus dem Text nehmen, solange getippt wird
        // Do not take the focus away from the text while typing
        b.addEventListener('pointerdown', (e) => e.preventDefault());
        b.addEventListener('click', fn);
        return b;
    }

    /**
     * Knopf mit einheitlichem Symbol (Twemoji) und Wort daneben.
     *
     * Button with a uniform symbol (Twemoji) and a word next to it.
     */
    function symbolButton(image, word, fn, on) {
        const b = button('<span class="ed-b-symbol" aria-hidden="true"></span><span></span>', word, fn, on);
        BDB.emojiImage(image, b.firstChild);
        b.lastChild.textContent = word;
        b.classList.add('ed-with-symbol');
        return b;
    }

    function group(title, ...children) {
        const g = document.createElement('div');
        g.className = 'ed-group';
        if (title) {
            const h = document.createElement('div');
            h.className = 'ed-group-title';
            h.textContent = title;
            g.appendChild(h);
        }
        const z = document.createElement('div');
        z.className = 'ed-row';
        children.flat().forEach((k) => z.appendChild(k));
        g.appendChild(z);
        return g;
    }

    function colourChoice(current, fn, withNone) {
        const list = COLOURS.map((f) => {
            const b = button('', f, () => change((x) => fn(x, f)), current === f);
            b.className = 'ed-colour' + (current === f ? ' an' : '');
            b.style.background = f;
            return b;
        });
        if (withNone) {
            const b = button('∅', t('Keine'), () => change((x) => fn(x, '')), !current);
            b.className = 'ed-colour keine' + (!current ? ' an' : '');
            list.unshift(b);
        }
        return list;
    }

    function properties() {
        const x = selected && element(selected);
        props.hidden = false;
        props.classList.toggle('empty', !x);
        document.getElementById('ed').classList.toggle('ed-has-selection', !!x);
        props.textContent = '';
        if (!x) {
            // Leiste bleibt stehen, damit die Seite beim Auswählen nicht springt
            // The bar stays in place so the page does not jump when selecting
            const h = document.createElement('p');
            h.className = 'ed-empty-hint';
            h.textContent = t('Tippe etwas auf der Seite an, um es zu ändern. Zweimal tippen bearbeitet Text.');
            props.appendChild(h);
            return;
        }

        if (x.type === 'text') {
            const size = document.createElement('span');
            size.className = 'ed-value';
            size.textContent = Math.round(x.size);
            props.appendChild(group(t('Schriftgröße'),
                button('A−', t('Kleiner'), () => change((e) => { e.size = Math.max(14, Math.round(e.size / 1.15)); })),
                size,
                button('A+', t('Größer'), () => change((e) => { e.size = Math.min(300, Math.round(e.size * 1.15)); e.h = Math.max(e.h, e.size * 1.5); }))));
            props.appendChild(group(t('Textfarbe'), colourChoice(x.color, (e, f) => { e.color = f; })));
            const fonts = [['rounded', t('Rund')], ['sans', t('Klar')], ['serif', t('Buch')], ['hand', t('Hand')], ['heavy', t('Dick')]];
            props.appendChild(group(t('Schrift'), fonts.map(([k, n]) => {
                const b = button(n, n, () => change((e) => { e.font = k; }), x.font === k);
                b.classList.add('bdb-f-' + k);
                return b;
            })));
            props.appendChild(group(t('Stil'),
                button('<b>B</b>', t('Fett'), () => change((e) => { e.bold = !e.bold; }), x.bold),
                button('<i>I</i>', t('Schräg'), () => change((e) => { e.italic = !e.italic; }), x.italic),
                button('⇤', t('Links'), () => change((e) => { e.align = 'left'; }), x.align === 'left'),
                button('↔', t('Mitte'), () => change((e) => { e.align = 'center'; }), x.align === 'center'),
                button('⇥', t('Rechts'), () => change((e) => { e.align = 'right'; }), x.align === 'right')));
            props.appendChild(group(t('Hinterlegung'), colourChoice(x.bg, (e, f) => { e.bg = f; }, true)));
        } else if (x.type === 'shape') {
            props.appendChild(group(t('Farbe'), colourChoice(x.fill, (e, f) => { e.fill = f; })));
            props.appendChild(group(t('Form'),
                button('▬', t('Rechteck'), () => change((e) => { e.shape = 'rectangle'; }), x.shape === 'rectangle'),
                button('▢', t('Abgerundet'), () => change((e) => { e.shape = 'rounded'; }), x.shape === 'rounded'),
                button('●', t('Kreis'), () => change((e) => { e.shape = 'circle'; }), x.shape === 'circle')));
        } else if (x.type === 'audio') {
            props.appendChild(group(t('Ton'), symbolButton('🔊', t('Anhören'), () => { const k = nodeOf(x.id); if (k) BDB.playAudio(k, x); })));
            props.appendChild(group(t('Farbe'), colourChoice(x.color, (e, f) => { e.color = f; })));
        } else if (x.type === 'image') {
            props.appendChild(group(t('Bild'), symbolButton('↔️', t('Spiegeln'), () => change((e) => { e.flip = !e.flip; }), x.flip)));
        }

        // Anordnen: jeder Knopf mit Wort und kurzer Erklärung, Symbole allein versteht kein Kind
        // Arrange: every button with a word and a short explanation, no child understands symbols alone
        const list = page().elements;
        const pos = list.findIndex((e) => e.id === x.id);
        const withText = (image, word, explanation, fn, out) => {
            const b = button('<span class="ed-b-symbol" aria-hidden="true"></span><span class="ed-b-text"><b></b><small></small></span>', word, fn);
            BDB.emojiImage(image, b.querySelector('.ed-b-symbol'));
            b.querySelector('b').textContent = word;
            b.querySelector('small').textContent = explanation;
            b.disabled = !!out;
            return b;
        };
        const arrange = group(t('Anordnen'),
            withText('⬆️', t('Ganz nach vorne'), t('liegt über allem anderen'), () => order(1), pos === list.length - 1),
            withText('⬇️', t('Ganz nach hinten'), t('liegt unter allem anderen'), () => order(-1), pos === 0),
            x.r ? withText('↕️', t('Gerade stellen'), t('Drehung aufheben'), () => change((e) => { e.r = 0; })) : [],
            withText('📑', t('Doppeln'), t('legt eine Kopie daneben'), duplicate),
            withText('🗑️', t('Löschen'), t('nimmt es von der Seite'), remove));
        arrange.classList.add('ed-arrange');
        arrange.querySelector('.ed-row button:last-child').classList.add('ed-delete');
        props.appendChild(arrange);
    }

    function order(direction) {
        const list = page().elements;
        const i = list.findIndex((e) => e.id === selected);
        const j = direction > 0 ? list.length - 1 : 0;
        if (i < 0 || i === j) return;
        remember();
        const [x] = list.splice(i, 1);
        if (direction > 0) list.push(x); else list.unshift(x);
        draw();
        changed();
    }

    function duplicate() {
        const x = element(selected);
        if (!x) return;
        add(Object.assign({}, x, { id: newId(), x: x.x + 30, y: x.y + 30 }));
    }

    function remove() {
        if (!selected) return;
        if (editing) { editing = null; }
        remember();
        page().elements = page().elements.filter((e) => e.id !== selected);
        selected = null;
        draw();
        changed();
        fox('deleted');
    }

    // ---- Seitenleiste / Page sidebar ------------------------------------------------

    const previewTimer = new Map();
    function previewLater(s) {
        clearTimeout(previewTimer.get(s.id));
        previewTimer.set(s.id, setTimeout(() => preview(s), 300));
    }
    function preview(s) {
        const box = pageBar.querySelector('[data-page="' + s.id + '"] .ed-mini-image');
        if (box) BDB.page(box, s, format, { width: BDB.height(format) > BDB.WIDTH ? 64 : 90 });
    }

    function drawPageBar() {
        pageBar.textContent = '';
        const actions = document.createElement('div');
        actions.className = 'ed-page-actions';
        if (R.reorderPages) {
            actions.append(
                button('◀', t('Seite nach vorne'), () => movePage(-1)),
                button('▶', t('Seite nach hinten'), () => movePage(1)),
                button('📑', t('Seite doppeln'), () => newPage(true)),
                button('🗑', t('Seite löschen'), deletePage));
        } else {
            actions.classList.add('duplicate-only');
            actions.append(button('📑', t('Seite doppeln'), () => newPage(true)));
        }
        pageBar.appendChild(actions);

        book.pages.forEach((s, i) => {
            const b = document.createElement('button');
            b.className = 'ed-mini' + (i === nr ? ' an' : '');
            b.dataset.page = s.id;
            b.innerHTML = '<span class="ed-mini-image"></span><span class="ed-mini-nr">' + (i + 1) + '</span>';
            b.setAttribute('aria-label', t('Seite :n', { n: i + 1 }));
            b.addEventListener('click', () => showPage(i));
            pageBar.appendChild(b);
            preview(s);
        });
        const plus = document.createElement('button');
        plus.className = 'ed-mini ed-plus';
        plus.innerHTML = '<span>＋</span><span class="ed-mini-nr">' + t('Neue Seite') + '</span>';
        plus.title = t('Neue Seite');
        plus.addEventListener('click', () => newPage(false));
        pageBar.appendChild(plus);
        const on = pageBar.querySelector('.ed-mini.on');
        if (on) on.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        presence(others);
    }

    function showPage(i) {
        if (editing) finishText();
        nr = Math.max(0, Math.min(book.pages.length - 1, i));
        selected = null;
        draw();
        pageBar.querySelectorAll('.ed-mini').forEach((b) => b.classList.toggle('on', b.dataset.page == page().id));
        const on = pageBar.querySelector('.ed-mini.on');
        if (on) on.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        $('ed-read').href = E.urls.read + '#' + (nr + 1);
    }

    async function newPage(copy) {
        if (editing) finishText();
        const s = page();
        try {
            if (copy && planned.has(s.id)) await save(s);
            if (copy) await chains.get(s.id);
            const r = await request(E.urls.pages, 'POST', copy ? { after: s.id, copy: s.id } : { after: s.id });
            baseOf.set(r.page.id, { b: r.page.background, e: JSON.parse(JSON.stringify(r.page.elements)) });
            book.pages.splice(r.index, 0, r.page);
            nr = r.index;
            selected = null;
            drawPageBar();
            draw();
            const n = book.pages.length;
            if (n === 5 || n === 10 || n === 20) fox('many_pages', { n }, true);
            else fox('new_page');
        } catch (e) {
            alert(e.message);
        }
    }

    async function deletePage() {
        if (book.pages.length <= 1) { alert(t('Ein Buch braucht mindestens eine Seite.')); return; }
        if (!confirm(t('Seite :n löschen?', { n: nr + 1 }))) return;
        const s = page();
        try {
            clearTimeout(planned.get(s.id));
            planned.delete(s.id);
            await request(E.urls.pages + '/' + s.id, 'DELETE');
            book.pages.splice(nr, 1);
            nr = Math.min(nr, book.pages.length - 1);
            selected = null;
            drawPageBar();
            draw();
            status('ok');
        } catch (e) {
            alert(e.message);
        }
    }

    async function movePage(d) {
        const target = nr + d;
        if (target < 0 || target >= book.pages.length) return;
        const [s] = book.pages.splice(nr, 1);
        book.pages.splice(target, 0, s);
        nr = target;
        drawPageBar();
        try {
            await request(E.urls.order, 'POST', { ids: book.pages.map((x) => x.id) });
        } catch (e) {
            status('error', e.message);
        }
    }

    // ---- Buch-Einstellungen / Book settings --------------------------------------

    let titleTimer;
    $('ed-title').addEventListener('input', (e) => {
        clearTimeout(titleTimer);
        titleTimer = setTimeout(() => changeBook({ title: e.target.value }).catch(() => {}), 600);
    });
    $('ed-title').addEventListener('keydown', (e) => { if (e.key === 'Enter') e.target.blur(); });

    async function changeBook(data) {
        try {
            status('saving');
            const r = await request(E.urls.book, 'PATCH', data);
            if (r && r.book) {
                book.sharing = r.book.sharing;
                if (data.title !== undefined) document.title = r.book.title + ' · BilDaBooky';
            }
            status('ok');
        } catch (e) {
            status('error', e.message);
            throw e;
        }
    }

    const bookDialog = $('ed-book-dialog');
    $('ed-settings').addEventListener('click', () => bookDialog.showModal());
    bookDialog.querySelectorAll('input[name=ed-format]').forEach((r) => r.addEventListener('change', () => {
        format = r.value;
        changeBook({ format }).catch(() => {});
        draw();
        drawPageBar();
    }));
    // Zwei Knöpfe zum Teilen: mit der Lehrkraft / mit der Klasse (nochmal tippen = aufheben)
    // Two buttons for sharing: with the teacher / with the class (tap again = undo)
    const share = $('ed-share');
    function showShare() {
        const f = share.dataset.sharing;
        share.querySelectorAll('[data-level]').forEach((b) => {
            const on = f === b.dataset.level || (b.dataset.level === 'teacher' && f === 'class');
            b.classList.toggle('on', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.disabled = b.dataset.level === 'teacher' && f === 'class';
        });
    }
    if (share) {
        showShare();
        share.addEventListener('click', async (e) => {
            const b = e.target.closest('[data-level]');
            if (!b) return;
            const target = share.dataset.sharing === b.dataset.level ? 'private' : b.dataset.level;
            // Lehrkraft hebt das Teilen eines Kinderbuchs auf → erst nachfragen
            // Teacher removes the sharing of a child's book → ask first
            if (target === 'private' && R.othersBook
                && !confirm(t('Das Teilen von „:titel“ (von :name) wirklich aufheben?', { title: $('ed-title').value, name: E.owner || '' }))) return;
            await changeBook({ sharing: target });
            share.dataset.sharing = target;
            showShare();
        });
    }
    const subjectSelect = $('ed-subject');
    if (subjectSelect) subjectSelect.addEventListener('change', () => changeBook({ subject_id: subjectSelect.value ? +subjectSelect.value : null }).catch(() => {}));
    const classSelect = $('ed-class');
    if (classSelect) classSelect.addEventListener('change', () => changeBook({ school_class_id: classSelect.value ? +classSelect.value : null }));

    // Rückgabe durch die Lehrkraft: Hinweis oben, je Rückgabe einmal wegklickbar
    // Given back by the teacher: notice at the top, can be dismissed once per give-back
    const returned = $('ed-returned');
    if (returned) {
        const storeKey = 'bdb-returned-' + returned.dataset.key;
        let dismissed = false;
        dismissed = BDB.stored(storeKey, 'bw-rueckgabe-' + returned.dataset.key) === '1';   // alter Schlüssel / old key
        if (dismissed) returned.remove();
        else returned.querySelector('.ed-returned-close').addEventListener('click', () => {
            try { localStorage.setItem(storeKey, '1'); } catch (e) { /* egal / ignore */ }
            returned.remove();
        });
    }

    // Version einer Vorlage abgeben — Knopf oben neben „Lesen“
    // Hand in a version of a template — button at the top next to "Lesen" (Read)
    const submitButton = $('ed-submit');
    function showSubmit() {
        const at = submitButton.dataset.submitted;
        submitButton.classList.toggle('submitted', !!at);
        submitButton.querySelector('.ed-submit-symbol').textContent = at ? '📬' : '✅';
        submitButton.querySelector('.ed-submit-word').textContent = at ? t('Abgegeben') : t('Abgeben');
        // Erklärung für Tooltip, langes Drücken und Erklär-Modus
        // Explanation for tooltip, long press and explain mode
        submitButton.dataset.tip = at ? t('Abgegeben') : t('Abgeben');
        submitButton.setAttribute('aria-label', at ? t('Abgegeben am :datum. Antippen, um die Abgabe zurückzunehmen.', { datum: at }) : t('Abgeben'));
        if (window.Symbols) window.Symbols.swap(submitButton);
    }
    if (submitButton) {
        showSubmit();
        submitButton.addEventListener('click', async () => {
            const at = submitButton.dataset.submitted;
            const question = at
                ? t('Abgabe zurücknehmen? Deine Lehrkraft sieht dann wieder, dass du noch arbeitest.')
                : t('Buch jetzt abgeben? Deine Lehrkraft sieht dann, dass du fertig bist. Du kannst danach trotzdem weiterarbeiten.');
            if (!confirm(question)) return;
            try {
                await saveAll();
                const r = await request(E.urls.submit, 'POST', { done: !at });
                submitButton.dataset.submitted = r.submitted || '';
                showSubmit();
                if (r.submitted) { fox('submitted'); if (returned) returned.remove(); }
            } catch (e) { alert(e.message); }
        });
    }

    // ---- Werkzeuge und Tasten / Tools and keys ---------------------------------------

    document.querySelector('.ed-tools').addEventListener('click', (e) => {
        const b = e.target.closest('[data-tool]');
        if (!b || painting) return;
        if (editing) finishText();
        ({
            text: newText,
            photo: () => $('ed-file-photo').click(),
            camera: () => $('ed-file-camera').click(),
            paint: startPainting,
            audio: recordAudio,
            sticker: stickerPicker,
            shape: newShape,
            background: backgroundPicker,
        })[b.dataset.tool]();
    });
    ['ed-file-photo', 'ed-file-camera'].forEach((id) => $(id).addEventListener('change', (e) => {
        newPhoto(e.target.files[0]);
        e.target.value = '';
    }));
    undoBtn.addEventListener('click', undo);

    document.addEventListener('keydown', (e) => {
        const inField = e.target.closest('input, textarea, select, [contenteditable]');
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !inField) { e.preventDefault(); undo(); return; }
        if (inField || painting || dialog.open || bookDialog.open) return;
        if ((e.key === 'Delete' || e.key === 'Backspace') && selected) { e.preventDefault(); remove(); return; }
        if (e.key === 'Escape' && selected) { selected = null; draw(); return; }
        if (e.key === 'Enter' && selected && element(selected).type === 'text') { e.preventDefault(); editText(selected); return; }
        const arrows = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
        if (arrows[e.key] && selected) {
            e.preventDefault();
            const s = e.shiftKey ? 20 : 4;
            change((x) => { x.x += arrows[e.key][0] * s; x.y += arrows[e.key][1] * s; });
        }
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => { if (!editing && !painting && !drag) draw(); }, 150);
    });

    // ---- Start -------------------------------------------------------------------
    book.pages.forEach((x) => baseOf.set(x.id, { b: x.background, e: JSON.parse(JSON.stringify(x.elements)) }));
    // ---- Erklärungen zu Knöpfen / Explanations for buttons ---------------------
    // Drei Wege zur selben Erklär-Blase (Name fett, kurze Erklärung, ggf. Taste):
    //  · PC: mit der Maus draufzeigen (Tooltip)
    //  · Tablet/Handy: Knopf lange drücken — loslassen löst dann nichts aus
    //  · überall: ❓ oben schaltet den Erklär-Modus ein; jeder angetippte Knopf
    //    erklärt sich, statt etwas zu tun
    // Quelle ist das title-Attribut (wird übernommen, damit kein zweiter
    // Browser-Hinweis kommt).
    // Three ways to the same explanation bubble (name in bold, short explanation, key if any):
    //  · PC: hover with the mouse (tooltip)
    //  · Tablet/phone: long-press the button — releasing then triggers nothing
    //  · everywhere: ❓ at the top switches on explain mode; every tapped button
    //    explains itself instead of doing something
    // The source is the title attribute (it is taken over so that no second
    // browser hint appears).
    (function explanations() {
        const MOUSE = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        const MAC = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
        const key = (k) => (MAC ? k.replace('Strg+', '⌘').replace('Entf', '⌫') : k);
        const E = {};   // Titel → { name, text, taste } / title → { name, text, taste }
        const explain = (title, text, k, name) => { E[title] = { name: name || title, text, key: k }; };
        explain(t('Erklär-Modus'), t('Danach erklärt jeder Knopf, was er macht, statt es zu tun. Nochmal antippen zum Beenden.'));
        explain(t('Zur Bibliothek'), t('Zurück zu allen Büchern. Alles ist schon gespeichert.'));
        explain(t('Rückgängig (Strg+Z)'), t('Den letzten Schritt zurücknehmen.'), 'Strg+Z', t('Rückgängig'));
        explain(t('Buch-Einstellungen'), t('Seitenformat, Klasse und mehr.'));
        explain(t('Abgeben'), t('Fertig? Dann gib dein Buch ab. Deine Lehrkraft sieht es in ihrer Übersicht.'));
        explain(t('Abgegeben'), t('Deine Lehrkraft weiß, dass du fertig bist. Antippen nimmt die Abgabe zurück.'));
        explain(t('Änderungsprotokoll'), t('Wer hat wann was geändert?'));
        explain(t('Gerade dabei'), t('Diese Kinder arbeiten gerade mit an diesem Buch.'));
        explain(t('Lesen'), t('Das Buch Seite für Seite ansehen und anhören.'));
        explain(t('Text'), t('Ein Textfeld auf die Seite setzen. Zweimal antippen, um zu schreiben.'));
        explain(t('Foto'), t('Ein Bild von diesem Gerät einfügen.'));
        explain(t('Kamera'), t('Mit der Kamera ein Foto machen und einfügen.'));
        explain(t('Malen'), t('Mit Maus oder Finger auf der Seite zeichnen.'));
        explain(t('Ton'), t('Etwas mit dem Mikrofon aufnehmen – zum Anhören im Buch.'));
        explain(t('Sticker'), t('Einen von rund 900 Stickern aufkleben.'));
        explain(t('Form'), t('Ein Rechteck oder einen Kreis einfügen, z. B. als Hintergrund für Text.'));
        explain(t('Seite'), t('Die Hintergrundfarbe dieser Seite wählen.'));
        explain(t('Drehen'), t('Ziehen, um zu drehen. Gerade Winkel rasten ein.'));
        explain(t('Größe'), t('Ziehen, um es größer oder kleiner zu machen.'));
        explain(t('Kleiner'), t('Schrift kleiner machen.'));
        explain(t('Größer'), t('Schrift größer machen.'));
        explain(t('Fett'), t('Dicke Buchstaben.'));
        explain(t('Schräg'), t('Schräge Buchstaben.'));
        explain(t('Links'), t('Text links ausrichten.'));
        explain(t('Mitte'), t('Text in die Mitte setzen.'));
        explain(t('Rechts'), t('Text rechts ausrichten.'));
        explain(t('Keine'), t('Ohne Farbe dahinter.'));
        explain(t('Abgerundet'), t('Rechteck mit runden Ecken.'));
        explain(t('Seite nach vorne'), t('Diese Seite eine Stelle nach vorne schieben.'));
        explain(t('Seite nach hinten'), t('Diese Seite eine Stelle nach hinten schieben.'));
        explain(t('Seite doppeln'), t('Eine Kopie dieser Seite dahinter einfügen.'));
        explain(t('Seite löschen'), t('Diese Seite aus dem Buch entfernen.'));
        explain(t('Neue Seite'), t('Eine leere Seite ans Ende anfügen.'));
        explain(t('Radierer'), t('Striche wegradieren.'));
        explain(t('Rückgängig'), t('Den letzten Strich zurücknehmen.'));
        explain(t('Anhören'), t('Die Aufnahme abspielen. Oder den Lautsprecher nochmal antippen.'));
        explain(t('Spiegeln'), t('Das Bild seitenverkehrt zeigen.'));
        explain(t('Löschen'), t('Nimmt das ausgewählte Teil von der Seite.'), 'Entf');
        const COLOUR_NAMES = {
            '#26324a': t('Dunkelblau'), '#ffffff': t('Weiß'), '#e63946': t('Rot'), '#ff7a3d': t('Orange'), '#ffd166': t('Gelb'),
            '#2a9d8f': t('Türkis'), '#3d7bff': t('Blau'), '#8e5cf7': t('Lila'), '#ff6fb5': t('Rosa'), '#7a4b2a': t('Braun'),
            '#8d99ae': t('Grau'), '#000000': t('Schwarz'),
        };

        const bubble = document.createElement('div');
        bubble.className = 'ed-tip';
        bubble.setAttribute('role', 'tooltip');
        bubble.hidden = true;
        let target = null, timer = null, recent = 0, hideTimer = null;
        const explainButton = $('ed-explain');
        let mode = false;

        /**
         * Element mit Erklärung zum Ziel; title wird dabei übernommen
         *
         * Element with an explanation for the target; title is taken over
         */
        function tipElement(start) {
            const el = start && start.closest && start.closest('[title], [data-tip]');
            if (!el) return null;
            if (el.hasAttribute('title')) {
                el.dataset.tip = el.title;
                el.removeAttribute('title');
            }
            return el;
        }

        function content(el, explicit) {
            const title = el.dataset.tip;
            if (!title) return null;
            if (COLOUR_NAMES[title]) return { name: COLOUR_NAMES[title], colour: title };
            if (/^#[0-9a-f]{6}$/i.test(title)) return explicit ? { name: t('Farbe'), colour: title } : null;
            const e = E[title];
            if (e) return e;
            // sichtbare Erklärung im Knopf (z. B. „Anordnen“) mitnehmen
            // take along the visible explanation in the button (e.g. "Anordnen")
            const small = el.querySelector('.ed-b-text small');
            if (explicit && small) return { name: title, text: small.textContent };
            // beim bloßen Draufzeigen: steht die Beschriftung schon da, kein Tooltip
            // on mere hover: if the label is already shown, no tooltip
            if (!explicit && (el.textContent || '').includes(title)) return null;
            return { name: title };
        }

        function show(el, explicit) {
            const i = content(el, explicit);
            if (!i || !el.isConnected) return false;
            bubble.textContent = '';
            const header = document.createElement('b');
            if (i.colour) {
                const f = document.createElement('i');
                f.className = 'ed-tip-colour';
                f.style.background = i.colour;
                header.appendChild(f);
            }
            header.appendChild(document.createTextNode(i.name));
            if (i.key && MOUSE) {
                const k = document.createElement('kbd');
                k.textContent = key(i.key);
                header.appendChild(k);
            }
            bubble.appendChild(header);
            if (i.text) {
                const p = document.createElement('span');
                p.textContent = i.text;
                bubble.appendChild(p);
            }
            // in offenen Dialogen muss die Blase im Dialog liegen (oberste Ebene)
            // in open dialogs the bubble must lie inside the dialog (top layer)
            (el.closest('dialog[open]') || document.body).appendChild(bubble);
            bubble.hidden = false;
            bubble.classList.toggle('large', !MOUSE);
            const r = el.getBoundingClientRect();
            const b = bubble.getBoundingClientRect();
            const right = el.closest('.ed-tools') && window.innerWidth > 760;
            let x, y;
            if (right) {
                x = r.right + 10; y = r.top + r.height / 2 - b.height / 2;
                bubble.dataset.side = 'right';
            } else {
                // am Finger lieber über dem Knopf, damit die Hand sie nicht verdeckt
                // under a finger rather above the button so the hand does not cover it
                let above = !MOUSE || r.bottom + b.height + 18 > window.innerHeight;
                if (above && r.top - b.height - 10 < 8) above = false;
                x = r.left + r.width / 2 - b.width / 2;
                y = above ? r.top - b.height - 10 : r.bottom + 10;
                bubble.dataset.side = above ? 'above' : 'below';
            }
            x = Math.max(8, Math.min(x, window.innerWidth - b.width - 8));
            y = Math.max(8, Math.min(y, window.innerHeight - b.height - 8));
            bubble.style.left = x + 'px';
            bubble.style.top = y + 'px';
            bubble.classList.add('visible');
            try { localStorage.setItem('bdb-explained', '1'); } catch (e) { /* egal / ignore */ }
            return true;
        }

        function hide() {
            clearTimeout(timer);
            clearTimeout(hideTimer);
            if (!bubble.hidden) recent = Date.now();
            timer = null;
            target = null;
            bubble.hidden = true;
            bubble.classList.remove('visible');
        }

        // ausdrücklich erfragte Erklärung bleibt eine Weile stehen
        // an explicitly requested explanation stays for a while
        function showFixed(el) {
            hide();
            if (show(el, true)) hideTimer = setTimeout(hide, 4500);
        }

        // 1) PC: Tooltip beim Draufzeigen
        // 1) PC: tooltip on hover
        if (MOUSE) {
            document.addEventListener('pointerover', (e) => {
                if (e.pointerType !== 'mouse' || e.buttons || mode) return;
                const el = tipElement(e.target);
                if (!el || el === target) return;
                hide();
                target = el;
                // nach dem ersten Tooltip kommen die nächsten sofort
                // after the first tooltip the next ones come immediately
                timer = setTimeout(() => show(el, false), Date.now() - recent < 700 ? 40 : 450);
            });
            document.addEventListener('pointerout', (e) => {
                if (target && !mode && !target.contains(e.relatedTarget)) hide();
            });
            document.addEventListener('wheel', hide, true);
        }

        // 2) Tablet/Handy: lange drücken — loslassen löst dann nichts aus
        // 2) Tablet/phone: long press — releasing then triggers nothing
        let press = null, swallowClick = false;
        document.addEventListener('pointerdown', (e) => {
            if (!mode) { hide(); recent = 0; }
            if (e.pointerType === 'mouse' || mode) return;
            const el = tipElement(e.target);
            if (!el) return;
            press = { el, x: e.clientX, y: e.clientY, id: e.pointerId };
            press.timer = setTimeout(() => {
                if (press && show(el, true)) {
                    swallowClick = true;
                    press.shown = true;
                }
            }, 500);
        }, true);
        document.addEventListener('pointermove', (e) => {
            if (press && e.pointerId === press.id && Math.hypot(e.clientX - press.x, e.clientY - press.y) > 10) {
                clearTimeout(press.timer);
                if (press.shown) { hide(); swallowClick = false; }
                press = null;
            }
        }, true);
        const pressEnd = () => {
            if (!press) return;
            clearTimeout(press.timer);
            if (press.shown) {
                hideTimer = setTimeout(hide, 2500);
                // falls kein click folgt, beim nächsten Tippen nicht versehentlich schlucken
                // if no click follows, do not accidentally swallow the next tap
                setTimeout(() => { swallowClick = false; }, 400);
            }
            press = null;
        };
        document.addEventListener('pointerup', pressEnd, true);
        document.addEventListener('pointercancel', pressEnd, true);
        document.addEventListener('click', (e) => {
            if (swallowClick) { swallowClick = false; e.preventDefault(); e.stopPropagation(); }
        }, true);
        // kein Kontextmenü beim langen Drücken auf Knöpfe
        // no context menu on a long press on buttons
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('[data-tip], [title], button')) e.preventDefault();
        });

        // 3) Erklär-Modus über ❓ — Knöpfe erklären sich, statt auszulösen
        // 3) Explain mode via ❓ — buttons explain themselves instead of triggering
        const hint = document.createElement('div');
        hint.className = 'ed-explain-hint';
        hint.setAttribute('role', 'status');
        hint.hidden = true;
        hint.textContent = t('Erklär-Modus: Tippe auf einen Knopf, dann erkläre ich ihn. Zum Beenden ❓ antippen.');
        document.body.appendChild(hint);
        function setMode(on) {
            mode = on;
            document.body.classList.toggle('ed-explain-mode', on);
            explainButton.setAttribute('aria-pressed', on ? 'true' : 'false');
            explainButton.classList.toggle('on', on);
            hint.hidden = !on;
            hide();
        }
        explainButton.addEventListener('click', (e) => {
            if (swallowClick) return;
            e.stopPropagation();
            setMode(!mode);
        });
        const intercept = (e) => {
            if (!mode || e.target.closest('#ed-explain')) return;
            const el = tipElement(e.target);
            if (!el) return;
            e.preventDefault();
            e.stopPropagation();
            if (e.type === 'click') showFixed(el);
        };
        ['pointerdown', 'click'].forEach((kind) => document.addEventListener(kind, intercept, true));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mode) { e.stopPropagation(); setMode(false); return; }
            if (!mode) hide();
        }, true);
        window.addEventListener('scroll', () => { if (!mode) hide(); }, true);
        window.addEventListener('blur', hide);

        // Der Fuchs verrät es einmal (je Gerät), solange noch nie eine Erklärung kam
        // The fox reveals it once (per device) as long as no explanation has ever come
        let alreadyExplained = false;
        alreadyExplained = BDB.stored('bdb-explained', 'bw_erklaert') === '1';   // alter Schlüssel / old key
        if (!alreadyExplained) setTimeout(() => fox(MOUSE ? 'explain_mouse' : 'explain_touch'), 40000);
    })();

    nr = Math.max(0, Math.min(book.pages.length - 1, (parseInt(location.hash.slice(1), 10) || 1) - 1));
    drawPageBar();
    draw();
    status('ok');
})();
