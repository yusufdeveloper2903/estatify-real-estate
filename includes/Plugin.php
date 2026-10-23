<?php
/**
 * Plugin bootstrap.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify;

use Estatify\Admin\SettingsPage;
use Estatify\Blocks\Blocks;
use Estatify\Blocks\Templates;
use Estatify\CLI\Command;
use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Rest\PropertiesController;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every component to WordPress hooks.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns the shared instance.
	 */
	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	/**
	 * Registers all hooks. Runs on `plugins_loaded`.
	 */
	public function boot(): void {
		( new PropertyPostType() )->register_hooks();
		( new PropertyMeta() )->register_hooks();
		( new Blocks() )->register_hooks();
		( new Templates() )->register_hooks();
		( new SettingsPage() )->register_hooks();

		add_action(
			'rest_api_init',
			static function (): void {
				( new PropertiesController() )->register_routes();
			}
		);

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'estatify', Command::class );
		}
	}

	/**
	 * Activation: register content types and flush rewrite rules once.
	 */
	public static function activate(): void {
		( new PropertyPostType() )->register();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: drop our rewrite rules.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
