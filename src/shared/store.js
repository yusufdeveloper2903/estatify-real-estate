/**
 * Shared Interactivity API store for the Property Search, Grid and Map blocks.
 *
 * The server seeds `state` (see includes/Blocks/SearchState.php) so the first
 * paint is fully rendered. Afterwards every filter change queries the
 * `estatify/v1/properties` REST endpoint and swaps the results in place,
 * keeping the URL query string in sync so searches can be shared.
 */
import { store, getElement } from '@wordpress/interactivity';

/** Filter key => URL query parameter (mirrors SearchState::URL_PARAMS). */
const URL_PARAMS = {
	search: 'q',
	type: 'ptype',
	location: 'loc',
	status: 'status',
	min_price: 'min_price',
	max_price: 'max_price',
	beds: 'beds',
	sort: 'sort',
	page: 'pg',
};

/** Sort option => REST orderby/order (mirrors SearchState::SORTS). */
const SORTS = {
	newest: [ 'date', 'desc' ],
	price_asc: [ 'price', 'asc' ],
	price_desc: [ 'price', 'desc' ],
	area_desc: [ 'area', 'desc' ],
};

/** Form control `name` (the URL parameter) => filter key. */
const PARAM_TO_KEY = Object.fromEntries(
	Object.entries( URL_PARAMS ).map( ( [ key, param ] ) => [ param, key ] )
);

const EMPTY_FILTERS = {
	search: '',
	type: '',
	location: '',
	status: '',
	min_price: '',
	max_price: '',
	beds: '',
	sort: 'newest',
	page: '1',
};

let abortController = null;
let debounceTimer = null;

const sprintf = ( template, ...values ) => {
	let i = 0;
	return template
		.replace( /%(\d)\$d/g, ( _, n ) => String( values[ Number( n ) - 1 ] ) )
		.replace( /%d/g, () => String( values[ i++ ] ) );
};

const { state, actions } = store( 'estatify', {
	actions: {
		/**
		 * Any form control: `name` is the filter key. Text and number inputs
		 * are debounced; selects and radios fetch immediately.
		 *
		 * @param {Event} event Input or change event from the search form.
		 */
		updateFilter( event ) {
			const { name, value, type } = event.target;
			const key = PARAM_TO_KEY[ name ];
			if ( ! key ) {
				return;
			}
			// Text-like inputs react to `input` (debounced); selects and
			// radios react to `change`. This avoids double requests.
			const isText = [ 'search', 'number', 'text' ].includes( type );
			if ( isText !== ( event.type === 'input' ) ) {
				return;
			}
			state.filters[ key ] = value;
			state.filters.page = '1';

			window.clearTimeout( debounceTimer );
			if ( isText ) {
				debounceTimer = window.setTimeout(
					() => actions.fetchResults(),
					350
				);
			} else {
				actions.fetchResults();
			}
		},

		submit( event ) {
			event.preventDefault();
			window.clearTimeout( debounceTimer );
			actions.fetchResults();
		},

		reset() {
			Object.assign( state.filters, EMPTY_FILTERS );

			// Form controls are uncontrolled, so clear them directly.
			document
				.querySelectorAll( '.estatify-search form' )
				.forEach( ( form ) => {
					form.querySelectorAll( 'input, select' ).forEach(
						( el ) => {
							if ( el.type === 'radio' ) {
								el.checked = el.value === '';
							} else if ( el.name === URL_PARAMS.sort ) {
								el.value = 'newest';
							} else {
								el.value = '';
							}
						}
					);
				} );

			actions.fetchResults();
		},

		nextPage() {
			if ( ! state.isLastPage ) {
				actions.goToPage( state.page + 1 );
			}
		},

		prevPage() {
			if ( ! state.isFirstPage ) {
				actions.goToPage( state.page - 1 );
			}
		},

		goToPage( page ) {
			// Read the element synchronously: scope is lost after awaiting.
			const { ref } = getElement() || {};
			const grid =
				ref?.closest?.( '.wp-block-estatify-property-grid' ) ||
				document.querySelector( '.wp-block-estatify-property-grid' );
			state.filters.page = String( page );
			actions.fetchResults().then( () =>
				grid?.scrollIntoView( {
					behavior: 'smooth',
					block: 'start',
				} )
			);
		},

		*fetchResults() {
			abortController?.abort();
			abortController = new AbortController();

			const { filters } = state;
			const [ orderby, order ] = SORTS[ filters.sort ] || SORTS.newest;
			const params = new URLSearchParams( {
				page: filters.page || '1',
				per_page: String( state.perPage || 9 ),
				orderby,
				order,
			} );
			[
				'search',
				'type',
				'location',
				'status',
				'min_price',
				'max_price',
				'beds',
			].forEach( ( key ) => {
				if ( filters[ key ] !== '' && filters[ key ] !== undefined ) {
					params.set( key, filters[ key ] );
				}
			} );
			if ( state.hasMap ) {
				params.set( 'with_markers', '1' );
			}

			state.loading = true;
			state.error = '';
			actions.syncUrl();

			try {
				const separator = state.restUrl.includes( '?' ) ? '&' : '?';
				const response = yield fetch(
					`${ state.restUrl }${ separator }${ params.toString() }`,
					{
						signal: abortController.signal,
						headers: { Accept: 'application/json' },
					}
				);
				if ( ! response.ok ) {
					throw new Error( `HTTP ${ response.status }` );
				}
				const data = yield response.json();

				state.results = data.items;
				if ( data.markers ) {
					state.markers = data.markers;
				}
				const pages = Math.max( 1, data.total_pages );
				const page = Math.min( data.page, pages );
				state.total = data.total;
				state.totalPages = pages;
				state.page = page;
				state.hasResults = data.total > 0;
				state.isFirstPage = page <= 1;
				state.isLastPage = page >= pages;
				state.hidePager = pages <= 1;
				state.pageLabel = sprintf( state.i18nPage, page, pages );
				if ( data.total === 0 ) {
					state.resultsLabel = state.i18nNone;
				} else if ( data.total === 1 ) {
					state.resultsLabel = state.i18nOne;
				} else {
					state.resultsLabel = sprintf( state.i18nMany, data.total );
				}
				state.loading = false;
			} catch ( error ) {
				if ( error?.name !== 'AbortError' ) {
					state.error = state.i18nError;
					state.loading = false;
				}
			}
		},

		/** Mirrors non-default filters into the address bar. */
		syncUrl() {
			const url = new URL( window.location.href );
			Object.entries( URL_PARAMS ).forEach( ( [ key, param ] ) => {
				const value = state.filters[ key ];
				if ( value && value !== EMPTY_FILTERS[ key ] ) {
					url.searchParams.set( param, value );
				} else {
					url.searchParams.delete( param );
				}
			} );
			window.history.replaceState( null, '', url );
		},
	},
} );

export { state, actions };
