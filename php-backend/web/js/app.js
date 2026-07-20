(() => {
  "use strict";

  const initializeAccessSubjectPicker = () => {
    const typeSelect = document.querySelector("#access-subject-type");
    const subjectSelect = document.querySelector("#access-subject-id");
    if (!(typeSelect instanceof HTMLSelectElement) || !(subjectSelect instanceof HTMLSelectElement)) {
      return;
    }

    const synchronize = () => {
      const selectedType = typeSelect.value;
      for (const option of subjectSelect.options) {
        const optionType = option.dataset.subjectType;
        option.disabled = Boolean(optionType && optionType !== selectedType);
      }

      const selectedOption = subjectSelect.selectedOptions[0];
      if (selectedOption?.dataset.subjectType && selectedOption.dataset.subjectType !== selectedType) {
        subjectSelect.value = "";
      }
    };

    typeSelect.addEventListener("change", synchronize);
    synchronize();
  };

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

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeAccessSubjectPicker, { once: true });
  } else {
    initializeAccessSubjectPicker();
  }
})();
