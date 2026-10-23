<?php
/**
 * Property Grid — server render.
 *
 * Cards are rendered from a single `<template data-wp-each>` so the same
 * markup is used for the server render and for client-side updates.
 *
 * @package Estatify
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

use Estatify\Blocks\SearchState;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Icons;
use Estatify\Support\PropertyQuery;

defined( 'ABSPATH' ) || exit;

$estatify_columns   = max( 1, min( 4, (int) ( $attributes['columns'] ?? 3 ) ) );
$estatify_count     = max( 1, min( PropertyQuery::MAX_PER_PAGE, (int) ( $attributes['count'] ?? 6 ) ) );
$estatify_connected = ! empty( $attributes['connectToSearch'] );
$estatify_context   = array();

if ( $estatify_connected ) {
	SearchState::bootstrap( $estatify_count );
	$estatify_source = 'state.results';
} else {
	$estatify_filters = array(
		'type'     => (string) ( $attributes['propertyType'] ?? '' ),
		'location' => (string) ( $attributes['location'] ?? '' ),
		'status'   => (string) ( $attributes['status'] ?? '' ),
		'sort'     => (string) ( $attributes['orderBy'] ?? 'newest' ),
		'page'     => '1',
	);

	$estatify_query = SearchState::to_query( $estatify_filters, $estatify_count );

	// On a property taxonomy archive, optionally list that term's properties.
	if ( ! empty( $attributes['inheritArchive'] ) && is_tax( array( PropertyPostType::TAX_TYPE, PropertyPostType::TAX_LOCATION, PropertyPostType::TAX_FEATURE ) ) ) {
		$estatify_term = get_queried_object();
		if ( $estatify_term instanceof WP_Term ) {
			$estatify_map = array(
				PropertyPostType::TAX_TYPE     => 'type',
				PropertyPostType::TAX_LOCATION => 'location',
				PropertyPostType::TAX_FEATURE  => 'feature',
			);
			$estatify_query[ $estatify_map[ $estatify_term->taxonomy ] ] = $estatify_term->slug;
		}
	}

	$estatify_current = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
	if ( ! empty( $attributes['excludeCurrent'] ) && $estatify_current ) {
		$estatify_query['exclude'] = array( $estatify_current );
	}

	$estatify_result  = PropertyQuery::run( PropertyQuery::normalize( $estatify_query ) );
	$estatify_context = array( 'items' => $estatify_result['items'] );
	$estatify_source  = 'context.items';

	if ( empty( $estatify_result['items'] ) ) {
		return;
	}
}

$estatify_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'estatify-grid',
		'style' => '--estatify-columns:' . $estatify_columns . ';',
	)
);
?>
<div <?php echo $estatify_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="estatify"
	<?php if ( $estatify_context ) : ?>
		<?php echo wp_interactivity_data_wp_context( $estatify_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php else : ?>
		data-wp-class--is-loading="state.loading"
	<?php endif; ?>
>
	<ul class="estatify-grid__list" role="list">
		<template data-wp-each--property="<?php echo esc_attr( $estatify_source ); ?>" data-wp-each-key="context.property.id">
			<li class="estatify-card" data-wp-bind--data-status="context.property.status">
				<a class="estatify-card__media" data-wp-bind--href="context.property.link" tabindex="-1" aria-hidden="true">
					<img data-wp-bind--src="context.property.image" data-wp-bind--alt="context.property.imageAlt" loading="lazy" decoding="async" width="768" height="512" />
					<span class="estatify-card__badge" data-wp-text="context.property.statusLabel"></span>
				</a>
				<div class="estatify-card__body">
					<p class="estatify-card__price" data-wp-text="context.property.priceFormatted"></p>
					<h3 class="estatify-card__title"><a data-wp-bind--href="context.property.link" data-wp-text="context.property.title"></a></h3>
					<p class="estatify-card__address">
						<?php Icons::render( 'pin', 15 ); ?>
						<span data-wp-text="context.property.address"></span>
					</p>
					<ul class="estatify-card__specs" role="list">
						<li><?php Icons::render( 'bed' ); ?><span data-wp-text="context.property.bedsLabel"></span></li>
						<li><?php Icons::render( 'bath' ); ?><span data-wp-text="context.property.bathsLabel"></span></li>
						<li><?php Icons::render( 'area' ); ?><span data-wp-text="context.property.areaFormatted"></span></li>
					</ul>
				</div>
			</li>
		</template>
	</ul>

	<?php if ( $estatify_connected ) : ?>
		<div class="estatify-grid__empty" data-wp-bind--hidden="state.hasResults">
			<p><?php esc_html_e( 'No homes match these filters yet. Try widening the price range or choosing another neighbourhood.', 'estatify' ); ?></p>
			<button type="button" class="estatify-button" data-wp-on--click="actions.reset"><?php esc_html_e( 'Clear all filters', 'estatify' ); ?></button>
		</div>

		<?php if ( ! empty( $attributes['showPagination'] ) ) : ?>
			<nav class="estatify-pager" aria-label="<?php esc_attr_e( 'Listings pagination', 'estatify' ); ?>" data-wp-bind--hidden="state.hidePager">
				<button type="button" class="estatify-button estatify-button--ghost" data-wp-on--click="actions.prevPage" data-wp-bind--disabled="state.isFirstPage">
					<?php Icons::render( 'chevronL', 16 ); ?>
					<?php esc_html_e( 'Previous', 'estatify' ); ?>
				</button>
				<span class="estatify-pager__label" data-wp-text="state.pageLabel"></span>
				<button type="button" class="estatify-button estatify-button--ghost" data-wp-on--click="actions.nextPage" data-wp-bind--disabled="state.isLastPage">
					<?php esc_html_e( 'Next', 'estatify' ); ?>
					<?php Icons::render( 'chevronR', 16 ); ?>
				</button>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
