<?php
/**
 * Property Details — server render.
 *
 * @package Estatify
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Format;
use Estatify\Support\Icons;
use Estatify\Support\Presenter;

defined( 'ABSPATH' ) || exit;

$estatify_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

// In the Site Editor (no property in context) preview the latest listing.
if ( ( ! $estatify_post_id || PropertyPostType::POST_TYPE !== get_post_type( $estatify_post_id ) ) && wp_is_serving_rest_request() ) {
	$estatify_latest  = get_posts(
		array(
			'post_type'      => PropertyPostType::POST_TYPE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$estatify_post_id = (int) ( $estatify_latest[0] ?? 0 );
}

if ( ! $estatify_post_id || PropertyPostType::POST_TYPE !== get_post_type( $estatify_post_id ) ) {
	return;
}

$estatify_card = Presenter::card( $estatify_post_id );
$estatify_meta = Presenter::meta( $estatify_post_id );

// Gallery: cover photo first, then the gallery meta (deduplicated).
$estatify_ids = array_values(
	array_unique(
		array_filter(
			array_merge( array( (int) get_post_thumbnail_id( $estatify_post_id ) ), $estatify_meta['gallery'] )
		)
	)
);

$estatify_images = array();
foreach ( $estatify_ids as $estatify_id ) {
	$estatify_src = wp_get_attachment_image_url( $estatify_id, 'full' );
	if ( $estatify_src ) {
		$estatify_images[] = array(
			'src'   => $estatify_src,
			'thumb' => (string) wp_get_attachment_image_url( $estatify_id, 'medium' ),
			'alt'   => Presenter::image_alt( $estatify_id, $estatify_card['title'] ),
		);
	}
}

$estatify_specs = array(
	array( 'bed', __( 'Bedrooms', 'estatify' ), (string) $estatify_card['bedrooms'] ),
	array( 'bath', __( 'Bathrooms', 'estatify' ), (string) $estatify_card['bathrooms'] ),
	array( 'area', __( 'Living area', 'estatify' ), $estatify_card['areaFormatted'] ),
	array( 'home', __( 'Type', 'estatify' ), $estatify_card['type'] ? $estatify_card['type'] : '—' ),
	array( 'pin', __( 'Neighbourhood', 'estatify' ), $estatify_card['location'] ? $estatify_card['location'] : '—' ),
	array( 'tag', __( 'Status', 'estatify' ), $estatify_card['statusLabel'] ),
);

if ( PropertyMeta::STATUS_RENT !== $estatify_card['status'] && $estatify_card['price'] > 0 && $estatify_card['area'] > 0 ) {
	$estatify_specs[] = array( 'ruler', __( 'Price per m²', 'estatify' ), Format::money( $estatify_card['price'] / $estatify_card['area'] ) );
}

wp_interactivity_state(
	'estatify/details',
	array(
		'activeSrc'     => static function (): string {
			$context = wp_interactivity_get_context();
			return (string) ( $context['images'][ $context['active'] ]['src'] ?? '' );
		},
		'activeAlt'     => static function (): string {
			$context = wp_interactivity_get_context();
			return (string) ( $context['images'][ $context['active'] ]['alt'] ?? '' );
		},
		'counter'       => static function (): string {
			$context = wp_interactivity_get_context();
			return ( (int) $context['active'] + 1 ) . ' / ' . count( (array) $context['images'] );
		},
		'isActiveThumb' => static function (): bool {
			$context = wp_interactivity_get_context();
			$element = wp_interactivity_get_element();
			return isset( $element['attributes']['data-index'] ) && (int) $element['attributes']['data-index'] === (int) $context['active'];
		},
	)
);

$estatify_gallery = ! empty( $attributes['showGallery'] ) && $estatify_images;
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'estatify-details is-' . esc_attr( $estatify_card['status'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="estatify/details"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'images' => $estatify_images,
			'active' => 0,
		)
	);
	?>
	aria-label="<?php esc_attr_e( 'Property details', 'estatify' ); ?>">

	<?php if ( ! empty( $attributes['showHeader'] ) ) : ?>
		<header class="estatify-details__header">
			<div class="estatify-details__heading">
				<p class="estatify-details__eyebrow">
					<span class="estatify-badge"><?php echo esc_html( $estatify_card['statusLabel'] ); ?></span>
					<?php if ( $estatify_card['type'] || $estatify_card['location'] ) : ?>
						<span><?php echo esc_html( implode( ' · ', array_filter( array( $estatify_card['type'], $estatify_card['location'] ) ) ) ); ?></span>
					<?php endif; ?>
				</p>
				<h1 class="estatify-details__title"><?php echo esc_html( $estatify_card['title'] ); ?></h1>
				<?php if ( $estatify_card['address'] ) : ?>
					<p class="estatify-details__address"><?php Icons::render( 'pin', 17 ); ?><?php echo esc_html( $estatify_card['address'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="estatify-details__price">
				<span class="estatify-details__price-label">
					<?php echo esc_html( PropertyMeta::STATUS_RENT === $estatify_card['status'] ? __( 'Monthly rent', 'estatify' ) : __( 'Asking price', 'estatify' ) ); ?>
				</span>
				<strong><?php echo esc_html( $estatify_card['priceFormatted'] ); ?></strong>
			</div>
		</header>
	<?php endif; ?>

	<?php if ( $estatify_gallery ) : ?>
		<div class="estatify-gallery" data-wp-on--keydown="actions.keydown">
			<figure class="estatify-gallery__main">
				<img data-wp-bind--src="state.activeSrc" data-wp-bind--alt="state.activeAlt" width="1200" height="800" fetchpriority="high" />
				<?php if ( count( $estatify_images ) > 1 ) : ?>
					<button type="button" class="estatify-gallery__nav is-prev" data-wp-on--click="actions.prev" aria-label="<?php esc_attr_e( 'Previous photo', 'estatify' ); ?>"><?php Icons::render( 'chevronL', 22 ); ?></button>
					<button type="button" class="estatify-gallery__nav is-next" data-wp-on--click="actions.next" aria-label="<?php esc_attr_e( 'Next photo', 'estatify' ); ?>"><?php Icons::render( 'chevronR', 22 ); ?></button>
					<span class="estatify-gallery__counter" data-wp-text="state.counter"></span>
				<?php endif; ?>
			</figure>
			<?php if ( count( $estatify_images ) > 1 ) : ?>
				<ul class="estatify-gallery__thumbs" role="list">
					<?php foreach ( $estatify_images as $estatify_index => $estatify_image ) : ?>
						<li>
							<button type="button" data-index="<?php echo esc_attr( (string) $estatify_index ); ?>"
								data-wp-on--click="actions.select" data-wp-class--is-active="state.isActiveThumb"
								data-wp-bind--aria-pressed="state.isActiveThumb"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %d: photo number. */ __( 'Show photo %d', 'estatify' ), $estatify_index + 1 ) ); ?>">
								<img src="<?php echo esc_url( $estatify_image['thumb'] ); ?>" alt="" loading="lazy" width="300" height="200" />
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<dl class="estatify-specs">
		<?php foreach ( $estatify_specs as $estatify_spec ) : ?>
			<div class="estatify-specs__item">
				<dt><?php Icons::render( $estatify_spec[0], 20 ); ?><?php echo esc_html( $estatify_spec[1] ); ?></dt>
				<dd><?php echo esc_html( $estatify_spec[2] ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>

	<?php if ( ! empty( $attributes['showFeatures'] ) && $estatify_card['features'] ) : ?>
		<div class="estatify-features">
			<h2 class="estatify-features__title"><?php esc_html_e( 'Features', 'estatify' ); ?></h2>
			<ul class="estatify-features__list" role="list">
				<?php foreach ( $estatify_card['features'] as $estatify_feature ) : ?>
					<li><?php Icons::render( 'check', 16 ); ?><?php echo esc_html( $estatify_feature ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
