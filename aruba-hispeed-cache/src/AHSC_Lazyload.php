<?php

if ( isset( AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_lazy_load'] ) &&
      AHSC_CONSTANT['ARUBA_HISPEED_CACHE_OPTIONS']['ahsc_lazy_load'] ) {
	add_action( 'plugins_loaded', 'ahsc_wp_lazy_loading_initialize_filters', 1 );
}
function ahsc_wp_lazy_loading_initialize_filters() {
	foreach ( array( 'the_content', 'the_excerpt', 'widget_text_content','do_shortcode_tag','render_block','post_thumbnail_html') as $filter ) {
		add_filter( $filter, 'ahsc_wp_filter_content_tags' );
		//add_filter( $filter, 'ahsc_add_image_dimensions' );
	}
	add_filter( 'wp_get_attachment_image_attributes', 'ahsc_wp_lazy_loading_add_attribute_to_attachment_image' );
	add_filter( 'get_avatar', 'ahsc_wp_lazy_loading_add_attribute_to_avatar' );
	//remove_filter( 'the_content', 'wp_make_content_images_responsive' );
}

function ahsc_wp_lazy_loading_add_attribute_to_avatar( $avatar ) {
	if ( ahsc_wp_lazy_loading_enabled( 'img', 'get_avatar' ) && false === strpos( $avatar, ' loading=' ) ) {
		$avatar = str_replace( '<img ', '<img decoding="async" loading="lazy" ', $avatar );
	}

	return $avatar;
}

function ahsc_wp_lazy_loading_add_attribute_to_attachment_image( $attr ) {
	if ( ahsc_wp_lazy_loading_enabled( 'img', 'wp_get_attachment_image' ) && ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
		$attr['decoding'] = 'async';
	}
	return $attr;
}

function ahsc_wp_lazy_loading_enabled( $tag_name, $context ) {
	$default = ( 'img' === $tag_name );
	return (bool) apply_filters( 'wp_lazy_loading_enabled', $default, $tag_name, $context );
}

function ahsc_wp_filter_content_tags( $content, $context = null ) {
	if ( null === $context ) {
		$context = current_filter();
	}
	$add_loading_attr = ahsc_wp_lazy_loading_enabled( 'img', $context );

	if ( false === strpos( $content, '<img' ) ) {
		return $content;
	}

	if ( ! preg_match_all( '/<img\s[^>]+>/', $content, $matches ) ) {
		return $content;
	}

	$images = array();
	foreach ( $matches[0] as $image ) {
		if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
			$attachment_id = absint( $class_id[1] );

			if ( $attachment_id ) {
				$images[ $image ] = $attachment_id;
				continue;
			}
		}elseif ( preg_match( '/< *img[^>]*src *= *["\']?([^"\']*)/i', $image, $class_id ) ){
				$images[ $image ] = attachment_url_to_postid($class_id[1]);

		}else {
			$images[ $image ] = 0;
		}
	}

	$attachment_ids = array_unique( array_filter( array_values( $images ) ) );
	if ( count( $attachment_ids ) > 1 ) {
		_prime_post_caches( $attachment_ids, false, true );
	}

	foreach ( $images as $image => $attachment_id ) {
		$filtered_image = $image;
		if ( $attachment_id >= 0 && false === strpos( $filtered_image, ' srcset=' ) ) {
			$filtered_image = ahsc_wp_img_tag_add_srcset_and_sizes_attr( $filtered_image, $context, $attachment_id );
		}
		if ( $add_loading_attr && false === strpos( $filtered_image, ' loading=' ) ) {
			$filtered_image = ahsc_wp_img_tag_add_loading_attr( $filtered_image, $context );
		}

		if ( $filtered_image !== $image ) {
			$content = str_replace( $image, $filtered_image, $content );
		}
	}
	return $content;
}

function ahsc_wp_img_tag_add_loading_attr( $image, $context ) {

	$value = apply_filters( 'wp_img_tag_add_loading_attr', 'lazy', $image, $context );

	if ( $value ) {
		if ( ! in_array( $value, array( 'lazy', 'eager' ), true ) ) {
			$value = 'lazy';
		}
		return str_replace( '<img', '<img decoding="async" loading="' . $value . '"', $image );
	}

	return $image;
}

function ahsc_wp_img_tag_add_srcset_and_sizes_attr( $image, $context, $attachment_id ) {

	$add = apply_filters( 'wp_img_tag_add_srcset_and_sizes_attr', true, $image, $context, $attachment_id );

	if ( true === $add ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id ,true);
		return wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id );
	}

	return $image;
}

function ahsc_add_image_dimensions( $content ) {

	preg_match_all( '/<img[^>]+>/i', $content, $images);

	if (count($images) < 1)
		return $content;

	foreach ($images[0] as $image) {
		preg_match_all( '/(alt|title|src|width|class|id|height|style)=("[^"]*")/i', $image, $img );

		if ( !in_array( 'src', $img[1] ) )
			continue;

		if ( !in_array( 'width', $img[1] ) || !in_array( 'height', $img[1] ) ) {
			$src = $img[2][ array_search('src', $img[1]) ];
			$alt = in_array( 'alt', $img[1] ) ? ' alt=' . $img[2][ array_search('alt', $img[1]) ] : '';
			$title = in_array( 'title', $img[1] ) ? ' title=' . $img[2][ array_search('title', $img[1]) ] : '';
			$class = in_array( 'class', $img[1] ) ? ' class=' . $img[2][ array_search('class', $img[1]) ] : '';
			$id = in_array( 'id', $img[1] ) ? ' id=' . $img[2][ array_search('id', $img[1]) ] : '';
			$style = in_array( 'style', $img[1] ) ? ' style=' . $img[2][ array_search('style', $img[1]) ] : '';
			list( $width, $height, $type, $attr ) = getimagesize( str_replace( "\"", "" , $src ) );

			$image_tag = sprintf( '<img src=%s%s%s%s%s%s width="%d" height="%d" />', $src, $alt, $title, $class, $id,$style, $width, $height );
			$content = str_replace($image, $image_tag, $content);
		}
	}

	return $content;
}
