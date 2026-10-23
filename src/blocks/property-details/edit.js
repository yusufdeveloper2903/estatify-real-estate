import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import { BuildingIcon } from '../../shared/icons';

const EmptyPlaceholder = () => (
	<Placeholder
		icon={ <BuildingIcon size={ 24 } /> }
		label={ __( 'Property Details', 'estatify' ) }
		instructions={ __(
			'Shows the gallery, price and spec table of the current property. Add it to the Single Property template or to a property.',
			'estatify'
		) }
	/>
);

export default function Edit( { attributes, setAttributes, name, context } ) {
	const { showGallery, showFeatures, showHeader } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Sections', 'estatify' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Title and price', 'estatify' ) }
						checked={ showHeader }
						onChange={ ( value ) =>
							setAttributes( { showHeader: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Photo gallery', 'estatify' ) }
						checked={ showGallery }
						onChange={ ( value ) =>
							setAttributes( { showGallery: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Features list', 'estatify' ) }
						checked={ showFeatures }
						onChange={ ( value ) =>
							setAttributes( { showFeatures: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block={ name }
					attributes={ attributes }
					urlQueryArgs={
						context.postId ? { post_id: context.postId } : {}
					}
					EmptyResponsePlaceholder={ EmptyPlaceholder }
					LoadingResponsePlaceholder={ () => <Spinner /> }
				/>
			</div>
		</>
	);
}
