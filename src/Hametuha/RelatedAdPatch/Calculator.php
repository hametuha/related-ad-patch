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
	 * Get logic for post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function get_logic_for_post( $post ) {
		$logic = get_option( 'rap-logic', '' );
		return apply_filters( 'related_posts_logic', $logic, $post );
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
		$logic = $this->get_logic_for_post( $post );
		switch ( $logic ) {
			case 'date':
				return $this->get_nearest_posts( $post, $limit );
			default:
				return $this->get_related_by_taxonomies( $post, $limit );
		}
	}

	/**
	 * Get related post by
	 *
	 * @param \WP_Post $post Post object.
	 * @param int       $limit
	 * @return \WP_Post[]
	 */
	public function get_nearest_posts( $post, $limit ) {
		$query_args = [
			'post_type'      => $post->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => [ $post->ID ],
			'orderby'        => [
				'date' => 'DESC',
			],
			'tax_query'      => [
				'relation' => 'OR',
			],
			'date_query'     => [
				[
					'before' => $post->post_date,
				],
			],
		];
		$scores     = $this->taxonomy_score( $post->post_type );
		foreach ( array_keys( $scores ) as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$query_args['tax_query'] [] = [
					'taxonomy' => $taxonomy,
					'terms'    => array_map( function( \WP_Term $term ) {
						return $term->term_id;
					}, $terms ),
					'fields'   => 'term_id',
				];
			}
		}
		$query_args = apply_filters( 'related_posts_nearest_posts', $query_args, $post );
		$query      = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Get related posts by taxonomies.
	 *
	 * @param \WP_Post $post Post object.
	 * @param int       $limit
	 * @return \WP_Post[]
	 */
	public function get_related_by_taxonomies( $post, $limit ) {
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
		$when  = implode( "\n", $when );
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
