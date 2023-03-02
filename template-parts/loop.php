<?php
/**
 * Loop inside post.
 */
?>
<div class="related-posts-item related-posts-item-<?php echo esc_attr( get_post_type() ); ?>">
	<a class="related-posts-link" href="<?php the_permalink(); ?>"<?php do_action( 'related_post_link_attributes', get_post() ); ?>>
		<div class="related-posts-thumbnail related-posts-thumbnail-<?php echo esc_attr( get_post_type() ); ?>">
			<?php the_post_thumbnail(); ?>
		</div>
		<strong class="related-posts-title">
			<?php the_title(); ?>
		</strong>
		<span class="related-posts-meta">
			<span class="related-posts-meta-label">
				<?php
				if ( related_post_is_ad() ) {
					echo 'PR';
				} else {
					esc_html_e( 'Tag', 'rap' );
				}
				?>
			</span>
			<span class="related-posts-meta-tags">
				<?php
				// List terms.
				$terms = related_post_get_main_terms();
				if ( empty( $terms ) ) {
					echo '---';
				} else {
					echo esc_html( implode( ', ', array_map( function( $term ) {
						return $term->name;
					}, $terms ) ) );
				}
				?>
			</span>
		</span>
		<?php do_action( 'related_post_after_content', get_post() ); ?>
	</a>
</div>
