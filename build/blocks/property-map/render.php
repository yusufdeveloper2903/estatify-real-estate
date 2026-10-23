<?php
/**
 * Property Map — server render.
 *
 * @package Estatify
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

use Estatify\Blocks\SearchState;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Presenter;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

$estatify_height  = max( 200, min( 1200, (int) ( $attributes['height'] ?? 440 ) ) );
$estatify_zoom    = max( 2, min( 19, (int) ( $attributes['zoom'] ?? 13 ) ) );
$estatify_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$estatify_is_one  = $estatify_post_id && PropertyPostType::POST_TYPE === ( $block->context['postType'] ?? get_post_type( $estatify_post_id ) );

$estatify_context = array(
	'mode'       => $estatify_is_one ? 'single' : 'search',
	'zoom'       => $estatify_zoom,
	'scrollZoom' => ! empty( $attributes['scrollZoom'] ),
	'center'     => array( (float) Settings::get( 'map_lat' ), (float) Settings::get( 'map_lng' ) ),
	'markers'    => array(),
);

if ( $estatify_is_one ) {
	$estatify_marker = Presenter::marker( $estatify_post_id );
	if ( ! $estatify_marker ) {
		return;
	}
	$estatify_context['markers'] = array( $estatify_marker );
	$estatify_context['center']  = array( $estatify_marker['lat'], $estatify_marker['lng'] );
	$estatify_label              = __( 'Map showing the property location', 'estatify' );
} else {
	SearchState::bootstrap();
	wp_interactivity_state( 'estatify', array( 'hasMap' => true ) );
	$estatify_label = __( 'Map of matching properties', 'estatify' );
}

$estatify_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'estatify-map',
		'style' => 'height:' . $estatify_height . 'px;',
	)
);
?>
<div <?php echo $estatify_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="estatify"
	<?php echo wp_interactivity_data_wp_context( $estatify_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="estatify-map__canvas" role="region" aria-label="<?php echo esc_attr( $estatify_label ); ?>" data-wp-watch="callbacks.renderMap"></div>
</div>
