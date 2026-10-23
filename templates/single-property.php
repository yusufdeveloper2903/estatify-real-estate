<?php
/**
 * Block template: single property.
 *
 * @package Estatify
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:estatify/property-details {"align":"wide"} /-->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"60%"} -->
		<div class="wp-block-column" style="flex-basis:60%">
			<!-- wp:heading {"fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'About this home', 'estatify' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:post-content {"layout":{"type":"default"}} /-->

			<!-- wp:heading {"fontSize":"large","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
			<h2 class="wp-block-heading has-large-font-size" style="margin-top:var(--wp--preset--spacing--50)"><?php echo esc_html__( 'Location', 'estatify' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:estatify/property-map {"height":380,"zoom":15} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:estatify/mortgage-calculator /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:heading {"align":"wide","fontSize":"large","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
	<h2 class="wp-block-heading alignwide has-large-font-size" style="margin-top:var(--wp--preset--spacing--60)"><?php echo esc_html__( 'More homes you might like', 'estatify' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:estatify/property-grid {"count":3,"connectToSearch":false,"excludeCurrent":true,"align":"wide"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
<?php
return (string) ob_get_clean();
