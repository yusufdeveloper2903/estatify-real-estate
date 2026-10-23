/**
 * Captures the README screenshots from a running Playground (npm run dev).
 *
 * Usage: npm run screenshots   (BASE_URL defaults to http://127.0.0.1:9403)
 */
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:9403';
const OUT = join( dirname( fileURLToPath( import.meta.url ) ), '..', 'docs', 'screenshots' );
await mkdir( OUT, { recursive: true } );

const browser = await chromium.launch( { args: [ '--lang=en-US' ], env: { ...process.env, LANG: 'en_US.UTF-8' } } );
const desktop = await browser.newContext( { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2, locale: 'en-US' } );
const page = await desktop.newPage();

const shot = async ( target, name, options = {} ) => {
	// Chromium on macOS formats number inputs with the OS region (e.g. "3,4").
	// Show them the way an en-US visitor sees them.
	await target.evaluate( () =>
		document.querySelectorAll( 'input[type="number"]' ).forEach( ( input ) => {
			const { value } = input;
			input.type = 'text';
			input.value = value;
		} )
	);
	await target.screenshot( { path: join( OUT, name ), ...options } );
	console.log( `✓ ${ name }` );
};

// Hide the Playground admin bar on the front end for cleaner shots.
const hideAdminBar = () =>
	page.addStyleTag( { content: '#wpadminbar{display:none!important} html{margin-top:0!important}' } );

const waitForTiles = async () => {
	await page.waitForSelector( '.estatify-pin' );
	await page
		.waitForFunction( () => [ ...document.querySelectorAll( '.leaflet-tile' ) ].every( ( t ) => t.complete ), null, { timeout: 15000 } )
		.catch( () => {} );
	await page.waitForTimeout( 600 );
};

/* 1. Find a home */
await page.goto( `${ BASE }/` );
await hideAdminBar();
await waitForTiles();
await shot( page, '01-find-a-home.png', { clip: { x: 0, y: 0, width: 1440, height: 1250 }, fullPage: true } );

/* 2. Live filtering with a popup */
await page.selectOption( '.estatify-search select[name="ptype"]', 'apartment' );
await page.click( '.estatify-pill:has-text("For sale")' );
await page.waitForFunction( () => document.querySelector( '.estatify-search__count' ).textContent.includes( '3 properties' ) );
await waitForTiles();
await page.locator( '.estatify-pin[title*="Chiado"]' ).click();
await page.waitForSelector( '.estatify-popup img' );
await page.waitForTimeout( 800 );
await page.evaluate( () => window.scrollTo( 0, 230 ) );
await page.waitForTimeout( 300 );
await shot( page, '02-live-filters-and-map.png' );

/* 3. Single property */
await page.goto( `${ BASE }/property/azulejo-tiled-family-home-in-principe-real/` );
await hideAdminBar();
await page.waitForSelector( '.estatify-gallery__main img' );
await page.waitForTimeout( 500 );
const header = await page.locator( 'header.wp-block-template-part' ).boundingBox();
await shot( page, '03-single-property.png', { fullPage: true, clip: { x: 0, y: header ? header.y + header.height - 10 : 0, width: 1440, height: 1500 } } );

/* 4. Specs, map and calculator on the single property */
await page.locator( '.wp-block-estatify-mortgage-calculator' ).scrollIntoViewIfNeeded();
await page.waitForSelector( '.wp-block-estatify-property-map .estatify-pin' );
await page.waitForTimeout( 1500 );
const columns = await page.evaluate( () => {
	const rect = document.querySelector( '.wp-block-columns:has(.wp-block-estatify-mortgage-calculator)' ).getBoundingClientRect();
	return { y: rect.top + window.scrollY, height: rect.height };
} );
await shot( page, '04-location-and-mortgage-calculator.png', {
	fullPage: true,
	clip: { x: 0, y: columns.y - 40, width: 1440, height: Math.min( 900, columns.height + 80 ) },
} );

/* 5. Mortgage calculator page */
await page.goto( `${ BASE }/mortgage-calculator/` );
await hideAdminBar();
await page.fill( '.estatify-calc input[name="price"]', '585000' );
await page.locator( '.estatify-calc input[name="downPayment"]' ).fill( '25' );
await page.evaluate( () => document.activeElement?.blur() );
await page.waitForTimeout( 300 );
const calcTop = await page.evaluate( () => document.querySelector( 'main h1, main .wp-block-post-title' ).getBoundingClientRect().top + window.scrollY );
await shot( page, '05-mortgage-calculator.png', { fullPage: true, clip: { x: 0, y: calcTop - 60, width: 1440, height: 680 } } );

/* 6. Editor: inserter with the Estatify blocks */
await page.goto( `${ BASE }/wp-admin/post-new.php?post_type=page` );
await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' ) );
await page.evaluate( () => {
	window.wp.data.dispatch( 'core/preferences' ).set( 'core', 'welcomeGuide', false );
	window.wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
	window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Homes in Lisbon' } );
	const { createBlock } = window.wp.blocks;
	window.wp.data.dispatch( 'core/block-editor' ).insertBlocks( [
		createBlock( 'estatify/property-search' ),
		createBlock( 'estatify/property-grid', { columns: 3, count: 3, connectToSearch: false } ),
	] );
} );
await page.waitForTimeout( 2500 );
// Dismiss the "Choose a pattern" modal shown for new pages.
if ( await page.locator( '.components-modal__screen-overlay' ).count() ) {
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 400 );
}
await page.evaluate( () => window.wp.data.dispatch( 'core/block-editor' ).clearSelectedBlock() );
await page.click( 'button[aria-label="Block Inserter"], button[aria-label="Toggle block inserter"]' );
await page.fill( '.block-editor-inserter__search input', 'estatify' );
await page.waitForTimeout( 1000 );
await page.waitForFunction( () => ! document.querySelector( '.block-editor-inserter__panel-content .components-spinner, .block-editor-inserter__main-area .components-spinner' ), null, { timeout: 15000 } ).catch( () => {} );
await page.waitForTimeout( 1000 );
await shot( page, '06-editor-block-inserter.png' );

/* 7. Editor: property sidebar panel */
const id = await page.evaluate(
	async () => ( await window.wp.apiFetch( { path: '/wp/v2/properties?slug=sunlit-alfama-townhouse-with-river-glimpses' } ) )[ 0 ].id
);
await page.setViewportSize( { width: 1440, height: 1240 } );
await page.goto( `${ BASE }/wp-admin/post.php?post=${ id }&action=edit` );
await page.waitForSelector( '.estatify-panel' );
await page.evaluate( () => {
	window.wp.data.dispatch( 'core/preferences' ).set( 'core', 'welcomeGuide', false );
	window.wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
} );
if ( ! ( await page.locator( '.estatify-panel.is-opened' ).count() ) ) {
	await page.click( '.estatify-panel .components-panel__body-toggle' );
}
// Collapse the other document panels so ours is in view.
await page.evaluate( () => {
	document
		.querySelectorAll( '.editor-sidebar .components-panel__body.is-opened:not(.estatify-panel) > .components-panel__body-title button' )
		.forEach( ( b ) => b.click() );
} );
await page.waitForTimeout( 400 );
// Keep the shot focused on the panel (the sidebar can't scroll it any higher).
await page.addStyleTag( { content: '.editor-post-trash, .editor-post-summary + .components-panel__body ~ * .editor-post-trash { display: none !important; }' } );
await page.evaluate( () => document.querySelector( '.estatify-panel' ).scrollIntoView( { block: 'start' } ) );
await page.waitForTimeout( 2500 );
await shot( page, '07-editor-property-panel.png' );
await page.setViewportSize( { width: 1440, height: 900 } );

/* 8. Settings page */
await page.goto( `${ BASE }/wp-admin/edit.php?post_type=property&page=estatify-settings` );
await shot( page, '09-settings-page.png', { clip: { x: 0, y: 0, width: 1440, height: 760 } } );

/* 9. Mobile */
const mobile = await browser.newContext( {
	viewport: { width: 390, height: 844 },
	deviceScaleFactor: 3,
	isMobile: true,
	locale: 'en-US',
	hasTouch: true,
	storageState: await desktop.storageState(),
} );
const phone = await mobile.newPage();
await phone.goto( `${ BASE }/?ptype=townhouse` );
await phone.addStyleTag( { content: '#wpadminbar{display:none!important} html{margin-top:0!important}' } );
await phone.waitForSelector( '.estatify-card' );
await phone.waitForTimeout( 800 );
const grid = await phone.locator( '.wp-block-estatify-property-grid' ).boundingBox();
await phone.screenshot( {
	path: join( OUT, '08-mobile.png' ),
	fullPage: true,
	clip: { x: 0, y: Math.max( 0, grid.y - 900 ), width: 390, height: 1500 },
} );
console.log( '✓ 08-mobile.png' );

await browser.close();
