import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

import { BedIcon, BathIcon, AreaIcon, PinIcon } from '../../shared/icons';

const SORTS = {
	newest: [ 'date', 'desc' ],
	price_asc: [ 'price', 'asc' ],
	price_desc: [ 'price', 'desc' ],
	area_desc: [ 'area', 'desc' ],
};

const termOptions = ( terms, anyLabel ) => [
	{ label: anyLabel, value: '' },
	...( terms || [] ).map( ( term ) => ( {
		label: term.name,
		value: term.slug,
	} ) ),
];

export function PropertyCard( { property } ) {
	return (
		<li className="estatify-card" data-status={ property.status }>
			<span className="estatify-card__media">
				{ property.image && (
					<img src={ property.image } alt={ property.imageAlt } />
				) }
				<span className="estatify-card__badge">
					{ property.statusLabel }
				</span>
			</span>
			<div className="estatify-card__body">
				<p className="estatify-card__price">
					{ property.priceFormatted }
				</p>
				<h3 className="estatify-card__title">
					<a
						href={ property.link }
						onClick={ ( e ) => e.preventDefault() }
					>
						{ property.title }
					</a>
				</h3>
				<p className="estatify-card__address">
					<PinIcon size={ 15 } />
					<span>{ property.address }</span>
				</p>
				<ul className="estatify-card__specs">
					<li>
						<BedIcon />
						<span>{ property.bedsLabel }</span>
					</li>
					<li>
						<BathIcon />
						<span>{ property.bathsLabel }</span>
					</li>
					<li>
						<AreaIcon />
						<span>{ property.areaFormatted }</span>
					</li>
				</ul>
			</div>
		</li>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		columns,
		count,
		connectToSearch,
		showPagination,
		propertyType,
		location,
		status,
		orderBy,
		excludeCurrent,
		inheritArchive,
	} = attributes;

	const [ items, setItems ] = useState( null );
	const [ error, setError ] = useState( '' );

	const { types, locations } = useSelect( ( select ) => {
		const { getEntityRecords } = select( coreStore );
		const query = {
			per_page: 50,
			hide_empty: true,
			_fields: 'id,name,slug',
		};
		return {
			types: getEntityRecords( 'taxonomy', 'property_type', query ),
			locations: getEntityRecords(
				'taxonomy',
				'property_location',
				query
			),
		};
	}, [] );

	useEffect( () => {
		const [ orderby, order ] = SORTS[ orderBy ] || SORTS.newest;
		const query = { per_page: count, orderby, order };
		if ( ! connectToSearch ) {
			Object.assign( query, {
				type: propertyType || undefined,
				location: location || undefined,
				status: status || undefined,
			} );
		}

		let cancelled = false;
		setError( '' );
		apiFetch( { path: addQueryArgs( '/estatify/v1/properties', query ) } )
			.then( ( response ) => ! cancelled && setItems( response.items ) )
			.catch( ( err ) => ! cancelled && setError( err.message ) );

		return () => {
			cancelled = true;
		};
	}, [ count, connectToSearch, propertyType, location, status, orderBy ] );

	const blockProps = useBlockProps( {
		className: 'estatify-grid',
		style: { '--estatify-columns': columns },
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'estatify' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Columns', 'estatify' ) }
						value={ columns }
						min={ 1 }
						max={ 4 }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Listings per page', 'estatify' ) }
						value={ count }
						min={ 1 }
						max={ 24 }
						onChange={ ( value ) =>
							setAttributes( { count: value } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Listings', 'estatify' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Connect to Property Search', 'estatify' ) }
						help={
							connectToSearch
								? __(
										'Results follow the Property Search block on this page.',
										'estatify'
									)
								: __(
										'Shows a fixed selection using the filters below.',
										'estatify'
									)
						}
						checked={ connectToSearch }
						onChange={ ( value ) =>
							setAttributes( { connectToSearch: value } )
						}
					/>
					{ connectToSearch && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show pagination', 'estatify' ) }
							checked={ showPagination }
							onChange={ ( value ) =>
								setAttributes( { showPagination: value } )
							}
						/>
					) }
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Default order', 'estatify' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Newest first', 'estatify' ),
								value: 'newest',
							},
							{
								label: __( 'Price: low to high', 'estatify' ),
								value: 'price_asc',
							},
							{
								label: __( 'Price: high to low', 'estatify' ),
								value: 'price_desc',
							},
							{
								label: __( 'Largest first', 'estatify' ),
								value: 'area_desc',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
					/>
				</PanelBody>
				{ ! connectToSearch && (
					<PanelBody title={ __( 'Filters', 'estatify' ) }>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Property type', 'estatify' ) }
							value={ propertyType }
							options={ termOptions(
								types,
								__( 'Any type', 'estatify' )
							) }
							onChange={ ( value ) =>
								setAttributes( { propertyType: value } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Neighbourhood', 'estatify' ) }
							value={ location }
							options={ termOptions(
								locations,
								__( 'Anywhere', 'estatify' )
							) }
							onChange={ ( value ) =>
								setAttributes( { location: value } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Status', 'estatify' ) }
							value={ status }
							options={ [
								{
									label: __( 'Any status', 'estatify' ),
									value: '',
								},
								...Object.entries(
									window.estatifyEditor?.statuses || {}
								).map( ( [ value, label ] ) => ( {
									value,
									label,
								} ) ),
							] }
							onChange={ ( value ) =>
								setAttributes( { status: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Exclude the current property',
								'estatify'
							) }
							help={ __(
								'Useful for "More homes" sections on single property pages.',
								'estatify'
							) }
							checked={ excludeCurrent }
							onChange={ ( value ) =>
								setAttributes( { excludeCurrent: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Use the archive term', 'estatify' ) }
							help={ __(
								'On type, location or feature archives, list that term’s properties.',
								'estatify'
							) }
							checked={ inheritArchive }
							onChange={ ( value ) =>
								setAttributes( { inheritArchive: value } )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>

			<div { ...blockProps }>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ ! items && ! error && <Spinner /> }
				{ items && items.length === 0 && (
					<div className="estatify-grid__empty">
						<p>
							{ __(
								'No properties match these settings.',
								'estatify'
							) }
						</p>
					</div>
				) }
				{ items && items.length > 0 && (
					<ul className="estatify-grid__list">
						{ items.map( ( property ) => (
							<PropertyCard
								key={ property.id }
								property={ property }
							/>
						) ) }
					</ul>
				) }
			</div>
		</>
	);
}
