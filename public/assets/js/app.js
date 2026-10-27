// Progressive enhancement only: every page works without JavaScript.
(() => {
  'use strict';

  // Ask before destructive actions: <form data-confirm="Are you sure?">
  document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
      event.preventDefault();
      return;
    }
    // Prevent double submission (e.g. placing an order twice): <button data-once>
    const button = form.querySelector('button[data-once]');
    if (button) {
      if (button.dataset.submitted) {
        event.preventDefault();
        return;
      }
      button.dataset.submitted = '1';
      button.setAttribute('aria-disabled', 'true');
      button.textContent = 'Placing order…';
    }
  });

  // Submit the sort dropdown as soon as it changes: <select data-autosubmit>
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
