/**
 * Generates the demo listing photos in demo/images/ as flat-style SVG
 * illustrations rendered to JPEG with Playwright. Everything is original
 * artwork, so the repository ships its own sample media (no hotlinking).
 *
 * Usage: npm run images
 */
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const OUT = join( ROOT, 'demo', 'images' );
const W = 1500;
const H = 1000;

/* ---------- helpers ---------- */

function rng( seed ) {
	let s = seed >>> 0;
	return () => {
		s = ( s * 1664525 + 1013904223 ) >>> 0;
		return s / 4294967296;
	};
}

const shade = ( hex, amt ) => {
	const n = parseInt( hex.slice( 1 ), 16 );
	const c = [ n >> 16, ( n >> 8 ) & 255, n & 255 ].map( ( v ) =>
		Math.max( 0, Math.min( 255, Math.round( amt < 0 ? v * ( 1 + amt ) : v + ( 255 - v ) * amt ) ) )
	);
	return `#${ c.map( ( v ) => v.toString( 16 ).padStart( 2, '0' ) ).join( '' ) }`;
};

const SKIES = {
	day: [ '#8ec5e8', '#d7ecf7', '#fdf6e9' ],
	golden: [ '#f4b886', '#f9d9b0', '#fdf0dc' ],
	dusk: [ '#7f8fc7', '#e2a6b4', '#f8d6c2' ],
	clear: [ '#5fa8d9', '#a9d4ef', '#eef7fb' ],
};

function sky( kind, r ) {
	const [ a, b, c ] = SKIES[ kind ];
	let clouds = '';
	for ( let i = 0; i < 4; i++ ) {
		const x = r() * W;
		const y = 60 + r() * 220;
		const s = 0.6 + r() * 0.8;
		clouds += `<g opacity="0.75" transform="translate(${ x } ${ y }) scale(${ s })">
			<ellipse cx="0" cy="0" rx="90" ry="26" fill="#fff"/>
			<ellipse cx="40" cy="-14" rx="55" ry="30" fill="#fff"/>
			<ellipse cx="-35" cy="-8" rx="45" ry="22" fill="#fff"/></g>`;
	}
	const sun =
		kind === 'golden' || kind === 'dusk'
			? `<circle cx="${ 200 + r() * 1100 }" cy="${ 300 + r() * 80 }" r="70" fill="#fff4d6" opacity="0.85"/>`
			: `<circle cx="${ 150 + r() * 1200 }" cy="${ 110 + r() * 60 }" r="48" fill="#fffbe8" opacity="0.9"/>`;
	return `
	<defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
		<stop offset="0" stop-color="${ a }"/><stop offset="0.6" stop-color="${ b }"/><stop offset="1" stop-color="${ c }"/>
	</linearGradient></defs>
	<rect width="${ W }" height="${ H }" fill="url(#sky)"/>${ sun }${ clouds }`;
}

function distantCity( r, base = 640, tint = '#b9c7cf' ) {
	let out = `<path d="M0 ${ base } Q 300 ${ base - 90 } 650 ${ base - 40 } T 1500 ${ base - 70 } V ${ H } H 0 Z" fill="${ shade( tint, 0.25 ) }"/>`;
	let x = -20;
	while ( x < W ) {
		const w = 40 + r() * 70;
		const h = 40 + r() * 110;
		const y = base - 30 - h + Math.sin( x / 260 ) * 30;
		out += `<rect x="${ x }" y="${ y }" width="${ w }" height="${ h + 200 }" fill="${ tint }" opacity="${ 0.55 + r() * 0.3 }"/>`;
		if ( r() > 0.5 ) {
			out += `<path d="M${ x - 4 } ${ y } L${ x + w / 2 } ${ y - 18 } L${ x + w + 4 } ${ y } Z" fill="${ shade( '#c97b5a', 0.35 ) }" opacity="0.7"/>`;
		}
		x += w + 4 + r() * 10;
	}
	return out;
}

function tree( x, y, s, r, color = '#5f8f5a' ) {
	return `<g transform="translate(${ x } ${ y }) scale(${ s })">
		<rect x="-7" y="-10" width="14" height="110" rx="5" fill="#7a5a43"/>
		<circle cx="0" cy="-60" r="70" fill="${ color }"/>
		<circle cx="-48" cy="-30" r="48" fill="${ shade( color, -0.12 ) }"/>
		<circle cx="45" cy="-35" r="52" fill="${ shade( color, 0.1 ) }"/>
		<circle cx="10" cy="-100" r="42" fill="${ shade( color, 0.18 ) }"/>
	</g>`;
}

function palm( x, y, s ) {
	let fronds = '';
	for ( let i = 0; i < 7; i++ ) {
		const a = -160 + i * 25;
		fronds += `<path d="M0 0 Q ${ Math.cos( ( a * Math.PI ) / 180 ) * 80 } ${ Math.sin( ( a * Math.PI ) / 180 ) * 80 - 30 } ${ Math.cos( ( a * Math.PI ) / 180 ) * 150 } ${ Math.sin( ( a * Math.PI ) / 180 ) * 150 + 30 }" stroke="#4f8a4c" stroke-width="18" fill="none" stroke-linecap="round"/>`;
	}
	return `<g transform="translate(${ x } ${ y }) scale(${ s })">
		<path d="M-10 300 Q -25 150 0 0 L 14 0 Q 0 150 12 300 Z" fill="#9a7552"/>${ fronds }</g>`;
}

function lamp( x, y ) {
	return `<g transform="translate(${ x } ${ y })">
		<rect x="-4" y="-260" width="8" height="260" fill="#2f3a40"/>
		<path d="M-22 -262 h44 l-8 -34 h-28 Z" fill="#2f3a40"/>
		<rect x="-14" y="-292" width="28" height="22" fill="#ffe7a3" opacity="0.9"/>
	</g>`;
}

function cobbles( y, r ) {
	let out = `<rect x="0" y="${ y }" width="${ W }" height="${ H - y }" fill="#d8d2c6"/>`;
	out += `<rect x="0" y="${ y }" width="${ W }" height="14" fill="#bdb5a6"/>`;
	for ( let row = 0; row < 12; row++ ) {
		for ( let i = 0; i < 40; i++ ) {
			const cx = i * 42 + ( row % 2 ) * 21 + r() * 6;
			const cy = y + 30 + row * 22;
			if ( cy > H ) {
				break;
			}
			out += `<rect x="${ cx }" y="${ cy }" width="34" height="16" rx="6" fill="${ r() > 0.94 ? '#8d8a84' : '#ece7dd' }" opacity="0.8"/>`;
		}
	}
	return out;
}

function windowEl( x, y, w, h, opts ) {
	const { frame = '#ffffff', shutter, glass = '#9cc3d8', arch = false, balcony = false, r } = opts;
	const top = arch
		? `<path d="M${ x } ${ y + w / 2 } A ${ w / 2 } ${ w / 2 } 0 0 1 ${ x + w } ${ y + w / 2 } V ${ y + h } H ${ x } Z" fill="${ frame }"/>
		   <path d="M${ x + 7 } ${ y + w / 2 } A ${ w / 2 - 7 } ${ w / 2 - 7 } 0 0 1 ${ x + w - 7 } ${ y + w / 2 } V ${ y + h - 7 } H ${ x + 7 } Z" fill="${ glass }"/>`
		: `<rect x="${ x }" y="${ y }" width="${ w }" height="${ h }" fill="${ frame }"/>
		   <rect x="${ x + 7 }" y="${ y + 7 }" width="${ w - 14 }" height="${ h - 14 }" fill="${ glass }"/>`;
	const reflect = `<path d="M${ x + 12 } ${ y + h - 14 } L ${ x + w * 0.55 } ${ y + ( arch ? w / 2 : 14 ) }" stroke="#ffffff" stroke-opacity="0.35" stroke-width="10"/>`;
	const mullions = `<rect x="${ x + w / 2 - 3 }" y="${ y + 7 }" width="6" height="${ h - 14 }" fill="${ frame }"/>
		<rect x="${ x + 7 }" y="${ y + h * 0.45 }" width="${ w - 14 }" height="6" fill="${ frame }"/>`;
	let s = '';
	if ( shutter ) {
		const open = r() > 0.35;
		if ( open ) {
			s = `<rect x="${ x - w * 0.42 }" y="${ y }" width="${ w * 0.4 }" height="${ h }" fill="${ shutter }"/>
				 <rect x="${ x + w + 2 }" y="${ y }" width="${ w * 0.4 }" height="${ h }" fill="${ shutter }"/>`;
			for ( let i = 1; i < 9; i++ ) {
				s += `<rect x="${ x - w * 0.42 }" y="${ y + ( i * h ) / 9 }" width="${ w * 0.4 }" height="2" fill="${ shade( shutter, -0.25 ) }"/>
					  <rect x="${ x + w + 2 }" y="${ y + ( i * h ) / 9 }" width="${ w * 0.4 }" height="2" fill="${ shade( shutter, -0.25 ) }"/>`;
			}
		}
	}
	let b = '';
	if ( balcony ) {
		const by = y + h - 4;
		b = `<rect x="${ x - 18 }" y="${ by }" width="${ w + 36 }" height="10" fill="#3b4246"/>
			 <rect x="${ x - 18 }" y="${ by - 52 }" width="${ w + 36 }" height="4" fill="#3b4246"/>`;
		for ( let i = 0; i <= 10; i++ ) {
			b += `<rect x="${ x - 18 + ( i * ( w + 34 ) ) / 10 }" y="${ by - 50 }" width="3" height="50" fill="#3b4246"/>`;
		}
		if ( r() > 0.4 ) {
			b += `<ellipse cx="${ x + w * 0.2 }" cy="${ by - 50 }" rx="22" ry="16" fill="#6f9f58"/><circle cx="${ x + w * 0.15 }" cy="${ by - 60 }" r="5" fill="#e0627a"/><circle cx="${ x + w * 0.28 }" cy="${ by - 56 }" r="5" fill="#f2c14e"/>`;
		}
	}
	return `${ s }${ top }${ reflect }${ arch ? '' : mullions }${ b }`;
}

function azulejo( id, base, ink ) {
	return `<pattern id="${ id }" width="40" height="40" patternUnits="userSpaceOnUse">
		<rect width="40" height="40" fill="${ base }"/>
		<circle cx="20" cy="20" r="9" fill="none" stroke="${ ink }" stroke-width="3"/>
		<path d="M20 0 V8 M20 32 V40 M0 20 H8 M32 20 H40" stroke="${ ink }" stroke-width="3"/>
		<circle cx="0" cy="0" r="5" fill="${ ink }"/><circle cx="40" cy="0" r="5" fill="${ ink }"/>
		<circle cx="0" cy="40" r="5" fill="${ ink }"/><circle cx="40" cy="40" r="5" fill="${ ink }"/>
		<rect width="40" height="40" fill="none" stroke="${ shade( base, -0.08 ) }" stroke-width="1"/>
	</pattern>`;
}

/* ---------- exterior: Lisbon townhouse ---------- */

function townhouse( p ) {
	const r = rng( p.seed );
	const floors = p.floors;
	const fw = p.width;
	const floorH = 150;
	const groundY = 820;
	const bx = ( W - fw ) / 2 + p.offset;
	const by = groundY - floors * floorH - 40;
	const cols = p.cols;
	const winW = 78;
	const gap = ( fw - cols * winW ) / ( cols + 1 );

	let neighbours = '';
	const nColors = p.neighbours;
	// left neighbour
	neighbours += `<rect x="${ bx - 420 }" y="${ by + 90 }" width="420" height="${ groundY - by }" fill="${ nColors[ 0 ] }"/>`;
	neighbours += `<path d="M${ bx - 440 } ${ by + 92 } L ${ bx - 210 } ${ by + 30 } L ${ bx + 10 } ${ by + 92 } Z" fill="#b8623f"/>`;
	for ( let f = 0; f < floors - 1; f++ ) {
		for ( let c = 0; c < 3; c++ ) {
			neighbours += windowEl( bx - 380 + c * 130, by + 150 + f * floorH, 70, 105, { frame: '#f7f3ea', glass: '#8fb4c6', r, shutter: f % 2 ? null : '#3f6e5a' } );
		}
	}
	// right neighbour
	neighbours += `<rect x="${ bx + fw }" y="${ by + 140 }" width="460" height="${ groundY - by }" fill="${ nColors[ 1 ] }"/>`;
	neighbours += `<rect x="${ bx + fw - 6 }" y="${ by + 128 }" width="472" height="16" fill="${ shade( nColors[ 1 ], -0.2 ) }"/>`;
	for ( let f = 0; f < floors - 1; f++ ) {
		for ( let c = 0; c < 3; c++ ) {
			neighbours += windowEl( bx + fw + 50 + c * 140, by + 190 + f * floorH, 70, 100, { frame: '#ffffff', glass: '#97bdd0', r, balcony: f === 0 } );
		}
	}

	const facadeFill = p.tiles ? 'url(#tiles)' : p.color;
	let house = `<rect x="${ bx }" y="${ by }" width="${ fw }" height="${ groundY - by }" fill="${ facadeFill }"/>`;
	// cornice + roof
	house += `<rect x="${ bx - 14 }" y="${ by - 18 }" width="${ fw + 28 }" height="22" fill="${ p.trim }"/>`;
	house += `<path d="M${ bx - 22 } ${ by - 18 } L ${ bx + fw / 2 } ${ by - 120 } L ${ bx + fw + 22 } ${ by - 18 } Z" fill="#c0643f"/>`;
	for ( let i = 1; i < 6; i++ ) {
		house += `<path d="M${ bx - 22 + i * 18 } ${ by - 18 - i * 17 } L ${ bx + fw + 22 - i * 18 } ${ by - 18 - i * 17 }" stroke="#a5512f" stroke-width="3"/>`;
	}
	// chimney
	house += `<rect x="${ bx + fw * 0.72 }" y="${ by - 120 }" width="34" height="70" fill="${ shade( p.trim, -0.05 ) }"/>`;
	// pilasters
	house += `<rect x="${ bx }" y="${ by }" width="16" height="${ groundY - by }" fill="${ p.trim }"/>
			  <rect x="${ bx + fw - 16 }" y="${ by }" width="16" height="${ groundY - by }" fill="${ p.trim }"/>`;
	// floor bands
	for ( let f = 1; f < floors + 1; f++ ) {
		house += `<rect x="${ bx }" y="${ by + 40 + f * floorH - 22 }" width="${ fw }" height="10" fill="${ p.trim }" opacity="0.9"/>`;
	}
	// windows
	for ( let f = 0; f < floors - 1; f++ ) {
		for ( let c = 0; c < cols; c++ ) {
			const x = bx + gap + c * ( winW + gap );
			const y = by + 50 + f * floorH;
			house += windowEl( x, y, winW, 112, {
				frame: '#ffffff',
				shutter: p.shutter,
				glass: p.glass,
				arch: p.arch && f === 0,
				balcony: f === 0 || ( p.balconies && r() > 0.4 ),
				r,
			} );
		}
	}
	// ground floor: door + shop windows
	const gy = by + 40 + ( floors - 1 ) * floorH + 6;
	const doorX = bx + fw / 2 - 50;
	house += `<rect x="${ bx }" y="${ gy - 4 }" width="${ fw }" height="${ groundY - gy + 4 }" fill="${ shade( p.color, -0.08 ) }" opacity="${ p.tiles ? 0.0 : 1 }"/>`;
	house += `<path d="M${ doorX } ${ groundY } V ${ gy + 60 } A 50 50 0 0 1 ${ doorX + 100 } ${ gy + 60 } V ${ groundY } Z" fill="${ p.door }"/>`;
	house += `<rect x="${ doorX + 48 }" y="${ gy + 60 }" width="4" height="${ groundY - gy - 60 }" fill="${ shade( p.door, -0.3 ) }"/>`;
	house += `<circle cx="${ doorX + 40 }" cy="${ gy + 110 }" r="4" fill="#d9b45a"/><circle cx="${ doorX + 60 }" cy="${ gy + 110 }" r="4" fill="#d9b45a"/>`;
	house += `<rect x="${ doorX - 14 }" y="${ groundY - 8 }" width="128" height="8" fill="#9c9488"/>`;
	[ bx + 40, bx + fw - 40 - 110 ].forEach( ( x ) => {
		house += windowEl( x, gy + 40, 110, 120, { frame: '#ffffff', glass: p.glass, r } );
	} );
	// house number plate
	house += `<rect x="${ doorX + 36 }" y="${ gy + 22 }" width="28" height="20" rx="3" fill="#2a5aa0"/><text x="${ doorX + 50 }" y="${ gy + 37 }" font-family="Arial" font-size="13" fill="#fff" text-anchor="middle" font-weight="700">${ p.number }</text>`;

	let extras = '';
	if ( p.tram ) {
		extras += `<g transform="translate(${ p.tram } 690)">
			<rect x="0" y="0" width="330" height="150" rx="22" fill="#f6c431"/>
			<rect x="0" y="105" width="330" height="45" rx="10" fill="#e9e3d2"/>
			<rect x="-6" y="-14" width="342" height="20" rx="8" fill="#e9e3d2"/>
			${ [ 0, 1, 2, 3 ].map( ( i ) => `<rect x="${ 20 + i * 76 }" y="22" width="60" height="62" rx="8" fill="#7fa8bf"/>` ).join( '' ) }
			<rect x="150" y="-60" width="4" height="48" fill="#333"/><path d="M152 -60 L 260 -110" stroke="#333" stroke-width="3"/>
			<circle cx="70" cy="155" r="17" fill="#333"/><circle cx="260" cy="155" r="17" fill="#333"/>
			<rect x="120" y="112" width="90" height="22" rx="4" fill="#2f3438"/><text x="165" y="128" font-family="Arial" font-size="15" fill="#fff" text-anchor="middle" font-weight="700">28</text>
		</g>`;
	}

	const r2 = rng( p.seed + 7 );
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${ W }" height="${ H }" viewBox="0 0 ${ W } ${ H }">
		${ sky( p.sky, r2 ) }
		<defs>${ azulejo( 'tiles', p.tileBase || '#eef3f8', p.tileInk || '#2f5f9e' ) }</defs>
		${ distantCity( r2, 600 ) }
		${ neighbours }
		${ house }
		${ cobbles( groundY, r2 ) }
		${ p.trees ? tree( bx - 140, groundY + 10, 1.25, r2 ) + tree( bx + fw + 170, groundY + 20, 1.1, r2, '#6a9a5f' ) : '' }
		${ lamp( bx + fw + 40, groundY + 40 ) }
		${ extras }
		<rect width="${ W }" height="${ H }" fill="url(#vignette)"/>
		<defs><radialGradient id="vignette" cx="0.5" cy="0.45" r="0.75"><stop offset="0.65" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity="0.18"/></radialGradient></defs>
	</svg>`;
}

/* ---------- exterior: modern apartment block ---------- */

function modern( p ) {
	const r = rng( p.seed );
	const groundY = 840;
	const bx = 260 + p.offset;
	const bw = 980;
	const floors = p.floors;
	const fh = 98;
	const top = groundY - floors * fh;
	let b = `<rect x="${ bx }" y="${ top }" width="${ bw }" height="${ groundY - top }" fill="${ p.color }"/>`;
	b += `<rect x="${ bx + bw * 0.62 }" y="${ top - 60 }" width="${ bw * 0.38 }" height="${ groundY - top + 60 }" fill="${ shade( p.color, -0.06 ) }"/>`;
	for ( let f = 0; f < floors; f++ ) {
		const y = top + f * fh;
		b += `<rect x="${ bx - 20 }" y="${ y + fh - 14 }" width="${ bw * 0.62 + 20 }" height="14" fill="#ffffff"/>`;
		b += `<rect x="${ bx + 20 }" y="${ y + 14 }" width="${ bw * 0.6 - 40 }" height="${ fh - 30 }" fill="${ p.glass }" opacity="0.92"/>`;
		for ( let i = 1; i < 5; i++ ) {
			b += `<rect x="${ bx + 20 + ( i * ( bw * 0.6 - 40 ) ) / 5 }" y="${ y + 14 }" width="4" height="${ fh - 30 }" fill="#e9eef1"/>`;
		}
		b += `<rect x="${ bx - 20 }" y="${ y + fh - 52 }" width="${ bw * 0.62 + 20 }" height="38" fill="#ffffff" opacity="0.35"/>`;
		if ( r() > 0.5 ) {
			b += `<ellipse cx="${ bx + 60 + r() * 400 }" cy="${ y + fh - 52 }" rx="26" ry="18" fill="#6f9f58"/>`;
		}
		for ( let c = 0; c < 3; c++ ) {
			b += `<rect x="${ bx + bw * 0.62 + 40 + c * 120 }" y="${ y + 18 }" width="80" height="${ fh - 36 }" fill="${ p.glass }"/>`;
		}
	}
	b += `<rect x="${ bx + bw * 0.62 }" y="${ top - 70 }" width="${ bw * 0.38 + 10 }" height="14" fill="#ffffff"/>`;
	const r2 = rng( p.seed + 3 );
	const water = p.water
		? `<rect x="0" y="${ groundY - 70 }" width="${ W }" height="70" fill="#7fb1c8"/><path d="M0 ${ groundY - 50 } Q 300 ${ groundY - 60 } 600 ${ groundY - 50 } T 1500 ${ groundY - 50 }" stroke="#a9d1e2" stroke-width="4" fill="none"/>`
		: '';
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${ W }" height="${ H }" viewBox="0 0 ${ W } ${ H }">
		${ sky( p.sky, r2 ) }
		${ distantCity( r2, 680, '#c4cfd5' ) }
		${ water }
		${ b }
		<rect x="0" y="${ groundY }" width="${ W }" height="${ H - groundY }" fill="#cfd6c8"/>
		<rect x="0" y="${ groundY }" width="${ W }" height="40" fill="#e9ece4"/>
		<rect x="0" y="${ groundY + 40 }" width="${ W }" height="${ H - groundY - 40 }" fill="#86a874"/>
		${ tree( 140, groundY + 30, 1.1, r2 ) }${ tree( 1380, groundY + 40, 1.2, r2, '#6b9d63' ) }${ tree( 1180, groundY + 90, 0.8, r2, '#79a96c' ) }
		<rect width="${ W }" height="${ H }" fill="url(#v2)"/>
		<defs><radialGradient id="v2" cx="0.5" cy="0.45" r="0.75"><stop offset="0.65" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity="0.16"/></radialGradient></defs>
	</svg>`;
}

/* ---------- exterior: villa with pool ---------- */

function villa( p ) {
	const r = rng( p.seed );
	const groundY = 760;
	const r2 = rng( p.seed + 9 );
	let v = '';
	v += `<rect x="260" y="430" width="620" height="${ groundY - 430 }" fill="#f6f2ea"/>`;
	v += `<rect x="820" y="520" width="440" height="${ groundY - 520 }" fill="#efe8dc"/>`;
	v += `<rect x="240" y="410" width="660" height="26" fill="#ffffff"/><rect x="800" y="500" width="480" height="24" fill="#ffffff"/>`;
	v += `<rect x="300" y="470" width="540" height="130" fill="#87b3c9"/>`;
	for ( let i = 1; i < 4; i++ ) {
		v += `<rect x="${ 300 + i * 135 }" y="470" width="5" height="130" fill="#f6f2ea"/>`;
	}
	v += `<rect x="300" y="630" width="250" height="${ groundY - 630 }" fill="#87b3c9"/><rect x="590" y="630" width="250" height="${ groundY - 630 }" fill="#87b3c9"/>`;
	v += `<rect x="860" y="560" width="360" height="120" fill="#8fbad0"/>`;
	v += `<rect x="260" y="436" width="620" height="8" fill="#000" opacity="0.06"/>`;
	v += `<rect x="560" y="420" width="10" height="${ groundY - 420 }" fill="${ p.wood }"/>`;
	v += `<rect x="880" y="700" width="300" height="${ groundY - 700 }" fill="${ p.wood }"/>`;
	for ( let i = 0; i < 12; i++ ) {
		v += `<rect x="${ 884 + i * 25 }" y="704" width="3" height="${ groundY - 708 }" fill="${ shade( p.wood, -0.2 ) }"/>`;
	}
	const pool = `<path d="M180 ${ groundY + 40 } H 1320 L 1400 ${ H } H 100 Z" fill="#e9e4d8"/>
		<path d="M300 ${ groundY + 70 } H 1180 L 1230 ${ groundY + 190 } H 250 Z" fill="#4fb3cf"/>
		<path d="M330 ${ groundY + 100 } Q 500 ${ groundY + 85 } 700 ${ groundY + 110 } T 1150 ${ groundY + 100 }" stroke="#a6e1ef" stroke-width="5" fill="none"/>
		<path d="M290 ${ groundY + 150 } Q 520 ${ groundY + 135 } 760 ${ groundY + 160 } T 1200 ${ groundY + 150 }" stroke="#a6e1ef" stroke-width="5" fill="none" opacity="0.7"/>
		<rect x="1240" y="${ groundY + 60 }" width="90" height="16" rx="4" fill="#ffffff"/><rect x="1236" y="${ groundY + 70 }" width="98" height="8" fill="#e0d6c3"/>`;
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${ W }" height="${ H }" viewBox="0 0 ${ W } ${ H }">
		${ sky( p.sky, r2 ) }
		${ distantCity( r2, 640, '#c7d2c0' ) }
		<rect x="0" y="560" width="${ W }" height="${ H - 560 }" fill="#9cbb84"/>
		${ tree( 120, 700, 1.4, r ) }${ tree( 1380, 690, 1.3, r, '#6a9a5f' ) }
		${ v }
		${ palm( 1300, 470, 1 ) }${ palm( 190, 500, 0.85 ) }
		<rect x="0" y="${ groundY }" width="${ W }" height="${ H - groundY }" fill="#86a874"/>
		${ pool }
		<rect width="${ W }" height="${ H }" fill="url(#v3)"/>
		<defs><radialGradient id="v3" cx="0.5" cy="0.45" r="0.75"><stop offset="0.65" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity="0.16"/></radialGradient></defs>
	</svg>`;
}

/* ---------- interiors ---------- */

function room( kind, pal, seed ) {
	const r = rng( seed );
	const floorY = 700;
	let s = `<rect width="${ W }" height="${ H }" fill="${ pal.wall }"/>
		<rect x="0" y="${ floorY }" width="${ W }" height="${ H - floorY }" fill="${ pal.floor }"/>`;
	for ( let i = 0; i < 16; i++ ) {
		s += `<path d="M${ i * 110 - 200 } ${ H } L ${ 750 + ( i - 8 ) * 30 } ${ floorY }" stroke="${ shade( pal.floor, -0.1 ) }" stroke-width="2"/>`;
	}
	s += `<rect x="0" y="${ floorY - 16 }" width="${ W }" height="16" fill="${ shade( pal.wall, -0.1 ) }"/>`;
	// big window with a city view
	const wx = kind === 'kitchen' ? 900 : 860;
	s += `<rect x="${ wx }" y="140" width="460" height="460" fill="#ffffff"/>
		<rect x="${ wx + 14 }" y="154" width="432" height="432" fill="#bfe0f0"/>
		<rect x="${ wx + 14 }" y="420" width="432" height="166" fill="#d9c7ae"/>`;
	for ( let i = 0; i < 9; i++ ) {
		s += `<rect x="${ wx + 20 + i * 48 }" y="${ 380 + r() * 60 }" width="40" height="200" fill="${ [ '#f1d9b5', '#e8b9a0', '#f4efe6', '#d6e2e8' ][ i % 4 ] }"/>`;
		s += `<path d="M${ wx + 16 + i * 48 } ${ 382 + r() * 50 } l 24 -16 l 24 16 Z" fill="#c0643f" opacity="0.85"/>`;
	}
	s += `<rect x="${ wx + 226 }" y="154" width="8" height="432" fill="#ffffff"/><rect x="${ wx + 14 }" y="366" width="432" height="8" fill="#ffffff"/>`;
	s += `<path d="M${ wx - 40 } 120 H ${ wx + 500 }" stroke="${ shade( pal.accent, -0.3 ) }" stroke-width="6"/>`;
	s += `<path d="M${ wx - 30 } 124 q 30 250 0 520 h 70 q -30 -270 0 -520 Z" fill="${ pal.curtain }"/>`;
	s += `<path d="M${ wx + 420 } 124 q 30 250 0 520 h 70 q -30 -270 0 -520 Z" fill="${ pal.curtain }"/>`;

	if ( kind === 'living' ) {
		s += `<rect x="120" y="190" width="300" height="210" fill="#ffffff"/><rect x="134" y="204" width="272" height="182" fill="${ pal.art }"/>
			<circle cx="230" cy="290" r="55" fill="${ shade( pal.art, 0.4 ) }"/><path d="M134 386 L 260 300 L 406 386 Z" fill="${ shade( pal.art, -0.25 ) }"/>`;
		s += `<rect x="80" y="${ floorY + 110 }" width="760" height="40" rx="18" fill="${ shade( pal.rug, -0.05 ) }" opacity="0.6"/>`;
		s += `<ellipse cx="560" cy="${ floorY + 150 }" rx="520" ry="70" fill="${ pal.rug }"/>`;
		s += `<rect x="110" y="520" width="640" height="130" rx="30" fill="${ shade( pal.sofa, -0.1 ) }"/>
			<rect x="90" y="590" width="680" height="120" rx="26" fill="${ pal.sofa }"/>
			<rect x="150" y="560" width="250" height="70" rx="18" fill="${ shade( pal.sofa, 0.12 ) }"/>
			<rect x="420" y="560" width="250" height="70" rx="18" fill="${ shade( pal.sofa, 0.12 ) }"/>
			<rect x="200" y="530" width="80" height="70" rx="14" fill="${ pal.accent }" transform="rotate(-8 240 565)"/>
			<rect x="120" y="710" width="16" height="40" fill="#5a4636"/><rect x="724" y="710" width="16" height="40" fill="#5a4636"/>`;
		s += `<ellipse cx="560" cy="790" rx="170" ry="34" fill="${ pal.wood }"/><rect x="430" y="790" width="14" height="70" fill="${ shade( pal.wood, -0.3 ) }"/><rect x="676" y="790" width="14" height="70" fill="${ shade( pal.wood, -0.3 ) }"/>
			<rect x="520" y="745" width="60" height="40" rx="6" fill="#ffffff"/><circle cx="640" cy="770" r="18" fill="${ pal.accent }"/>`;
		s += `<rect x="1380" y="560" width="70" height="150" rx="10" fill="#e9e1d3"/><circle cx="1415" cy="470" r="100" fill="#6f9f58"/><circle cx="1370" cy="520" r="60" fill="#5d8c4a"/><circle cx="1460" cy="420" r="60" fill="#7cae64"/>`;
		s += `<path d="M820 200 v 360" stroke="#333" stroke-width="4"/><path d="M780 560 h 80 l -10 30 h -60 Z" fill="#333"/><path d="M790 160 h 60 l 20 50 h -100 Z" fill="${ pal.accent }"/>`;
	} else if ( kind === 'kitchen' ) {
		s += `<rect x="60" y="150" width="760" height="190" fill="${ pal.cabinet }"/>`;
		for ( let i = 0; i < 4; i++ ) {
			s += `<rect x="${ 72 + i * 187 }" y="162" width="175" height="166" fill="${ shade( pal.cabinet, 0.06 ) }"/><rect x="${ 150 + i * 187 }" y="300" width="22" height="6" rx="3" fill="#c9a86a"/>`;
		}
		s += `<rect x="60" y="340" width="760" height="150" fill="url(#tileK)"/>`;
		s += `<defs><pattern id="tileK" width="50" height="25" patternUnits="userSpaceOnUse"><rect width="50" height="25" fill="#ffffff"/><rect width="50" height="25" fill="none" stroke="#e2e2e2" stroke-width="2"/></pattern></defs>`;
		s += `<rect x="60" y="490" width="760" height="24" fill="${ pal.counter }"/><rect x="60" y="514" width="760" height="186" fill="${ pal.cabinet }"/>`;
		for ( let i = 0; i < 4; i++ ) {
			s += `<rect x="${ 72 + i * 187 }" y="526" width="175" height="162" fill="${ shade( pal.cabinet, 0.06 ) }"/><rect x="${ 150 + i * 187 }" y="540" width="22" height="6" rx="3" fill="#c9a86a"/>`;
		}
		s += `<rect x="520" y="430" width="120" height="60" rx="6" fill="#d8d8d8"/><path d="M570 430 v -40 h 40" stroke="#bbb" stroke-width="8" fill="none"/>`;
		s += `<rect x="300" y="620" width="760" height="40" fill="${ pal.counter }"/><rect x="320" y="660" width="720" height="220" fill="${ pal.island }"/>`;
		for ( let i = 0; i < 3; i++ ) {
			s += `<path d="M${ 470 + i * 220 } 0 v 260" stroke="#333" stroke-width="3"/><path d="M${ 430 + i * 220 } 300 q 40 -60 80 0 Z" fill="${ pal.accent }"/><ellipse cx="${ 470 + i * 220 }" cy="302" rx="40" ry="6" fill="#fff6d8"/>`;
		}
		s += `<circle cx="420" cy="600" r="24" fill="#f2c14e"/><circle cx="455" cy="606" r="20" fill="#e0627a"/><ellipse cx="440" cy="618" rx="60" ry="10" fill="#ffffff"/>`;
		s += `<rect x="1100" y="720" width="14" height="200" fill="#333"/><rect x="1250" y="720" width="14" height="200" fill="#333"/><rect x="1080" y="700" width="200" height="30" rx="10" fill="${ pal.wood }"/>`;
	} else {
		s += `<rect x="140" y="300" width="640" height="300" rx="20" fill="${ pal.headboard }"/>`;
		s += `<rect x="100" y="520" width="720" height="200" rx="18" fill="#ffffff"/>
			<rect x="100" y="600" width="720" height="140" rx="16" fill="${ pal.bedding }"/>
			<rect x="170" y="470" width="240" height="90" rx="26" fill="#f7f4ee"/><rect x="430" y="470" width="240" height="90" rx="26" fill="#f7f4ee"/>
			<rect x="300" y="520" width="200" height="70" rx="20" fill="${ pal.accent }"/>
			<rect x="100" y="740" width="720" height="26" fill="${ shade( pal.wood, -0.2 ) }"/>`;
		[ 0, 1 ].forEach( ( i ) => {
			const x = i ? 850 : 0;
			s += `<rect x="${ x }" y="580" width="90" height="140" fill="${ pal.wood }"/><rect x="${ x + 30 }" y="470" width="30" height="110" fill="#d9cbb5"/><path d="M${ x + 5 } 470 h 80 l -15 -60 h -50 Z" fill="${ shade( pal.accent, 0.3 ) }"/>`;
		} );
		s += `<rect x="260" y="140" width="400" height="120" fill="#ffffff"/><rect x="272" y="152" width="376" height="96" fill="${ pal.art }"/><path d="M272 248 q 90 -80 190 -20 t 186 -30 v 50 Z" fill="${ shade( pal.art, -0.25 ) }"/>`;
		s += `<rect x="1390" y="600" width="60" height="110" rx="8" fill="#e9e1d3"/><circle cx="1420" cy="540" r="70" fill="#6f9f58"/><circle cx="1390" cy="580" r="40" fill="#5d8c4a"/>`;
	}
	s += `<rect width="${ W }" height="${ H }" fill="url(#light)"/>
		<defs><linearGradient id="light" x1="1" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff8e6" stop-opacity="0.28"/><stop offset="1" stop-color="#000" stop-opacity="0.08"/></linearGradient></defs>`;
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${ W }" height="${ H }" viewBox="0 0 ${ W } ${ H }">${ s }</svg>`;
}

/* ---------- catalogue ---------- */

const PALETTES = [
	{ wall: '#f3ede4', floor: '#c9a27c', sofa: '#4f6d7a', accent: '#d9774b', rug: '#e7dccb', art: '#9cc0c9', curtain: '#efe3cf', wood: '#a47551', cabinet: '#3f5d55', counter: '#efebe4', island: '#f4f1ec', headboard: '#7c8f7a', bedding: '#dfe8ea' },
	{ wall: '#e9eef0', floor: '#d8bf9f', sofa: '#c8a27a', accent: '#2f6f8f', rug: '#f1ece2', art: '#e7b49a', curtain: '#f5efe6', wood: '#8a5f3d', cabinet: '#f2efe9', counter: '#2f3438', island: '#3c5a6e', headboard: '#c9b29b', bedding: '#f3e7d6' },
	{ wall: '#f6efe8', floor: '#b7896a', sofa: '#8b9a6b', accent: '#c4572f', rug: '#ead9c6', art: '#5d7f96', curtain: '#e8d9c4', wood: '#6e4b33', cabinet: '#244a5c', counter: '#f3efe8', island: '#e7ded0', headboard: '#2f4a5c', bedding: '#efe4d5' },
];

const NEIGHBOURS = [
	[ '#e8c9a0', '#cfe0dc' ],
	[ '#f0d7d0', '#e6dcc2' ],
	[ '#d7e4ec', '#efd9a8' ],
	[ '#ecdcc0', '#d8c4dd' ],
];

const exteriors = [
	[ 'alfama-townhouse', townhouse, { seed: 11, floors: 4, width: 520, cols: 3, offset: 0, color: '#f2c4b3', trim: '#ffffff', shutter: '#3f6e5a', glass: '#97bfd4', door: '#2f5a46', sky: 'day', number: '28', tram: 1080, trees: false, neighbours: NEIGHBOURS[ 0 ], arch: true } ],
	[ 'chiado-apartment', townhouse, { seed: 23, floors: 5, width: 560, cols: 4, offset: -40, color: '#f3dca2', trim: '#fbf7ee', shutter: null, glass: '#8fb7cc', door: '#5b2e2e', sky: 'golden', number: '14', trees: true, neighbours: NEIGHBOURS[ 1 ], balconies: true } ],
	[ 'principe-real-tiles', townhouse, { seed: 37, floors: 4, width: 540, cols: 3, offset: 20, color: '#dfe9f2', tiles: true, tileBase: '#eef3f8', tileInk: '#2f5f9e', trim: '#ffffff', shutter: null, glass: '#8db2c6', door: '#1f3c66', sky: 'clear', number: '52', trees: true, neighbours: NEIGHBOURS[ 2 ], arch: true } ],
	[ 'estrela-family-home', townhouse, { seed: 41, floors: 3, width: 600, cols: 4, offset: 0, color: '#cfe3d6', trim: '#ffffff', shutter: '#ffffff', glass: '#9ac2d6', door: '#7a4b2a', sky: 'day', number: '7', trees: true, neighbours: NEIGHBOURS[ 3 ] } ],
	[ 'baixa-loft', townhouse, { seed: 53, floors: 5, width: 520, cols: 3, offset: 30, color: '#e9d4c4', trim: '#f7f1e7', shutter: '#6b3b2a', glass: '#90b5c9', door: '#2b2b2b', sky: 'dusk', number: '101', tram: 120, trees: false, neighbours: NEIGHBOURS[ 1 ], balconies: true } ],
	[ 'campo-de-ourique-flat', townhouse, { seed: 67, floors: 4, width: 560, cols: 4, offset: -20, color: '#f6efe0', tiles: true, tileBase: '#f7f2e6', tileInk: '#c27d2a', trim: '#ffffff', shutter: '#2f6f8f', glass: '#9cc3d8', door: '#2f6f8f', sky: 'golden', number: '33', trees: true, neighbours: NEIGHBOURS[ 0 ] } ],
	[ 'parque-nacoes-riverside', modern, { seed: 71, floors: 7, offset: 0, color: '#eef1f2', glass: '#7fa9c1', sky: 'clear', water: true } ],
	[ 'belem-villa', villa, { seed: 83, wood: '#a47551', sky: 'day' } ],
	[ 'graca-viewpoint', townhouse, { seed: 97, floors: 4, width: 500, cols: 3, offset: 10, color: '#c9dbe8', trim: '#ffffff', shutter: '#3a5a8a', glass: '#99c0d4', door: '#3a5a8a', sky: 'dusk', number: '9', trees: true, neighbours: NEIGHBOURS[ 2 ], arch: true } ],
	[ 'avenidas-novas-modern', modern, { seed: 103, floors: 8, offset: 40, color: '#f3ede4', glass: '#86aec4', sky: 'golden', water: false } ],
	[ 'restelo-garden-house', villa, { seed: 113, wood: '#7a5236', sky: 'golden' } ],
	[ 'santos-design-studio', townhouse, { seed: 127, floors: 4, width: 520, cols: 3, offset: -10, color: '#e7b9a8', trim: '#fbf5ee', shutter: null, glass: '#8db4c9', door: '#1f1f1f', sky: 'clear', number: '61', trees: false, neighbours: NEIGHBOURS[ 3 ], balconies: true } ],
];

const interiors = [];
[ 'living', 'kitchen', 'bedroom' ].forEach( ( kind ) =>
	PALETTES.forEach( ( pal, i ) => interiors.push( [ `interior-${ kind }-${ i + 1 }`, () => room( kind, pal, 300 + i ) ] ) )
);

/* ---------- render ---------- */

await mkdir( OUT, { recursive: true } );
const browser = await chromium.launch();
const page = await browser.newPage( { viewport: { width: W, height: H } } );

const jobs = [
	...exteriors.map( ( [ name, fn, params ] ) => [ name, () => fn( params ) ] ),
	...interiors,
];

for ( const [ name, build ] of jobs ) {
	await page.setContent(
		`<html><body style="margin:0;background:#fff">${ build() }</body></html>`
	);
	await page.screenshot( {
		path: join( OUT, `${ name }.jpg` ),
		type: 'jpeg',
		quality: 82,
		clip: { x: 0, y: 0, width: W, height: H },
	} );
	console.log( `✓ ${ name }.jpg` );
}

await browser.close();
