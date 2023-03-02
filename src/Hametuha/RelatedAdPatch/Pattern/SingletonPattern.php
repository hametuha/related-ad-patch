<?php

namespace Hametuha\RelatedAdPatch\Pattern;


use phpDocumentor\Reflection\Types\Void_;

/**
 * Singleton pattern.
 */
abstract class SingletonPattern {

	/**
	 * @var static[] Instance holders.
	 */
	private static $instances = [];

	/**
	 * Constructor
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Initialized in constructor.
	 *
	 * @return Void
	 */
	protected function init() {
		// Do something.
	}

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function get_instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}
}
