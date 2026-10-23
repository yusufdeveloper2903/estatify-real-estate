<?php
/**
 * Uninstall: remove plugin options. Listings (posts, terms, media) are kept
 * on purpose so content is never lost by deleting the plugin.
 *
 * @package Estatify
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'estatify_settings' );
delete_option( 'estatify_demo_seeded' );
