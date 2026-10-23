<?php
/**
 * Block template: property archive (/properties/).
 *
 * @package Estatify
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"level":1,"align":"wide"} -->
	<h1 class="wp-block-heading alignwide"><?php echo esc_html__( 'All properties', 'estatify' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:estatify/property-search {"align":"wide"} /-->

	<!-- wp:estatify/property-map {"align":"wide","height":420} /-->

	<!-- wp:estatify/property-grid {"count":9,"align":"wide"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
<?php
return (string) ob_get_clean();
