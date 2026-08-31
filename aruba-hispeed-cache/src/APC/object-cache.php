<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * This file is the object-cache.php drop-in, not ordinary plugin code: it gets copied to
 * wp-content/ and has to define the wp_cache_* API under exactly those names, because that
 * is the contract WordPress core calls. The prefixing rule therefore cannot apply, and the
 * blanket ignoreFile that used to sit here has been narrowed to just that rule so
 * everything else in the file is still checked.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- drop-in implementing the core object cache API.

if ( function_exists( 'apcu_fetch' ) ) :

	if ( version_compare( phpversion(), '7.4', '<' ) ) {
		wp_die( 'The APC object cache backend requires PHP 7.4 or higher. You are running ' . esc_attr(phpversion()) . '. Please remove the <code>object-cache.php</code> file from your content directory.' );
	}

	if ( function_exists( 'wp_cache_add' ) ) {
		// esc_attr() was being used as if it were a sanitizer on the raw $_SERVER value;
		// the path is now sanitized once and escaped where it is printed.
		$ahsc_document_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';

		// Regular die, not wp_die(), because it gets sandboxed and shown in a small iframe
		die(
			'<strong>ERROR:</strong> This is <em>not</em> a plugin, and it should not be activated as one.<br /><br />Instead, <code>'
			. esc_html( str_replace( $ahsc_document_root, '', __FILE__ ) )
			. '</code> must be moved to <code>'
			. esc_html( str_replace( $ahsc_document_root, '', trailingslashit( WP_CONTENT_DIR ) ) )
			. 'object-cache.php</code>'
		);
	} else {

		if ( !defined( 'WP_APC_KEY_SALT' ) )
			define( 'WP_APC_KEY_SALT', 'wp' );

		function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			return $wp_object_cache->add( $key, $data, $group, $expire );
		}

		function wp_cache_incr( $key, $n = 1, $group = '' ) {
			global $wp_object_cache;

			return $wp_object_cache->incr2( $key, $n, $group );
		}

		function wp_cache_decr( $key, $n = 1, $group = '' ) {
			global $wp_object_cache;

			return $wp_object_cache->decr( $key, $n, $group );
		}

		function wp_cache_close() {
			return true;
		}

		function wp_cache_delete( $key, $group = '' ) {
			global $wp_object_cache;

			return $wp_object_cache->delete( $key, $group );
		}

		function wp_cache_flush() {
			global $wp_object_cache;

			return $wp_object_cache->flush();
		}

		function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
			global $wp_object_cache;

			return $wp_object_cache->get( $key, $group, $force, $found );
		}

		function wp_cache_init() {
			global $wp_object_cache;

			$wp_object_cache = new APC_Object_Cache();
		}

		function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			return $wp_object_cache->replace( $key, $data, $group, $expire );
		}

		function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			if ( defined('WP_INSTALLING') == false )
				return $wp_object_cache->set( $key, $data, $group, $expire );
			else
				return $wp_object_cache->delete( $key, $group );
		}

		function wp_cache_switch_to_blog( $blog_id ) {
			global $wp_object_cache;

			return $wp_object_cache->switch_to_blog( $blog_id );
		}

		function wp_cache_add_global_groups( $groups ) {
			global $wp_object_cache;

			$wp_object_cache->add_global_groups( $groups );
		}

		function wp_cache_add_non_persistent_groups( $groups ) {
			global $wp_object_cache;

			$wp_object_cache->add_non_persistent_groups( $groups );
		}

		class WP_Object_Cache {
			public $global_groups = array();

			public $no_mc_groups = array();

			public $cache = array();
			public $stats = array( 'get' => 0, 'delete' => 0, 'add' => 0 );
			public $group_ops = array();

			public $cache_enabled = true;
			public $default_expiration = 0;
			public $abspath = '';
			public $debug = false;
			public $global_prefix = '';
			public $blog_prefix = '';
			public $cache_hits = 0;
			public $cache_misses = 0;

			function add( $id, $data, $group = 'default', $expire = 0 ) {
				$key = $this->key( $id, $group );

				if ( is_object( $data ) )
					$data = clone $data;

				$store_data = $data;

				if ( is_array( $data ) )
					$store_data = new ArrayObject( $data );

				if ( in_array( $group, $this->no_mc_groups ) ) {
					$this->cache[$key] = $data;
					return true;
				} elseif ( isset( $this->cache[$key] ) && $this->cache[$key] !== false ) {
					return false;
				}

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;

				$result = apcu_add( $key, $store_data, $expire );
				if ( false !== $result ) {
					@ ++$this->stats['add'];
					$this->group_ops[$group][] = "add $id";
					$this->cache[$key] = $data;
				}

				return $result;
			}

			function add_global_groups( $groups ) {
				if ( !is_array( $groups ) )
					$groups = (array) $groups;

				$this->global_groups = array_merge( $this->global_groups, $groups );
				$this->global_groups = array_unique( $this->global_groups );
			}

			function add_non_persistent_groups( $groups ) {
				if ( !is_array( $groups ) )
					$groups = (array) $groups;

				$this->no_mc_groups = array_merge( $this->no_mc_groups, $groups );
				$this->no_mc_groups = array_unique( $this->no_mc_groups );
			}

			// This is named incr2 because Batcache looks for incr
			// We will define that in a class extension if it is available (APC 3.1.1 or higher)
			function incr2( $id, $n = 1, $group = 'default' ) {
				$key = $this->key( $id, $group );
				if ( function_exists( 'apcu_inc' ) )
					return apcu_inc( $key, $n );
				else
					return false;
			}

			function decr( $id, $n = 1, $group = 'default' ) {
				$key = $this->key( $id, $group );
				if ( function_exists( 'apcu_dec' ) )
					return apcu_dec( $key, $n );
				else
					return false;
			}

			function close() {
				return true;
			}

			function delete( $id, $group = 'default' ) {
				$key = $this->key( $id, $group );

				if ( in_array( $group, $this->no_mc_groups ) ) {
					unset( $this->cache[$key] );
					return true;
				}

				$result = apcu_delete( $key );

				@ ++$this->stats['delete'];
				$this->group_ops[$group][] = "delete $id";

				if ( false !== $result )
					unset( $this->cache[$key] );

				return $result;
			}

			function flush() {
				// Don't flush if multi-blog.
				if ( function_exists( 'is_site_admin' ) || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) )
					return true;

				$this->cache = array();
				return apcu_clear_cache();
			}

			function get($id, $group = 'default', $force = false, &$found = null) {
				$key = $this->key($id, $group);

				if ( isset($this->cache[$key]) && ( !$force || in_array($group, $this->no_mc_groups) ) ) {
					$found = true;
					if ( is_object( $this->cache[$key] ) )
						$value = clone $this->cache[$key];
					else
						$value = $this->cache[$key];
				} else if ( in_array($group, $this->no_mc_groups) ) {
					$found = false;
					$this->cache[$key] = $value = false;
				} else {
					$value = apcu_fetch( $key, $success );
					$found = $success;
					if ( is_object( $value ) && 'ArrayObject' == get_class( $value ) )
						$value = $value->getArrayCopy();
					if ( NULL === $value )
						$value = false;
					$this->cache[$key] = ( is_object( $value ) ) ? clone $value : $value;
				}

				@ ++$this->stats['get'];
				$this->group_ops[$group][] = "get $id";

				if ( 'checkthedatabaseplease' === $value ) {
					unset( $this->cache[$key] );
					$value = false;
					$found = false;
				}

				return $value;
			}

			function key( $key, $group ) {
				global $blog_id, $table_prefix;
				if ( empty( $group ) )
					$group = 'default';

				if ( false !== array_search( $group, $this->global_groups ) )
					$prefix = $this->global_prefix;
				else
					$prefix =( is_multisite() ? $blog_id : $table_prefix );

				return WP_APC_KEY_SALT . ':' . $this->abspath . ":$prefix$group:$key";
			}

			function replace( $id, $data, $group = 'default', $expire = 0 ) {
				return $this->set( $id, $data, $group, $expire );
			}

			function set( $id, $data, $group = 'default', $expire = 0 ) {
				$key = $this->key( $id, $group );
				if ( isset( $this->cache[$key] ) && ('checkthedatabaseplease' === $this->cache[$key] ) )
					return false;

				if ( is_object( $data ) )
					$data = clone $data;

				$store_data = $data;

				if ( is_array( $data ) )
					$store_data = new ArrayObject( $data );

				$this->cache[$key] = $data;

				if ( in_array( $group, $this->no_mc_groups ) )
					return true;

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
				$result = apcu_store( $key, $store_data, $expire );

				return $result;
			}

			function switch_to_blog( $blog_id ) {
				global $table_prefix;

				$blog_id = (int) $blog_id;
				$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
			}
			function __construct() {
				$this->abspath = md5( ABSPATH );

				global $blog_id, $table_prefix;
				$this->global_prefix = '';
				$this->blog_prefix = '';
				if ( function_exists( 'is_multisite' ) ) {
					$this->global_prefix = ( is_multisite() || defined('CUSTOM_USER_TABLE') && defined('CUSTOM_USER_META_TABLE') ) ? '' : $table_prefix;
					$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
				}

				$this->cache_hits =& $this->stats['get'];
				$this->cache_misses =& $this->stats['add'];
			}
		}

		if ( function_exists( 'apcu_inc' ) ) {
			class APC_Object_Cache extends WP_Object_Cache {
				function incr( $id, $n = 1, $group = 'default' ) {
					return parent::incr2( $id, $n, $group );
				}
			}
		} else {
			class APC_Object_Cache extends WP_Object_Cache {
				// Blank
			}
		}

	} // !function_exists( 'wp_cache_add' )

else : // No APC
	$GLOBALS['_wp_using_ext_object_cache'] = false; // This will get overridden as of WP 3.5, so we have to hook in to 'all':
	require_once ( ABSPATH . WPINC . '/cache.php' );
endif;