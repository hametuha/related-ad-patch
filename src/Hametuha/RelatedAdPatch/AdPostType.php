<?php

namespace Hametuha\RelatedAdPatch;


use Hametuha\RelatedAdPatch\Pattern\SingletonPattern;

/**
 * Ad post type.
 */
class AdPostType extends SingletonPattern {

	const POST_TYPE = 'related-post-ad';

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_post' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		// Template matters.
		add_action( 'related_post_link_attributes', [ $this, 'link_rel' ] );
		add_action( 'related_post_after_content', [ $this, 'link_after' ] );
		add_filter( 'related_post_patch_main_taxonomy', [ $this, 'taxonomy_filter' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'post_permalink' ], 10, 2 );
	}

	/**
	 * Register post types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		register_post_type( self::POST_TYPE, [
			'labels'            => [
				'name'          => __( 'Ads', 'rap' ),
				'singular_name' => __( 'Ad', 'rap' ),
			],
			'public'            => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'menu_position'     => 29,
			'menu_icon'         => 'dashicons-megaphone',
			'show_in_rest'      => false,
			'supports'          => [ 'title', 'excerpt', 'thumbnail', 'author', 'page-attributes' ],
		] );

		register_taxonomy( 'ad-source', [ self::POST_TYPE ], [
			'label'              => __( 'Advertiser', 'rap' ),
			'public'             => false,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_admin_columns' => true,
		] );
	}

	/**
	 * Save post meta.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_relatedpostadnonce' ), 'update_related_post_ad' ) ) {
			return;
		}
		foreach ( [
			'external_url',
			'optional_tag',
		] as $key ) {
			update_post_meta( $post_id, '_' . $key, filter_input( INPUT_POST, $key ) );
		}
	}

	/**
	 * Save post type.
	 *
	 * @param string $post_type Post type to save.
	 * @return void
	 */
	public function add_meta_box( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}
		add_meta_box( 'related-post-ad-post', __( 'Advertisemnet Setting', 'rap' ), function( \WP_Post $post ) {
			wp_nonce_field( 'update_related_post_ad', '_relatedpostadnonce', false );
			?>
			<label style="display: block; margin: 0 0 20px;">
				URL<br />
				<input type="url" class="regular-text" name="external_url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_external_url', true ) ); ?>" />
			</label>
			<label style="display: block; margin: 0 0 20px;">
				<?php esc_html_e( 'Optional Tag', 'rap' ); ?><br />
				<textarea name="optional_tag" style="box-sizing: border-box; width: 100%;" rows="5"><?php echo esc_textarea( get_post_meta( $post->ID, '_optional_tag', true ) ); ?></textarea>
			</label>
			<?php
		}, $post_type );
	}

	/**
	 * Post object.
	 *
	 * @param \WP_Post[] $posts    Posts in loop.
	 * @param int        $total    Slot total.
	 * @param int        $ad_count Ads to replace.
	 * @param string     $order    'rand', 'menu_order', 'date'
	 * @return \WP_Post[]
	 */
	public function add_ads( $posts, $total = 8, $ad_count = 2, $order = 'rand' ) {
		$ads = $this->get_ads( $ad_count, $order );
		if ( empty( $ads ) ) {
			return $posts;
		}
		$diff = $total - count( $posts );
		if ( 0 < $diff ) {
			// Simply push ads into posts.
			for ( $i = 0; $i < $diff; $i++ ) {
				if ( empty( $ads ) ) {
					// No more ads.
					break 1;
				}
				$posts[] = array_shift( $ads );
			}
		}
		foreach ( $ads as $ad ) {
			$key           = rand( 0, count( $posts ) - 1 );
			$posts[ $key ] = $ad;
		}
		return $posts;
	}

	/**
	 * Get advertisement.
	 *
	 * @param int    $ad_count Number of ads.
	 * @param string $order    'rand', 'menu_order', 'date'
	 * @return int[]|\WP_Post[]
	 */
	public function get_ads( $ad_count = 2, $order = 'rand' ) {
		$post_args = [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $ad_count,
		];
		switch ( $order ) {
			case 'rand':
				$post_args['orderby'] = 'rand';
				break;
			case 'menu_order':
				$post_args['orderby'] = [ 'menu_order' => 'DESC' ];
				break;
			default:
				// Date.
				$post_args['orderby'] = [ 'date' => 'DESC' ];
				break;
		}
		return get_posts( $post_args );
	}

	/**
	 * Add rel attributes.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function link_rel( $post ) {
		if ( related_post_is_ad( $post ) ) {
			echo 'rel="sponsored"';
		}
	}

	/**
	 * Add rel.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function link_after( $post ) {
		if ( ! related_post_is_ad( $post ) ) {
			return;
		}
		$after = get_post_meta( $post->ID, '_optional_tag', true );
		if ( $after ) {
			echo $after;
		}
	}

	/**
	 * Change taxonomy for this post.
	 *
	 * @param string   $taxonomy Taxonomy name.
	 * @param \WP_Post $post     Post object.
	 * @return string
	 */
	public function taxonomy_filter( $taxonomy, $post ) {
		if ( related_post_is_ad( $post ) ) {
			$taxonomy = 'ad-source';
		}
		return $taxonomy;
	}

	/**
	 * Get permalink.
	 *
	 * @param string   $link URL.
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public function post_permalink( $link, $post ) {
		if ( related_post_is_ad( $post ) ) {
			$url = get_post_meta( $post->ID, '_external_url', true );
			if ( $url ) {
				$link = $url;
			} else {
				$link = home_url( '' );
			}
		}
		return $link;
	}
}
