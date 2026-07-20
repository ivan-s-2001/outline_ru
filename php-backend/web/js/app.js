(() => {
  "use strict";

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.checkValidity()) {
      return;
    }

    const submitter = event.submitter;
    if (submitter instanceof HTMLButtonElement) {
      submitter.disabled = true;
      submitter.dataset.originalText = submitter.textContent || "";
      submitter.textContent = "Подождите…";
    }
  });
})();
