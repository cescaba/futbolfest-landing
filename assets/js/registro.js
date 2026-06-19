(function () {
	'use strict';

	var config = window.FutbolFestRegistro || {};
	var forms = document.querySelectorAll('[data-futbolfest-registro-form]');

	if (!forms.length || !config.ajaxUrl || !config.nonce) {
		return;
	}

	function initRegistroForm(form) {
		var card = form.closest('.registro-card, .registro-qr-card');
		var section = form.closest('.registro-section, .registro-qr-section');
		var confirmation = section ? section.querySelector('[data-futbolfest-registro-confirmacion]') : null;
		var reset = confirmation ? confirmation.querySelector('[data-futbolfest-registro-reset]') : null;
		var close = confirmation ? confirmation.querySelector('[data-futbolfest-registro-close]') : null;
		var confirmationName = confirmation ? confirmation.querySelector('[data-futbolfest-registro-nombre]') : null;
		var message = form.querySelector('[data-futbolfest-registro-message]');
		var submit = form.querySelector('button[type="submit"]');
		var kidsPanel = form.querySelector('[data-registro-kids-panel]');
		var kidsList = form.querySelector('[data-registro-kids-list]');
		var kidsAdd = form.querySelector('[data-registro-kids-add]');
		var kidsTemplate = kidsList && kidsList.querySelector('[data-registro-kid-row]')
			? kidsList.querySelector('[data-registro-kid-row]').cloneNode(true)
			: null;
		var formRenderedAt = Math.floor(Date.now() / 1000);
		var maxKids = 10;

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

		function getKidRows() {
			return kidsList ? Array.prototype.slice.call(kidsList.querySelectorAll('[data-registro-kid-row]')) : [];
		}

		function setKidRowState(row, isEnabled) {
			row.querySelectorAll('input, select').forEach(function (field) {
				field.disabled = !isEnabled;
				field.required = isEnabled;

				if (!isEnabled) {
					field.value = '';
				}
			});
		}

		function refreshKidRemoveButtons() {
			var rows = getKidRows();

			rows.forEach(function (row, index) {
				var remove = row.querySelector('[data-registro-kids-remove]');
				var title = row.querySelector('[data-registro-kid-title]');

				if (title) {
					title.textContent = 'Niño ' + (index + 1);
				}

				if (remove) {
					remove.hidden = rows.length <= 1;
				}
			});

			if (kidsAdd) {
				kidsAdd.disabled = rows.length >= maxKids;
			}
		}

		function addKidRow() {
			if (!kidsList || !kidsTemplate || getKidRows().length >= maxKids) {
				return;
			}

			var row = kidsTemplate.cloneNode(true);

			row.querySelectorAll('input, select').forEach(function (field) {
				field.value = '';
				field.disabled = false;
				field.required = true;
			});

			kidsList.appendChild(row);
			refreshKidRemoveButtons();
		}

		function resetKidsPanel() {
			if (!kidsPanel || !kidsList || !kidsTemplate) {
				return;
			}

			getKidRows().forEach(function (row) {
				row.remove();
			});

			var row = kidsTemplate.cloneNode(true);
			setKidRowState(row, false);
			kidsList.appendChild(row);
			kidsPanel.hidden = true;
			refreshKidRemoveButtons();
		}

		function setKidsPanelVisibility(value) {
			var hasKids = value === '1';

			if (!kidsPanel || !kidsTemplate) {
				return;
			}

			kidsPanel.hidden = !hasKids;

			if (!getKidRows().length) {
				kidsList.appendChild(kidsTemplate.cloneNode(true));
			}

			getKidRows().forEach(function (row) {
				setKidRowState(row, hasKids);
			});

			refreshKidRemoveButtons();
		}

		function showConfirmation() {
			if (!confirmation) {
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
					resetKidsPanel();
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
				resetKidsPanel();
				showForm();
			});
		}

		if (close) {
			close.addEventListener('click', function () {
				form.reset();
				resetKidsPanel();
				showForm();
			});
		}

		form.querySelectorAll('input[name="ninos"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				setKidsPanelVisibility(radio.value);
			});
		});

		if (kidsAdd) {
			kidsAdd.addEventListener('click', addKidRow);
		}

		if (kidsList) {
			kidsList.addEventListener('click', function (event) {
				var remove = event.target.closest('[data-registro-kids-remove]');

				if (!remove || getKidRows().length <= 1) {
					return;
				}

				remove.closest('[data-registro-kid-row]').remove();
				refreshKidRemoveButtons();
			});
		}

		resetKidsPanel();
	}

	forms.forEach(initRegistroForm);
}());
