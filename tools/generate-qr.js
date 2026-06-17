const fs = require('fs');
const path = require('path');

const TARGET_URL = 'https://futbolfestx.com/formulario-registro/';
const OUT_FILE = path.join(__dirname, '..', 'assets', 'images', 'qr-futbolfestx.svg');

const VERSION = 5;
const SIZE = VERSION * 4 + 17;
const ERROR_LEVEL_H_BITS = 2;
const DATA_CODEWORDS = 46;
const ECC_CODEWORDS_PER_BLOCK = 22;
const DATA_BLOCK_SIZES = [11, 11, 12, 12];
const QUIET = 4;
const SCALE = 10;

function appendBits(value, length, bits) {
	for (let i = length - 1; i >= 0; i--) {
		bits.push((value >>> i) & 1);
	}
}

function createDataCodewords(text) {
	const bytes = Array.from(Buffer.from(text, 'utf8'));
	if (bytes.length > 44) {
		throw new Error('Version 5-H only supports up to 44 bytes for this generator.');
	}

	const bits = [];
	appendBits(0x4, 4, bits);
	appendBits(bytes.length, 8, bits);
	for (const b of bytes) {
		appendBits(b, 8, bits);
	}

	const capacityBits = DATA_CODEWORDS * 8;
	const terminator = Math.min(4, capacityBits - bits.length);
	appendBits(0, terminator, bits);
	while (bits.length % 8 !== 0) {
		bits.push(0);
	}

	const pads = [0xec, 0x11];
	let padIndex = 0;
	while (bits.length < capacityBits) {
		appendBits(pads[padIndex % 2], 8, bits);
		padIndex++;
	}

	const result = [];
	for (let i = 0; i < bits.length; i += 8) {
		let b = 0;
		for (let j = 0; j < 8; j++) {
			b = (b << 1) | bits[i + j];
		}
		result.push(b);
	}
	return result;
}

const EXP = new Array(512);
const LOG = new Array(256);

function initGalois() {
	let x = 1;
	for (let i = 0; i < 255; i++) {
		EXP[i] = x;
		LOG[x] = i;
		x <<= 1;
		if (x & 0x100) {
			x ^= 0x11d;
		}
	}
	for (let i = 255; i < 512; i++) {
		EXP[i] = EXP[i - 255];
	}
}

function multiply(a, b) {
	return a === 0 || b === 0 ? 0 : EXP[LOG[a] + LOG[b]];
}

function reedSolomonDivisor(degree) {
	let result = new Array(degree).fill(0);
	result[degree - 1] = 1;
	let root = 1;

	for (let i = 0; i < degree; i++) {
		for (let j = 0; j < result.length; j++) {
			result[j] = multiply(result[j], root);
			if (j + 1 < result.length) {
				result[j] ^= result[j + 1];
			}
		}
		root = multiply(root, 0x02);
	}
	return result;
}

function reedSolomonRemainder(data, divisor) {
	const result = new Array(divisor.length).fill(0);
	for (const b of data) {
		const factor = b ^ result.shift();
		result.push(0);
		for (let i = 0; i < result.length; i++) {
			result[i] ^= multiply(divisor[i], factor);
		}
	}
	return result;
}

function buildCodewords(dataCodewords) {
	const divisor = reedSolomonDivisor(ECC_CODEWORDS_PER_BLOCK);
	const blocks = [];
	let offset = 0;

	for (const blockSize of DATA_BLOCK_SIZES) {
		const data = dataCodewords.slice(offset, offset + blockSize);
		offset += blockSize;
		blocks.push({
			data,
			ecc: reedSolomonRemainder(data, divisor),
		});
	}

	const codewords = [];
	const maxDataBlockSize = Math.max(...DATA_BLOCK_SIZES);
	for (let i = 0; i < maxDataBlockSize; i++) {
		for (const block of blocks) {
			if (i < block.data.length) {
				codewords.push(block.data[i]);
			}
		}
	}
	for (let i = 0; i < ECC_CODEWORDS_PER_BLOCK; i++) {
		for (const block of blocks) {
			codewords.push(block.ecc[i]);
		}
	}
	return codewords;
}

function blankQr() {
	return {
		modules: Array.from({ length: SIZE }, () => new Array(SIZE).fill(false)),
		isFunction: Array.from({ length: SIZE }, () => new Array(SIZE).fill(false)),
	};
}

function setModule(qr, x, y, dark, isFunction = true) {
	if (x < 0 || y < 0 || x >= SIZE || y >= SIZE) {
		return;
	}
	qr.modules[y][x] = Boolean(dark);
	if (isFunction) {
		qr.isFunction[y][x] = true;
	}
}

function drawFinder(qr, x, y) {
	for (let dy = -1; dy <= 7; dy++) {
		for (let dx = -1; dx <= 7; dx++) {
			const xx = x + dx;
			const yy = y + dy;
			const dark = dx >= 0 && dx <= 6 && dy >= 0 && dy <= 6
				&& (dx === 0 || dx === 6 || dy === 0 || dy === 6
					|| (dx >= 2 && dx <= 4 && dy >= 2 && dy <= 4));
			setModule(qr, xx, yy, dark);
		}
	}
}

function drawAlignment(qr, cx, cy) {
	for (let dy = -2; dy <= 2; dy++) {
		for (let dx = -2; dx <= 2; dx++) {
			const dist = Math.max(Math.abs(dx), Math.abs(dy));
			setModule(qr, cx + dx, cy + dy, dist !== 1);
		}
	}
}

function reserveFormat(qr) {
	for (let i = 0; i < 9; i++) {
		if (i !== 6) {
			setModule(qr, 8, i, false);
			setModule(qr, i, 8, false);
		}
	}
	for (let i = 0; i < 8; i++) {
		setModule(qr, SIZE - 1 - i, 8, false);
		setModule(qr, 8, SIZE - 1 - i, false);
	}
}

function drawFunctionPatterns(qr) {
	drawFinder(qr, 0, 0);
	drawFinder(qr, SIZE - 7, 0);
	drawFinder(qr, 0, SIZE - 7);
	drawAlignment(qr, 30, 30);

	for (let i = 0; i < SIZE; i++) {
		if (!qr.isFunction[6][i]) {
			setModule(qr, i, 6, i % 2 === 0);
		}
		if (!qr.isFunction[i][6]) {
			setModule(qr, 6, i, i % 2 === 0);
		}
	}

	setModule(qr, 8, SIZE - 8, true);
	reserveFormat(qr);
}

function maskBit(mask, x, y) {
	switch (mask) {
		case 0: return (x + y) % 2 === 0;
		case 1: return y % 2 === 0;
		case 2: return x % 3 === 0;
		case 3: return (x + y) % 3 === 0;
		case 4: return (Math.floor(y / 2) + Math.floor(x / 3)) % 2 === 0;
		case 5: return ((x * y) % 2 + (x * y) % 3) === 0;
		case 6: return (((x * y) % 2 + (x * y) % 3) % 2) === 0;
		case 7: return (((x + y) % 2 + (x * y) % 3) % 2) === 0;
		default: throw new Error('Invalid mask.');
	}
}

function addCodewords(qr, codewords, mask) {
	const bits = [];
	for (const cw of codewords) {
		appendBits(cw, 8, bits);
	}

	let bitIndex = 0;
	let upward = true;
	for (let right = SIZE - 1; right >= 1; right -= 2) {
		if (right === 6) {
			right--;
		}
		for (let vert = 0; vert < SIZE; vert++) {
			const y = upward ? SIZE - 1 - vert : vert;
			for (let j = 0; j < 2; j++) {
				const x = right - j;
				if (!qr.isFunction[y][x]) {
					const bit = bitIndex < bits.length ? bits[bitIndex] : 0;
					bitIndex++;
					qr.modules[y][x] = Boolean(bit) !== maskBit(mask, x, y);
				}
			}
		}
		upward = !upward;
	}
}

function formatBits(mask) {
	const data = (ERROR_LEVEL_H_BITS << 3) | mask;
	let rem = data;
	for (let i = 0; i < 10; i++) {
		rem = (rem << 1) ^ (((rem >>> 9) & 1) * 0x537);
	}
	return ((data << 10) | rem) ^ 0x5412;
}

function getBit(value, index) {
	return ((value >>> index) & 1) !== 0;
}

function drawFormat(qr, mask) {
	const bits = formatBits(mask);

	for (let i = 0; i <= 5; i++) {
		setModule(qr, 8, i, getBit(bits, i));
	}
	setModule(qr, 8, 7, getBit(bits, 6));
	setModule(qr, 8, 8, getBit(bits, 7));
	setModule(qr, 7, 8, getBit(bits, 8));
	for (let i = 9; i < 15; i++) {
		setModule(qr, 14 - i, 8, getBit(bits, i));
	}

	for (let i = 0; i < 8; i++) {
		setModule(qr, SIZE - 1 - i, 8, getBit(bits, i));
	}
	for (let i = 8; i < 15; i++) {
		setModule(qr, 8, SIZE - 15 + i, getBit(bits, i));
	}
	setModule(qr, 8, SIZE - 8, true);
}

function penalty(qr) {
	let result = 0;

	for (let y = 0; y < SIZE; y++) {
		let runColor = qr.modules[y][0];
		let runLength = 1;
		for (let x = 1; x < SIZE; x++) {
			if (qr.modules[y][x] === runColor) {
				runLength++;
				if (runLength === 5) result += 3;
				else if (runLength > 5) result++;
			} else {
				runColor = qr.modules[y][x];
				runLength = 1;
			}
		}
	}

	for (let x = 0; x < SIZE; x++) {
		let runColor = qr.modules[0][x];
		let runLength = 1;
		for (let y = 1; y < SIZE; y++) {
			if (qr.modules[y][x] === runColor) {
				runLength++;
				if (runLength === 5) result += 3;
				else if (runLength > 5) result++;
			} else {
				runColor = qr.modules[y][x];
				runLength = 1;
			}
		}
	}

	for (let y = 0; y < SIZE - 1; y++) {
		for (let x = 0; x < SIZE - 1; x++) {
			const c = qr.modules[y][x];
			if (c === qr.modules[y][x + 1] && c === qr.modules[y + 1][x] && c === qr.modules[y + 1][x + 1]) {
				result += 3;
			}
		}
	}

	const patternA = [true, false, true, true, true, false, true, false, false, false, false];
	const patternB = [false, false, false, false, true, false, true, true, true, false, true];
	for (let y = 0; y < SIZE; y++) {
		for (let x = 0; x <= SIZE - 11; x++) {
			const row = Array.from({ length: 11 }, (_, i) => qr.modules[y][x + i]);
			if (row.every((v, i) => v === patternA[i]) || row.every((v, i) => v === patternB[i])) {
				result += 40;
			}
		}
	}
	for (let x = 0; x < SIZE; x++) {
		for (let y = 0; y <= SIZE - 11; y++) {
			const col = Array.from({ length: 11 }, (_, i) => qr.modules[y + i][x]);
			if (col.every((v, i) => v === patternA[i]) || col.every((v, i) => v === patternB[i])) {
				result += 40;
			}
		}
	}

	let dark = 0;
	for (let y = 0; y < SIZE; y++) {
		for (let x = 0; x < SIZE; x++) {
			if (qr.modules[y][x]) dark++;
		}
	}
	const total = SIZE * SIZE;
	const k = Math.ceil(Math.abs(dark * 20 - total * 10) / total) - 1;
	result += k * 10;

	return result;
}

function makeQr(mask) {
	const qr = blankQr();
	drawFunctionPatterns(qr);
	addCodewords(qr, buildCodewords(createDataCodewords(TARGET_URL)), mask);
	drawFormat(qr, mask);
	return qr;
}

function chooseQr() {
	let best = null;
	let bestPenalty = Infinity;
	for (let mask = 0; mask < 8; mask++) {
		const qr = makeQr(mask);
		const score = penalty(qr);
		if (score < bestPenalty) {
			best = qr;
			bestPenalty = score;
		}
	}
	return best;
}

function svgFor(qr) {
	const canvasModules = SIZE + QUIET * 2;
	const canvasSize = canvasModules * SCALE;
	const pathParts = [];

	for (let y = 0; y < SIZE; y++) {
		for (let x = 0; x < SIZE; x++) {
			if (qr.modules[y][x]) {
				pathParts.push(`M${(x + QUIET) * SCALE},${(y + QUIET) * SCALE}h${SCALE}v${SCALE}h-${SCALE}z`);
			}
		}
	}

	const c = canvasSize / 2;
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${canvasSize}" height="${canvasSize}" viewBox="0 0 ${canvasSize} ${canvasSize}" role="img" aria-label="QR de registro Futbol Fest X">
	<metadata>Encoded URL: ${TARGET_URL}</metadata>
	<rect width="100%" height="100%" fill="#FFFFFF"/>
	<path fill="#0D1B4B" d="${pathParts.join(' ')}"/>
	<g aria-hidden="true">
		<circle cx="${c}" cy="${c}" r="38" fill="#FFFFFF"/>
		<circle cx="${c}" cy="${c}" r="30" fill="#FFFFFF" stroke="#0D1B4B" stroke-width="4"/>
		<polygon points="${c},${c - 13} ${c + 13},${c - 4} ${c + 8},${c + 12} ${c - 8},${c + 12} ${c - 13},${c - 4}" fill="#0D1B4B"/>
		<path d="M${c - 8} ${c - 27}L${c} ${c - 13}L${c + 8} ${c - 27}M${c + 29} ${c - 5}L${c + 13} ${c - 4}L${c + 25} ${c + 13}M${c + 18} ${c + 24}L${c + 8} ${c + 12}L${c - 8} ${c + 12}L${c - 18} ${c + 24}M${c - 25} ${c + 13}L${c - 13} ${c - 4}L${c - 29} ${c - 5}" fill="none" stroke="#0D1B4B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
	</g>
</svg>
`;
}

initGalois();
fs.writeFileSync(OUT_FILE, svgFor(chooseQr()), 'utf8');
console.log(`QR generado: ${OUT_FILE}`);
