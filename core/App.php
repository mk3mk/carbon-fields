<?php

namespace Carbon_Fields;

use Carbon_Fields\Pimple\Container as PimpleContainer;
use Carbon_Fields\Loader\Loader;
use Carbon_Fields\Container\Repository as ContainerRepository;
use Carbon_Fields\Toolset\Key_Toolset;
use Carbon_Fields\Toolset\WP_Toolset;
use Carbon_Fields\Service\Meta_Query_Service;
use Carbon_Fields\Service\Legacy_Storage_Service_v_1_5;
use Carbon_Fields\Service\REST_API_Service;
use Carbon_Fields\Libraries\Sidebar_Manager\Sidebar_Manager;

use Carbon_Fields\REST_API\Router as REST_API_Router;
use Carbon_Fields\REST_API\Decorator as REST_API_Decorator;

use Carbon_Fields\Container\Condition\Factory as ConditionFactory;
use Carbon_Fields\Container\Condition\Fulfillable_Collection;

/**
 * Holds a static reference to the ioc container
 */
class App {

	/**
	 * Flag if Carbon Fields has been booted
	 * 
	 * @var bool
	 */
	public $booted = false;

	/**
	 * Inversion of Control container instance
	 * 
	 * @var PimpleContainer
	 */
	protected $ioc = null;

	/**
	 * Singleton implementation
	 *
	 * @return App
	 */
	public static function instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Get default IoC container dependencies
	 * 
	 * @return PimpleContainer
	 */
	protected static function get_default_ioc() {
		$ioc = new PimpleContainer();

		$ioc['loader'] = function( $ioc ) {
			return new Loader( $ioc['sidebar_manager'], $ioc['container_repository'] );
		};

		$ioc['container_repository'] = function() {
			return new ContainerRepository();
		};

		$ioc['key_toolset'] = function() {
			return new Key_Toolset();
		};

		$ioc['wp_toolset'] = function() {
			return new WP_Toolset();
		};

		$ioc['sidebar_manager'] = function() {
			return new Sidebar_Manager();
		};

		$ioc['rest_api_router'] = function( $ioc ) {
			return new REST_API_Router( $ioc['container_repository'] );
		};

		$ioc['rest_api_decorator'] = function( $ioc ) {
			return new REST_API_Decorator( $ioc['container_repository'] );
		};

		/* Services */
		$ioc['meta_query_service'] = function( $ioc ) {
			return new Meta_Query_Service( $ioc['container_repository'], $ioc['key_toolset'] );
		};

		$ioc['legacy_storage_service'] = function( $ioc ) {
			return new Legacy_Storage_Service_v_1_5( $ioc['container_repository'], $ioc['key_toolset'] );
		};

		$ioc['rest_api_service'] = function( $ioc ) {
			return new REST_API_Service( $ioc['rest_api_router'], $ioc['rest_api_decorator'] );
		};

		/* Container Conditions */
		$ioc['container_condition_fulfillable_collection'] = $ioc->factory( function( $ioc ) {
			return new Fulfillable_Collection( $ioc['container_condition_factory'], $ioc['container_condition_translator_array'] );
		} );

		$ioc['container_condition_type_post_id'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Post_ID_Condition();
		} );

		$ioc['container_condition_type_post_parent_id'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Post_Parent_ID_Condition();
		} );

		$ioc['container_condition_type_post_format'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Post_Format_Condition();
		} );

		$ioc['container_condition_type_post_level'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Post_Level_Condition();
		} );

		$ioc['container_condition_type_post_template'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Post_Template_Condition();
		} );

		$ioc['container_condition_type_post_term'] = $ioc->factory( function( $ioc ) {
			return new \Carbon_Fields\Container\Condition\Post_Term_Condition( $ioc['wp_toolset'] );
		} );

		$ioc['container_condition_type_term'] = $ioc->factory( function( $ioc ) {
			return new \Carbon_Fields\Container\Condition\Term_Condition( $ioc['wp_toolset'] );
		} );

		$ioc['container_condition_type_term_taxonomy'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Term_Taxonomy_Condition();
		} );

		$ioc['container_condition_type_term_level'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Term_Level_Condition();
		} );

		$ioc['container_condition_factory'] = function() {
			$factory = new ConditionFactory();
			$factory->register( 'post_id', 'Carbon_Fields\\Container\\Condition\\Post_ID_Condition' );
			$factory->register( 'post_parent_id', 'Carbon_Fields\\Container\\Condition\\Post_Parent_ID_Condition' );
			$factory->register( 'post_format', 'Carbon_Fields\\Container\\Condition\\Post_Format_Condition' );
			$factory->register( 'post_level', 'Carbon_Fields\\Container\\Condition\\Post_Level_Condition' );
			$factory->register( 'post_template', 'Carbon_Fields\\Container\\Condition\\Post_Template_Condition' );
			$factory->register( 'post_term', 'Carbon_Fields\\Container\\Condition\\Post_Term_Condition' );

			$factory->register( 'term', 'Carbon_Fields\\Container\\Condition\\Term_Condition' );
			$factory->register( 'term_taxonomy', 'Carbon_Fields\\Container\\Condition\\Term_Taxonomy_Condition' );
			$factory->register( 'term_level', 'Carbon_Fields\\Container\\Condition\\Term_Level_Condition' );
			return $factory;
		};

		/* Container Condition Comparers */
		$ioc['container_condition_comparer_type_equality'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Comparer\Equality_Comparer();
		} );

		$ioc['container_condition_comparer_type_contain'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Comparer\Contain_Comparer();
		} );

		$ioc['container_condition_comparer_type_scalar'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Comparer\Scalar_Comparer();
		} );

		$ioc['container_condition_comparer_type_regex'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Comparer\Regex_Comparer();
		} );

		$ioc['container_condition_comparer_type_custom'] = $ioc->factory( function() {
			return new \Carbon_Fields\Container\Condition\Comparer\Custom_Comparer();
		} );

		/* Container Condition Translators */
		$ioc['container_condition_translator_array'] = function( $ioc ) {
			return new \Carbon_Fields\Container\Condition\Translator\Array_Translator( $ioc['container_condition_factory'] );
		};

		return $ioc;
	}

	/**
	 * Resolve a dependency through IoC
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function resolve( $key ) {
		return static::instance()->ioc[ $key ];
	}

	/**
	 * Resolve a service through IoC
	 *
	 * @param string $service_name
	 * @return mixed
	 */
	public static function service( $service_name ) {
		return static::resolve( $service_name . '_service' );
	}

	/**
	 * Check if a dependency is registered
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function has( $key ) {
		return isset( static::instance()->ioc[ $key ] );
	}

	/**
	 * Replace the ioc container for the App
	 * 
	 * @param  PimpleContainer $ioc
	 */
	public function install( PimpleContainer $ioc ) {
		$this->ioc = $ioc;
	}

	/**
	 * Boot Carbon Fields with default IoC dependencies
	 */
	public static function boot() {
		if ( static::is_booted() ) {
			return;
		}

		if ( defined( __NAMESPACE__ . '\VERSION' ) ) {
			return; // Possibly attempting to load multiple versions of Carbon Fields; bail in favor of already loaded version
		}

		static::instance()->install( static::get_default_ioc() );
		static::resolve( 'loader' )->boot();
		static::instance()->booted = true;
	}

	/**
	 * Check if Carbon Fields has booted
	 */
	public static function is_booted() {
		return static::instance()->booted;
	}

	/**
	 * Throw exception if Carbon Fields has not been booted
	 */
	public static function verify_boot() {
		if ( ! static::is_booted() ) {
			throw new \Exception( 'You must call Carbon_Fields\App::boot() in a suitable WordPress hook before using Carbon Fields.' );
		}
	}
}