(function () {
    'use strict';

    var body = document.body;
    var scope = body.getAttribute('data-session-scope');
    if (!scope) {
        return; // Heartbeat only runs on logged-in admin or unlocked gated pages.
    }

    var statusUrl = body.getAttribute('data-session-status-url');
    var actionUrl = body.getAttribute('data-session-action-url');
    var intervalMs = parseInt(body.getAttribute('data-session-heartbeat-ms'), 10) || 120000;
    var turnstileSiteKey = body.getAttribute('data-turnstile-sitekey') || '';
    var isAdmin = (scope === 'admin');

    var modal = null;
    var shown = false;
    var turnstileWidgetId = null;

    // ---------------------------------------------------------------------
    // Heartbeat
    // ---------------------------------------------------------------------
    function check() {
        if (shown) {
            return;
        }
        fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.alive) {
                    showModal(data.csrf || '');
                }
            })
            .catch(function () { /* network hiccup — try again next tick */ });
    }

    // ---------------------------------------------------------------------
    // Re-login modal
    // ---------------------------------------------------------------------
    function buildModal() {
        var overlay = document.createElement('div');
        overlay.className = 'session-modal';
        overlay.innerHTML =
            '<div class="session-modal__backdrop"></div>' +
            '<div class="session-modal__dialog" role="dialog" aria-modal="true" aria-label="Session expired">' +
                '<div class="session-modal__icon" aria-hidden="true">\uD83D\uDD12</div>' +
                '<h2 class="session-modal__title">Session expired</h2>' +
                '<p class="session-modal__intro">' +
                    (isAdmin
                        ? 'You were signed out due to inactivity. Sign in to continue.'
                        : 'This page locked after a period of inactivity. Re-enter the password to continue.') +
                '</p>' +
                '<p class="session-modal__error" hidden></p>' +
                '<form class="session-modal__form">' +
                    '<input type="hidden" name="_csrf" value="">' +
                    (isAdmin
                        ? '<input type="email" name="email" placeholder="Email" autocomplete="username" required>'
                        : '') +
                    '<input type="password" name="password" placeholder="Password" autocomplete="current-password" required>' +
                    '<div class="session-modal__turnstile"></div>' +
                    '<button class="btn btn--primary" type="submit">Unlock</button>' +
                '</form>' +
            '</div>';
        document.body.appendChild(overlay);

        overlay.querySelector('.session-modal__form').addEventListener('submit', onSubmit);
        return overlay;
    }

    function renderTurnstile() {
        if (!turnstileSiteKey || !window.turnstile) {
            return;
        }
        var holder = modal.querySelector('.session-modal__turnstile');
        if (turnstileWidgetId !== null) {
            window.turnstile.reset(turnstileWidgetId);
        } else {
            turnstileWidgetId = window.turnstile.render(holder, {
                sitekey: turnstileSiteKey,
                theme: 'light',
                size: 'flexible'
            });
        }
    }

    function showModal(csrf) {
        if (shown) {
            return;
        }
        shown = true;
        if (!modal) {
            modal = buildModal();
        }
        modal.querySelector('input[name="_csrf"]').value = csrf;
        modal.hidden = false;
        modal.classList.add('is-open');

        // Turnstile may still be loading; retry briefly until it's ready.
        var tries = 0;
        (function tryRender() {
            if (!turnstileSiteKey || window.turnstile) {
                renderTurnstile();
            } else if (tries++ < 20) {
                setTimeout(tryRender, 150);
            }
        })();

        var first = modal.querySelector('input[name="email"]') || modal.querySelector('input[name="password"]');
        setTimeout(function () { first.focus(); }, 50);
    }

    function setError(msg) {
        var el = modal.querySelector('.session-modal__error');
        el.textContent = msg;
        el.hidden = !msg;
    }

    function onSubmit(e) {
        e.preventDefault();
        setError('');

        var form = e.target;
        var btn = form.querySelector('button');
        btn.disabled = true;

        // FormData captures every field, including the cf-turnstile-response
        // input that Turnstile injects into the form.
        var data = new URLSearchParams(new FormData(form));

        // The login/gate endpoints redirect rather than returning JSON, so we
        // submit, then re-query the status endpoint to learn if we're back in.
        fetch(actionUrl, {
            method: 'POST',
            body: data,
            headers: { 'Accept': 'text/html' },
            credentials: 'same-origin'
        })
            .then(function () {
                return fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.alive) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    setError(isAdmin ? 'Incorrect email or password.' : 'Incorrect password.');
                    form.querySelector('input[name="_csrf"]').value = res.csrf || '';
                    if (turnstileWidgetId !== null && window.turnstile) {
                        window.turnstile.reset(turnstileWidgetId);
                    }
                }
            })
            .catch(function () {
                btn.disabled = false;
                setError('Something went wrong. Please try again.');
                if (turnstileWidgetId !== null && window.turnstile) {
                    window.turnstile.reset(turnstileWidgetId);
                }
            });
    }

    setInterval(check, intervalMs);
})();
