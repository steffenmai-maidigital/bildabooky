{{-- Lehrkraft: abgegebene Version ans Kind zurückgeben (Knöpfe mit data-zurueckgeben öffnen den Dialog)

     Teacher: give a handed-in version back to the pupil (buttons with data-zurueckgeben open the dialog) --}}
<dialog id="return-dialog" class="return-dialog">
    <form method="post" action="">
        @csrf
        <h2 style="margin-top:0">↩️ {{ __('Zurückgeben') }}</h2>
        <p class="rd-question"></p>
        <label for="rd-note">{{ __('Nachricht an das Kind (freiwillig)') }}</label>
        <textarea id="rd-note" name="note" rows="3" maxlength="500" placeholder="{{ __('z. B. Auf Seite 3 fehlt noch ein Bild.') }}"></textarea>
        <p class="hint">{{ __('Das Kind sieht die Nachricht im Buch, bis es wieder abgibt. Die Rückgabe steht im Änderungsprotokoll.') }}</p>
        <div class="actions">
            <button type="button" onclick="this.closest('dialog').close()">{{ __('Abbrechen') }}</button>
            <button class="primary">↩️ {{ __('Zurückgeben') }}</button>
        </div>
    </form>
</dialog>
<script>
    (function () {
        const d = document.getElementById('return-dialog');
        document.addEventListener('click', (e) => {
            const b = e.target.closest('[data-return]');
            if (!b) return;
            e.preventDefault();
            d.querySelector('form').action = b.dataset.return;
            d.querySelector('.rd-question').textContent = @json(__(':name kann danach weiterarbeiten und das Buch erneut abgeben.')).replace(':name', b.dataset.name || '');
            d.querySelector('textarea').value = '';
            d.showModal();
            d.querySelector('textarea').focus();
        });
    })();
</script>
