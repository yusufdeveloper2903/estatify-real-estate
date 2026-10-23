<?php
/**
 * REST endpoint: estatify/v1/properties.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Rest;

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Presenter;
use Estatify\Support\PropertyQuery;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Filterable, sortable, paginated listing search.
 *
 * GET /wp-json/estatify/v1/properties
 * GET /wp-json/estatify/v1/properties/<id>
 */
final class PropertiesController extends WP_REST_Controller {

	/**
	 * Requests per minute allowed per IP for anonymous clients.
	 */
	private const RATE_LIMIT = 120;

	/**
	 * Sets the namespace and base.
	 */
	public function __construct() {
		$this->namespace = 'estatify/v1';
		$this->rest_base = 'properties';
	}

	/**
	 * Registers the routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Property ID.', 'estatify' ),
							'type'        => 'integer',
							'minimum'     => 1,
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Public, read-only data. Anonymous clients are lightly rate limited;
	 * the endpoint only ever exposes published listings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! post_type_exists( PropertyPostType::POST_TYPE ) ) {
			return new WP_Error( 'estatify_unavailable', __( 'Listings are not available.', 'estatify' ), array( 'status' => 503 ) );
		}

		$post_type = get_post_type_object( PropertyPostType::POST_TYPE );
		if ( ! $post_type || ! $post_type->public ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to list properties.', 'estatify' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! is_user_logged_in() && $this->is_rate_limited() ) {
			return new WP_Error( 'estatify_rate_limited', __( 'Too many requests. Please slow down.', 'estatify' ), array( 'status' => 429 ) );
		}

		return true;
	}

	/**
	 * Single listings follow the same rules, plus the post must be readable.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$check = $this->get_items_permissions_check( $request );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$post = get_post( (int) $request['id'] );
		if ( ! $post || PropertyPostType::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid property ID.', 'estatify' ), array( 'status' => 404 ) );
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post->ID ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to view this property.', 'estatify' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Lists properties.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function get_items( $request ): WP_REST_Response {
		$filters = PropertyQuery::normalize( $request->get_params() );
		$result  = PropertyQuery::run( $filters, (bool) $request['with_markers'] );

		$response = rest_ensure_response( $result );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['total_pages'] );
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * A single property.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function get_item( $request ): WP_REST_Response {
		return rest_ensure_response( Presenter::card( (int) $request['id'] ) );
	}

	/**
	 * Query parameters, with types, enums and sanitizers so WordPress
	 * validates every request before our callback runs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		$slug_list = array(
			'type'              => array( 'string', 'array' ),
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => array( self::class, 'sanitize_slug_list' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return array(
			'page'         => array(
				'description' => __( 'Current page of the collection.', 'estatify' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page'     => array(
				'description' => __( 'Maximum number of items per page.', 'estatify' ),
				'type'        => 'integer',
				'default'     => 9,
				'minimum'     => 1,
				'maximum'     => PropertyQuery::MAX_PER_PAGE,
			),
			'search'       => array(
				'description'       => __( 'Keyword search in title and description.', 'estatify' ),
				'type'              => 'string',
				'maxLength'         => 100,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'         => array_merge( $slug_list, array( 'description' => __( 'Property type slug(s), comma separated.', 'estatify' ) ) ),
			'location'     => array_merge( $slug_list, array( 'description' => __( 'Location slug(s), comma separated.', 'estatify' ) ) ),
			'feature'      => array_merge( $slug_list, array( 'description' => __( 'Feature slug(s); listings must have all of them.', 'estatify' ) ) ),
			'status'       => array(
				'description' => __( 'Listing status.', 'estatify' ),
				'type'        => 'string',
				'enum'        => array_merge( array( '' ), array_keys( PropertyMeta::statuses() ) ),
			),
			'min_price'    => array(
				'description' => __( 'Minimum price.', 'estatify' ),
				'type'        => 'integer',
				'minimum'     => 0,
			),
			'max_price'    => array(
				'description' => __( 'Maximum price (0 = no limit).', 'estatify' ),
				'type'        => 'integer',
				'minimum'     => 0,
			),
			'beds'         => array(
				'description' => __( 'Minimum number of bedrooms.', 'estatify' ),
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => 20,
			),
			'baths'        => array(
				'description' => __( 'Minimum number of bathrooms.', 'estatify' ),
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => 20,
			),
			'orderby'      => array(
				'description' => __( 'Sort field.', 'estatify' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array_keys( PropertyQuery::ORDERBY ),
			),
			'order'        => array(
				'description' => __( 'Sort direction.', 'estatify' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			),
			'with_markers' => array(
				'description' => __( 'Include lightweight map markers for every match.', 'estatify' ),
				'type'        => 'boolean',
				'default'     => false,
			),
		);
	}

	/**
	 * JSON schema for a listing.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'estatify-property',
			'type'       => 'object',
			'properties' => array(
				'id'             => array( 'type' => 'integer' ),
				'title'          => array( 'type' => 'string' ),
				'link'           => array(
					'type'   => 'string',
					'format' => 'uri',
				),
				'excerpt'        => array( 'type' => 'string' ),
				'image'          => array( 'type' => 'string' ),
				'price'          => array( 'type' => 'number' ),
				'priceFormatted' => array( 'type' => 'string' ),
				'status'         => array(
					'type' => 'string',
					'enum' => array_keys( PropertyMeta::statuses() ),
				),
				'statusLabel'    => array( 'type' => 'string' ),
				'bedrooms'       => array( 'type' => 'integer' ),
				'bathrooms'      => array( 'type' => 'integer' ),
				'area'           => array( 'type' => 'number' ),
				'areaFormatted'  => array( 'type' => 'string' ),
				'address'        => array( 'type' => 'string' ),
				'lat'            => array( 'type' => 'number' ),
				'lng'            => array( 'type' => 'number' ),
				'type'           => array( 'type' => 'string' ),
				'location'       => array( 'type' => 'string' ),
				'features'       => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Accepts "a,b" or ["a","b"] and returns sanitized slugs.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	public static function sanitize_slug_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		return array_values( array_filter( array_map( 'sanitize_title', (array) $value ) ) );
	}

	/**
	 * Simple fixed-window limiter backed by transients.
	 */
	private function is_rate_limited(): bool {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $ip ) {
			return false;
		}

		$key   = 'estatify_rl_' . md5( $ip . gmdate( 'YmdHi' ) );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		/**
		 * Filters the anonymous requests-per-minute limit. Return 0 to disable.
		 *
		 * @param int $limit Requests per minute.
		 */
		$limit = (int) apply_filters( 'estatify_rest_rate_limit', self::RATE_LIMIT );

		return $limit > 0 && $count >= $limit;
	}
}
