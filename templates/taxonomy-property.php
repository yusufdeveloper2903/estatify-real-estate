<?php
/**
 * Block template: property type / location / feature archives.
 *
 * @package Estatify
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","wideSize":"1240px"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:query-title {"type":"archive","showPrefix":false,"align":"wide"} /-->

	<!-- wp:term-description {"align":"wide"} /-->

	<!-- wp:estatify/property-grid {"count":12,"connectToSearch":false,"inheritArchive":true,"align":"wide"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
<?php
return (string) ob_get_clean();
