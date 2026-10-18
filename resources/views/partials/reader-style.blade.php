    <style>
        html, body { margin: 0; height: 100%; }
        body.reader-body { background: #2b2f3a; color: #fff; font-family: "Avenir Next", "Segoe UI", system-ui, sans-serif; overflow: hidden; }
        .reader { position: fixed; inset: 0; display: flex; flex-direction: column; }
        .reader-header, .reader-footer { display: flex; align-items: center; gap: .6rem; padding: .5rem .8rem; }
        .reader-header { justify-content: space-between; }
        .reader-header b { font-size: 1.05rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reader-footer { justify-content: center; gap: 1.2rem; padding-bottom: max(.6rem, env(safe-area-inset-bottom)); }
        .reader a, .reader button { all: unset; cursor: pointer; background: rgba(255,255,255,.12); color: #fff; border-radius: 999px;
            min-width: 48px; height: 48px; padding: 0 1rem; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
        .reader button.page-turn { width: 64px; height: 64px; font-size: 2rem; background: var(--accent, #ff7a3d); color: var(--accent-ink, #fff); }
        .reader-submit { display: inline-flex; margin: 0; }
        .reader .reader-return { background: rgba(255,209,102,.22); box-shadow: inset 0 0 0 2px #ffd166; }
        .reader-returned { margin: 0 .8rem .4rem; padding: .5rem .8rem; border-radius: 14px; background: #fff3cd; color: #5c4400; font-size: .95rem; line-height: 1.35; }
        .reader-returned span { display: block; margin-top: .15rem; overflow-wrap: anywhere; }
        .reader .reader-submit button { background: #2a9d8f; }
        .reader .reader-submit button.submitted { background: rgba(42,157,143,.25); box-shadow: inset 0 0 0 2px #2a9d8f; }
        .reader button:disabled { opacity: .3; cursor: default; }
        .reader-stage { flex: 1; display: flex; align-items: center; justify-content: center; min-height: 0; touch-action: pan-y; }
        .reader-page { box-shadow: 0 10px 40px rgba(0,0,0,.45); border-radius: 6px; }
        .enter-right { animation: enter-r .28s ease-out; } .enter-left { animation: enter-l .28s ease-out; }
        @keyframes enter-r { from { transform: translateX(40px); opacity: 0; } }
        @keyframes enter-l { from { transform: translateX(-40px); opacity: 0; } }
        #reader-counter { min-width: 5rem; text-align: center; font-variant-numeric: tabular-nums; }
    </style>
