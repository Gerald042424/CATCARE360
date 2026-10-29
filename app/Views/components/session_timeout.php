<?php
$sessionTimeoutIdleSeconds = (int) ($idleSeconds ?? 1800);
$sessionTimeoutWarningSeconds = (int) ($warningSeconds ?? 120);
$sessionTimeoutAbsoluteSeconds = (int) ($absoluteSeconds ?? 28800);
$sessionTimeoutStartedAt = (int) (session('auth_session_started_at') ?: time());
?>
<div id="session-timeout-dialog" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="session-timeout-title">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <h2 id="session-timeout-title" class="text-xl font-bold text-slate-900">Your session is about to expire</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">For your account safety, you will be signed out in <strong id="session-timeout-countdown">2:00</strong> because of inactivity.</p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= esc($logoutUrl, 'attr') ?>?reason=inactive" class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign out now</a>
            <button id="session-timeout-continue" type="button" class="rounded-lg bg-rescue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rescue-700">Stay signed in</button>
        </div>
    </div>
</div>
<script>
(() => {
    const idleMs = <?= json_encode($sessionTimeoutIdleSeconds * 1000) ?>;
    const warningMs = <?= json_encode($sessionTimeoutWarningSeconds * 1000) ?>;
    const absoluteExpiresAt = <?= json_encode(($sessionTimeoutStartedAt + $sessionTimeoutAbsoluteSeconds) * 1000) ?>;
    const keepAliveUrl = <?= json_encode($keepAliveUrl) ?>;
    const logoutUrl = <?= json_encode($logoutUrl . '?reason=inactive') ?>;
    const dialog = document.getElementById('session-timeout-dialog');
    const countdown = document.getElementById('session-timeout-countdown');
    const continueButton = document.getElementById('session-timeout-continue');
    const keepAliveThrottleMs = 5 * 60 * 1000;
    let lastInteractionAt = Date.now();
    let lastKeepAliveAt = Date.now();
    let requestInFlight = false;
    let keepAliveController = null;
    let countdownTimerId = null;
    let signingOut = false;

    const showDialog = () => {
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
    };
    const hideDialog = () => {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
    };
    const remainingSessionMs = () => {
        const now = Date.now();
        return Math.min(idleMs - (now - lastInteractionAt), absoluteExpiresAt - now);
    };
    const signOut = () => {
        if (signingOut) return;

        signingOut = true;
        if (countdownTimerId !== null) window.clearInterval(countdownTimerId);
        if (keepAliveController !== null) keepAliveController.abort();
        countdown.textContent = '0:00';
        continueButton.disabled = true;
        continueButton.textContent = 'Signing out…';
        showDialog();

        // replace() prevents returning to an expired authenticated page and the
        // guard above prevents repeated redirects while logout is still loading.
        window.location.replace(logoutUrl);
    };

    async function keepAlive(force = false) {
        const now = Date.now();
        if (signingOut || requestInFlight || (!force && now - lastKeepAliveAt < keepAliveThrottleMs)) return;
        requestInFlight = true;
        keepAliveController = new AbortController();
        try {
            const response = await fetch(keepAliveUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: keepAliveController.signal,
            });
            if (response.status === 401) return signOut();
            if (!response.ok) throw new Error('Unable to refresh session');
            lastKeepAliveAt = Date.now();
            lastInteractionAt = lastKeepAliveAt;
            hideDialog();
        } catch (error) {
            if (!signingOut && error.name !== 'AbortError') {
                console.warn('Session refresh failed; the next request will verify the session.', error);
            }
        } finally {
            requestInFlight = false;
            keepAliveController = null;
        }
    }

    function recordInteraction() {
        if (signingOut) return;
        if (remainingSessionMs() <= 0) return signOut();

        lastInteractionAt = Date.now();
        hideDialog();
        void keepAlive(false);
    }

    ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach((eventName) => {
        window.addEventListener(eventName, recordInteraction, { passive: true });
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) recordInteraction();
    });
    continueButton.addEventListener('click', () => void keepAlive(true));

    countdownTimerId = window.setInterval(() => {
        const remaining = remainingSessionMs();
        if (remaining <= 0) return signOut();
        if (remaining <= warningMs) {
            const seconds = Math.max(0, Math.ceil(remaining / 1000));
            countdown.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
            showDialog();
        }
    }, 1000);
})();
</script>
