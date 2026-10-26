<?php
/**
 * Lifecycle of the wp-content/object-cache.php drop-in.
 *
 * The drop-in is a *copy* of src/APC/object-cache.php, not a symlink, so updating the
 * plugin does not update it: an install that enabled APCu once keeps running whatever
 * revision was current on that day. This file closes that gap. It records which revision
 * is installed, notices when the bundled one is newer, and replaces the file in place.
 *
 * php version 7.4
 *
 * @category Wordpress-plugin
 * @package  Aruba-HiSpeed-Cache
 * @author   Aruba Developer <hispeedcache.developer@aruba.it>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Revision of src/APC/object-cache.php that this plugin ships.
 *
 * Bump this together with the AHSC-Dropin-Version marker inside that file, in the same
 * commit as any change to it. This constant is the authority for "do we need to re-copy?"
 * because checking it costs nothing; the marker is the authority for "what is installed?"
 * because that has to be read off disk. ahsc_dropin_sync() compares the two and logs a
 * warning if they drift.
 */
const AHSC_DROPIN_VERSION = '1.6.0';

/**
 * Site option holding the drop-in revision currently installed in wp-content.
 *
 * Autoloaded, so the per-request check below is a cache hit rather than a query.
 */
const AHSC_DROPIN_VERSION_OPTION = 'ahsc_dropin_version';

/**
 * Header carried by our drop-in. Its presence is the ownership test.
 */
const AHSC_DROPIN_MARKER = 'AHSC-Dropin-Version';

/**
 * Site option holding the timestamp of the last failed sync attempt.
 *
 * Without it a host that cannot give us a usable WP_Filesystem — anything where
 * get_filesystem_method() does not return "direct" and no credentials are stored — would
 * retry on every single request, dragging the wp-admin filesystem includes into every
 * front-end page load for a copy that is never going to succeed.
 */
const AHSC_DROPIN_RETRY_OPTION = 'ahsc_dropin_sync_retry';

/**
 * How long to wait before retrying a failed sync.
 */
const AHSC_DROPIN_RETRY_INTERVAL = 3600;

/**
 * Site option remembering the last object cache status written to the log.
 *
 * Holds the line itself and when it was written, so the status is recorded when something
 * actually changes rather than on every request.
 */
const AHSC_DROPIN_STATUS_OPTION = 'ahsc_dropin_status';

/**
 * How long an unchanged status stays quiet before it is written again.
 */
const AHSC_DROPIN_STATUS_INTERVAL = 21600;

/**
 * Absolute path of the drop-in this plugin ships.
 *
 * @return string
 */
function ahsc_dropin_source_path() {
	return __DIR__ . DIRECTORY_SEPARATOR . 'APC' . DIRECTORY_SEPARATOR . 'object-cache.php';
}

/**
 * Absolute path of the installed drop-in.
 *
 * @return string
 */
function ahsc_dropin_target_path() {
	return WP_CONTENT_DIR . '/object-cache.php';
}

/**
 * Bring up WP_Filesystem.
 *
 * Everything here goes through WP_Filesystem rather than PHP's own filesystem calls, for
 * the same reason ahsc_create_apc_file() does: on hosts where PHP cannot write directly,
 * WordPress falls back to an FTP or SSH transport, and a plain copy() would simply fail.
 *
 * @return bool True when $wp_filesystem is usable.
 */
function ahsc_dropin_init_filesystem() {
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
		return true;
	}

	return WP_Filesystem() && $wp_filesystem instanceof WP_Filesystem_Base;
}

/**
 * Extract the AHSC-Dropin-Version marker from a drop-in's contents.
 *
 * @param  string $contents File contents.
 * @return string|null The revision, or null when the file carries no marker.
 */
function ahsc_dropin_parse_version( $contents ) {
	if ( ! is_string( $contents ) || '' === $contents ) {
		return null;
	}

	$pattern = '/' . preg_quote( AHSC_DROPIN_MARKER, '/' ) . ':\s*([0-9]+(?:\.[0-9]+)*)/';

	if ( ! preg_match( $pattern, $contents, $matches ) ) {
		return null;
	}

	return $matches[1];
}

/**
 * Recognise a copy of our drop-in written before the marker existed.
 *
 * Without this every install that enabled APCu before this release would be classified as
 * foreign and skipped — which is precisely the population the sync exists to reach, since
 * they are the ones still running the drop-in with the PHP-4 constructor bug.
 *
 * Every revision this plugin has ever shipped descends from the same APC backend and
 * defines both of these names. Redis Object Cache, W3TC and LiteSpeed define neither; note
 * in particular that memcached-based drop-ins use WP_CACHE_KEY_SALT, not WP_APC_KEY_SALT,
 * so the two do not collide.
 *
 * @param  string $contents File contents.
 * @return bool
 */
function ahsc_dropin_looks_legacy( $contents ) {
	return is_string( $contents )
		&& false !== strpos( $contents, 'WP_APC_KEY_SALT' )
		&& false !== strpos( $contents, 'APC_Object_Cache' );
}

/**
 * Read the marker out of the drop-in this plugin ships.
 *
 * Used only to detect a release where the file changed but one of the two version bumps
 * was forgotten. Never on the fast path.
 *
 * @return string|null
 */
function ahsc_dropin_bundled_version() {
	global $wp_filesystem;

	if ( ! ahsc_dropin_init_filesystem() ) {
		return null;
	}

	return ahsc_dropin_parse_version( $wp_filesystem->get_contents( ahsc_dropin_source_path() ) );
}

/**
 * Describe the drop-in currently sitting in wp-content.
 *
 * Only ever called on the slow path — when the recorded revision does not match the
 * bundled one — because it reads the file off disk.
 *
 * @return array {
 *     @type string      $state   One of 'ours', 'foreign', 'missing', 'unknown'.
 *     @type string|null $version Revision when $state is 'ours' — the literal 'legacy' for
 *                                a copy predating the marker — and null otherwise.
 * }
 */
function ahsc_dropin_inspect() {
	global $wp_filesystem;

	if ( ! ahsc_dropin_init_filesystem() ) {
		// Cannot tell without reading the file. Treat as unknown and retry next request
		// rather than guessing, because guessing wrong means overwriting someone else's
		// object cache.
		return array(
			'state'   => 'unknown',
			'version' => null,
		);
	}

	$target = ahsc_dropin_target_path();

	if ( ! $wp_filesystem->exists( $target ) ) {
		return array(
			'state'   => 'missing',
			'version' => null,
		);
	}

	$contents = $wp_filesystem->get_contents( $target );
	$version  = ahsc_dropin_parse_version( $contents );

	if ( null !== $version ) {
		return array(
			'state'   => 'ours',
			'version' => $version,
		);
	}

	if ( ahsc_dropin_looks_legacy( $contents ) ) {
		// Ours, written before the marker existed. Reported as a distinct revision so it
		// never compares equal to AHSC_DROPIN_VERSION and always gets replaced.
		return array(
			'state'   => 'ours',
			'version' => 'legacy',
		);
	}

	return array(
		'state'   => 'foreign',
		'version' => null,
	);
}

/**
 * Copy the bundled drop-in over the installed one.
 *
 * The copy lands on a uniquely named temporary file first and is then moved into place.
 * WP_Filesystem::move() is a rename() on the direct transport, which is atomic within a
 * filesystem, so a concurrent request can never load a half-written object-cache.php —
 * a truncated file there is a fatal error on every page of the site, front end included.
 * The unique name means two requests racing to sync cannot corrupt each other's temporary
 * file either; they write separate ones and the second rename simply wins.
 *
 * Overwrites unconditionally: callers own the decision about whether the file in place may
 * be replaced. ahsc_dropin_sync() checks with ahsc_dropin_inspect(); the settings-page flow
 * checks with ahsc_check_apc_file() before it gets here.
 *
 * @return bool
 */
function ahsc_dropin_install() {
	global $wp_filesystem;

	if ( ! ahsc_dropin_init_filesystem() ) {
		AHSC_log(
			'Could not initialise WP_Filesystem, the object-cache.php drop-in was not updated.',
			'apcu',
			'warning'
		);
		return false;
	}

	$source = ahsc_dropin_source_path();
	$target = ahsc_dropin_target_path();
	$temp   = $target . '.' . uniqid( 'ahsc', true ) . '.tmp';

	if ( ! $wp_filesystem->copy( $source, $temp, true, FS_CHMOD_FILE ) ) {
		AHSC_log(
			sprintf( 'Could not stage the object-cache.php drop-in at %s.', $temp ),
			'apcu',
			'warning'
		);
		return false;
	}

	if ( ! $wp_filesystem->move( $temp, $target, true ) ) {
		$wp_filesystem->delete( $temp );
		AHSC_log(
			sprintf( 'Could not move the object-cache.php drop-in into %s.', $target ),
			'apcu',
			'warning'
		);
		return false;
	}

	return true;
}

/**
 * Discard everything the previous drop-in wrote.
 *
 * The key format changed between revisions — 4f260c4 restored the constructor, which put
 * md5( ABSPATH ) back into every key, and 8b448c2 changed the prefix used for global
 * groups — so entries written by the copy being replaced are unreachable. With no TTL on
 * them yet — the default expiration arrived in a later revision — they would occupy
 * shared memory until APCu expunges the whole segment under pressure, which is
 * precisely the stampede we are trying to avoid.
 *
 * apcu_clear_cache() is called directly rather than through wp_cache_flush(): the object
 * cache loaded in *this* request is still the old drop-in, whose flush() can silently
 * return true without flushing anything. This is pool-wide, but it happens once per
 * upgrade rather than once per content change, and the namespaced flush() in the new
 * drop-in keeps ordinary flushes inside this install's namespace.
 *
 * @return void
 */
function ahsc_dropin_flush_after_upgrade() {
	if ( function_exists( 'apcu_clear_cache' ) ) {
		apcu_clear_cache();
	}
}

/**
 * Record the revision now installed, and clear any pending retry back-off.
 *
 * Called on every outcome that leaves wp-content holding the revision we ship, so that the
 * per-request check short-circuits from here on.
 *
 * @return void
 */
function ahsc_dropin_record_version() {
	update_site_option( AHSC_DROPIN_VERSION_OPTION, AHSC_DROPIN_VERSION );
	delete_site_option( AHSC_DROPIN_RETRY_OPTION );
}

/**
 * Forget everything we track about the installed drop-in.
 *
 * Call whenever the file is removed — disabling the setting, deactivating, resetting the
 * options. Leaving the recorded revision behind would make the next enable look like an
 * already-current drop-in and skip the copy entirely.
 *
 * The status marker goes too, so the next request with logging enabled states the new
 * situation rather than treating it as unchanged.
 *
 * uninstall.php deletes the same options by name, because WordPress runs that file without
 * loading the plugin.
 *
 * @return void
 */
function ahsc_dropin_forget_version() {
	delete_site_option( AHSC_DROPIN_VERSION_OPTION );
	delete_site_option( AHSC_DROPIN_RETRY_OPTION );
	delete_site_option( AHSC_DROPIN_STATUS_OPTION );
}

/**
 * Remove the drop-in from wp-content, but only when it is ours.
 *
 * Deactivating, resetting or uninstalling used to delete wp-content/object-cache.php
 * unconditionally. On a site that also runs Redis Object Cache, W3 Total Cache or
 * LiteSpeed Cache that destroyed *their* drop-in: instant site-wide cold cache and a
 * database load spike, from switching off an unrelated plugin. Installation had always
 * checked for a foreign drop-in before writing, so the asymmetry was an oversight rather
 * than a decision.
 *
 * ahsc_dropin_inspect() is the same ownership test that gates the re-copy in
 * ahsc_dropin_sync(), including its recognition of our own pre-marker revisions, so
 * ownership is decided in exactly one place.
 *
 * The recorded revision is cleared for 'ours' and 'foreign' alike: in the first case the
 * file is gone, in the second it was never ours to describe. Only 'unknown' leaves it
 * alone — we could not read the file, so we know nothing, and the next request can retry.
 *
 * @return bool True when our drop-in is no longer installed, false when something is
 *              there that we refused to touch, or could not delete.
 */
function ahsc_dropin_remove() {
	global $wp_filesystem;

	$ahsc_dropin = ahsc_dropin_inspect();

	if ( 'missing' === $ahsc_dropin['state'] ) {
		ahsc_dropin_forget_version();

		return true;
	}

	if ( 'ours' !== $ahsc_dropin['state'] ) {
		AHSC_log(
			sprintf(
				'Leaving the %s object-cache.php drop-in in place; it is not ours to delete.',
				$ahsc_dropin['state']
			),
			'apcu',
			'warning'
		);

		if ( 'foreign' === $ahsc_dropin['state'] ) {
			ahsc_dropin_forget_version();
		}

		return false;
	}

	$target = ahsc_dropin_target_path();

	if ( ! $wp_filesystem->delete( $target ) ) {
		AHSC_log(
			sprintf( 'Could not delete the object-cache.php drop-in at %s.', $target ),
			'apcu',
			'warning'
		);

		return false;
	}

	ahsc_dropin_forget_version();

	return true;
}
/**
 * Replace the installed drop-in when it is ours and out of date.
 *
 * @return bool True when the file was rewritten.
 */
function ahsc_dropin_sync() {
	$bundled = ahsc_dropin_bundled_version();

	if ( null !== $bundled && AHSC_DROPIN_VERSION !== $bundled ) {
		// A release that edited the drop-in but forgot one of the two version bumps. The
		// file on disk wins, but say so loudly: the constant is what drives the cheap
		// per-request check, so while they disagree the sync runs on every request.
		AHSC_log(
			sprintf(
				'Drop-in version mismatch: AHSC_DROPIN_VERSION is %1$s but src/APC/object-cache.php declares %2$s.',
				AHSC_DROPIN_VERSION,
				$bundled
			),
			'apcu',
			'warning'
		);
	}

	$dropin = ahsc_dropin_inspect();

	if ( 'ours' !== $dropin['state'] ) {
		/*
		 * 'foreign' — the file belongs to another object cache. Overwriting it would
		 *             silently swap out Redis/W3TC/LiteSpeed underneath a running site.
		 * 'missing' — the setting says APCu is on but the file is gone. Deliberately not
		 *             re-created here: someone removing a drop-in by hand is usually
		 *             disabling a cache that is misbehaving, and resurrecting it from a
		 *             front-end request would fight them. The settings page still
		 *             re-installs it on demand.
		 * 'unknown' — WP_Filesystem is unavailable; retry on a later request.
		 */
		if ( 'foreign' === $dropin['state'] ) {
			$reason = 'it belongs to another plugin';
		} elseif ( 'missing' === $dropin['state'] ) {
			$reason = 'it is not installed';
		} else {
			$reason = 'WP_Filesystem is unavailable on this host';
		}

		AHSC_log(
			sprintf( 'Skipping object-cache.php sync: %s.', $reason ),
			'apcu',
			'warning'
		);

		return false;
	}

	if ( AHSC_DROPIN_VERSION === $dropin['version'] ) {
		// Already current — the recorded revision had simply gone missing or stale, which
		// is the normal state on the first request after upgrading from a version that
		// did not track it.
		ahsc_dropin_record_version();
		return false;
	}

	$previous = $dropin['version'];

	if ( ! ahsc_dropin_install() ) {
		return false;
	}

	ahsc_dropin_record_version();
	ahsc_dropin_flush_after_upgrade();

	AHSC_log(
		sprintf(
			'Updated the object-cache.php drop-in from %1$s to %2$s and cleared APCu. The new copy takes effect on the next request.',
			$previous,
			AHSC_DROPIN_VERSION
		),
		'apcu',
		'info'
	);

	return true;
}

/**
 * Per-request check: is the installed drop-in the one we ship?
 *
 * The fast path is a single read of an autoloaded option and a string comparison — no
 * filesystem access — so this is safe to run on front-end requests too. Only a mismatch
 * reaches ahsc_dropin_sync() and touches the disk.
 *
 * Hooked on plugins_loaded rather than upgrader_process_complete on purpose. That hook
 * fires while the *previous* version of this plugin is still the code in memory, so
 * AHSC_DROPIN_VERSION would still hold the old value; the first request after the upgrade
 * has the new constant loaded and gets it right in one pass. Note the replacement drop-in
 * only becomes active on the request after that, since wp_start_object_cache() runs long
 * before plugins_loaded.
 *
 * @return void
 */
function ahsc_dropin_maybe_sync() {
	$options  = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	$enabled  = is_array( $options ) && ! empty( $options['ahsc_apc'] );
	$recorded = (string) get_site_option( AHSC_DROPIN_VERSION_OPTION, '' );

	if ( ! $enabled ) {
		if ( '' !== $recorded ) {
			ahsc_dropin_forget_version();
		}
		return;
	}

	if ( AHSC_DROPIN_VERSION === $recorded ) {
		return;
	}

	// Past this point every outcome touches the disk, so back off after a failure rather
	// than repeating the attempt — and the wp-admin includes it pulls in — on every hit.
	$last_attempt = (int) get_site_option( AHSC_DROPIN_RETRY_OPTION, 0 );

	if ( $last_attempt > 0 && ( time() - $last_attempt ) < AHSC_DROPIN_RETRY_INTERVAL ) {
		return;
	}

	update_site_option( AHSC_DROPIN_RETRY_OPTION, time() );

	ahsc_dropin_sync();
}
add_action( 'plugins_loaded', 'ahsc_dropin_maybe_sync', 5 );

/**
 * Build the one-line summary of the object cache state.
 *
 * Reads no files — the installed revision comes from the autoloaded option maintained
 * above — so it is cheap enough to build on every request and compare.
 *
 * @return string
 */
function ahsc_dropin_status_line() {
	$options = AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS'];
	$enabled = is_array( $options ) && ! empty( $options['ahsc_apc'] );
	$dropin  = (string) get_site_option( AHSC_DROPIN_VERSION_OPTION, '' );

	if ( function_exists( 'apcu_enabled' ) && apcu_enabled() ) {
		$apcu = 'enabled';
	} elseif ( extension_loaded( 'apcu' ) ) {
		// Worth calling out rather than reporting a bare "off": in this state the drop-in
		// still convinces WordPress an external object cache is in use while every store
		// and fetch fails.
		$apcu = 'loaded but disabled';
	} else {
		$apcu = 'absent';
	}

	return sprintf(
		'object cache: setting=%1$s drop-in=%2$s bundled=%3$s apcu=%4$s stores=%5$s',
		$enabled ? 'on' : 'off',
		'' !== $dropin ? $dropin : 'none recorded',
		AHSC_DROPIN_VERSION,
		$apcu,
		ahsc_dropin_store_health()
	);
}

/**
 * Whether writes to APCu are landing.
 *
 * Reported as a state rather than the raw counter on purpose. The count differs on every
 * request, so including it would defeat the write-on-change rule below and produce a log
 * line per request on exactly the site that is already struggling. What matters for
 * support is the transition: a site that starts failing writes has run out of
 * apc.shm_size, and everything it thinks it is caching is being thrown away.
 *
 * @return string One of 'ok', 'failing', 'unknown'.
 */
function ahsc_dropin_store_health() {
	if ( ! isset( $GLOBALS['wp_object_cache'] ) || ! is_object( $GLOBALS['wp_object_cache'] ) ) {
		return 'unknown';
	}

	$cache = $GLOBALS['wp_object_cache'];

	/*
	 * Absent on a foreign object cache, on core's own, and on our drop-in before 1.4.0.
	 *
	 * isset() rather than reading the property and testing the result: core's
	 * WP_Object_Cache has no $stats at all, and reading an undefined property is an
	 * E_WARNING on PHP 8. That path is no longer rare — since item 13 the drop-in defines
	 * nothing when APCu is disabled, so core's cache is what this sees on those sites.
	 */
	if ( ! isset( $cache->stats ) || ! is_array( $cache->stats ) ||
	     ! isset( $cache->stats['store_failed'] ) ) {
		return 'unknown';
	}

	return $cache->stats['store_failed'] > 0 ? 'failing' : 'ok';
}

/**
 * Record the object cache state when it changes.
 *
 * Deliberately not surfaced in the admin UI: this is support diagnostics, and the settings
 * page has no room for it. It lands in the plugin's own log, which is written only when
 * WP_DEBUG is on and is readable from the Debug tab.
 *
 * Writes a line when the summary differs from the last one recorded, so what lands in the
 * log is the handful of moments that matter — the feature being switched on, a drop-in
 * upgrade, APCu disappearing from under a site, writes starting to fail — rather than a
 * line per request. An
 * unchanged status is repeated every AHSC_DROPIN_STATUS_INTERVAL so the log still carries a
 * recent statement of the state instead of one entry from months ago.
 *
 * The marker is updated only on the path that actually writes. If it were updated while
 * logging is switched off, turning WP_DEBUG on later would find an unchanged status and
 * stay silent, which is precisely when somebody wants to see this.
 *
 * Hooked on shutdown rather than plugins_loaded because the stores=… token reads a counter
 * that only means anything once the request has done its work; at plugins_loaded it is
 * always zero. Everything else in the line is known far earlier and does not mind waiting.
 *
 * @return void
 */
function ahsc_dropin_log_status() {
	if ( ! class_exists( '\ArubaSPA\HiSpeedCache\Debug\Logger' ) ) {
		return;
	}

	$status = ahsc_dropin_status_line();
	$last   = get_site_option( AHSC_DROPIN_STATUS_OPTION, array() );

	$previous  = is_array( $last ) && isset( $last['status'] ) ? (string) $last['status'] : '';
	$logged_at = is_array( $last ) && isset( $last['time'] ) ? (int) $last['time'] : 0;

	if ( $status === $previous && ( time() - $logged_at ) < AHSC_DROPIN_STATUS_INTERVAL ) {
		return;
	}

	AHSC_log( $status, 'apcu', 'info' );

	update_site_option(
		AHSC_DROPIN_STATUS_OPTION,
		array(
			'status' => $status,
			'time'   => time(),
		)
	);
}
add_action( 'shutdown', 'ahsc_dropin_log_status' );
