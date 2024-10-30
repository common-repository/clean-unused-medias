<?php
	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	//
	global $wp_query, $wpdb;

	//
	$error   = 1;
	$message = __( 'Error. Parameters missing.', 'clean-unused-medias' );

	//
	ob_start();

if ( ! check_ajax_referer( 'lnjcm_get_medias_used_in', 'lnjcm_get_medias_used_in_nonce', false ) ) {
	$message = __( 'Error. Access denied.', 'clean-unused-medias' );
	$message = '<p>' . $message . '</p>';
} elseif ( empty( $media_id ) && empty( $_POST['type'] ) ) {
	$message = __( 'Error. Parameters missing.', 'clean-unused-medias' );
	$message = '<p>' . $message . '</p>';
} else {
	$error   = 0;
	$message = '';
	//
	$media_id = $_POST['media_id'];

	//
	$media = get_post( $media_id );

	// search if media URL or ID in content
	$media_full_url = wp_get_attachment_url( $media_id );
	$content_medias = array( basename( $media_full_url ) );
	foreach ( $lnjcm_thumb_sizes as $thumb_size ) {
		$thumb = wp_get_attachment_image_src( $media_id, $thumb_size );
		if ( isset( $thumb[0] ) && ! empty( $thumb[0] ) ) {
			$thumb_basename   = basename( $thumb[0] );
			$content_medias[] = $thumb_basename;
		}
	}

	//
	$lnjcm_used_in = get_post_meta( $media_id, 'lnjcm_used_in', 1 );
	$lnjcm_used_in = ( ! is_array( $lnjcm_used_in ) || empty( $lnjcm_used_in ) ) ? array() : $lnjcm_used_in;

	//
	$message .= '<h3>' . $media->post_title . '</h3>';

	//
	if ( lnjcm_is_favicon( $media->ID ) ) {
		$lnjcm_used_in[] = 'favicon'; }
	if ( lnjcm_is_featured_media( $media->ID ) ) {
		$lnjcm_used_in[] = 'featured'; }
	if ( lnjcm_is_related_media( $media->ID ) ) {
		$lnjcm_used_in[] = 'related'; }
	if ( lnjcm_is_ACF_media( $media->ID ) ) {
		$lnjcm_used_in[] = 'ACF'; }
	if ( lnjcm_is_media_in_content( $media->ID ) ) {
		$lnjcm_used_in[] = 'content'; }
	if ( lnjcm_is_media_in_postmeta( $media->ID ) ) {
		$lnjcm_used_in[] = 'postmeta'; }
	if ( lnjcm_is_media_in_usermeta( $media->ID ) ) {
		$lnjcm_used_in[] = 'usermeta'; }
	if ( lnjcm_is_media_in_option( $media->ID ) ) {
		$lnjcm_used_in[] = 'option'; }

	//
	$lnjcm_used_in = array_unique( $lnjcm_used_in );

	//
	if ( sizeof( $lnjcm_used_in ) > 0 ) {
		$message .= '<p>' . __( 'This media is:', 'clean-unused-medias' ) . '</p>';
		$message .= '<ul>';
		foreach ( $lnjcm_used_in as $lnjcm_tag ) {
			switch ( $lnjcm_tag ) {
				case 'favicon':
					$message .= '<li>' . __( 'used as a favicon', 'clean-unused-medias' ) . '</li>';
    				break;
				case 'featured':
					$posts = lnjcm_is_featured_media( $media_id );
					if ( sizeof( $posts ) == 1 ) {
						$post     = get_post( $posts[0] );
						$message .= '<li>' . sprintf( __( 'a featured image of <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
					} elseif ( sizeof( $posts ) > 1 ) {
						$message .= '<li>' . __( 'a featured image of:', 'clean-unused-medias' );
						foreach ( $posts as $post_id ) {
							$post = get_post( $post_id );

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
				case 'related':
					$post = get_post( $media->post_parent );

					$message .= '<li>' . sprintf( __( 'related to <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
    				break;
				case 'ACF':
					$posts = lnjcm_is_ACF_media( $media_id );
					if ( sizeof( $posts ) == 1 ) {
						$post     = get_post( $posts[0] );
						$message .= '<li>' . sprintf( __( 'used in an ACF of <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
					} elseif ( sizeof( $posts ) > 1 ) {
						$message .= '<li>' . __( 'used in an ACF of:', 'clean-unused-medias' );
						foreach ( $posts as $post_id ) {
							$post = get_post( $post_id );

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
				case 'content':
					$posts = lnjcm_is_media_in_content( $media_id );
					if ( sizeof( $posts ) == 1 ) {
						$post     = get_post( $posts[0] );
						$message .= '<li>' . sprintf( __( 'used in the content of <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
					} elseif ( sizeof( $posts ) > 1 ) {
						$message .= '<li>' . __( 'used in the content of:', 'clean-unused-medias' );
						foreach ( $posts as $post_id ) {
							$post = get_post( $post_id );

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
				case 'postmeta':
					$posts = lnjcm_is_media_in_postmeta( $media_id );
					if ( sizeof( $posts ) == 1 ) {
						$post     = get_post( $posts[0] );
						$message .= '<li>' . sprintf( __( 'used in the meta of <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
					} elseif ( sizeof( $posts ) > 1 ) {
						$message .= '<li>' . __( 'used in the meta of:', 'clean-unused-medias' );
						foreach ( $posts as $post_id ) {
							$post = get_post( $post_id );

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_post_link( $post->ID ), $post->post_title ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
				case 'usermeta':
					$users = lnjcm_is_media_in_usermeta( $media_id );
					if ( sizeof( $users ) == 1 ) {
						$User     = new WP_User( $users[0] );
						$message .= '<li>' . sprintf( __( 'used in meta for user <a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_user_link( $users[0] ), $User->display_name ) . '</li>';
					} elseif ( sizeof( $users ) > 1 ) {
						$message .= '<li>' . __( 'used in meta for the users:', 'clean-unused-medias' );
						foreach ( $users as $user_id ) {
							$User = new WP_User( $user_id );

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'clean-unused-medias' ), get_edit_user_link( $user_id ), $User->display_name ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
				case 'option':
					$options = lnjcm_is_media_in_option( $media_id );
					if ( sizeof( $options ) == 1 ) {
						$result   = $options[0];
						$message .= '<li>' . sprintf( __( 'used in the option <strong>`%s`</strong>', 'clean-unused-medias' ), $result->option_name ) . '</li>';
					} elseif ( sizeof( $options ) > 1 ) {
						$message .= '<li>' . __( 'used in the options:', 'clean-unused-medias' );
						foreach ( $options as $result ) {

							$message .= '<ul>';
							$message .= '<li>' . sprintf( __( '<strong>`%s`</strong>', 'clean-unused-medias' ), $result->option_name ) . '</li>';
							$message .= '</ul>';
						}
						$message .= '</li>';
					}
    				break;
			}
		}
		$message .= '</ul>';
	} else {
		$message .= '<p>' . __( 'This media is used anywhere.', 'clean-unused-medias' ) . '</p>';
	}
}

	//
	$debug = ob_get_contents();
	ob_end_clean();

    //
	header( 'Content-Type: text/javascript' );

	//
	$array = array(
		'error'   => $error,
  		'message' => $message,
  		'debug'   => $debug,
	);

	//
	echo json_encode( $array );
