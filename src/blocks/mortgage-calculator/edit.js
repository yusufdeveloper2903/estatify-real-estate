import { __, _n, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

import { calculate, formatMoney, TERMS } from '../../shared/mortgage';

const settings = window.estatifyEditor?.settings || {};

export default function Edit( { attributes, setAttributes } ) {
	const {
		heading,
		price,
		downPayment,
		rate,
		years,
		usePropertyPrice,
		showBreakdown,
	} = attributes;
	const effectiveRate = rate ?? Number( settings.mortgage_rate ?? 3.5 );
	const currency = {
		symbol: settings.currency_symbol || '€',
		position: settings.currency_position || 'before',
	};
	const result = calculate( {
		price,
		downPayment,
		rate: effectiveRate,
		years,
	} );
	const money = ( amount ) => formatMoney( amount, currency );

	const blockProps = useBlockProps( { className: 'estatify-calc' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Default values', 'estatify' ) }>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Home price', 'estatify' ) }
						value={ price }
						min={ 0 }
						step={ 1000 }
						onChange={ ( value ) =>
							setAttributes( { price: Number( value ) || 0 } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Down payment (%)', 'estatify' ) }
						value={ downPayment }
						min={ 0 }
						max={ 90 }
						onChange={ ( value ) =>
							setAttributes( { downPayment: value } )
						}
					/>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Interest rate (%)', 'estatify' ) }
						help={ __(
							'Leave empty to use the rate from Properties → Settings.',
							'estatify'
						) }
						value={ rate ?? '' }
						placeholder={ String( settings.mortgage_rate ?? 3.5 ) }
						min={ 0 }
						max={ 25 }
						step={ 0.05 }
						onChange={ ( value ) =>
							setAttributes( {
								rate:
									value === '' || value === undefined
										? undefined
										: Number( value ),
							} )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Loan term', 'estatify' ) }
						value={ String( years ) }
						options={ TERMS.map( ( term ) => ( {
							value: String( term ),
							/* translators: %d: number of years. */
							label: sprintf(
								_n( '%d year', '%d years', term, 'estatify' ),
								term
							),
						} ) ) }
						onChange={ ( value ) =>
							setAttributes( { years: Number( value ) } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Behaviour', 'estatify' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Use the listing price on property pages',
							'estatify'
						) }
						checked={ usePropertyPrice }
						onChange={ ( value ) =>
							setAttributes( { usePropertyPrice: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show cost breakdown', 'estatify' ) }
						checked={ showBreakdown }
						onChange={ ( value ) =>
							setAttributes( { showBreakdown: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="estatify-calc__header">
					<RichText
						tagName="h3"
						className="estatify-calc__title"
						value={ heading }
						placeholder={ __( 'Mortgage calculator', 'estatify' ) }
						allowedFormats={ [] }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<p className="estatify-calc__subtitle">
						{ __(
							'Estimate your monthly repayment in seconds.',
							'estatify'
						) }
					</p>
				</div>
				<div className="estatify-calc__grid">
					<div className="estatify-calc__inputs">
						<div className="estatify-calc__field">
							<label>{ __( 'Home price', 'estatify' ) }</label>
							<div
								className={ `estatify-calc__affix ${ currency.position === 'after' ? 'is-after' : '' }` }
							>
								<span>{ currency.symbol }</span>
								<input
									type="number"
									value={ price }
									readOnly
									tabIndex={ -1 }
								/>
							</div>
						</div>
						<div className="estatify-calc__field">
							<div className="estatify-calc__label-row">
								<label>
									{ __( 'Down payment', 'estatify' ) }
								</label>
								<output>
									<strong>
										{ Math.round( downPayment ) }%
									</strong>{ ' ' }
									· { money( result.downAmount ) }
								</output>
							</div>
							<input
								type="range"
								min={ 0 }
								max={ 90 }
								value={ downPayment }
								readOnly
								tabIndex={ -1 }
							/>
						</div>
						<div className="estatify-calc__row">
							<div className="estatify-calc__field">
								<label>
									{ __( 'Interest rate', 'estatify' ) }
								</label>
								<div className="estatify-calc__affix is-after">
									<input
										type="number"
										value={ effectiveRate }
										readOnly
										tabIndex={ -1 }
									/>
									<span>%</span>
								</div>
							</div>
							<div className="estatify-calc__field">
								<label>{ __( 'Loan term', 'estatify' ) }</label>
								<select value={ String( years ) } disabled>
									{ TERMS.map( ( term ) => (
										<option
											key={ term }
											value={ String( term ) }
										>
											{ sprintf(
												_n(
													'%d year',
													'%d years',
													term,
													'estatify'
												),
												term
											) }
										</option>
									) ) }
								</select>
							</div>
						</div>
					</div>
					<div className="estatify-calc__result">
						<p className="estatify-calc__result-label">
							{ __( 'Estimated monthly payment', 'estatify' ) }
						</p>
						<p className="estatify-calc__monthly">
							{ money( result.monthly ) }
						</p>
						{ showBreakdown && (
							<>
								<div className="estatify-calc__bar">
									<span
										className="estatify-calc__bar-principal"
										style={ {
											width: `${ result.principalShare }%`,
										} }
									/>
								</div>
								<dl className="estatify-calc__breakdown">
									<div className="is-principal">
										<dt>
											{ __( 'Loan amount', 'estatify' ) }
										</dt>
										<dd>{ money( result.principal ) }</dd>
									</div>
									<div className="is-interest">
										<dt>
											{ __(
												'Total interest',
												'estatify'
											) }
										</dt>
										<dd>{ money( result.interest ) }</dd>
									</div>
									<div className="is-total">
										<dt>
											{ __( 'Total repaid', 'estatify' ) }
										</dt>
										<dd>{ money( result.totalPaid ) }</dd>
									</div>
								</dl>
							</>
						) }
						<p className="estatify-calc__note">
							{ __(
								'Illustrative estimate only. Excludes taxes, insurance and fees.',
								'estatify'
							) }
						</p>
					</div>
				</div>
			</div>
		</>
	);
}
