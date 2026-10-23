<?php
/**
 * Mortgage Calculator — server render.
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
use Estatify\Support\Mortgage;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

$estatify_price = (float) ( $attributes['price'] ?? 450000 );
$estatify_down  = (float) ( $attributes['downPayment'] ?? 20 );
$estatify_rate  = isset( $attributes['rate'] ) ? (float) $attributes['rate'] : (float) Settings::get( 'mortgage_rate' );
$estatify_years = (int) ( $attributes['years'] ?? 30 );

// On a single property for sale, start from the listing price.
$estatify_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
if ( ! empty( $attributes['usePropertyPrice'] ) && $estatify_post_id && PropertyPostType::POST_TYPE === get_post_type( $estatify_post_id ) ) {
	$estatify_status = (string) get_post_meta( $estatify_post_id, PropertyMeta::STATUS, true );
	$estatify_listed = (float) get_post_meta( $estatify_post_id, PropertyMeta::PRICE, true );
	if ( PropertyMeta::STATUS_RENT === $estatify_status ) {
		return; // A mortgage makes no sense for a rental.
	}
	if ( $estatify_listed > 0 ) {
		$estatify_price = $estatify_listed;
	}
}

$estatify_result = Mortgage::calculate( $estatify_price, $estatify_down, $estatify_rate, $estatify_years );
$estatify_terms  = array( 10, 15, 20, 25, 30, 35, 40 );
$estatify_symbol = (string) Settings::get( 'currency_symbol' );
$estatify_after  = 'after' === Settings::get( 'currency_position' );
$estatify_uid    = wp_unique_id( 'estatify-calc-' );
$estatify_head   = '' !== trim( (string) ( $attributes['heading'] ?? '' ) ) ? (string) $attributes['heading'] : __( 'Mortgage calculator', 'estatify' );

$estatify_context = array(
	'price'          => $estatify_price,
	'downPayment'    => $estatify_down,
	'rate'           => $estatify_rate,
	'years'          => $estatify_years,
	'symbol'         => $estatify_symbol,
	'position'       => $estatify_after ? 'after' : 'before',
	'monthlyLabel'   => Format::money( $estatify_result['monthly'] ),
	'downLabel'      => Format::money( $estatify_result['downAmount'] ),
	'principalLabel' => Format::money( $estatify_result['principal'] ),
	'interestLabel'  => Format::money( $estatify_result['interest'] ),
	'totalLabel'     => Format::money( $estatify_result['totalPaid'] ),
	'downPctLabel'   => round( $estatify_down ) . '%',
	'principalStyle' => 'width:' . $estatify_result['principalShare'] . '%',
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'estatify-calc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="estatify/calculator"
	<?php echo wp_interactivity_data_wp_context( $estatify_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="estatify-calc__header">
		<h3 class="estatify-calc__title"><?php echo esc_html( $estatify_head ); ?></h3>
		<p class="estatify-calc__subtitle"><?php esc_html_e( 'Estimate your monthly repayment in seconds.', 'estatify' ); ?></p>
	</div>

	<div class="estatify-calc__grid">
		<div class="estatify-calc__inputs">
			<div class="estatify-calc__field">
				<label for="<?php echo esc_attr( $estatify_uid . '-price' ); ?>"><?php esc_html_e( 'Home price', 'estatify' ); ?></label>
				<div class="estatify-calc__affix <?php echo $estatify_after ? 'is-after' : ''; ?>">
					<span aria-hidden="true"><?php echo esc_html( $estatify_symbol ); ?></span>
					<input id="<?php echo esc_attr( $estatify_uid . '-price' ); ?>" type="number" name="price" min="0" step="1000" inputmode="numeric"
						value="<?php echo esc_attr( (string) $estatify_price ); ?>" data-wp-on--input="actions.update" />
				</div>
			</div>

			<div class="estatify-calc__field">
				<div class="estatify-calc__label-row">
					<label for="<?php echo esc_attr( $estatify_uid . '-down' ); ?>"><?php esc_html_e( 'Down payment', 'estatify' ); ?></label>
					<output for="<?php echo esc_attr( $estatify_uid . '-down' ); ?>">
						<strong data-wp-text="context.downPctLabel"></strong> · <span data-wp-text="context.downLabel"></span>
					</output>
				</div>
				<input id="<?php echo esc_attr( $estatify_uid . '-down' ); ?>" type="range" name="downPayment" min="0" max="90" step="1"
					value="<?php echo esc_attr( (string) $estatify_down ); ?>" data-wp-on--input="actions.update" />
			</div>

			<div class="estatify-calc__row">
				<div class="estatify-calc__field">
					<label for="<?php echo esc_attr( $estatify_uid . '-rate' ); ?>"><?php esc_html_e( 'Interest rate', 'estatify' ); ?></label>
					<div class="estatify-calc__affix is-after">
						<input id="<?php echo esc_attr( $estatify_uid . '-rate' ); ?>" type="number" name="rate" min="0" max="25" step="0.05" inputmode="decimal"
							value="<?php echo esc_attr( (string) $estatify_rate ); ?>" data-wp-on--input="actions.update" />
						<span aria-hidden="true">%</span>
					</div>
				</div>
				<div class="estatify-calc__field">
					<label for="<?php echo esc_attr( $estatify_uid . '-years' ); ?>"><?php esc_html_e( 'Loan term', 'estatify' ); ?></label>
					<select id="<?php echo esc_attr( $estatify_uid . '-years' ); ?>" name="years" data-wp-on--change="actions.update">
						<?php foreach ( $estatify_terms as $estatify_term ) : ?>
							<option value="<?php echo esc_attr( (string) $estatify_term ); ?>" <?php selected( $estatify_years, $estatify_term ); ?>>
								<?php
								/* translators: %d: number of years. */
								echo esc_html( sprintf( _n( '%d year', '%d years', $estatify_term, 'estatify' ), $estatify_term ) );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<div class="estatify-calc__result" aria-live="polite">
			<p class="estatify-calc__result-label"><?php esc_html_e( 'Estimated monthly payment', 'estatify' ); ?></p>
			<p class="estatify-calc__monthly" data-wp-text="context.monthlyLabel"></p>

			<?php if ( ! empty( $attributes['showBreakdown'] ) ) : ?>
				<div class="estatify-calc__bar" aria-hidden="true">
					<span class="estatify-calc__bar-principal" data-wp-bind--style="context.principalStyle"></span>
				</div>
				<dl class="estatify-calc__breakdown">
					<div class="is-principal">
						<dt><?php esc_html_e( 'Loan amount', 'estatify' ); ?></dt>
						<dd data-wp-text="context.principalLabel"></dd>
					</div>
					<div class="is-interest">
						<dt><?php esc_html_e( 'Total interest', 'estatify' ); ?></dt>
						<dd data-wp-text="context.interestLabel"></dd>
					</div>
					<div class="is-total">
						<dt><?php esc_html_e( 'Total repaid', 'estatify' ); ?></dt>
						<dd data-wp-text="context.totalLabel"></dd>
					</div>
				</dl>
			<?php endif; ?>
			<p class="estatify-calc__note"><?php esc_html_e( 'Illustrative estimate only. Excludes taxes, insurance and fees.', 'estatify' ); ?></p>
		</div>
	</div>
</div>
