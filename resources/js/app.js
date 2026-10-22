// Global duplicate-submission guard: disables submit controls while a form
// request is in flight, and restores them on back/forward-cache restore.
// Skips forms that already had their submission cancelled (e.g. a dismissed
// confirm() dialog via onsubmit="return confirm(...)") since defaultPrevented
// is already set by the time this bubble-phase listener runs.
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;

    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-busy')) return;

    form.querySelectorAll('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])').forEach((control) => {
        const isButton = control.tagName === 'BUTTON';
        control.dataset.busyOriginal = isButton ? control.innerHTML : control.value;
        const busyText = control.dataset.busyText || 'Processing…';
        if (isButton) {
            control.innerHTML = busyText;
        } else {
            control.value = busyText;
        }
        control.disabled = true;
        control.setAttribute('aria-busy', 'true');
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-busy-original]').forEach((control) => {
        const isButton = control.tagName === 'BUTTON';
        if (isButton) {
            control.innerHTML = control.dataset.busyOriginal;
        } else {
            control.value = control.dataset.busyOriginal;
        }
        delete control.dataset.busyOriginal;
        control.disabled = false;
        control.removeAttribute('aria-busy');
    });
});
