<?php
/**
 * Demo content importer (used by blueprint.json and `wp estatify seed`).
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Demo;

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Creates fictional Lisbon listings, bundled photos, demo pages and a menu.
 */
final class Seeder {

	private const FLAG = 'estatify_demo_seeded';

	/**
	 * Attachment IDs by image name, so shared photos are imported once.
	 *
	 * @var array<string, int>
	 */
	private array $attachments = array();

	/**
	 * Log callback.
	 *
	 * @var callable
	 */
	private $log;

	/**
	 * @param callable|null $log Receives progress messages.
	 */
	public function __construct( ?callable $log = null ) {
		$this->log = $log ?? static function ( string $message ): void {}; // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	}

	/**
	 * Static entry point for blueprints.
	 *
	 * @param bool $force Seed again even if demo content exists.
	 */
	public static function run( bool $force = false ): void {
		( new self() )->seed( $force );
	}

	/**
	 * Runs the import.
	 *
	 * @param bool $force Seed again even if demo content exists.
	 * @throws RuntimeException When the data file is missing.
	 */
	public function seed( bool $force = false ): void {
		if ( get_option( self::FLAG ) && ! $force ) {
			( $this->log )( 'Demo content already exists. Use --force to import again.' );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = ESTATIFY_DIR . 'demo/listings.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'demo/listings.json is missing or invalid.' );
		}

		if ( ! get_current_user_id() ) {
			$admins = get_users(
				array(
					'role'   => 'administrator',
					'number' => 1,
					'fields' => 'ID',
				)
			);
			wp_set_current_user( (int) ( $admins[0] ?? 1 ) );
		}

		$this->site_settings();
		$this->terms( $data );
		$ids = $this->listings( (array) $data['listings'] );
		$this->pages();
		$this->cleanup_defaults();

		update_option( self::FLAG, gmdate( 'c' ) );
		flush_rewrite_rules( false );

		( $this->log )( sprintf( 'Imported %d listings.', count( $ids ) ) );
	}

	/**
	 * Site title, permalinks and plugin settings.
	 */
	private function site_settings(): void {
		update_option( 'blogname', 'Estatify' );
		update_option( 'blogdescription', 'Homes for sale and rent in Lisbon' );
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'thumbnail_size_w', 300 );
		update_option( 'thumbnail_size_h', 300 );
		update_option(
			'estatify_settings',
			array(
				'currency_symbol'   => '€',
				'currency_position' => 'before',
				'area_unit'         => 'sqm',
				'mortgage_rate'     => 3.4,
				'map_lat'           => 38.7223,
				'map_lng'           => -9.1393,
				'map_zoom'          => 13,
			)
		);
	}

	/**
	 * Types, locations (with descriptions) and features.
	 *
	 * @param array<string, mixed> $data Demo data.
	 */
	private function terms( array $data ): void {
		foreach ( (array) $data['types'] as $name ) {
			$this->ensure_term( (string) $name, PropertyPostType::TAX_TYPE );
		}
		foreach ( (array) $data['locations'] as $location ) {
			$this->ensure_term( (string) $location['name'], PropertyPostType::TAX_LOCATION, (string) ( $location['description'] ?? '' ) );
		}
		foreach ( (array) $data['features'] as $name ) {
			$this->ensure_term( (string) $name, PropertyPostType::TAX_FEATURE );
		}
	}

	/**
	 * Inserts a term unless it exists.
	 *
	 * @param string $name        Term name.
	 * @param string $taxonomy    Taxonomy.
	 * @param string $description Description.
	 */
	private function ensure_term( string $name, string $taxonomy, string $description = '' ): int {
		$existing = term_exists( $name, $taxonomy );
		if ( $existing ) {
			return (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
		}
		$term = wp_insert_term( $name, $taxonomy, array( 'description' => $description ) );
		return is_wp_error( $term ) ? 0 : (int) $term['term_id'];
	}

	/**
	 * Creates the listings, newest first in the JSON file.
	 *
	 * @param array<int, array<string, mixed>> $listings Listings.
	 * @return int[] Created post IDs.
	 */
	private function listings( array $listings ): array {
		$ids   = array();
		$count = count( $listings );

		foreach ( $listings as $index => $listing ) {
			$content = '';
			foreach ( (array) $listing['description'] as $paragraph ) {
				$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( (string) $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
			}

			// Stagger dates so "Newest first" follows the order in the file.
			$date = gmdate( 'Y-m-d H:i:s', time() - ( $index * DAY_IN_SECONDS * 3 ) - HOUR_IN_SECONDS );

			$post_id = wp_insert_post(
				array(
					'post_type'     => PropertyPostType::POST_TYPE,
					'post_status'   => 'publish',
					'post_title'    => (string) $listing['title'],
					'post_excerpt'  => (string) $listing['excerpt'],
					'post_content'  => $content,
					'post_date_gmt' => $date,
					'post_date'     => get_date_from_gmt( $date ),
					'meta_input'    => array(
						PropertyMeta::PRICE     => (float) $listing['price'],
						PropertyMeta::BEDROOMS  => (int) $listing['bedrooms'],
						PropertyMeta::BATHROOMS => (int) $listing['bathrooms'],
						PropertyMeta::AREA      => (float) $listing['area'],
						PropertyMeta::ADDRESS   => (string) $listing['address'],
						PropertyMeta::LAT       => (float) $listing['lat'],
						PropertyMeta::LNG       => (float) $listing['lng'],
						PropertyMeta::STATUS    => (string) $listing['status'],
					),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				( $this->log )( 'Skipped "' . $listing['title'] . '": ' . $post_id->get_error_message() );
				continue;
			}

			wp_set_object_terms( $post_id, array( (string) $listing['type'] ), PropertyPostType::TAX_TYPE );
			wp_set_object_terms( $post_id, array( (string) $listing['location'] ), PropertyPostType::TAX_LOCATION );
			wp_set_object_terms( $post_id, array_map( 'strval', (array) $listing['features'] ), PropertyPostType::TAX_FEATURE );

			$cover = $this->attachment( (string) $listing['image'], (string) $listing['title'], $post_id );
			if ( $cover ) {
				set_post_thumbnail( $post_id, $cover );
			}

			$gallery = array();
			foreach ( (array) $listing['gallery'] as $image ) {
				$label = ucwords( str_replace( array( 'interior-', '-' ), array( '', ' ' ), preg_replace( '/-\d+$/', '', (string) $image ) ) );
				$id    = $this->attachment( (string) $image, $label . ' — ' . $listing['title'], $post_id );
				if ( $id ) {
					$gallery[] = $id;
				}
			}
			update_post_meta( $post_id, PropertyMeta::GALLERY, $gallery );

			$ids[] = (int) $post_id;
			( $this->log )( sprintf( '[%d/%d] %s', $index + 1, $count, $listing['title'] ) );
		}

		return $ids;
	}

	/**
	 * Imports a bundled JPEG into the media library (once per image).
	 *
	 * @param string $name    Image name without extension.
	 * @param string $alt     Alt text.
	 * @param int    $parent  Parent post ID.
	 */
	private function attachment( string $name, string $alt, int $parent ): int {
		if ( isset( $this->attachments[ $name ] ) ) {
			return $this->attachments[ $name ];
		}

		$source = ESTATIFY_DIR . 'demo/images/' . sanitize_file_name( $name ) . '.jpg';
		if ( ! is_readable( $source ) ) {
			return 0;
		}

		$upload = wp_upload_bits( basename( $source ), null, (string) file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			( $this->log )( 'Upload failed for ' . $name . ': ' . $upload['error'] );
			return 0;
		}

		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $alt,
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$parent
		);

		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );

		$this->attachments[ $name ] = (int) $id;
		return (int) $id;
	}

	/**
	 * "Find a home" (front page) and "Mortgage calculator" pages, plus the menu.
	 */
	private function pages(): void {
		$find = <<<'HTML'
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"760px","justifyContent":"left"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20)"><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.2rem, 5vw, 3.4rem)","lineHeight":"1.08","letterSpacing":"-0.02em"}}} -->
<h1 class="wp-block-heading" style="font-size:clamp(2.2rem, 5vw, 3.4rem);letter-spacing:-0.02em;line-height:1.08">Find your place in Lisbon</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.15rem"}}} -->
<p style="font-size:1.15rem">Townhouses in Alfama, riverside apartments and family villas. Filter by type, neighbourhood, budget and bedrooms: results and the map update instantly.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:estatify/property-search {"align":"wide"} /-->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"},"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:column {"width":"58%"} -->
<div class="wp-block-column" style="flex-basis:58%"><!-- wp:estatify/property-grid {"columns":2,"count":6} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"42%"} -->
<div class="wp-block-column" style="flex-basis:42%"><!-- wp:estatify/property-map {"height":720,"className":"is-sticky"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML;

		$calculator = <<<'HTML'
<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.15rem"}}} -->
<p style="font-size:1.15rem">See what a home would cost you each month. Change the price, deposit, interest rate or term, and the estimate updates as you type.</p>
<!-- /wp:paragraph -->

<!-- wp:estatify/mortgage-calculator {"heading":"Your monthly payment","price":650000,"downPayment":20,"years":30,"align":"wide"} /-->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Rates are examples only. Speak to a lender for a personalised offer.</p>
<!-- /wp:paragraph -->
HTML;

		$home_id = $this->upsert_page( 'find-a-home', 'Find a home', $find, 'page-no-title' );
		$calc_id = $this->upsert_page( 'mortgage-calculator', 'Mortgage calculator', $calculator );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );

		$archive = (string) get_post_type_archive_link( PropertyPostType::POST_TYPE );
		$links   = array(
			array( 'Find a home', (string) get_permalink( $home_id ), $home_id, 'page' ),
			array( 'All properties', $archive ? $archive : home_url( '/properties/' ), 0, '' ),
			array( 'Mortgage calculator', (string) get_permalink( $calc_id ), $calc_id, 'page' ),
		);

		$nav = '';
		foreach ( $links as $link ) {
			$attrs = array(
				'label' => $link[0],
				'url'   => $link[1],
				'kind'  => $link[2] ? 'post-type' : 'custom',
			);
			if ( $link[2] ) {
				$attrs['id']   = $link[2];
				$attrs['type'] = $link[3];
			}
			$nav .= '<!-- wp:navigation-link ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
		}

		// The theme header's Navigation block falls back to the newest menu.
		$existing = get_page_by_path( 'estatify-main-menu', OBJECT, 'wp_navigation' );
		wp_insert_post(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_name'    => 'estatify-main-menu',
				'post_title'   => 'Main menu',
				'post_content' => $nav,
			)
		);
	}

	/**
	 * Creates or updates a page by slug.
	 *
	 * @param string $slug     Slug.
	 * @param string $title    Title.
	 * @param string $content  Block markup.
	 * @param string $template Block template slug.
	 */
	private function upsert_page( string $slug, string $title, string $content, string $template = '' ): int {
		$existing = get_page_by_path( $slug );
		$id       = wp_insert_post(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);

		if ( $template && $id ) {
			update_post_meta( (int) $id, '_wp_page_template', $template );
		}

		return (int) $id;
	}

	/**
	 * Removes WordPress' "Hello world!" post and "Sample Page".
	 */
	private function cleanup_defaults(): void {
		foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $default ) {
			$post = get_page_by_path( $default[0], OBJECT, $default[1] );
			if ( $post ) {
				wp_delete_post( $post->ID, true );
			}
		}
	}
}
