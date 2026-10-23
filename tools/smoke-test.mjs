/**
 * End-to-end smoke test against a running Playground (npm run dev).
 *
 * Checks the live search (results + URL sync), the map markers, the single
 * property template, the mortgage calculator, block validity in the editor
 * and saving meta from the sidebar panel. Fails on PHP notices/warnings in
 * rendered pages and on JavaScript console errors.
 *
 * Usage: npm run test:smoke   (BASE_URL defaults to http://127.0.0.1:9403)
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:9403';
const results = [];
const consoleErrors = [];
const phpProblems = [];

const check = ( name, ok, detail = '' ) => {
	results.push( { name, ok: !! ok, detail } );
	console.log( `${ ok ? '✓' : '✗' } ${ name }${ detail ? ` — ${ detail }` : '' }` );
};

const browser = await chromium.launch();
const context = await browser.newContext( { viewport: { width: 1440, height: 1000 } } );
const page = await context.newPage();

page.on( 'console', ( msg ) => {
	if ( msg.type() === 'error' ) {
		const text = msg.text();
		// Map tiles are loaded from openstreetmap.org; ignore offline tile failures.
		if ( ! /tile\.openstreetmap\.org/.test( text ) ) {
			consoleErrors.push( `${ page.url() }: ${ text }` );
		}
	}
} );
page.on( 'pageerror', ( err ) => consoleErrors.push( `${ page.url() }: ${ err.message }` ) );
page.on( 'response', async ( response ) => {
	const type = response.headers()[ 'content-type' ] || '';
	if ( response.url().startsWith( BASE ) && type.includes( 'text/html' ) ) {
		const body = await response.text().catch( () => '' );
		const match = body.match( /<b>(Warning|Notice|Deprecated|Fatal error)<\/b>:[^\n]*/g );
		if ( match ) {
			phpProblems.push( ...match.map( ( m ) => `${ response.url() }: ${ m.replace( /<[^>]+>/g, '' ) }` ) );
		}
	}
} );

/* 1. Find a home: live filtering, URL sync, map */
await page.goto( `${ BASE }/` );
await page.waitForSelector( '.estatify-pin' );
const initialCards = await page.locator( '.wp-block-estatify-property-grid .estatify-card' ).count();
const initialPins = await page.locator( '.estatify-pin' ).count();
check( 'Grid renders server-side', initialCards === 6, `${ initialCards } cards` );
check( 'Map shows a marker per listing', initialPins === 12, `${ initialPins } markers` );

let requests = 0;
page.on( 'request', ( req ) => req.url().includes( 'estatify/v1/properties' ) && requests++ );
// Marker that disappears if the document is reloaded.
await page.evaluate( () => ( window.__estatifyNoReload = true ) );

await page.selectOption( '.estatify-search select[name="ptype"]', 'apartment' );
await page.waitForFunction( () => document.querySelector( '.estatify-search__count' ).textContent.includes( '5 properties' ) );
const sameDocument = await page.evaluate( () => window.__estatifyNoReload === true );
check( 'Type filter updates results without reload', sameDocument && requests > 0, `${ requests } REST call(s), label "${ ( await page.textContent( '.estatify-search__count' ) ).trim() }"` );
check( 'URL query string is synced', page.url().includes( 'ptype=apartment' ), page.url() );
check( 'Map markers follow the filters', ( await page.locator( '.estatify-pin' ).count() ) === 5 );

await page.click( '.estatify-pill:has-text("For sale")' );
await page.waitForFunction( () => document.querySelector( '.estatify-search__count' ).textContent.includes( '3 properties' ) );
await page.fill( '.estatify-search input[name="max_price"]', '700000' );
await page.waitForFunction( () => document.querySelector( '.estatify-search__count' ).textContent.includes( '2 properties' ) );
check( 'Status + price filters combine', page.url().includes( 'status=for_sale' ) && page.url().includes( 'max_price=700000' ), page.url() );

// Shareable URL: reload with the query string and expect the same result server-side.
await page.reload();
check( 'Filtered URL renders the same results server-side', ( await page.locator( '.wp-block-estatify-property-grid .estatify-card' ).count() ) === 2 );

await page.click( '.estatify-search button:has-text("Reset")' );
await page.waitForFunction( () => document.querySelector( '.estatify-search__count' ).textContent.includes( '12 properties' ) );
await page.click( '.estatify-pager button:has-text("Next")' );
await page.waitForFunction( () => document.querySelector( '.estatify-pager__label' ).textContent.includes( 'Page 2 of 2' ) );
check( 'Pagination loads page 2', page.url().includes( 'pg=2' ) && ( await page.locator( '.wp-block-estatify-property-grid .estatify-card' ).count() ) === 6 );

await page.locator( '.estatify-pin[title*="Parque"]' ).click();
await page.waitForSelector( '.estatify-popup .estatify-popup__title' );
check( 'Marker popup opens', true, ( await page.textContent( '.estatify-popup .estatify-popup__title' ) ).trim() );

/* 2. Single property */
await page.goto( `${ BASE }/property/sunlit-alfama-townhouse-with-river-glimpses/` );
await page.waitForSelector( '.estatify-details__title' );
const firstSrc = await page.getAttribute( '.estatify-gallery__main img', 'src' );
await page.click( '.estatify-gallery__thumbs li:nth-child(2) button' );
const secondSrc = await page.getAttribute( '.estatify-gallery__main img', 'src' );
check( 'Single property renders details', ( await page.locator( '.estatify-specs__item' ).count() ) >= 6, await page.textContent( '.estatify-details__title' ) );
check( 'Gallery thumbnails switch the photo', firstSrc && secondSrc && firstSrc !== secondSrc );
await page.waitForSelector( '.estatify-pin' );
check( 'Single property map shows one marker', ( await page.locator( '.estatify-pin' ).count() ) === 1 );
check( 'Calculator uses the listing price', ( await page.inputValue( '.estatify-calc input[name="price"]' ) ) === '685000' );
check( '"More homes" grid excludes the current listing', ( await page.locator( '.wp-block-estatify-property-grid .estatify-card' ).count() ) === 3 );

/* 3. Mortgage calculator */
await page.goto( `${ BASE }/mortgage-calculator/` );
await page.fill( '.estatify-calc input[name="price"]', '500000' );
await page.fill( '.estatify-calc input[name="rate"]', '4' );
await page.selectOption( '.estatify-calc select[name="years"]', '25' );
const monthly = ( await page.textContent( '.estatify-calc__monthly' ) ).trim();
// 400,000 at 4% over 25 years = 2,111.37/month.
check( 'Mortgage calculator computes', monthly === '€2,111', monthly );

/* 4. Taxonomy + archive templates */
await page.goto( `${ BASE }/properties/` );
check( 'Property archive template renders', ( await page.locator( '.wp-block-estatify-property-search' ).count() ) === 1 );
await page.goto( `${ BASE }/location/belem-restelo/` );
check( 'Location archive lists its properties', ( await page.locator( '.estatify-card' ).count() ) === 2 );

/* 5. Block editor: insert every block, check validity */
await page.goto( `${ BASE }/wp-admin/post-new.php?post_type=page` );
await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' ) );
await page.evaluate( () => {
	window.wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
	window.wp.data.dispatch( 'core/preferences' ).set( 'core', 'welcomeGuide', false );
} );
const blockNames = await page.evaluate( () =>
	window.wp.blocks.getBlockTypes().filter( ( b ) => b.name.startsWith( 'estatify/' ) ).map( ( b ) => b.name )
);
check( 'All five blocks are registered', blockNames.length === 5, blockNames.join( ', ' ) );
await page.evaluate( ( names ) => {
	const { createBlock } = window.wp.blocks;
	window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Block test' } );
	window.wp.data.dispatch( 'core/block-editor' ).insertBlocks( names.map( ( n ) => createBlock( n ) ) );
}, blockNames );
await page.waitForTimeout( 4000 );
await page.evaluate( () => window.wp.data.dispatch( 'core/editor' ).savePost() );
await page.waitForFunction( () => ! window.wp.data.select( 'core/editor' ).isSavingPost() && window.wp.data.select( 'core/editor' ).getCurrentPostId() );
await page.reload();
await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' )?.getBlocks().length > 0 );
await page.waitForTimeout( 3000 );
const validity = await page.evaluate( () =>
	window.wp.data.select( 'core/block-editor' ).getBlocks().map( ( b ) => ( { name: b.name, valid: b.isValid } ) )
);
const frame = page.frameLocator( 'iframe[name="editor-canvas"]' );
const invalidText = await frame.locator( 'text=unexpected or invalid content' ).count();
check( 'Blocks are valid after save + reload', validity.length === 5 && validity.every( ( b ) => b.valid ) && invalidText === 0, JSON.stringify( validity ) );
check( 'Grid preview renders cards in the editor', ( await frame.locator( '.estatify-card' ).count() ) > 0 );
check( 'Map preview renders in the editor', ( await frame.locator( '.leaflet-container' ).count() ) > 0 );

/* 6. Sidebar panel saves meta */
const propertyId = await page.evaluate( async () =>
	( await window.wp.apiFetch( { path: '/wp/v2/properties?slug=chiado-apartment-with-wrought-iron-balconies' } ) )[ 0 ].id
);
await page.goto( `${ BASE }/wp-admin/post.php?post=${ propertyId }&action=edit` );
await page.waitForSelector( '.estatify-panel' );
if ( ! ( await page.locator( '.estatify-panel.is-opened' ).count() ) ) {
	await page.click( '.estatify-panel .components-panel__body-toggle' );
}
await page.waitForSelector( '.estatify-panel input' );
const priceInput = page.locator( '.estatify-panel input' ).nth( 0 );
await priceInput.fill( '935000' );
await page.locator( '.estatify-panel' ).getByLabel( 'Bedrooms' ).fill( '3' );
await page.evaluate( () => window.wp.data.dispatch( 'core/editor' ).savePost() );
await page.waitForFunction( () => ! window.wp.data.select( 'core/editor' ).isSavingPost() );
const saved = await page.evaluate( async ( id ) => {
	const post = await window.wp.apiFetch( { path: `/wp/v2/properties/${ id }?context=edit` } );
	return post.meta;
}, propertyId );
check( 'Sidebar panel saves meta', saved._estatify_price === 935000 && saved._estatify_bedrooms === 3, `price=${ saved._estatify_price }, beds=${ saved._estatify_bedrooms }` );
// Restore the demo value.
await page.evaluate( async ( id ) => {
	await window.wp.apiFetch( { path: `/wp/v2/properties/${ id }`, method: 'POST', data: { meta: { _estatify_price: 920000, _estatify_bedrooms: 2 } } } );
}, propertyId );

/* 7. REST validation */
// APIRequestContext shares the browser cookies but not the console.
const bad = ( await page.request.get( `${ BASE }/wp-json/estatify/v1/properties?status=haunted&per_page=500` ) ).status();
check( 'REST rejects invalid arguments with 400', bad === 400, `HTTP ${ bad }` );

check( 'No JavaScript console errors', consoleErrors.length === 0, consoleErrors.slice( 0, 5 ).join( ' | ' ) );
check( 'No PHP warnings/notices in pages', phpProblems.length === 0, phpProblems.slice( 0, 5 ).join( ' | ' ) );

await browser.close();

const failed = results.filter( ( r ) => ! r.ok );
console.log( `\n${ results.length - failed.length }/${ results.length } checks passed` );
process.exit( failed.length ? 1 : 0 );
