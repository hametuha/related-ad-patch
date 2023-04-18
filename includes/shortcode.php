<?php
/**
 * Short code related functions.
 */

/**
 * Display related posts.
 */
add_shortcode( 'related_pot_patch', function( $attr = [], $content = '' ) {
	$attr = shortcode_atts(  [
		'count' => 8,
		'ads'   => 2,
	], $attr, 'related_post_patch' );
	return get_the_related_post_patch( null, $attr['count'], $attr['ads'] );
} );
