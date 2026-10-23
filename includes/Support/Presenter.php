<?php
/**
 * Converts property posts into arrays for REST responses and block state.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * One source of truth for the shape of a listing, so the server-rendered
 * markup and the client-side (REST) updates always match.
 */
final class Presenter {

	/**
	 * Raw meta values for a property.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function meta( int $post_id ): array {
		$status = (string) get_post_meta( $post_id, PropertyMeta::STATUS, true );

		return array(
			'price'     => (float) get_post_meta( $post_id, PropertyMeta::PRICE, true ),
			'bedrooms'  => (int) get_post_meta( $post_id, PropertyMeta::BEDROOMS, true ),
			'bathrooms' => (int) get_post_meta( $post_id, PropertyMeta::BATHROOMS, true ),
			'area'      => (float) get_post_meta( $post_id, PropertyMeta::AREA, true ),
			'address'   => (string) get_post_meta( $post_id, PropertyMeta::ADDRESS, true ),
			'lat'       => (float) get_post_meta( $post_id, PropertyMeta::LAT, true ),
			'lng'       => (float) get_post_meta( $post_id, PropertyMeta::LNG, true ),
			'status'    => '' === $status ? PropertyMeta::STATUS_SALE : $status,
			'gallery'   => array_map( 'absint', (array) get_post_meta( $post_id, PropertyMeta::GALLERY, true ) ),
		);
	}

	/**
	 * Card data: everything the grid, the map popup and the REST API need.
	 *
	 * @param WP_Post|int $post Post.
	 * @return array<string, mixed>
	 */
	public static function card( $post ): array {
		$post     = get_post( $post );
		$id       = (int) $post->ID;
		$meta     = self::meta( $id );
		$statuses = PropertyMeta::statuses();
		$thumb_id = (int) get_post_thumbnail_id( $id );

		return array(
			'id'             => $id,
			'title'          => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'link'           => (string) get_permalink( $id ),
			'excerpt'        => wp_strip_all_tags( get_the_excerpt( $id ) ),
			'image'          => $thumb_id ? (string) wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '',
			'imageAlt'       => self::image_alt( $thumb_id, get_the_title( $id ) ),
			'price'          => $meta['price'],
			'priceFormatted' => Format::price( $meta['price'], $meta['status'] ),
			'status'         => $meta['status'],
			'statusLabel'    => $statuses[ $meta['status'] ] ?? '',
			'bedrooms'       => $meta['bedrooms'],
			'bathrooms'      => $meta['bathrooms'],
			'area'           => $meta['area'],
			'areaFormatted'  => Format::area( $meta['area'] ),
			/* translators: %d: number of bedrooms. */
			'bedsLabel'      => sprintf( _n( '%d bed', '%d beds', $meta['bedrooms'], 'estatify' ), $meta['bedrooms'] ),
			/* translators: %d: number of bathrooms. */
			'bathsLabel'     => sprintf( _n( '%d bath', '%d baths', $meta['bathrooms'], 'estatify' ), $meta['bathrooms'] ),
			'address'        => $meta['address'],
			'lat'            => $meta['lat'],
			'lng'            => $meta['lng'],
			'type'           => self::first_term_name( $id, PropertyPostType::TAX_TYPE ),
			'location'       => self::first_term_name( $id, PropertyPostType::TAX_LOCATION ),
			'features'       => self::term_names( $id, PropertyPostType::TAX_FEATURE ),
		);
	}

	/**
	 * Lightweight marker data, or null when the listing has no coordinates.
	 *
	 * @param WP_Post|int $post Post.
	 * @return array<string, mixed>|null
	 */
	public static function marker( $post ): ?array {
		$card = self::card( $post );

		if ( 0.0 === $card['lat'] && 0.0 === $card['lng'] ) {
			return null;
		}

		return array(
			'id'             => $card['id'],
			'title'          => $card['title'],
			'link'           => $card['link'],
			'image'          => $card['image'],
			'lat'            => $card['lat'],
			'lng'            => $card['lng'],
			'status'         => $card['status'],
			'priceFormatted' => $card['priceFormatted'],
			'priceShort'     => self::short_price( $card['price'], $card['status'] ),
			'summary'        => implode( ' · ', array( $card['bedsLabel'], $card['bathsLabel'], $card['areaFormatted'] ) ),
		);
	}

	/**
	 * Compact price for map pins: €485k, €1.2M, €1,850.
	 *
	 * @param float  $price  Price.
	 * @param string $status Listing status.
	 */
	public static function short_price( float $price, string $status ): string {
		$symbol = (string) Settings::get( 'currency_symbol' );

		if ( $price >= 1000000 ) {
			$value = rtrim( rtrim( number_format_i18n( $price / 1000000, 1 ), '0' ), '.,' ) . 'M';
		} elseif ( $price >= 10000 ) {
			$value = number_format_i18n( $price / 1000, 0 ) . 'k';
		} else {
			$value = number_format_i18n( $price, 0 );
		}

		$value = 'after' === Settings::get( 'currency_position' ) ? $value . ' ' . $symbol : $symbol . $value;

		return PropertyMeta::STATUS_RENT === $status ? $value . '/mo' : $value;
	}

	/**
	 * Attachment alt text, falling back to the listing title.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $fallback      Fallback text.
	 */
	public static function image_alt( int $attachment_id, string $fallback ): string {
		$alt = $attachment_id ? trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';
		return html_entity_decode( '' !== $alt ? $alt : $fallback, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 */
	private static function first_term_name( int $post_id, string $taxonomy ): string {
		$names = self::term_names( $post_id, $taxonomy );
		return $names[0] ?? '';
	}

	/**
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string[]
	 */
	private static function term_names( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_map(
			static fn( \WP_Term $term ): string => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			$terms
		);
	}
}
