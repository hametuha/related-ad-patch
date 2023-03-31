<?php

namespace Hametuha\RelatedAdPatch;


use Hametuha\RelatedAdPatch\Pattern\SingletonPattern;

/**
 * Calculate
 */
class Calculator extends SingletonPattern {

	/**
	 * Post type to calculate.
	 *
	 * @return string[]
	 */
	protected function post_types() {
		return apply_filters( 'related_posts_post_types', [ 'post' ] );
	}

	/**
	 * Is post type available?
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public function is_available( $post_type ) {
		return in_array( $post_type, $this->post_types(), true );
	}

	/**
	 * Taxonomy and score to calculate related posts.
	 *
	 * @param string $post_type Post type.
	 * @return array
	 */
	public function taxonomy_score( $post_type ) {
		return apply_filters( 'related_posts_taxonomy_score', [
			'category' => 1,
			'post_tag' => 3,
		], $post_type );
	}

	/**
	 * Get related posts.
	 *
	 * @param null|int|\WP_Post $post  Post object.
	 * @param int               $limit Number of related posts..
	 * @return \WP_Post[]
	 */
	public function get_related( $post = null, $limit = 10 ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return [];
		}
		// Get term ids to filter posts.
		$tt_ids = [];
		$scores = $this->taxonomy_score( $post->post_type );
		foreach ( array_keys( $scores ) as $taxonomy ) {
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
		// Build when clause.
		global $wpdb;
		$when = [];
		foreach ( $scores as $taxonomy => $score ) {
			$when[] = $wpdb->prepare( 'WHEN tt.taxonomy = %s THEN %d', $taxonomy, $score );
		}
		$when = implode( "\n", $when );
		$query = <<<SQL
			SELECT p.* FROM {$wpdb->posts} AS p
			INNER JOIN (
				SELECT r.object_id, SUM(
				    CASE
				        {$when}
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
