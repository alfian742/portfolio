(function () {
  "use strict";

  let forms = document.querySelectorAll('.php-email-form');

  forms.forEach(function (e) {
    e.addEventListener('submit', function (event) {
      event.preventDefault();
      let thisForm = this;

      let action = thisForm.getAttribute('action');
      if (!action) {
        displayGlobalError(thisForm, 'Form action belum diatur.');
        return;
      }

      thisForm.querySelector('.loading').classList.add('d-block');
      thisForm.querySelector('.error-message').classList.remove('d-block');
      thisForm.querySelector('.sent-message').classList.remove('d-block');
      clearFieldErrors(thisForm);

      let formData = new FormData(thisForm);

      fetch(action, {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          thisForm.querySelector('.loading').classList.remove('d-block');

          if (data.status === 'success') {
            thisForm.querySelector('.sent-message').innerHTML = data.message;
            thisForm.querySelector('.sent-message').classList.add('d-block');
            thisForm.reset();
          } else if (data.status === 'validation_error') {
            displayGlobalError(thisForm, data.message);

            if (data.errors) {
              for (let field in data.errors) {
                let input = thisForm.querySelector(`[name="${field}"]`);
                if (input) {
                  input.classList.add('is-invalid');
                  let feedback = input.parentElement.querySelector('.invalid-feedback');
                  if (feedback) {
                    feedback.innerHTML = data.errors[field];
                  }
                } else if (field === 'captcha') {
                  const captchaError = thisForm.querySelector('.captcha-error');
                  if (captchaError) {
                    captchaError.innerHTML = data.errors[field];
                    captchaError.style.display = 'block';
                  }
                }
              }
            }
          } else {
            displayGlobalError(thisForm, data.message || 'Terjadi kesalahan.');
          }
        })
        .catch(error => {
          thisForm.querySelector('.loading').classList.remove('d-block');
          displayGlobalError(thisForm, 'Terjadi kesalahan saat mengirimkan data.');
          console.error('Submit error:', error);
        });
    });
  });

  function displayGlobalError(form, message) {
    const errorDiv = form.querySelector('.error-message');
    errorDiv.innerHTML = message;
    errorDiv.classList.add('d-block');
  }

  function clearFieldErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.innerHTML = '');
    const captchaError = form.querySelector('.captcha-error');
    if (captchaError) captchaError.style.display = 'none';
  }
})();
