<?php
/** check for controll options delete comment*/
/*if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_deleted_comment']){
	\add_action( 'deleted_comment', 'ahsc_purge_page_on_deleted_comment' , 200, 2 );
	\add_action( 'rest_delete_comment',  'ahsc_purge_page_on_deleted_comment_rest' , 200, 3 );
}*/
/** check for controll options new comment*/
if(is_array(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']) && AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_new_comment']){
	\add_action( 'deleted_comment', 'ahsc_purge_page_on_deleted_comment' , 200, 2 );
	\add_action( 'rest_delete_comment',  'ahsc_purge_page_on_deleted_comment_rest' , 200, 3 );

	\add_action( 'wp_insert_comment', 'ahsc_purge_page_on_new_comment' , 200, 2 );
	\add_action( 'rest_insert_comment', 'ahsc_purge_page_on_new_comment_rest' , 200, 3 );
	\add_action( 'transition_comment_status','ahsc_purge_page_on_transition_comment_status' , 200, 3 );
}
/*
if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_deleted_comment'] ||
   AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_page_on_new_comment']){
	\add_action( 'transition_comment_status','ahsc_purge_page_on_transition_comment_status' , 200, 3 );
}*/

/**
 * Issues a call, via the 'WpPurger' class, to the proxy cache cleaner.
 *
 * @param int         $id      the comment id.
 * @param \WP_Comment $comment comment object.
 *
 * @return void
 */
 function ahsc_purge_page_on_deleted_comment( $id, $comment ) {
	$cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	 $_post_id = $comment->comment_post_ID;
	 $target   = \get_permalink( $_post_id );

	if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit']){
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook::deleted_comment::home' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeAll();
	    return;
	}

	if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
		// Logger.
		AHSC_log( 'hook::deleted_comment::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
		// Logger.
	}
	$cleaner->purgeUrl( $target );
}

/**
 * Wrap for rest delete comment.
 *
 * @param  WP_Comment      $comment .
 * @param  WP_REST_Request $request .
 * @param  boolean         $creating .
 * @return void
 */
 function ahsc_purge_page_on_deleted_comment_rest( $comment, $request, $creating ) {
	ahsc_purge_page_on_deleted_comment( $comment->comment_ID, $comment );
}


/**
 * Issues a call, via the 'WpPurger' class, to the proxy cache cleaner.
 *
 * @param int         $id      the comment id.
 * @param \WP_Comment $comment comment object.
 *
 * @return void
 */
 function ahsc_purge_page_on_new_comment( $id, $comment ) {
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	 $_post_id = $comment->comment_post_ID;
	 $target   = \get_permalink( $_post_id );

	 if(AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_purge_homepage_on_edit']){
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
 * Wrap for rest insert comment.
 *
 * @param  WP_Comment      $comment .
 * @param  WP_REST_Request $request .
 * @param  boolean         $creating .
 * @return void
 */
 function ahsc_purge_page_on_new_comment_rest( $comment, $request, $creating ) {
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
	 $cleaner =new \ArubaSPA\HiSpeedCache\Purger\WpPurger();
	 $cleaner->setPurger( AHSC_PURGER );
	 $_post_id = $comment->comment_post_ID;
	 $target   = \get_permalink( $_post_id );

	if ( 'approved' === $new_status ) {
		if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
			// Logger.
			AHSC_log( 'hook:approved:transition_comment_status::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
			// Logger.
		}
		$cleaner->purgeUrl( $target );
		return;
	}

	if ( 'trash' === $new_status ) {
		if (  'approved' === $old_status ) {
			if(class_exists('ArubaSPA\HiSpeedCache\Debug\Logger')) {
				// Logger.
				AHSC_log( 'hook:trash:transition_comment_status::' . $target, __NAMESPACE__ . '::' . __FUNCTION__, 'debug' );
				// Logger.
			}
			$cleaner->purgeUrl( $target );
		}
	}
}