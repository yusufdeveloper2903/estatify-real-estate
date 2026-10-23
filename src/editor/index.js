/**
 * "Property details" panel in the block editor's document sidebar.
 *
 * Reads and writes the registered post meta through core-data
 * (`useEntityProp`), so values are saved together with the post.
 */
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useEffect, useRef } from '@wordpress/element';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	Button,
	Flex,
	FlexBlock,
	TextControl,
	BaseControl,
	__experimentalNumberControl as NumberControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import L from 'leaflet';

import '../shared/vendor/_leaflet.scss';
import './editor.scss';

const config = window.estatifyEditor || {};
const KEYS = config.metaKeys || {};
const settings = config.settings || {};

function LocationPicker( { lat, lng, onChange } ) {
	const nodeRef = useRef();
	const mapRef = useRef();
	const markerRef = useRef();
	const onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

	const hasPoint = !! ( lat || lng );

	useEffect( () => {
		const center = hasPoint
			? [ lat, lng ]
			: [
					Number( settings.map_lat ) || 38.7223,
					Number( settings.map_lng ) || -9.1393,
				];
		const map = L.map( nodeRef.current, {
			scrollWheelZoom: false,
			attributionControl: false,
		} ).setView( center, hasPoint ? 15 : 12 );
		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
		} ).addTo( map );

		const marker = L.marker( center, {
			draggable: true,
			icon: L.divIcon( {
				className: 'estatify-picker-pin',
				iconSize: [ 22, 22 ],
				iconAnchor: [ 11, 11 ],
			} ),
			opacity: hasPoint ? 1 : 0.5,
		} ).addTo( map );

		const emit = ( latlng ) =>
			onChangeRef.current(
				Number( latlng.lat.toFixed( 6 ) ),
				Number( latlng.lng.toFixed( 6 ) )
			);

		marker.on( 'dragend', () => emit( marker.getLatLng() ) );
		map.on( 'click', ( event ) => {
			marker.setLatLng( event.latlng ).setOpacity( 1 );
			emit( event.latlng );
		} );

		mapRef.current = map;
		markerRef.current = marker;
		// The sidebar animates open: measure again once it has settled.
		const timer = setTimeout( () => map.invalidateSize(), 300 );

		return () => {
			clearTimeout( timer );
			map.remove();
		};
	}, [] );

	// Keep the pin in sync when coordinates are typed in.
	useEffect( () => {
		if ( markerRef.current && hasPoint ) {
			const current = markerRef.current.getLatLng();
			if ( current.lat !== lat || current.lng !== lng ) {
				markerRef.current.setLatLng( [ lat, lng ] ).setOpacity( 1 );
				mapRef.current.panTo( [ lat, lng ] );
			}
		}
	}, [ lat, lng ] );

	return <div ref={ nodeRef } className="estatify-picker" />;
}

function GalleryField( { ids, onChange } ) {
	const images = useSelect(
		( select ) =>
			ids.length
				? select( 'core' ).getEntityRecords( 'postType', 'attachment', {
						include: ids,
						per_page: ids.length,
						orderby: 'include',
					} ) || []
				: [],
		[ ids.join( ',' ) ]
	);

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="estatify-gallery"
			label={ __( 'Gallery', 'estatify' ) }
			help={ __(
				'Shown after the cover photo on the property page.',
				'estatify'
			) }
		>
			{ images.length > 0 && (
				<ul className="estatify-gallery-field">
					{ images.map( ( image ) => (
						<li key={ image.id }>
							<img
								src={
									image.media_details?.sizes?.thumbnail
										?.source_url || image.source_url
								}
								alt={ image.alt_text }
							/>
						</li>
					) ) }
				</ul>
			) }
			<MediaUploadCheck>
				<MediaUpload
					multiple
					gallery
					allowedTypes={ [ 'image' ] }
					value={ ids }
					onSelect={ ( selection ) =>
						onChange( selection.map( ( item ) => item.id ) )
					}
					render={ ( { open } ) => (
						<Flex justify="flex-start" gap={ 2 }>
							<Button
								variant="secondary"
								size="compact"
								onClick={ open }
							>
								{ ids.length
									? __( 'Edit gallery', 'estatify' )
									: __( 'Add photos', 'estatify' ) }
							</Button>
							{ ids.length > 0 && (
								<Button
									variant="tertiary"
									size="compact"
									isDestructive
									onClick={ () => onChange( [] ) }
								>
									{ __( 'Clear', 'estatify' ) }
								</Button>
							) }
						</Flex>
					) }
				/>
			</MediaUploadCheck>
		</BaseControl>
	);
}

function PropertyDetailsPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== config.postType || ! meta ) {
		return null;
	}

	const get = ( field ) => meta[ KEYS[ field ] ];
	const set = ( changes ) =>
		setMeta(
			Object.fromEntries(
				Object.entries( changes ).map( ( [ field, value ] ) => [
					KEYS[ field ],
					value,
				] )
			)
		);
	const toNumber = ( value ) =>
		value === '' || value === undefined ? 0 : Number( value );
	const status = get( 'status' ) || 'for_sale';
	const symbol = settings.currency_symbol || '€';

	return (
		<PluginDocumentSettingPanel
			name="estatify-property-details"
			title={ __( 'Property details', 'estatify' ) }
			className="estatify-panel"
		>
			<VStack spacing={ 4 }>
				<ToggleGroupControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					isBlock
					label={ __( 'Status', 'estatify' ) }
					value={ status }
					onChange={ ( value ) => set( { status: value } ) }
				>
					{ Object.entries( config.statuses || {} ).map(
						( [ value, label ] ) => (
							<ToggleGroupControlOption
								key={ value }
								value={ value }
								label={ label }
							/>
						)
					) }
				</ToggleGroupControl>

				<NumberControl
					__next40pxDefaultSize
					label={
						status === 'for_rent'
							? __( 'Monthly rent', 'estatify' )
							: __( 'Price', 'estatify' )
					}
					min={ 0 }
					step={ 1000 }
					spinControls="none"
					value={ get( 'price' ) || '' }
					prefix={
						<InputControlPrefixWrapper>
							{ symbol }
						</InputControlPrefixWrapper>
					}
					onChange={ ( value ) =>
						set( { price: toNumber( value ) } )
					}
				/>

				<Flex align="flex-start">
					<FlexBlock>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Bedrooms', 'estatify' ) }
							min={ 0 }
							max={ 50 }
							value={ get( 'bedrooms' ) ?? 0 }
							onChange={ ( value ) =>
								set( {
									bedrooms: Math.round( toNumber( value ) ),
								} )
							}
						/>
					</FlexBlock>
					<FlexBlock>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Bathrooms', 'estatify' ) }
							min={ 0 }
							max={ 50 }
							value={ get( 'bathrooms' ) ?? 0 }
							onChange={ ( value ) =>
								set( {
									bathrooms: Math.round( toNumber( value ) ),
								} )
							}
						/>
					</FlexBlock>
				</Flex>

				<NumberControl
					__next40pxDefaultSize
					label={ __( 'Living area', 'estatify' ) }
					min={ 0 }
					spinControls="none"
					value={ get( 'area' ) || '' }
					suffix={
						<InputControlSuffixWrapper>
							m²
						</InputControlSuffixWrapper>
					}
					onChange={ ( value ) => set( { area: toNumber( value ) } ) }
				/>

				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Address', 'estatify' ) }
					value={ get( 'address' ) || '' }
					placeholder={ __( 'Street, number, postcode', 'estatify' ) }
					onChange={ ( value ) => set( { address: value } ) }
				/>

				<BaseControl
					__nextHasNoMarginBottom
					id="estatify-location"
					label={ __( 'Location on map', 'estatify' ) }
					help={ __(
						'Click the map or drag the pin to set the coordinates.',
						'estatify'
					) }
				>
					<LocationPicker
						lat={ Number( get( 'lat' ) ) || 0 }
						lng={ Number( get( 'lng' ) ) || 0 }
						onChange={ ( lat, lng ) => set( { lat, lng } ) }
					/>
				</BaseControl>

				<Flex align="flex-start">
					<FlexBlock>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Latitude', 'estatify' ) }
							min={ -90 }
							max={ 90 }
							step="any"
							spinControls="none"
							value={ get( 'lat' ) || '' }
							onChange={ ( value ) =>
								set( { lat: toNumber( value ) } )
							}
						/>
					</FlexBlock>
					<FlexBlock>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Longitude', 'estatify' ) }
							min={ -180 }
							max={ 180 }
							step="any"
							spinControls="none"
							value={ get( 'lng' ) || '' }
							onChange={ ( value ) =>
								set( { lng: toNumber( value ) } )
							}
						/>
					</FlexBlock>
				</Flex>

				<GalleryField
					ids={
						Array.isArray( get( 'gallery' ) )
							? get( 'gallery' )
							: []
					}
					onChange={ ( ids ) => set( { gallery: ids } ) }
				/>

				{ get( 'price' ) > 0 &&
					get( 'area' ) > 0 &&
					status !== 'for_rent' && (
						<p className="estatify-panel__hint">
							{ sprintf(
								/* translators: %s: price per square metre. */
								__( '≈ %s per m²', 'estatify' ),
								`${ symbol }${ Math.round( get( 'price' ) / get( 'area' ) ).toLocaleString() }`
							) }
						</p>
					) }
			</VStack>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'estatify-property-details', {
	render: PropertyDetailsPanel,
	icon: 'building',
} );
