<?php
/**
 * Aruba HiSpeed Cache — WordPress object-cache.php drop-in.
 *
 * The marker below does double duty. It identifies the installed wp-content/object-cache.php
 * as ours, so the plugin never overwrites or deletes a drop-in belonging to Redis Object
 * Cache, W3TC or LiteSpeed; and it records which revision is installed, so an upgrade can
 * tell that the copy in wp-content is stale and replace it. Without it a plugin update
 * leaves the old drop-in in place forever, because wp-content/object-cache.php is a copy
 * and not a symlink.
 *
 * Bump AHSC-Dropin-Version in the same commit as any change to this file, and keep
 * AHSC_DROPIN_VERSION in src/AHSC_Dropin.php in sync — ahsc_dropin_sync() logs a warning
 * when the two drift apart.
 *
 * AHSC-Dropin-Version: 1.6.0
 *
 * @package ArubaHispeedCache
 */

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

/*
 * Gate on APCu actually working, not merely being loaded.
 *
 * This used to read function_exists( 'apcu_fetch' ), which is true whenever the
 * extension is loaded even when apc.enabled=0 — and under CLI, where apc.enable_cli
 * defaults to 0, so every WP-CLI invocation landed in that state. The consequence was
 * not a slow cache but a dead one that core believed in: defining wp_cache_init() makes
 * wp_start_object_cache() call wp_using_ext_object_cache( true ), so every
 * wp_cache_get() missed, every wp_cache_set() failed silently, and — worse — core
 * stored transients in the object cache instead of wp_options, meaning update checks,
 * oEmbed lookups, feeds and plugin API calls were recomputed, outbound HTTP included,
 * on every single request.
 *
 * apcu_enabled() reports apc.enabled and, in CLI, apc.enable_cli, which is exactly the
 * question that matters.
 */
if ( function_exists( 'apcu_enabled' ) && apcu_enabled() && function_exists( 'apcu_fetch' ) ) :

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

		/*
		 * Seconds an entry survives when the caller did not ask for an expiry of its own,
		 * which is the overwhelming majority of what WordPress stores.
		 *
		 * Entries used to be written with no expiry at all. APCu never dropped them, so a
		 * busy site filled apc.shm_size and hit the exhaustion handler, which throws away
		 * the *entire* segment — the hit rate collapses and every concurrent request
		 * rebuilds from MySQL at once. An hour trades a little hit rate for that never
		 * happening, and it is what makes orphaned entries collectable at all: keys left
		 * behind by a namespaced flush, by last_changed rotation, or by a drop-in upgrade
		 * that changed the key shape are unreachable, and without a TTL they sit in shared
		 * memory forever.
		 *
		 * Hosting can tune this per plan by defining WP_APC_DEFAULT_EXPIRATION in
		 * wp-config.php. Setting it to 0 restores the old never-expire behaviour.
		 *
		 * The literal 3600 rather than HOUR_IN_SECONDS is deliberate: this file is loaded
		 * by wp_start_object_cache() before most of WordPress, and an undefined constant
		 * here is a fatal error on every request rather than a missing convenience.
		 */
		if ( !defined( 'WP_APC_DEFAULT_EXPIRATION' ) )
			define( 'WP_APC_DEFAULT_EXPIRATION', 3600 );

		function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			return $wp_object_cache->add( $key, $data, $group, $expire );
		}

		function wp_cache_incr( $key, $n = 1, $group = '' ) {
			global $wp_object_cache;

			return $wp_object_cache->incr( $key, $n, $group );
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

		/*
		 * Everything from here to wp_cache_supports() is API WordPress added between 5.5
		 * and 6.1 that this drop-in never implemented. Core ships fallbacks for all of them
		 * in wp-includes/cache-compat.php, but those are defined only when the drop-in has
		 * not already, and they are either slow (the *_multiple shims loop the singular
		 * call) or inert (flush_runtime and flush_group refuse to do anything unless
		 * wp_cache_supports() vouches for them first).
		 */

		function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
			global $wp_object_cache;

			return $wp_object_cache->get_multiple( $keys, $group, $force );
		}

		function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			return $wp_object_cache->set_multiple( $data, $group, $expire );
		}

		function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
			global $wp_object_cache;

			return $wp_object_cache->add_multiple( $data, $group, $expire );
		}

		function wp_cache_delete_multiple( array $keys, $group = '' ) {
			global $wp_object_cache;

			return $wp_object_cache->delete_multiple( $keys, $group );
		}

		function wp_cache_flush_runtime() {
			global $wp_object_cache;

			return $wp_object_cache->flush_runtime();
		}

		function wp_cache_flush_group( $group ) {
			global $wp_object_cache;

			return $wp_object_cache->flush_group( $group );
		}

		/*
		 * Guarded, unlike its neighbours, because this is the one function callers invoke
		 * speculatively — including core's own cache-compat shims — and it can be reached
		 * in the window between this file being loaded and wp_cache_init() creating the
		 * object. Core's fallback is a plain "return false" with no object behind it, so
		 * answering false here rather than fataling matches what callers already handle.
		 */
		function wp_cache_supports( $feature ) {
			global $wp_object_cache;

			if ( ! is_object( $wp_object_cache ) )
				return false;

			return $wp_object_cache->supports( $feature );
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
			public $stats = array( 'get' => 0, 'delete' => 0, 'add' => 0, 'store_failed' => 0 );
			public $group_ops = array();

			public $cache_enabled = true;
			/*
			 * Applied by add() and set() whenever the caller passed no expiry. Assigned
			 * from WP_APC_DEFAULT_EXPIRATION in the constructor; 0 means never expire.
			 */
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

				if ( in_array( $group, $this->no_mc_groups ) ) {
					$this->cache[$key] = $data;
					return true;
				} elseif ( isset( $this->cache[$key] ) && $this->cache[$key] !== false ) {
					return false;
				}

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;

				$result = apcu_add( $key, $data, $expire );
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

			/*
			 * Does this key exist, as core's _exists() would answer it?
			 *
			 * A positive runtime entry settles it. A runtime false does not: this drop-in
			 * stores false on a miss, so it cannot tell "known absent" from "stored false",
			 * and it has to ask APCu. Non-persistent groups live only in the runtime array,
			 * so for those the runtime answer is the whole truth.
			 */
			function exists( $key, $group ) {
				if ( isset( $this->cache[$key] ) && false !== $this->cache[$key] &&
				     'checkthedatabaseplease' !== $this->cache[$key] ) {
					return true;
				}

				if ( in_array( $group, $this->no_mc_groups ) ) {
					return false;
				}

				return apcu_exists( $key );
			}

			/*
			 * Shared body of incr() and decr(); $offset is signed.
			 *
			 * Core's contract, which this now follows: a missing key returns false rather
			 * than being created, a non-numeric entry is treated as 0 before the offset is
			 * applied, the result is floored at 0, and the new value is returned *and* kept
			 * in the runtime array so a get() in the same request agrees with it. The old
			 * implementation did none of those: apcu_inc() creates a missing key, nothing
			 * floored, and the runtime copy was left stale.
			 *
			 * apcu_inc() / apcu_dec() are used rather than fetch-modify-store because they
			 * are atomic, which matters for the counters this exists for — rate limits,
			 * Batcache's request counts. Two corrections are needed on top: they refuse a
			 * non-integer entry (reported through $success), and apcu_dec() will go below
			 * zero. Both are repaired with a store, which resets the entry's TTL to the
			 * default — accepted, since the alternative is losing atomicity on every call.
			 *
			 * One race remains: if the entry expires between exists() and the increment,
			 * APCu creates it and we return the offset instead of false. Narrow, and the
			 * cost is a counter starting one cycle early.
			 */
			function offset_entry( $id, $offset, $group ) {
				$key    = $this->key( $id, $group );
				$offset = (int) $offset;

				if ( ! $this->exists( $key, $group ) ) {
					return false;
				}

				if ( in_array( $group, $this->no_mc_groups ) ) {
					$current = is_numeric( $this->cache[$key] ) ? (int) $this->cache[$key] : 0;
					$value   = max( 0, $current + $offset );

					$this->cache[$key] = $value;

					return $value;
				}

				$success = false;

				$value = ( $offset >= 0 )
					? apcu_inc( $key, $offset, $success )
					: apcu_dec( $key, -$offset, $success );

				if ( ! $success ) {
					/*
					 * APCu refuses anything that is not an integer entry. Core is more forgiving:
					 * it applies the offset to any is_numeric() value — so the string "5" counts —
					 * and treats everything else as 0. Read the entry back and follow that, rather
					 * than assuming 0 and turning a stored "5" into 1.
					 */
					$fetched = false;
					$current = apcu_fetch( $key, $fetched );
					$current = ( $fetched && is_numeric( $current ) ) ? (int) $current : 0;
					$value   = max( 0, $current + $offset );

					if ( false === apcu_store( $key, $value, $this->default_expiration ) ) {
						@ ++$this->stats['store_failed'];

						return false;
					}
				} elseif ( $value < 0 ) {
					$value = 0;

					if ( false === apcu_store( $key, $value, $this->default_expiration ) ) {
						@ ++$this->stats['store_failed'];

						return false;
					}
				}

				$this->cache[$key]         = $value;
				$this->group_ops[$group][] = ( $offset >= 0 ? 'incr ' : 'decr ' ) . $id;

				return $value;
			}

			function incr( $id, $n = 1, $group = 'default' ) {
				return $this->offset_entry( $id, (int) $n, $group );
			}

			function decr( $id, $n = 1, $group = 'default' ) {
				return $this->offset_entry( $id, -(int) $n, $group );
			}

			/*
			 * Legacy name. The comment that used to sit here said this was called incr2
			 * "because Batcache looks for incr", with incr() supplied by a subclass when
			 * apcu_inc() existed — but wp_cache_incr() called incr2() directly, so the
			 * subclass method was never reached and the indirection protected nothing.
			 * Kept only in case something outside the plugin learned the name.
			 */
			function incr2( $id, $n = 1, $group = 'default' ) {
				return $this->incr( $id, $n, $group );
			}

			function close() {
				return true;
			}

			/*
			 * The batch reads core leans on hardest: _prime_post_caches(),
			 * update_meta_cache(), _prime_term_caches() and _prime_comment_caches() all go
			 * through wp_cache_get_multiple(). Without this method core falls back to the
			 * shim in cache-compat.php, which loops wp_cache_get() and turns one archive
			 * page into a hundred round trips.
			 *
			 * Anything already in the runtime array is answered from there, and only the
			 * remainder goes to APCu, in a single apcu_fetch() on an array of keys. The
			 * per-key handling deliberately mirrors get() — same miss caching, same
			 * 'checkthedatabaseplease' sentinel, same stats — via normalise_fetched(), so
			 * the two cannot disagree about what a hit is.
			 */
			function get_multiple( $ids, $group = 'default', $force = false ) {
				$values = array();
				$lookup = array();

				foreach ( (array) $ids as $id ) {
					$key = $this->key( $id, $group );

					if ( isset( $this->cache[$key] ) && ( !$force || in_array( $group, $this->no_mc_groups ) ) ) {
						$values[$id] = ( is_object( $this->cache[$key] ) ) ? clone $this->cache[$key] : $this->cache[$key];
						@ ++$this->stats['get'];
						// Hit and miss counted per key, exactly as get() does.
						if ( false === $values[$id] ) {
							++ $this->cache_misses;
						} else {
							++ $this->cache_hits;
						}
						$this->group_ops[$group][] = "get $id";
						continue;
					}

					if ( in_array( $group, $this->no_mc_groups ) ) {
						$this->cache[$key] = false;
						$values[$id] = false;
						@ ++$this->stats['get'];
						++ $this->cache_misses;
						$this->group_ops[$group][] = "get $id";
						continue;
					}

					// Placeholder keeps the caller's key order even when APCu returns
					// nothing for this key.
					$values[$id]  = false;
					$lookup[$key] = $id;
				}

				if ( empty( $lookup ) )
					return $values;

				$fetched = apcu_fetch( array_keys( $lookup ) );

				if ( !is_array( $fetched ) )
					$fetched = array();

				foreach ( $lookup as $key => $id ) {
					$value = array_key_exists( $key, $fetched ) ? $this->normalise_fetched( $fetched[$key] ) : false;

					if ( 'checkthedatabaseplease' === $value ) {
						unset( $this->cache[$key] );
						$value = false;
					} else {
						$this->cache[$key] = ( is_object( $value ) ) ? clone $value : $value;
					}

					$values[$id] = $value;
					@ ++$this->stats['get'];

					if ( false === $value ) {
						++ $this->cache_misses;
					} else {
						++ $this->cache_hits;
					}

					$this->group_ops[$group][] = "get $id";
				}

				return $values;
			}

			/*
			 * The write batches below each run the per-key bookkeeping in a loop — runtime
			 * copy, non-persistent groups, the 'checkthedatabaseplease' guard, exactly as
			 * the singular methods do — and then hand APCu a single array. The saving is
			 * lock acquisitions, not PHP work: core's update_meta_cache() calls
			 * wp_cache_set_multiple() on nearly every page that loads posts, with one entry
			 * per object, and each separate apcu_store() takes the write lock on its own.
			 *
			 * apcu_store() / apcu_add() / apcu_delete() given an array return the list of
			 * keys that *failed*, so an empty array means complete success. The bool branch
			 * in each method is defensive: the return is documented as an array here, but
			 * answering a bool would otherwise be read as "every key failed" by accident.
			 */
			function set_multiple( $data, $group = 'default', $expire = 0 ) {
				$values = array();
				$store  = array();
				$ids    = array();

				foreach ( (array) $data as $id => $value ) {
					$key = $this->key( $id, $group );

					if ( isset( $this->cache[$key] ) && ( 'checkthedatabaseplease' === $this->cache[$key] ) ) {
						$values[$id] = false;
						continue;
					}

					if ( is_object( $value ) )
						$value = clone $value;

					$this->cache[$key] = $value;

					if ( in_array( $group, $this->no_mc_groups ) ) {
						$values[$id] = true;
						continue;
					}

					$store[$key] = $value;
					$ids[$key]   = $id;
					$values[$id] = true;
				}

				if ( empty( $store ) )
					return $values;

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
				$failed = apcu_store( $store, null, $expire );

				if ( !is_array( $failed ) )
					$failed = ( false === $failed ) ? array_keys( $store ) : array();

				foreach ( $failed as $key ) {
					if ( isset( $ids[$key] ) )
						$values[ $ids[$key] ] = false;

					@ ++$this->stats['store_failed'];
				}

				return $values;
			}

			function add_multiple( $data, $group = 'default', $expire = 0 ) {
				$values = array();
				$add    = array();
				$ids    = array();

				foreach ( (array) $data as $id => $value ) {
					$key = $this->key( $id, $group );

					if ( is_object( $value ) )
						$value = clone $value;

					if ( in_array( $group, $this->no_mc_groups ) ) {
						$this->cache[$key] = $value;
						$values[$id] = true;
						continue;
					}

					if ( isset( $this->cache[$key] ) && $this->cache[$key] !== false ) {
						$values[$id] = false;
						continue;
					}

					$add[$key]   = $value;
					$ids[$key]   = $id;
					$values[$id] = true;
				}

				if ( empty( $add ) )
					return $values;

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
				$failed = apcu_add( $add, null, $expire );

				if ( !is_array( $failed ) )
					$failed = ( false === $failed ) ? array_keys( $add ) : array();

				$failed = array_flip( $failed );

				// add() only takes the runtime copy when the store succeeded, because a
				// failed add means something else owns that key. Same rule here.
				foreach ( $ids as $key => $id ) {
					if ( isset( $failed[$key] ) ) {
						$values[$id] = false;
						continue;
					}

					@ ++$this->stats['add'];
					$this->group_ops[$group][] = "add $id";
					$this->cache[$key] = $add[$key];
				}

				return $values;
			}

			function delete_multiple( $ids, $group = 'default' ) {
				$values = array();
				$keys   = array();
				$map    = array();

				foreach ( (array) $ids as $id ) {
					$key = $this->key( $id, $group );

					// Unconditional and before APCu is touched, exactly as delete() does.
					unset( $this->cache[$key] );

					if ( in_array( $group, $this->no_mc_groups ) ) {
						$values[$id] = true;
						continue;
					}

					$keys[]      = $key;
					$map[$key]   = $id;
					$values[$id] = true;

					@ ++$this->stats['delete'];
					$this->group_ops[$group][] = "delete $id";
				}

				if ( empty( $keys ) )
					return $values;

				$failed = apcu_delete( $keys );

				if ( !is_array( $failed ) )
					$failed = ( false === $failed ) ? $keys : array();

				foreach ( $failed as $key ) {
					if ( isset( $map[$key] ) )
						$values[ $map[$key] ] = false;
				}

				return $values;
			}

			/*
			 * Empties the in-process array and nothing else. This is the escape hatch long
			 * in-request jobs need — Action Scheduler, importers, bulk REST operations —
			 * and until it existed core's cache-compat.php shim answered them with
			 * _doing_it_wrong() and a false, leaving the array to grow to memory_limit.
			 * It is unreachable unless wp_cache_supports( 'flush_runtime' ) says yes, which
			 * is why the two ship together.
			 */
			function flush_runtime() {
				$this->cache = array();
				$this->group_ops = array();

				return true;
			}

			/*
			 * Drops one group without touching the rest, which gives callers a scalpel
			 * where flush() is a hammer.
			 */
			function flush_group( $group ) {
				$prefix = $this->group_prefix( $group );

				foreach ( $this->cache as $key => $ignored ) {
					if ( 0 === strpos( $key, $prefix ) )
						unset( $this->cache[$key] );
				}

				if ( !class_exists( 'APCUIterator' ) )
					return false;

				$result = apcu_delete( new APCUIterator( '/^' . preg_quote( $prefix, '/' ) . '/' ) );

				if ( is_array( $result ) )
					return array() === $result;

				return (bool) $result;
			}

			/*
			 * Core feature-detects through this before it will call flush_runtime() or
			 * flush_group() at all, so every capability above is invisible without it.
			 */
			function supports( $feature ) {
				switch ( $feature ) {
					case 'add_multiple':
					case 'set_multiple':
					case 'get_multiple':
					case 'delete_multiple':
					case 'flush_runtime':
					case 'flush_group':
						return true;
					default:
						return false;
				}
			}

			function delete( $id, $group = 'default' ) {
				$key = $this->key( $id, $group );

				/*
				 * The runtime copy goes unconditionally, before APCu is touched at all.
				 * This used to be conditional on apcu_delete() returning true, which meant
				 * that deleting a key APCu had never stored left the old value sitting in
				 * $this->cache: a wp_cache_get() later in the same request handed back the
				 * value that had just been deleted. Worse, the usual read-modify-write
				 * shape — delete, rebuild from a get, set — then wrote that stale value
				 * back into APCu, where it outlived the request.
				 */
				unset( $this->cache[$key] );

				if ( in_array( $group, $this->no_mc_groups ) ) {
					return true;
				}

				$result = apcu_delete( $key );

				@ ++$this->stats['delete'];
				$this->group_ops[$group][] = "delete $id";

				return $result;
			}

			function flush() {
				/*
				 * This used to bail out and report success when a function named
				 * is_site_admin() existed, or when CUSTOM_USER_TABLE and
				 * CUSTOM_USER_META_TABLE were both defined, under a "don't flush if
				 * multi-blog" comment inherited from the WPMU-era original.
				 *
				 * Neither test means what it used to. is_site_admin() was removed from
				 * WordPress in 3.0 and now only shows up when some other plugin happens to
				 * define that name, and a shared user table is an ordinary configuration
				 * rather than a signal that flushing is unsafe. Both simply switched the
				 * flush off — silently, because the early return was `true`, so callers and
				 * the plugin's own log recorded a successful flush that never happened.
				 *
				 * There is nothing to protect here in the first place: the keys carry an
				 * md5( ABSPATH ) namespace, and neither condition says anything about who
				 * else is sharing the APCu segment.
				 */
				$this->cache = array();

				/*
				 * Delete only this install's keys rather than calling apcu_clear_cache().
				 * APCu shared memory belongs to the PHP pool, not to one site, so a blanket
				 * clear also throws away every other WordPress install sharing that pool —
				 * which is exactly what the md5( ABSPATH ) in key() exists to prevent.
				 *
				 * Every key this class writes goes through key() and therefore starts with
				 * "{salt}:{md5 of ABSPATH}:", so anchoring on that prefix reaches all of
				 * them and nothing else. On multisite the whole network shares one ABSPATH
				 * and so one namespace: the flush clears the network, which is the same
				 * blast radius as before and no worse.
				 *
				 * This walks the cache, so it is O(number of entries) and holds a lock
				 * while it runs. That is affordable now that flushing happens only on a
				 * deliberate user action or a plugin/theme change; it would not have been
				 * when every post save landed here.
				 */
				if ( class_exists( 'APCUIterator' ) ) {
					$pattern = '/^' . preg_quote( $this->install_prefix(), '/' ) . '/';

					$result = apcu_delete( new APCUIterator( $pattern ) );

					// apcu_delete() reports failures as an array of keys for some argument
					// types and as a bool for others; normalise both to a bool so callers
					// see the documented wp_cache_flush() return value either way.
					if ( is_array( $result ) ) {
						return array() === $result;
					}

					return (bool) $result;
				}

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
					$value = $this->normalise_fetched( $value );
					$this->cache[$key] = ( is_object( $value ) ) ? clone $value : $value;
				}

				@ ++$this->stats['get'];
				$this->group_ops[$group][] = "get $id";

				if ( 'checkthedatabaseplease' === $value ) {
					unset( $this->cache[$key] );
					$value = false;
					$found = false;
				}

				/*
				 * Counted here rather than aliased to stats['get'] and stats['add'], which is
				 * what the constructor used to do. Site Health, Query Monitor and every
				 * profiler read these two properties as hits and misses; pointing them at a
				 * total-reads counter and a writes counter made both numbers meaningless,
				 * including the hit ratio derived from them.
				 */
				if ( $found ) {
					++ $this->cache_hits;
				} else {
					++ $this->cache_misses;
				}

				return $value;
			}

			/*
			 * The prefix every key on this install shares. flush() anchors its APCUIterator
			 * pattern on this, so the two cannot drift apart.
			 */
			function install_prefix() {
				return WP_APC_KEY_SALT . ':' . $this->abspath . ':';
			}

			/*
			 * The prefix every key in one group shares, which is what flush_group() deletes
			 * by. The trailing colon matters: without it a flush of "post" would also match
			 * "postmeta".
			 *
			 * The blog segment comes from $this->blog_prefix, which switch_to_blog()
			 * maintains. Before 1.4.0 this re-derived it from the $blog_id global and the
			 * property went unread — it happened to work because core updates that global,
			 * but it meant the object could not be switched without also switching a
			 * global. It also ran the blog id straight into the group name, so blog 5 with
			 * a group named "5posts" and blog 55 with a group named "posts" produced the
			 * same key; the separator now makes that impossible.
			 */
			function group_prefix( $group ) {
				if ( empty( $group ) )
					$group = 'default';

				if ( false !== array_search( $group, $this->global_groups ) )
					$prefix = $this->global_prefix;
				else
					$prefix = $this->blog_prefix;

				return $this->install_prefix() . $prefix . $group . ':';
			}

			function key( $key, $group ) {
				return $this->group_prefix( $group ) . $key;
			}

			/*
			 * Normalise a value handed back by APCu, shared by get() and get_multiple() so
			 * the two cannot disagree about what a hit looks like.
			 *
			 * The ArrayObject unwrapping is for entries written by drop-in revisions before
			 * 1.4.0, which wrapped every array to dodge a serialisation bug that belonged to
			 * APC and never applied to APCu. New writes store plain arrays; this stays until
			 * those entries have aged out.
			 */
			function normalise_fetched( $value ) {
				if ( is_object( $value ) && 'ArrayObject' == get_class( $value ) )
					$value = $value->getArrayCopy();

				if ( NULL === $value )
					$value = false;

				return $value;
			}

			/*
			 * Core's replace() returns false when the key is absent and only then defers to
			 * set(). This used to be a straight alias for set(), so it created keys that the
			 * caller had asked it not to create — the opposite of add().
			 */
			function replace( $id, $data, $group = 'default', $expire = 0 ) {
				if ( ! $this->exists( $this->key( $id, $group ), $group ) ) {
					return false;
				}

				return $this->set( $id, $data, $group, $expire );
			}

			/*
			 * Note on 'alloptions', because it looks like an omission: writes to it are not
			 * locked, and deliberately so.
			 *
			 * update_option() reads the whole array, substitutes one value and stores it back,
			 * so two concurrent writers to different autoloaded options can drop one of the two
			 * values from the cached copy. The database is unaffected — the row is already
			 * committed — and the read-modify-write belongs to core, which means every
			 * persistent object cache has the same exposure. The default expiration bounds it:
			 * the stale array is replaced when the entry expires, which a backend with no TTL
			 * cannot say. apcu_cas() is no help here; it swaps integers, not serialised arrays.
			 *
			 * The alternative was considered and rejected: deleting the key on write instead of
			 * storing it, so the next reader rebuilds from the database. Correct, but it costs
			 * an alloptions query after every option write, which undoes most of what caching
			 * alloptions buys.
			 */
			function set( $id, $data, $group = 'default', $expire = 0 ) {
				$key = $this->key( $id, $group );
				if ( isset( $this->cache[$key] ) && ('checkthedatabaseplease' === $this->cache[$key] ) )
					return false;

				if ( is_object( $data ) )
					$data = clone $data;

				$this->cache[$key] = $data;

				if ( in_array( $group, $this->no_mc_groups ) )
					return true;

				$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
				$result = apcu_store( $key, $data, $expire );

				/*
				 * The runtime copy above is written before the persist is attempted, so a
				 * failure here is invisible for the rest of the request and the value
				 * silently never reaches APCu — shared memory full, or a value carrying a
				 * closure or resource that cannot be serialised. Counted rather than
				 * logged: when SHM fills, every single write fails, and an error_log() line
				 * per failure would fill the disk on top of it.
				 */
				if ( false === $result )
					@ ++$this->stats['store_failed'];

				return $result;
			}

			function switch_to_blog( $blog_id ) {
				global $table_prefix;

				$blog_id = (int) $blog_id;
				$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
			}
			function __construct() {
				/*
				 * Cast and floor rather than trusting the constant: it comes from
				 * wp-config.php, and a stray string or negative would be handed straight
				 * to apcu_store() for every write the site makes.
				 */
				$this->default_expiration = max( 0, (int) WP_APC_DEFAULT_EXPIRATION );

				$this->abspath = md5( ABSPATH );

				/*
				 * Both prefixes carry their own trailing separator as of 1.4.0, so
				 * group_prefix() can drop one in without knowing which it got. They used to
				 * disagree — blog_prefix ended in a colon, global_prefix did not — which is
				 * why key() ignored them both and re-derived the blog segment from the
				 * $blog_id global instead. Reconciling them is what lets switch_to_blog()
				 * actually drive key generation.
				 */
				global $blog_id, $table_prefix;
				$this->global_prefix = '';
				$this->blog_prefix = '';
				if ( function_exists( 'is_multisite' ) ) {
					$this->global_prefix = ( is_multisite() || defined('CUSTOM_USER_TABLE') && defined('CUSTOM_USER_META_TABLE') ) ? '' : $table_prefix . ':';
					$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
				}

				/*
				 * cache_hits and cache_misses used to be references to stats['get'] and
				 * stats['add'] — total reads and total writes. get() and get_multiple() now
				 * count them properly, so nothing is aliased here.
				 */
			}
		}

		/*
		 * The class wp_cache_init() instantiates. It used to be declared twice, in an
		 * if/else on function_exists( 'apcu_inc' ), so that one branch could add an incr()
		 * that forwarded to incr2(). Nothing reached it — wp_cache_incr() called incr2()
		 * itself — and the gate at the top of this file now requires a working APCu, so
		 * apcu_inc() is always present by the time we get here. One declaration, no
		 * override.
		 */
		class APC_Object_Cache extends WP_Object_Cache {
		}

	} // !function_exists( 'wp_cache_add' )

else :
	/*
	 * APCu is not available or not enabled: define nothing at all.
	 *
	 * This branch used to set $GLOBALS['_wp_using_ext_object_cache'] = false and then
	 * require wp-includes/cache.php itself, with a comment admitting the flag 'will get
	 * overridden as of WP 3.5, so we have to hook in to all' — a workaround that was never
	 * written. It could not work: loading cache.php *defines wp_cache_init()*, and
	 * wp_start_object_cache() decides there is an external object cache precisely by
	 * testing whether including this file defined that function
	 * (wp-includes/load.php). So the fallback branch was the thing turning the flag back
	 * on, and setting it to false a line earlier could not stop it.
	 *
	 * Defining nothing is all that is needed. wp_cache_init() stays undefined, core leaves
	 * wp_using_ext_object_cache() false, and four lines later core loads cache.php on its
	 * own. WordPress then runs on its default in-request cache with transients in
	 * wp_options, which is the correct behaviour when there is no persistent cache.
	 */
endif;