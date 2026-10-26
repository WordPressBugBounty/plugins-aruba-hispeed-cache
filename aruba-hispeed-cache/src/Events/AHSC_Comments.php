<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Comment purges. Every way a comment can appear on, or disappear from, a public page goes
 * through one of two hooks:
 *
 *   wp_insert_comment          a new comment. Purges only if it arrives already approved:
 *                              a pending or spam comment changes nothing a visitor can see,
 *                              and if it is approved later the transition below purges then.
 *   transition_comment_status  everything after that — approve, unapprove, spam, trash,
 *                              untrash and permanent deletion. Core routes all of them
 *                              through wp_transition_comment_status(): wp_trash_comment() via
 *                              wp_set_comment_status(), and wp_delete_comment() explicitly,
 *                              with 'delete' as the new status. The handler sees every one,
 *                              whether it came from the admin screens, REST or WP-CLI.
 *
 * deleted_comment and rest_delete_comment used to be hooked here as well, and they purged
 * unconditionally — whether or not the comment had ever been visible. Permanently deleting a
 * spam comment, or trashing one over REST, emptied the whole cache for a page change that
 * never happened; for an approved comment they only repeated the purge the transition
 * handler had already done. Both are gone.
 *
 * rest_insert_comment stays, for updates only: a REST create has already been through
 * wp_insert_comment(), while editing the text of an approved comment fires no status
 * transition, so this is the only signal for it.
 */
if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_new_comment']){
	\add_action( 'wp_insert_comment', 'ahsc_purge_page_on_new_comment' , 200, 2 );
	\add_action( 'rest_insert_comment', 'ahsc_purge_page_on_new_comment_rest' , 200, 3 );
	\add_action( 'transition_comment_status','ahsc_purge_page_on_transition_comment_status' , 200, 3 );
}

/**
 * Purge after a comment is added, if it is visible.
 *
 * @param int         $id      the comment id.
 * @param \WP_Comment $comment comment object.
 *
 * @return void
 */
 function ahsc_purge_page_on_new_comment( $id, $comment ) {
	/*
	 * Only an approved comment is on a public page, and comment_approved stores that as
	 * '1'; the alternatives are '0' (pending), 'spam' and 'trash'. Without this check every
	 * comment a spam bot posted emptied the whole cache: Akismet marks it as spam, but it is
	 * still inserted, and wp_insert_comment still fires.
	 */
	if ( ! ( $comment instanceof WP_Comment ) || '1' !== (string) $comment->comment_approved ) {
		return;
	}

	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	 $_post_id = $comment->comment_post_ID;
	 $target   = \get_permalink( $_post_id );

	/*
	 * Mode switch: whole cache, or only the page the comment is on. It reads the *comment*
	 * toggle — the one the settings screen shows for comment events, "A comment is entered,
	 * approved, declined, published or deleted in any part of the website", under the
	 * heading "Automatically clear whole cache when:". It used to read
	 * ahsc_purge_homepage_on_edit, the toggle for articles and pages, so turning that off
	 * silently demoted comment events to a targeted purge, which no label describes.
	 *
	 * The targeted purge below is unreachable while this same option also gates hook
	 * registration at the top of the file: toggle off means no comment hooks at all. It is
	 * kept for when that registration asymmetry is addressed.
	 */
	 if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_new_comment']){
		 if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			 AHSC_log( 'hook::wp_insert_comment::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			 // Logger.
		 }
		$cleaner->purgeAll();
		return;
	 }

	 if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		 // Logger.
		 AHSC_log( 'hook::wp_insert_comment::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		 // Logger.
	 }
	$cleaner->purgeUrl( $target );
}

/**
 * Purge after a comment is updated over REST, if it is visible.
 *
 * @param  WP_Comment      $comment  .
 * @param  WP_REST_Request $request  .
 * @param  boolean         $creating True when this request created the comment.
 * @return void
 */
 function ahsc_purge_page_on_new_comment_rest( $comment, $request, $creating ) {
	// A create has already reached ahsc_purge_page_on_new_comment() via wp_insert_comment().
	if ( $creating ) {
		return;
	}

	ahsc_purge_page_on_new_comment( $comment->comment_ID, $comment );
}

/**
 * Ahsc_purge_page_on_transition_comment_status
 * Purge the cache of item or site on canghe status of the comment
 *
 * @param int|string  $new_status new status .
 * @param int|string  $old_status old status .
 * @param \WP_Comment $comment    comment object .
 *
 * @return void
 */
 function ahsc_purge_page_on_transition_comment_status( $new_status, $old_status, $comment ) {
	/*
	 * Same mode switch as the insert handler: the comment toggle says "A comment
	 * is entered, approved, declined, published or deleted in any part of the website", under
	 * the heading "Automatically clear whole cache when:", so moderation clears the whole
	 * cache too.
	 *
	 * A targeted purge of the commented page is not enough to honour that, and not only on a
	 * technicality: a recent-comments widget or a comment count rendered in a sidebar or on
	 * the homepage would keep showing the old state until the proxy TTL expired. Those are
	 * the "any part of the website" the label is talking about.
	 *
	 * The condition below is 'approved' on *either* side of the transition, which is what
	 * makes a comment appear or disappear for readers: approve (unapproved to approved),
	 * decline (approved to unapproved), spam, trash, untrash back to approved, and permanent
	 * deletion, which core reports as 'delete'. Spam to trash has neither side approved, so
	 * it purges nothing — that comment was never on a public page. It used to
	 * test only for a new status of 'approved' or 'trash', so declining a comment left it
	 * visible in the cache — the "declined" case the label names was never handled.
	 * Decided 2026-09-16.
	 */
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	 $_post_id = $comment->comment_post_ID;
	 $target   = \get_permalink( $_post_id );

	/*
	 * Either side being 'approved' is what changes what a reader sees. Anything else, such
	 * as spam moving to trash, never touched a public page.
	 */
	if ( 'approved' !== $new_status && 'approved' !== $old_status ) {
		return;
	}

	$ahsc_log_tag = ( 'approved' === $new_status ) ? 'approved' : $new_status;

	if ( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_new_comment'] ) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook:' . $ahsc_log_tag . ':transition_comment_status::home', __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
		return;
	}

	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		// Logger.
		AHSC_log( 'hook:' . $ahsc_log_tag . ':transition_comment_status::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeUrl( $target );
}
