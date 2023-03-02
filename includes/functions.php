<?php
/**
 * Utility functions
 */

/**
 * Load template.
 *
 * @param string $name   File name.
 * @param string $suffix Suffix.
 * @param array  $args   Option arguments.
 * @return void
 */
function related_post_get_template_part( $name, $suffix = '', $args = [] ) {
	$dirs = [
		get_template_directory() . '/template-parts/related-post',
		dirname( __DIR__ ) . '/template-parts',
	];
	if ( get_template_directory() !== get_stylesheet_directory() ) {
		array_unshift( $dirs, get_stylesheet_directory() . '/template-parts/related-post' );
	}
	$files = [ $name . '.php' ];
	if ( $suffix ) {
		array_unshift( $files, $name . '-' . $suffix . '.php' );
	}
	$found = '';
	foreach ( $files as $file ) {
		foreach ( $dirs as $dir ) {
			$path = $dir . '/' . $file;
			if ( file_exists( $path ) ) {
				$found = $path;
				break 2;
			}
		}
	}
	$found = apply_filters( 'related_post_patch_template', $found, $name, $suffix, $args );
	if ( file_exists( $found ) ) {
		load_template( $found, false, $args );
	}
}

/**
 * Get related posts.
 *
 * @param int|null|WP_Post $post  Post object.
 * @param int              $count Total count.
 * @param int              $ads   Ads to include.
 * @return WP_Post[]
 */
function related_post_patch( $post = null, $count = 8, $ads = 2 ) {
	$results = \Hametuha\RelatedAdPatch\Calculator::get_instance()->get_related( $post, $count );
	$results = \Hametuha\RelatedAdPatch\AdPostType::get_instance()->add_ads( $results, $count, $ads, 'rand' );
	return $results;
}

/**
 * Get related posts html.
 *
 * @param int|null|WP_Post $post
 * @param int              $count
 * @return string
 */
function get_the_related_post_patch( $post = null, $count = 8 ) {
	$results = related_post_patch( $post, $count );
	ob_start();
	global $post;
	echo '<div class="related-posts">';
	foreach ( $results as $post ) {
		setup_postdata( $post );
		related_post_get_template_part( 'loop', get_post_type() );
	}
	wp_reset_postdata();
	echo '</div>';
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

/**
 * Render related posts.
 *
 * @param int|null|WP_Post $post  Post object.
 * @param int              $count Number to display.
 * @return void
 */
function the_related_post_patch( $post = null, $count = 8 ) {
	echo get_the_related_post_patch( $post, $count );
}

/**
 * Get related posts terms.
 *
 * @param int|null|WP_Post $post  Post object.
 * @return WP_Term[]
 */
function related_post_get_main_terms( $post = null ) {
	$post     = get_post( $post );
	$taxonomy = apply_filters( 'related_post_patch_main_taxonomy', 'post_tag', $post );
	$terms    = get_the_terms( $post, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return [];
	}
	return $terms;
}

/**
 * Is ad content?
 *
 * @param int|null|WP_Post $post  Post object.
 * @return bool
 */
function related_post_is_ad( $post = null ) {
	return \Hametuha\RelatedAdPatch\AdPostType::POST_TYPE === get_post_type( $post );
}
