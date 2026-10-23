/**
 * Property Map front end.
 *
 * `callbacks.renderMap` runs in a `data-wp-watch` directive, so it re-runs
 * whenever `state.markers` changes (i.e. after every search).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { state } from '../../shared/store';
import { createMap, setMarkers } from '../../shared/leaflet-map';

const instances = new WeakMap();

store( 'estatify', {
	callbacks: {
		renderMap() {
			const { ref } = getElement();
			const context = getContext();
			const source =
				context.mode === 'single' ? context.markers : state.markers;

			// Copy plain values so the watcher subscribes to every marker.
			const markers = ( source || [] ).map( ( marker ) => ( {
				...marker,
			} ) );

			let instance = instances.get( ref );
			if ( ! instance ) {
				instance = createMap( ref, {
					center: context.center,
					zoom: context.zoom,
					scrollZoom: context.scrollZoom,
				} );
				instances.set( ref, instance );
			}

			setMarkers(
				instance,
				markers,
				context.mode === 'single' ? context.zoom : 15
			);
		},
	},
} );
