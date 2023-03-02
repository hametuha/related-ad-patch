<?php

namespace Hametuha\RelatedAdPatch;


use Hametuha\RelatedAdPatch\Pattern\SingletonPattern;

class Template extends SingletonPattern {

	protected function init() {
		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_assets() {
		$assets = [
			'/css/related-post.css' => [],
		];
		foreach ( $assets as $rel_path => $deps ) {
			$abs_path = dirname( dirname( dirname( __DIR__ ) ) ) . '/dist';
			$abs_url  = plugin_dir_url( $abs_path ) . 'dist' . $rel_path;
			$updated  = filemtime( $abs_path . $rel_path );
			$handle   = 'related-post-patch' . $rel_path;
			if ( preg_match( '/\.css$/u', $rel_path ) ) {
				// CSS.
				wp_register_style( $handle, $abs_url, $deps, $updated, 'all' );
			} elseif ( preg_match( '/\.js$/u', $rel_path ) ) {
				// JS.
				wp_register_script( $handle, $abs_url, $deps, $updated, true );
			}
		}
	}

	public function enqueue_assets() {
		if ( is_singular() ) {
			wp_enqueue_style( 'related-post-patch/css/related-post.css' );
		}
	}
}
