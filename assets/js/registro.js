(function () {
	'use strict';

	var config = window.FutbolFestRegistro || {};
	var form = document.querySelector('[data-futbolfest-registro-form]');
	var card = form ? form.closest('.registro-card, .registro-qr-card') : null;
	var section = form ? form.closest('.registro-section, .registro-qr-section') : null;
	var confirmation = document.querySelector('[data-futbolfest-registro-confirmacion]');
	var reset = document.querySelector('[data-futbolfest-registro-reset]');
	var close = document.querySelector('[data-futbolfest-registro-close]');
	var confirmationName = document.querySelector('[data-futbolfest-registro-nombre]');

	if (!form || !config.ajaxUrl || !config.nonce) {
		return;
	}

	var message = form.querySelector('[data-futbolfest-registro-message]');
	var submit = form.querySelector('button[type="submit"]');
	var formRenderedAt = Math.floor(Date.now() / 1000);

	function setMessage(text, type) {
		if (!message) {
			return;
		}

		message.textContent = text || '';
		message.dataset.state = type || '';
	}

	function setLoading(isLoading) {
		if (!submit) {
			return;
		}

		submit.disabled = isLoading;
		submit.setAttribute('aria-busy', isLoading ? 'true' : 'false');
	}

	function showConfirmation() {
		if (!card || !confirmation) {
			return;
		}

		confirmation.hidden = false;
		if (section) {
			section.dataset.registroState = 'confirmacion';
		}
		document.body.classList.add('futbolfest-modal-open');
		confirmation.focus({ preventScroll: true });
	}

	function setConfirmationName(name) {
		if (!confirmationName) {
			return;
		}

		confirmationName.textContent = name ? name.trim().toUpperCase() : 'CRACK';
	}

	function showForm() {
		if (!card || !confirmation) {
			return;
		}

		confirmation.hidden = true;
		card.hidden = false;
		if (section) {
			section.dataset.registroState = 'formulario';
		}
		document.body.classList.remove('futbolfest-modal-open');
		setMessage('', '');
		card.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();

		if (!form.checkValidity()) {
			form.reportValidity();
			return;
		}

		var payload = new FormData(form);
		var submittedName = payload.get('nombre');
		payload.append('action', 'futbolfest_registro_submit');
		payload.append('nonce', config.nonce);
		payload.append('form_rendered_at', formRenderedAt);

		setLoading(true);
		setMessage('Guardando registro...', 'loading');

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (result) {
				var text = result && result.data && result.data.message
					? result.data.message
					: 'No pudimos procesar tu registro.';

				if (!result || !result.success) {
					setMessage(text, 'error');
					return;
				}

				form.reset();
				setMessage(text, 'success');
				setConfirmationName(submittedName);
				showConfirmation();
			})
			.catch(function () {
				setMessage('No pudimos conectar con el servidor. Inténtalo nuevamente.', 'error');
			})
			.finally(function () {
				setLoading(false);
			});
	});

	if (reset) {
		reset.addEventListener('click', function () {
			form.reset();
			showForm();
		});
	}

	if (close) {
		close.addEventListener('click', function () {
			form.reset();
			showForm();
		});
	}
}());
