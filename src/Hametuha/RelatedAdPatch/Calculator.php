<?php

namespace Hametuha\RelatedAdPatch;


use Hametuha\RelatedAdPatch\Pattern\SingletonPattern;

/**
 * Calculate
 */
class Calculator extends SingletonPattern {

	public function get_related( $post = null, $limit = 10 ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return [];
		}
		$tt_ids = [];
		foreach ( [ 'category', 'post_tag' ] as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$tt_ids[] = $term->term_taxonomy_id;
				}
			}
		}
		if ( empty( $tt_ids ) ) {
			return [];
		}
		$tt_ids = implode( ',', $tt_ids );
		global $wpdb;
		$query = <<<SQL
			SELECT p.* FROM {$wpdb->posts} AS p
			INNER JOIN (
				SELECT r.object_id, SUM(
				    CASE
				        WHEN tt.taxonomy = 'category'
				    		THEN 1
						WHEN tt.taxonomy = 'post_tag'
				    		THEN 3
				    	ELSE 0
					END
				) AS score
				FROM {$wpdb->term_relationships} AS r
				LEFT JOIN {$wpdb->term_taxonomy} AS tt
				ON r.term_taxonomy_id = tt.term_taxonomy_id
				WHERE r.term_taxonomy_id IN ({$tt_ids})
				  AND r.object_id != %d
				GROUP BY r.object_id
			) AS s
			ON p.ID = s.object_id
			WHERE p.post_type = %s
			  AND p.post_status = 'publish'
			ORDER BY
			    s.score DESC,
			    p.post_date DESC
			LIMIT %d
SQL;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results( $wpdb->prepare( $query, $post->ID, $post->post_type, $limit ) );
		return array_map( function( $row ) {
			return new \WP_Post( $row );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}, $result );
	}
}
