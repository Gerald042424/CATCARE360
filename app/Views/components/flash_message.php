<?php
$flashError = session('error');
$flashSuccess = session('success');
$flashMessage = $flashError ?: $flashSuccess;
$flashIsError = (bool) $flashError;
$flashTop = isset($top) ? (string) $top : '1rem';
?>
<?php if ($flashMessage): ?>
<div data-flash-message
     role="<?= $flashIsError ? 'alert' : 'status' ?>"
     aria-live="<?= $flashIsError ? 'assertive' : 'polite' ?>"
     class="fixed right-4 z-[250] flex w-[calc(100%-2rem)] max-w-md items-center gap-3 rounded-xl border bg-white px-4 py-3 text-sm font-semibold shadow-lg <?= $flashIsError ? 'border-red-200 text-red-700' : 'border-adopted-200 text-adopted-700' ?>"
     style="top:<?= esc($flashTop, 'attr') ?>;transition:opacity .25s ease,transform .25s ease">
    <i data-lucide="<?= $flashIsError ? 'alert-circle' : 'check-circle-2' ?>" class="h-5 w-5 shrink-0 <?= $flashIsError ? 'text-red-500' : 'text-adopted-600' ?>"></i>
    <span class="min-w-0 flex-1"><?= esc($flashMessage) ?></span>
    <button type="button" data-dismiss-flash class="-mr-1 rounded-lg p-1 text-current opacity-60 transition hover:bg-slate-100 hover:opacity-100" aria-label="Dismiss notification">
        <i data-lucide="x" class="h-4 w-4"></i>
    </button>
</div>
<script>
(function (script) {
    var message = script.previousElementSibling;
    if (!message || !message.matches('[data-flash-message]')) return;
    var dismissed = false;
    function dismiss() {
        if (dismissed) return;
        dismissed = true;
        message.style.opacity = '0';
        message.style.transform = 'translateY(-8px)';
        window.setTimeout(function () { message.remove(); }, 250);
    }
    var closeButton = message.querySelector('[data-dismiss-flash]');
    if (closeButton) closeButton.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 5000);
})(document.currentScript);
</script>
<?php endif; ?>
