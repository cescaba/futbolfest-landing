# Registro QR - CSS de referencia

Esta nota junta los estilos que usa `parts/registro-qr.html` para llevarlos a un proyecto separado.

## Estructura que usa el HTML

```html
<section id="registro-qr" class="general-section registro-section registro-qr-section">
	<div class="registro-card registro-qr-card">
		<div class="registro-content">...</div>
		<div class="registro-visual">
			<img src="/wp-content/themes/futbolfest-landing/assets/images/Containerqr.png" alt="Futbol Fest registro QR">
		</div>
	</div>

	<!-- REGISTRO CONFIRMACION -->
	<!-- wp:template-part {"slug":"registro-confirmacion"} /-->
</section>
```

## CSS base del template QR

```css
.futbolfest-registro-qr-page {
	min-height: 100vh;
	background: #F5F7FC;
}

.registro-qr-section {
	min-height: auto;
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 32px 20px;
	background: #F5F7FC;
}

.futbolfest-registro-qr-page .registro-qr-section {
	min-height: 100vh;
}

.registro-qr-card[hidden] {
	display: none;
}

.registro-qr-section:has(.registro-confirmacion:not([hidden])) {
	background-color: #F5F7FC;
}
```

## CSS compartido del formulario

```css
.registro-heading {
	display: flex;
	flex-direction: column;
	font-family: 'Barlow Condensed';
	font-weight: 900;
	font-size: 40px;
	line-height: 40px;
	letter-spacing: 0;
	margin-bottom: 10px;
}

.registro-heading-main {
	color: #000000;
}

.registro-heading-accent {
	color: #152CA7;
}

.registro-description {
	color: #5A6A9A;
	font-family: 'Barlow';
	font-weight: 400;
	font-size: 13px;
	line-height: 28px;
	margin-bottom: 24px;
}

.registro-card {
	width: 100%;
	min-height: 507px;
	background: #ffffff;
	border: 1px solid rgba(26, 63, 160, 0.08);
	border-radius: 16px;
	box-shadow: 0 40px 80px 8px rgba(13, 27, 75, 0.10);
	display: grid;
	grid-template-columns: 1fr 1fr;
	align-items: stretch;
	overflow: hidden;
}

.registro-card[hidden] {
	display: none;
}

.registro-content {
	min-width: 0;
	padding: 32px;
}

.registro-card-header {
	display: flex;
	align-items: center;
	gap: 10px;
	color: #F5A200;
	font-family: 'Barlow';
	font-weight: 700;
	font-size: 12px;
	margin-bottom: 10px;
	line-height: 17px;
	letter-spacing: 2px;
}

.registro-card-icon {
	width: 14px;
	height: 14px;
	flex-shrink: 0;
	color: #F5A200;
}

.registro-form {
	display: flex;
	flex-direction: column;
	gap: 0;
}

.registro-honeypot {
	position: absolute;
	left: -9999px;
	width: 1px;
	height: 1px;
	overflow: hidden;
	opacity: 0;
	pointer-events: none;
}

.registro-form-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px;
	margin-bottom: 20px;
}

.registro-field {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin: 0;
}

.registro-field-full {
	grid-column: 1 / -1;
}

.registro-field span {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: #0D1B4B;
	font-family: 'Barlow';
	font-weight: 700;
	font-size: 12px;
	line-height: 12px;
	letter-spacing: 0;
}

.registro-field-icon {
	width: 12px;
	height: 12px;
	flex-shrink: 0;
	color: #F5A200;
}

.registro-field input {
	width: 100%;
	height: 44px;
	padding: 4px 12px;
	background: #F5F7FC;
	border: 1px solid rgba(26, 63, 160, 0.18);
	border-radius: 11px;
	color: #5A6A9A;
	font-family: 'Barlow';
	font-size: 14px;
	line-height: 17px;
	letter-spacing: 0;
	outline: none;
	transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.registro-field input:focus {
	border-color: rgba(26, 63, 160, 0.45);
	box-shadow: 0 0 0 4px rgba(26, 63, 160, 0.08);
}

.btn.btn-primary.registro-submit {
	width: 100%;
	height: 46px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 10px;
	gap: 8px;
	padding: 12px 0;
	background-color: #152CA7;
	border-radius: 14px;
	color: #FFFFFF;
	font-family: 'Barlow';
	font-weight: 800;
	font-size: 16px;
	line-height: 23px;
	letter-spacing: 1px;
	text-align: center;
}

.btn.btn-primary.registro-submit:disabled {
	cursor: not-allowed;
	opacity: 0.65;
	transform: none;
}

.registro-submit-icon {
	width: 24px;
	height: 16px;
	flex-shrink: 0;
	color: currentColor;
}

.registro-visual {
	width: 100%;
	height: 100%;
	min-height: 507px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #ffffff;
}

.registro-visual img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: 13% center;
	border-radius: 0;
}

.registro-footer {
	color: #9CA3AF;
	font-family: 'Barlow';
	font-weight: 400;
	font-size: 12px;
	line-height: 17px;
	letter-spacing: 0;
	text-align: center;
}

.registro-message {
	display: none;
	margin: 0;
	font-family: 'Barlow';
	font-weight: 600;
	font-size: 12px;
	line-height: 18px;
	text-align: center;
}

.registro-message:not(:empty) {
	display: block;
	margin-top: 8px;
}

.registro-message[data-state="success"] {
	color: #15803d;
}

.registro-message[data-state="error"] {
	color: #dc2626;
}

.registro-message[data-state="loading"] {
	color: #5A6A9A;
}
```

## Boton base necesario

```css
.btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 0.75rem 1.25rem;
	border-radius: 0.5rem;
	font-weight: 600;
	text-decoration: none;
	border: none;
	cursor: pointer;
	transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
	font-size: 0.95rem;
}

.btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.btn:disabled,
.btn[aria-disabled="true"] {
	cursor: not-allowed;
	opacity: 0.65;
	transform: none;
	box-shadow: none;
}
```

## Responsive 768px

```css
@media (max-width: 768px) {
	.registro-card {
		grid-template-columns: 1fr;
		min-height: auto;
	}

	.registro-visual {
		display: none;
	}

	.futbolfest-registro-qr-page .registro-qr-section {
		min-height: 100svh;
		padding: 32px 24px;
	}

	.registro-qr-card {
		width: 100%;
		max-width: 560px;
	}
}
```

## Responsive 430px

```css
@media (max-width: 430px) {
	.registro-qr-section {
		padding-top: 48px;
		padding-bottom: 48px;
	}

	.futbolfest-registro-qr-page .registro-qr-section {
		min-height: 100svh;
		padding-top: 32px;
		padding-bottom: 32px;
	}

	.registro-heading {
		font-family: 'Barlow Condensed';
		font-weight: 900;
		font-size: 29px;
		line-height: 29px;
		letter-spacing: 0;
	}

	.registro-description {
		font-family: 'Barlow';
		font-weight: 400;
		font-size: 13px;
		line-height: 20px;
		letter-spacing: 0;
	}

	.registro-form-grid {
		grid-template-columns: 1fr;
	}

	.registro-field span {
		font-family: 'Barlow';
		font-weight: 700;
		font-size: 12px;
		line-height: 12px;
		letter-spacing: 0;
	}

	.registro-field input {
		width: 100%;
		height: 40px;
		padding-right: 12px;
		padding-left: 12px;
		border-radius: 11px;
		border-width: 1px;
		opacity: 1;
		font-family: 'Barlow';
		font-weight: 400;
		font-size: 15px;
		line-height: 100%;
		letter-spacing: 0;
	}

	.btn.btn-primary.registro-submit {
		width: 100%;
		height: 47px;
		padding-top: 12px;
		padding-bottom: 12px;
		gap: 8px;
		border-radius: 14px;
		opacity: 1;
		font-family: 'Barlow Condensed';
		font-weight: 800;
		font-size: 16px;
		line-height: 23px;
		letter-spacing: 1px;
	}

	.registro-footer {
		font-family: 'Barlow';
		font-weight: 400;
		font-size: 12px;
		line-height: 17px;
		letter-spacing: 0;
		text-align: center;
	}

	.registro-card {
		grid-template-columns: 1fr;
		min-height: auto;
	}

	.registro-qr-card {
		width: 100%;
		max-width: 342px;
	}

	.registro-content {
		padding: 24px;
	}

	.registro-visual {
		display: none;
	}
}
```

## Modal de confirmacion usado por QR

```css
body.futbolfest-modal-open {
	overflow: hidden;
}

.registro-confirmacion[hidden] {
	display: none;
}

.registro-confirmacion {
	width: 512px;
	min-height: 576px;
	max-height: calc(100svh - 32px);
	background: #ffffff;
	border-radius: 20px;
	box-shadow: 0 0 0 100vmax #00000080, 0 40px 80px 8px rgba(13, 27, 75, 0.18);
	display: flex;
	flex-direction: column;
	text-align: center;
	outline: none;
	overflow: auto;
	position: fixed;
	top: 50%;
	left: 50%;
	z-index: 1000;
	transform: translate(-50%, -50%);
}

.registro-confirmacion-hero {
	width: 100%;
	min-height: 192px;
	position: relative;
	background-image: url('/wp-content/themes/futbolfest-landing/assets/images/SuccessModal.png');
	background-size: cover;
	background-position: center;
	background-repeat: no-repeat;
	flex-shrink: 0;
}

.registro-confirmacion-close {
	width: 23px;
	height: 23px;
	position: absolute;
	top: 10px;
	right: 10px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 0;
	border: 0;
	background: transparent;
	color: #ffffff;
	cursor: pointer;
}

.registro-confirmacion-close svg {
	width: 23px;
	height: 23px;
	display: block;
}

.registro-confirmacion-body {
	width: 100%;
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: flex-start;
	padding: 24px;
}

.registro-confirmacion-label {
	margin: 0 0 10px;
	color: #0D1B4B;
	font-family: 'Barlow Condensed';
	font-weight: 900;
	font-size: 26px;
	line-height: 26px;
	letter-spacing: 0;
}

.registro-confirmacion-mensaje {
	margin: 0 0 10px;
	color: #152CA7;
	font-family: 'Barlow Condensed';
	font-weight: 700;
	font-size: 16px;
	line-height: 23px;
	letter-spacing: 0;
}

.registro-confirmacion-title {
	width: 100%;
	max-width: 270px;
	margin: 0 0 20px;
	font-family: 'Barlow';
	font-weight: 400;
	font-size: 14px;
	line-height: 21px;
	letter-spacing: 0;
	color: #5A6A9A;
}

.registro-confirmacion-title-strong {
	color: #0D1B4B;
	font-family: 'Barlow';
	font-weight: 700;
	font-size: 14px;
	line-height: 21px;
	letter-spacing: 0;
}

.registro-confirmacion-separador {
	width: 60px;
	height: 18px;
	object-fit: contain;
	margin-bottom: 12px;
	flex-shrink: 0;
}

.btn.btn-primary.registro-confirmacion-reset {
	width: 100%;
	height: 47px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 13px;
	background-color: #152CA7;
	border-radius: 14px;
	color: #FFFFFF;
	font-family: 'Barlow Condensed';
	font-weight: 800;
	font-size: 16px;
	line-height: 23px;
	letter-spacing: 1px;
	text-align: center;
	opacity: 1;
}

.registro-confirmacion-location {
	width: 100%;
	height: 75px;
	border-radius: 14px;
	display: flex;
	border: 1px solid #152CA71A;
	padding: 12px 16px;
	background-color: #F5F7FC;
	align-items: center;
	justify-content: center;
	gap: 12px;
	margin-bottom: 24px;
	text-align: left;
}

.registro-confirmacion-location-icon {
	width: 18px;
	height: 27px;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	font-size: 18px;
	line-height: 27px;
}

.registro-confirmacion-location-content {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.registro-confirmacion-location-label,
.registro-confirmacion-location-place,
.registro-confirmacion-location-date {
	margin: 0;
	font-family: 'Barlow';
	letter-spacing: 0;
}

.registro-confirmacion-location-label {
	color: #5A6A9A;
	font-weight: 400;
	font-size: 12px;
	line-height: 12px;
}

.registro-confirmacion-location-place {
	color: #0D1B4B;
	font-weight: 700;
	font-size: 13px;
	line-height: 20px;
}

.registro-confirmacion-location-date {
	color: #152CA7;
	font-weight: 600;
	font-size: 12px;
	line-height: 17px;
}
```

## Responsive del modal en 430px

```css
@media (max-width: 430px) {
	.registro-confirmacion {
		width: 100%;
		min-width: 0;
		max-width: 342px;
		min-height: auto;
		border-radius: 20px;
		box-shadow: 0 0 0 100vmax #00000080, 0 32px 64px 4px rgba(13, 27, 75, 0.18);
	}

	.registro-confirmacion-hero {
		min-height: 150px;
		background-size: cover;
		background-position: center;
	}

	.registro-confirmacion-body {
		padding: 20px;
	}

	.registro-confirmacion-separador {
		width: 48px;
		height: 14px;
		margin-bottom: 10px;
	}

	.registro-confirmacion-label {
		margin-bottom: 8px;
		font-size: 23px;
		line-height: 24px;
	}

	.registro-confirmacion-mensaje {
		margin-bottom: 8px;
		font-size: 15px;
		line-height: 21px;
	}

	.registro-confirmacion-title {
		max-width: 260px;
		margin-bottom: 16px;
		font-size: 13px;
		line-height: 20px;
	}

	.registro-confirmacion-title-strong {
		font-size: 13px;
		line-height: 20px;
	}

	.registro-confirmacion-location {
		height: auto;
		min-height: 72px;
		margin-bottom: 18px;
		padding: 12px;
		justify-content: flex-start;
		box-sizing: border-box;
	}

	.registro-confirmacion-location-content {
		min-width: 0;
	}

	.registro-confirmacion-location-place,
	.registro-confirmacion-location-date {
		overflow-wrap: anywhere;
	}

	.btn.btn-primary.registro-confirmacion-reset {
		width: 100%;
		height: 47px;
		font-size: 15px;
		line-height: 23px;
	}
}
```

