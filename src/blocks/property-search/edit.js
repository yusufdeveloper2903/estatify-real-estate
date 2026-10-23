import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

const STATUSES = [
	__( 'All', 'estatify' ),
	__( 'For sale', 'estatify' ),
	__( 'For rent', 'estatify' ),
	__( 'Sold', 'estatify' ),
];

function PreviewSelect( { label, any, options = [] } ) {
	return (
		<div className="estatify-field">
			<label>{ label }</label>
			<select tabIndex={ -1 } defaultValue="">
				<option value="">{ any }</option>
				{ options.map( ( option ) => (
					<option key={ option.id } value={ option.slug }>
						{ option.name }
					</option>
				) ) }
			</select>
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { showKeyword, showStatus, showPrice, showSort } = attributes;
	const blockProps = useBlockProps( {
		className: 'estatify-search is-preview',
	} );

	const { types, locations } = useSelect( ( select ) => {
		const { getEntityRecords } = select( coreStore );
		const query = {
			per_page: 50,
			hide_empty: true,
			_fields: 'id,name,slug',
		};
		return {
			types: getEntityRecords( 'taxonomy', 'property_type', query ) || [],
			locations:
				getEntityRecords( 'taxonomy', 'property_location', query ) ||
				[],
		};
	}, [] );

	const symbol = window.estatifyEditor?.settings?.currency_symbol || '€';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filters', 'estatify' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Status tabs (sale / rent / sold)',
							'estatify'
						) }
						checked={ showStatus }
						onChange={ ( value ) =>
							setAttributes( { showStatus: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Keyword field', 'estatify' ) }
						checked={ showKeyword }
						onChange={ ( value ) =>
							setAttributes( { showKeyword: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Price range', 'estatify' ) }
						checked={ showPrice }
						onChange={ ( value ) =>
							setAttributes( { showPrice: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Sort menu', 'estatify' ) }
						checked={ showSort }
						onChange={ ( value ) =>
							setAttributes( { showSort: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<form
					className="estatify-search__form"
					onSubmit={ ( e ) => e.preventDefault() }
				>
					{ showStatus && (
						<div className="estatify-search__status">
							{ STATUSES.map( ( label, index ) => (
								<label className="estatify-pill" key={ label }>
									<input
										type="radio"
										readOnly
										checked={ index === 0 }
									/>
									<span>{ label }</span>
								</label>
							) ) }
						</div>
					) }
					<div className="estatify-search__fields">
						{ showKeyword && (
							<div className="estatify-field estatify-field--keyword">
								<label>{ __( 'Keyword', 'estatify' ) }</label>
								<input
									type="search"
									readOnly
									placeholder={ __(
										'Terrace, river view, garden…',
										'estatify'
									) }
								/>
							</div>
						) }
						<PreviewSelect
							label={ __( 'Property type', 'estatify' ) }
							any={ __( 'Any type', 'estatify' ) }
							options={ types }
						/>
						<PreviewSelect
							label={ __( 'Neighbourhood', 'estatify' ) }
							any={ __( 'Anywhere', 'estatify' ) }
							options={ locations }
						/>
						<PreviewSelect
							label={ __( 'Bedrooms', 'estatify' ) }
							any={ __( 'Any', 'estatify' ) }
						/>
						{ showPrice && (
							<div className="estatify-field estatify-field--price">
								<span className="estatify-field__label">
									{ __( 'Price', 'estatify' ) }
								</span>
								<div className="estatify-field__range">
									<input
										type="number"
										readOnly
										placeholder={ `Min ${ symbol }` }
									/>
									<span aria-hidden="true">–</span>
									<input
										type="number"
										readOnly
										placeholder={ `Max ${ symbol }` }
									/>
								</div>
							</div>
						) }
					</div>
					<div className="estatify-search__bar">
						<p className="estatify-search__count">
							{ __(
								'Results update live on the front end',
								'estatify'
							) }
						</p>
						<div className="estatify-search__actions">
							{ showSort && (
								<label className="estatify-sort">
									<span>{ __( 'Sort', 'estatify' ) }</span>
									<select
										tabIndex={ -1 }
										defaultValue="newest"
									>
										<option value="newest">
											{ __( 'Newest first', 'estatify' ) }
										</option>
									</select>
								</label>
							) }
							<button
								type="button"
								className="estatify-button estatify-button--ghost"
								tabIndex={ -1 }
							>
								{ __( 'Reset', 'estatify' ) }
							</button>
						</div>
					</div>
				</form>
			</div>
		</>
	);
}
