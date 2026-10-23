<?php
/**
 * Property Search — server render.
 *
 * @package Estatify
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

use Estatify\Blocks\SearchState;
use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

SearchState::bootstrap();

$estatify_filters = SearchState::url_filters();
$estatify_symbol  = (string) Settings::get( 'currency_symbol' );

$estatify_term_options = static function ( string $taxonomy ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);
	return is_array( $terms ) ? wp_list_pluck( $terms, 'name', 'slug' ) : array();
};

$estatify_selects = array(
	'type'     => array(
		'param'   => 'ptype',
		'label'   => __( 'Property type', 'estatify' ),
		'any'     => __( 'Any type', 'estatify' ),
		'options' => $estatify_term_options( PropertyPostType::TAX_TYPE ),
	),
	'location' => array(
		'param'   => 'loc',
		'label'   => __( 'Neighbourhood', 'estatify' ),
		'any'     => __( 'Anywhere', 'estatify' ),
		'options' => $estatify_term_options( PropertyPostType::TAX_LOCATION ),
	),
	'beds'     => array(
		'param'   => 'beds',
		'label'   => __( 'Bedrooms', 'estatify' ),
		'any'     => __( 'Any', 'estatify' ),
		'options' => array(
			'1' => __( '1+ beds', 'estatify' ),
			'2' => __( '2+ beds', 'estatify' ),
			'3' => __( '3+ beds', 'estatify' ),
			'4' => __( '4+ beds', 'estatify' ),
		),
	),
);

$estatify_sorts = array(
	'newest'     => __( 'Newest first', 'estatify' ),
	'price_asc'  => __( 'Price: low to high', 'estatify' ),
	'price_desc' => __( 'Price: high to low', 'estatify' ),
	'area_desc'  => __( 'Largest first', 'estatify' ),
);

$estatify_statuses = array_merge( array( '' => __( 'All', 'estatify' ) ), PropertyMeta::statuses() );
$estatify_uid      = wp_unique_id( 'estatify-search-' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'estatify-search' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="estatify">
	<form class="estatify-search__form" method="get" role="search" aria-label="<?php esc_attr_e( 'Filter properties', 'estatify' ); ?>"
		data-wp-on--submit="actions.submit" data-wp-on--change="actions.updateFilter" data-wp-on--input="actions.updateFilter">

		<?php if ( ! empty( $attributes['showStatus'] ) ) : ?>
			<fieldset class="estatify-search__status">
				<legend class="screen-reader-text"><?php esc_html_e( 'Listing status', 'estatify' ); ?></legend>
				<?php foreach ( $estatify_statuses as $estatify_value => $estatify_label ) : ?>
					<label class="estatify-pill">
						<input type="radio" name="status" value="<?php echo esc_attr( $estatify_value ); ?>" <?php checked( $estatify_filters['status'], $estatify_value ); ?> />
						<span><?php echo esc_html( $estatify_label ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>

		<div class="estatify-search__fields">
			<?php if ( ! empty( $attributes['showKeyword'] ) ) : ?>
				<div class="estatify-field estatify-field--keyword">
					<label for="<?php echo esc_attr( $estatify_uid . '-q' ); ?>"><?php esc_html_e( 'Keyword', 'estatify' ); ?></label>
					<input id="<?php echo esc_attr( $estatify_uid . '-q' ); ?>" type="search" name="q" autocomplete="off"
						placeholder="<?php esc_attr_e( 'Terrace, river view, garden…', 'estatify' ); ?>"
						value="<?php echo esc_attr( $estatify_filters['search'] ); ?>" />
				</div>
			<?php endif; ?>

			<?php foreach ( $estatify_selects as $estatify_name => $estatify_select ) : ?>
				<div class="estatify-field">
					<label for="<?php echo esc_attr( $estatify_uid . '-' . $estatify_name ); ?>"><?php echo esc_html( $estatify_select['label'] ); ?></label>
					<select id="<?php echo esc_attr( $estatify_uid . '-' . $estatify_name ); ?>" name="<?php echo esc_attr( $estatify_select['param'] ); ?>">
						<option value=""><?php echo esc_html( $estatify_select['any'] ); ?></option>
						<?php foreach ( $estatify_select['options'] as $estatify_value => $estatify_label ) : ?>
							<option value="<?php echo esc_attr( (string) $estatify_value ); ?>" <?php selected( $estatify_filters[ $estatify_name ], (string) $estatify_value ); ?>><?php echo esc_html( $estatify_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endforeach; ?>

			<?php if ( ! empty( $attributes['showPrice'] ) ) : ?>
				<div class="estatify-field estatify-field--price">
					<span class="estatify-field__label" id="<?php echo esc_attr( $estatify_uid . '-price' ); ?>"><?php esc_html_e( 'Price', 'estatify' ); ?></span>
					<div class="estatify-field__range" role="group" aria-labelledby="<?php echo esc_attr( $estatify_uid . '-price' ); ?>">
						<input type="number" name="min_price" min="0" step="1000" inputmode="numeric"
							aria-label="<?php esc_attr_e( 'Minimum price', 'estatify' ); ?>"
							placeholder="<?php echo esc_attr( sprintf( /* translators: %s: currency symbol. */ __( 'Min %s', 'estatify' ), $estatify_symbol ) ); ?>"
							value="<?php echo esc_attr( $estatify_filters['min_price'] ); ?>" />
						<span aria-hidden="true">–</span>
						<input type="number" name="max_price" min="0" step="1000" inputmode="numeric"
							aria-label="<?php esc_attr_e( 'Maximum price', 'estatify' ); ?>"
							placeholder="<?php echo esc_attr( sprintf( /* translators: %s: currency symbol. */ __( 'Max %s', 'estatify' ), $estatify_symbol ) ); ?>"
							value="<?php echo esc_attr( $estatify_filters['max_price'] ); ?>" />
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="estatify-search__bar">
			<p class="estatify-search__count" aria-live="polite">
				<span class="estatify-spinner" aria-hidden="true" data-wp-class--is-active="state.loading"></span>
				<span data-wp-text="state.resultsLabel"></span>
			</p>
			<p class="estatify-search__error" role="alert" data-wp-text="state.error" data-wp-bind--hidden="!state.error"></p>
			<div class="estatify-search__actions">
				<?php if ( ! empty( $attributes['showSort'] ) ) : ?>
					<label class="estatify-sort">
						<span><?php esc_html_e( 'Sort', 'estatify' ); ?></span>
						<select name="sort">
							<?php foreach ( $estatify_sorts as $estatify_value => $estatify_label ) : ?>
								<option value="<?php echo esc_attr( $estatify_value ); ?>" <?php selected( $estatify_filters['sort'], $estatify_value ); ?>><?php echo esc_html( $estatify_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<button type="button" class="estatify-button estatify-button--ghost" data-wp-on--click="actions.reset">
					<?php esc_html_e( 'Reset', 'estatify' ); ?>
				</button>
			</div>
		</div>
		<noscript><button type="submit"><?php esc_html_e( 'Search', 'estatify' ); ?></button></noscript>
	</form>
</div>
