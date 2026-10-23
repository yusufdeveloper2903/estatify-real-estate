/**
 * Property Details gallery: thumbnails, previous/next and keyboard arrows.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'estatify/details', {
	state: {
		get activeImage() {
			const { images, active } = getContext();
			return images[ active ] || {};
		},
		get activeSrc() {
			return state.activeImage.src || '';
		},
		get activeAlt() {
			return state.activeImage.alt || '';
		},
		get counter() {
			const { images, active } = getContext();
			return `${ active + 1 } / ${ images.length }`;
		},
		get isActiveThumb() {
			const { active } = getContext();
			const { attributes } = getElement();
			return Number( attributes[ 'data-index' ] ) === active;
		},
	},
	actions: {
		select() {
			const context = getContext();
			const { attributes } = getElement();
			context.active = Number( attributes[ 'data-index' ] ) || 0;
		},
		next() {
			const context = getContext();
			context.active = ( context.active + 1 ) % context.images.length;
		},
		prev() {
			const context = getContext();
			context.active =
				( context.active - 1 + context.images.length ) %
				context.images.length;
		},
		keydown( event ) {
			if ( event.key === 'ArrowRight' ) {
				actions.next();
			} else if ( event.key === 'ArrowLeft' ) {
				actions.prev();
			}
		},
	},
} );
