<?php
/**
 * Plugin Name:       Estatify
 * Plugin URI:        https://github.com/yusufdeveloper2903/estatify-real-estate
 * Description:       Real-estate listings for block themes: a Property post type, a filterable REST API and Gutenberg blocks for search, grid, map, mortgage calculator and property details.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Yusuf Yuldashev
 * Author URI:        https://github.com/yusufdeveloper2903
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       estatify
 * Domain Path:       /languages
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify;

defined( 'ABSPATH' ) || exit;

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;

define( 'ESTATIFY_DIR', plugin_dir_path( __FILE__ ) );
define( 'ESTATIFY_URL', plugin_dir_url( __FILE__ ) );

require_once ESTATIFY_DIR . 'includes/Autoloader.php';

Autoloader::register( __NAMESPACE__, ESTATIFY_DIR . 'includes/' );

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( Plugin::instance(), 'boot' ) );
