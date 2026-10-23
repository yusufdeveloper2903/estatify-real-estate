import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { createMap, setMarkers } from '../../shared/leaflet-map';

const editorData = window.estatifyEditor || {};
const metaKeys = editorData.metaKeys || {};

export default function Edit( { attributes, setAttributes, context } ) {
	const { height, zoom, scrollZoom } = attributes;
	const isProperty =
		context.postType === editorData.postType && !! context.postId;

	const [ meta ] = useEntityProp(
		'postType',
		context.postType || 'post',
		'meta',
		context.postId
	);
	const [ markers, setMarkerList ] = useState( [] );
	const hostRef = useRef();
	const instanceRef = useRef();

	// Single property: preview the coordinates being edited in the sidebar.
	const lat = isProperty ? Number( meta?.[ metaKeys.lat ] ) : NaN;
	const lng = isProperty ? Number( meta?.[ metaKeys.lng ] ) : NaN;

	useEffect( () => {
		if ( isProperty ) {
			setMarkerList(
				lat || lng
					? [
							{
								id: context.postId,
								lat,
								lng,
								status: meta?.[ metaKeys.status ],
								priceShort: '●',
							},
						]
					: []
			);
			return;
		}
		apiFetch( {
			path: '/estatify/v1/properties?per_page=1&with_markers=1',
		} )
			.then( ( response ) => setMarkerList( response.markers || [] ) )
			.catch( () => setMarkerList( [] ) );
	}, [ isProperty, lat, lng ] );

	useEffect( () => {
		if ( ! hostRef.current || instanceRef.current ) {
			return;
		}
		const settings = editorData.settings || {};
		instanceRef.current = createMap( hostRef.current, {
			center: [
				Number( settings.map_lat ) || 38.7223,
				Number( settings.map_lng ) || -9.1393,
			],
			zoom,
		} );
		return () => {
			instanceRef.current?.map.remove();
			instanceRef.current?.node.remove();
			instanceRef.current = null;
		};
	}, [] );

	useEffect( () => {
		if ( instanceRef.current ) {
			instanceRef.current.map.invalidateSize();
			setMarkers( instanceRef.current, markers, isProperty ? zoom : 15, {
				interactive: false,
			} );
		}
	}, [ markers, zoom, height ] );

	const blockProps = useBlockProps( {
		className: 'estatify-map',
		style: { height },
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Map settings', 'estatify' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Height (px)', 'estatify' ) }
						value={ height }
						min={ 200 }
						max={ 900 }
						step={ 10 }
						onChange={ ( value ) =>
							setAttributes( { height: value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Zoom (single property)', 'estatify' ) }
						value={ zoom }
						min={ 3 }
						max={ 19 }
						onChange={ ( value ) =>
							setAttributes( { zoom: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Zoom with mouse wheel', 'estatify' ) }
						checked={ scrollZoom }
						onChange={ ( value ) =>
							setAttributes( { scrollZoom: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="estatify-map__canvas" ref={ hostRef } />
			</div>
		</>
	);
}
