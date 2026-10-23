/**
 * Thin Leaflet wrapper shared by the Property Map view module and its editor preview.
 */
import L from 'leaflet';

const TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const ATTRIBUTION =
	'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

const escapeHtml = ( value ) =>
	String( value ?? '' ).replace(
		/[&<>"']/g,
		( char ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;',
			} )[ char ]
	);

const safeUrl = ( value ) => {
	try {
		const url = new URL( value, window.location.href );
		return [ 'http:', 'https:' ].includes( url.protocol ) ? url.href : '';
	} catch {
		return '';
	}
};

function popupHtml( marker ) {
	const image = safeUrl( marker.image );
	const link = safeUrl( marker.link );
	return `
		${ image ? `<img src="${ escapeHtml( image ) }" alt="" loading="lazy" />` : '' }
		<div class="estatify-popup__body">
			<p class="estatify-popup__price">${ escapeHtml( marker.priceFormatted ) }</p>
			<a class="estatify-popup__title" href="${ escapeHtml( link ) }">${ escapeHtml( marker.title ) }</a>
			<p class="estatify-popup__meta">${ escapeHtml( marker.summary ) }</p>
		</div>`;
}

/**
 * Creates a map inside `container`.
 *
 * @param {HTMLElement} container          Host element (Leaflet gets its own child node).
 * @param {Object}      options            { center: [lat, lng], zoom, scrollZoom }.
 * @param               options.center
 * @param               options.zoom
 * @param               options.scrollZoom
 */
export function createMap( container, { center, zoom, scrollZoom = false } ) {
	const node = container.ownerDocument.createElement( 'div' );
	node.className = 'estatify-map__leaflet';
	container.appendChild( node );

	const map = L.map( node, {
		scrollWheelZoom: scrollZoom,
		zoomControl: true,
		zoomSnap: 0.25,
		attributionControl: true,
	} ).setView( center, zoom );

	L.tileLayer( TILE_URL, { maxZoom: 19, attribution: ATTRIBUTION } ).addTo(
		map
	);

	const layer = L.featureGroup().addTo( map );

	// Leaflet measures its container on init; re-measure once layout settles.
	const view = container.ownerDocument.defaultView || window;
	if ( 'ResizeObserver' in view ) {
		new view.ResizeObserver( () => map.invalidateSize() ).observe( node );
	}

	return { map, layer, node };
}

/**
 * Replaces all markers and fits the view to them.
 *
 * @param {Object} instance Result of createMap().
 * @param {Array}  markers  Marker objects from the REST API.
 * @param {number} maxZoom  Upper zoom bound when fitting.
 * @param {Object} options  { interactive: whether popups open on click }.
 */
export function setMarkers( instance, markers, maxZoom = 15, options = {} ) {
	const { map, layer } = instance;
	const interactive = options.interactive !== false;
	layer.clearLayers();

	markers.forEach( ( marker ) => {
		if (
			! Number.isFinite( marker.lat ) ||
			! Number.isFinite( marker.lng )
		) {
			return;
		}
		const icon = L.divIcon( {
			className: `estatify-pin is-${ escapeHtml( marker.status ) }`,
			html: `<span>${ escapeHtml( marker.priceShort ) }</span>`,
			iconSize: null,
		} );
		const pin = L.marker( [ marker.lat, marker.lng ], {
			icon,
			title: marker.title,
			alt: marker.title,
			riseOnHover: true,
			keyboard: interactive,
		} );
		if ( interactive ) {
			pin.bindPopup( popupHtml( marker ), {
				className: 'estatify-popup',
				maxWidth: 240,
				minWidth: 240,
				offset: [ 0, -30 ],
			} );
		}
		pin.addTo( layer );
	} );

	if ( markers.length === 1 ) {
		map.setView( [ markers[ 0 ].lat, markers[ 0 ].lng ], maxZoom );
	} else if ( markers.length > 1 ) {
		map.fitBounds( layer.getBounds(), {
			padding: [ 48, 48 ],
			maxZoom,
		} );
	}
}
