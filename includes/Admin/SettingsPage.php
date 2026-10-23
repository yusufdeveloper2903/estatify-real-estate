<?php
/**
 * Settings → Estatify (Settings API).
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Admin;

use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the option, sections and fields, and renders the page.
 */
final class SettingsPage {

	private const PAGE  = 'estatify-settings';
	private const GROUP = 'estatify_settings_group';

	/**
	 * Hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( \Estatify\PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Adds the page under the Properties menu.
	 */
	public function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . PropertyPostType::POST_TYPE,
			__( 'Estatify Settings', 'estatify' ),
			__( 'Settings', 'estatify' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'edit.php?post_type=' . PropertyPostType::POST_TYPE . '&page=' . self::PAGE );
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'estatify' ) ) );
		return $links;
	}

	/**
	 * Registers the option and its fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'estatify_display',
			__( 'Display', 'estatify' ),
			static function (): void {
				echo '<p>' . esc_html__( 'How prices and areas are shown on the front end.', 'estatify' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_section(
			'estatify_defaults',
			__( 'Defaults', 'estatify' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Fallback values used by the Map and Mortgage Calculator blocks.', 'estatify' ) . '</p>';
			},
			self::PAGE
		);

		$this->field( 'currency_symbol', __( 'Currency symbol', 'estatify' ), 'estatify_display', 'text', array( 'class' => 'small-text' ) );
		$this->field(
			'currency_position',
			__( 'Symbol position', 'estatify' ),
			'estatify_display',
			'select',
			array(
				'options' => array(
					'before' => __( 'Before the amount (€250,000)', 'estatify' ),
					'after'  => __( 'After the amount (250,000 €)', 'estatify' ),
				),
			)
		);
		$this->field(
			'area_unit',
			__( 'Area unit', 'estatify' ),
			'estatify_display',
			'select',
			array(
				'options'     => array(
					'sqm'  => __( 'Square metres (m²)', 'estatify' ),
					'sqft' => __( 'Square feet (ft²)', 'estatify' ),
				),
				'description' => __( 'Areas are stored in m² and converted for display.', 'estatify' ),
			)
		);
		$this->field(
			'mortgage_rate',
			__( 'Default interest rate (%)', 'estatify' ),
			'estatify_defaults',
			'number',
			array(
				'step'  => '0.05',
				'min'   => '0',
				'max'   => '25',
				'class' => 'small-text',
			)
		);
		$this->field( 'map_lat', __( 'Map centre latitude', 'estatify' ), 'estatify_defaults', 'number', array( 'step' => 'any', 'class' => 'regular-text' ) );
		$this->field( 'map_lng', __( 'Map centre longitude', 'estatify' ), 'estatify_defaults', 'number', array( 'step' => 'any', 'class' => 'regular-text' ) );
		$this->field(
			'map_zoom',
			__( 'Map zoom', 'estatify' ),
			'estatify_defaults',
			'number',
			array(
				'min'   => '2',
				'max'   => '19',
				'class' => 'small-text',
			)
		);
	}

	/**
	 * Adds one field.
	 *
	 * @param string               $key     Setting key.
	 * @param string               $label   Label.
	 * @param string               $section Section ID.
	 * @param string               $type    text|number|select.
	 * @param array<string, mixed> $args    Extra arguments.
	 */
	private function field( string $key, string $label, string $section, string $type, array $args = array() ): void {
		add_settings_field(
			'estatify_' . $key,
			$label,
			array( $this, 'render_field' ),
			self::PAGE,
			$section,
			array_merge(
				$args,
				array(
					'key'       => $key,
					'type'      => $type,
					'label_for' => 'estatify_' . $key,
				)
			)
		);
	}

	/**
	 * Renders one field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_field( array $args ): void {
		$key   = (string) $args['key'];
		$value = Settings::get( $key );
		$id    = 'estatify_' . $key;
		$name  = Settings::OPTION . '[' . $key . ']';

		if ( 'select' === $args['type'] ) {
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( (array) $args['options'] as $option => $text ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( (string) $option ),
					selected( (string) $value, (string) $option, false ),
					esc_html( (string) $text )
				);
			}
			echo '</select>';
		} else {
			$attrs = '';
			foreach ( array( 'step', 'min', 'max' ) as $attr ) {
				if ( isset( $args[ $attr ] ) ) {
					$attrs .= sprintf( ' %s="%s"', $attr, esc_attr( (string) $args[ $attr ] ) );
				}
			}
			printf(
				'<input type="%s" id="%s" name="%s" value="%s" class="%s"%s />',
				esc_attr( (string) $args['type'] ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				esc_attr( (string) ( $args['class'] ?? 'regular-text' ) ),
				$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		}

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( (string) $args['description'] ) );
		}
	}

	/**
	 * Renders the page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
