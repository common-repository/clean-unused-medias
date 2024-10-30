<?php
	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$medias_meta_keys = array( -1 );

// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add cron
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
add_action( 'init', 'ini_cron_lnjcm_check_medias_in_content' );
function ini_cron_lnjcm_check_medias_in_content() {
	if ( ! wp_next_scheduled( 'lnjcm_check_medias_in_content' ) ) {
		wp_schedule_event( time(), 'minutely', 'lnjcm_check_medias_in_content' );
	}
	if ( isset( $_GET ) && isset( $_GET['cumcheck'] ) ) {
		cron_lnjcm_check_medias_in_content(); }
}

//
add_action( 'lnjcm_check_medias_in_content', 'cron_lnjcm_check_medias_in_content_test' );
function cron_lnjcm_check_medias_in_content_test() {
	if ( ! lnjcm_get_option( 'lnjcm_medias_in_content_pause' ) && ! lnjcm_get_option( 'lnjcm_medias_in_content_completed' ) ) {
		cron_lnjcm_check_medias_in_content();
	}
}

//
function cron_lnjcm_check_medias_in_content() {
	//
	global $wpdb, $wp;

	//
	$lnjcm_medias_to_check         = 2;
	$lnjcm_medias_in_content_index = lnjcm_get_option( 'lnjcm_medias_in_content_index' );
	$lnjcm_medias_in_content_index = ( empty( $lnjcm_medias_in_content_index ) ) ? 0 : $lnjcm_medias_in_content_index;
	//
	$lnjcm_medias_in_content_ids  = lnjcm_get_option( 'lnjcm_medias_in_content_ids' );
	$lnjcm_medias_in_content_ids  = ( empty( $lnjcm_medias_in_content_index ) ) ? array() : unserialize( $lnjcm_medias_in_content_ids );
	$lnjcm_medias_in_postmeta_ids = lnjcm_get_option( 'lnjcm_medias_in_postmeta_ids' );
	$lnjcm_medias_in_postmeta_ids = ( empty( $lnjcm_medias_in_postmeta_ids ) ) ? array() : unserialize( $lnjcm_medias_in_postmeta_ids );
	$lnjcm_medias_in_usermeta_ids = lnjcm_get_option( 'lnjcm_medias_in_usermeta_ids' );
	$lnjcm_medias_in_usermeta_ids = ( empty( $lnjcm_medias_in_usermeta_ids ) ) ? array() : unserialize( $lnjcm_medias_in_usermeta_ids );
	$lnjcm_medias_in_option_ids   = lnjcm_get_option( 'lnjcm_medias_in_option_ids' );
	$lnjcm_medias_in_option_ids   = ( empty( $lnjcm_medias_in_option_ids ) ) ? array() : unserialize( $lnjcm_medias_in_option_ids );
	//
	$lnjcm_medias_in_content_last_check     = lnjcm_get_option( 'lnjcm_medias_in_content_last_check' );
	$lnjcm_medias_in_content_pause          = lnjcm_get_option( 'lnjcm_medias_in_content_pause' );
	$lnjcm_medias_in_content_completed      = lnjcm_get_option( 'lnjcm_medias_in_content_completed' );
	$lnjcm_medias_in_content_completed_date = lnjcm_get_option( 'lnjcm_medias_in_content_completed_date' );
	$lnjcm_medias_in_content_processing     = lnjcm_get_option( 'lnjcm_medias_in_content_processing' );

	//
	if ( ! empty( $lnjcm_medias_in_content_completed ) ) {
		return false;
	}

	//
	$lnjcm_post_types     = array();
	$lnjcm_get_post_types = get_post_types( '', 'names' );
	foreach ( $lnjcm_get_post_types as $lnjcm_post_type ) {
		$lnjcm_post_types[] = "'" . $lnjcm_post_type . "'";
	}
	$lnjcm_thumb_sizes = get_intermediate_image_sizes();

	//
	$sql           = '
		SELECT DISTINCT `ID`
		FROM `' . $wpdb->posts . "`
		WHERE
			`post_type` = 'attachment'
		ORDER BY `post_date_gmt` DESC
	";
	$all_media_ids = $wpdb->get_col( $sql );
	$total         = sizeof( $all_media_ids );

	//
	$sql      .= 'LIMIT ' . $lnjcm_medias_in_content_index . ', ' . $lnjcm_medias_to_check;
	$media_ids = $wpdb->get_col( $sql );

	if ( sizeof( $media_ids ) > 0 ) {
		foreach ( $media_ids as $media_id ) {
			//
			$lnjcm_used_in = get_post_meta( $media_id, 'lnjcm_used_in', 1 );
			$lnjcm_used_in = ( ! is_array( $lnjcm_used_in ) || empty( $lnjcm_used_in ) ) ? array() : $lnjcm_used_in;

			// search if media URL or ID in content
			$media_full_url = wp_get_attachment_url( $media_id );
			// $content_medias = array( basename( $media_full_url ) );
			$content_medias = array( $media_full_url );
			foreach ( $lnjcm_thumb_sizes as $thumb_size ) {
				$thumb = wp_get_attachment_image_src( $media_id, $thumb_size );
				if ( isset( $thumb[0] ) && ! empty( $thumb[0] ) ) {
					// $content_medias[] = str_replace("/", "\/", $thumb[0]);
					// $thumb_basename   = basename( $thumb[0] );
					// $content_medias[] = $thumb_basename;
					$content_medias[] = $thumb[0];
				}
			}
			//
			$sql                  = '
				SELECT DISTINCT `ID`
				FROM `' . $wpdb->posts . '`
				WHERE
					`post_type` IN (' . implode( ',', $lnjcm_post_types ) . ")
				AND (
						`post_content` REGEXP '" . implode( '|', $content_medias ) . "'
					OR  `post_content` REGEXP 'gallery(.*)ids=(\"|,){1}[ ,0-9]*(" . $media_id . ")(\"|,){1}'
				)
				AND `post_status` IN ('publish')
			";
			$posts_content_medias = $wpdb->get_col( $sql );
			if ( sizeof( $posts_content_medias ) > 0 ) {
				$lnjcm_medias_in_content_ids[] = $media_id;
				$lnjcm_used_in[]               = 'content';
			}

			// search if media URL is in `wp_options`
			$sql            = '
				SELECT DISTINCT `option_id`
				FROM `' . $wpdb->options . "`
				WHERE
					`option_value` REGEXP '" . implode( '|', $content_medias ) . "'
				AND `option_name` NOT IN ('_transient_jetpack_sitemap')
			";
			$options_medias = $wpdb->get_col( $sql );
			if ( sizeof( $options_medias ) > 0 ) {
				$lnjcm_medias_in_option_ids[] = $media_id;
				$lnjcm_used_in[]              = 'option';
			}

			// search if media URL is in `wp_postmeta`
			$sql              = '
				SELECT DISTINCT `meta_id`
				FROM `' . $wpdb->postmeta . "`
				WHERE
					`meta_value` REGEXP '" . implode( '|', $content_medias ) . "'
				AND `meta_key` NOT IN ('_wp_attached_file', '_wp_attachment_metadata', '_original_filename', '_imagify_data')
			";
			$postmetas_medias = $wpdb->get_col( $sql );
			if ( sizeof( $postmetas_medias ) > 0 ) {
				$lnjcm_medias_in_postmeta_ids[] = $media_id;
				$lnjcm_used_in[]                = 'postmeta';
			}

			// search if media URL is in `wp_usermeta`
			$sql              = '
				SELECT DISTINCT `umeta_id`
				FROM `' . $wpdb->usermeta . "`
				WHERE
					`meta_value` REGEXP '" . implode( '|', $content_medias ) . "'
			";
			$usermetas_medias = $wpdb->get_col( $sql );
			if ( sizeof( $usermetas_medias ) > 0 ) {
				$lnjcm_medias_in_usermeta_ids[] = $media_id;
				$lnjcm_used_in[]                = 'usermeta';
			}

			//
			update_post_meta( $media_id, 'lnjcm_used_in', $lnjcm_used_in );
		}
	}

	//
	$lnjcm_medias_in_content_index_next = $lnjcm_medias_in_content_index + $lnjcm_medias_to_check;
	if ( ( $lnjcm_medias_in_content_index_next ) >= $total ) {
		$lnjcm_medias_in_content_completed      = 1;
		$lnjcm_medias_in_content_completed_date = date( 'Y-m-d H:i:s' );
	}

	//
	lnjcm_update_option( 'lnjcm_medias_in_content_index', $lnjcm_medias_in_content_index_next );
	lnjcm_update_option( 'lnjcm_medias_in_content_ids', serialize( $lnjcm_medias_in_content_ids ) );
	lnjcm_update_option( 'lnjcm_medias_in_option_ids', serialize( $lnjcm_medias_in_option_ids ) );
	lnjcm_update_option( 'lnjcm_medias_in_postmeta_ids', serialize( $lnjcm_medias_in_postmeta_ids ) );
	lnjcm_update_option( 'lnjcm_medias_in_usermeta_ids', serialize( $lnjcm_medias_in_usermeta_ids ) );
	lnjcm_update_option( 'lnjcm_medias_in_content_last_check', date( 'Y-m-d H:i:s' ) );
	lnjcm_update_option( 'lnjcm_medias_in_content_completed', $lnjcm_medias_in_content_completed );
	lnjcm_update_option( 'lnjcm_medias_in_content_completed_date', $lnjcm_medias_in_content_completed_date );
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// init constant ACF fields
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_define_medias_meta_keys() {
	global $wpdb, $medias_meta_keys;

	//
	$sql                   = '
		SELECT DISTINCT `post_name`
		FROM `' . $wpdb->posts . "`
		WHERE
			`post_type` = 'acf-field'
		AND (`post_content` LIKE '%s:4:\"type\";s:5:\"image\";%' OR `post_content` LIKE '%s:4:\"type\";s:4:\"file\";%')
	";
	$medias_acf_field_keys = $wpdb->get_col( $sql );
	//
	if ( is_array( $medias_acf_field_keys ) && count( $medias_acf_field_keys ) ) {
		$sql              = "
			SELECT DISTINCT CONCAT('\'', SUBSTRING(`meta_key`, 2, CHAR_LENGTH(`meta_key`)), '\'') as `meta_key`
			FROM `" . $wpdb->postmeta . "`
			WHERE
			`meta_value` REGEXP '" . implode( '|', $medias_acf_field_keys ) . "'
		";
		$medias_meta_keys = $wpdb->get_col( $sql );
		$medias_meta_keys = ( ! is_array( $medias_meta_keys ) || empty( $medias_meta_keys ) ) ? array( -1 ) : $medias_meta_keys;
	}
}
add_action( 'init', 'lnjcm_define_medias_meta_keys' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// custom get_option
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_get_option( $name ) {
	global $wpdb;

	$result = $wpdb->get_col( 'SELECT `option_value` FROM `' . $wpdb->options . "` WHERE `option_name` = '" . $name . "'" );
	if ( isset( $result[0] ) ) {
		return $result[0];
	} else {
		return null;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// custom update_option
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_update_option( $name, $value ) {
	global $wpdb;

	$check = $wpdb->get_col( 'SELECT `option_value` FROM `' . $wpdb->options . "` WHERE `option_name` = '" . $name . "'" );
	if ( sizeof( $check ) == 0 ) {
		$wpdb->query( 'INSERT INTO `' . $wpdb->options . "` (`option_name`, `option_value`) VALUES ('" . $name . "', '" . $value . "')" );
	} else {
		$wpdb->query( 'UPDATE `' . $wpdb->options . "` SET `option_value` = '" . $value . "' WHERE `option_name` = '" . $name . "'" );
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is favicon
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_favicon( $media_id ) {
	$media_favicon = lnjcm_get_option( 'site_icon' );
	if ( ! empty( $media_favicon ) && $media_favicon == $media_id ) {
		return true;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is featured media
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_featured_media( $media_id ) {
	global $wpdb;

	$sql           = 'SELECT DISTINCT `post_id` FROM `' . $wpdb->postmeta . "` WHERE `meta_key` = '_thumbnail_id' AND `meta_value` = '" . $media_id . "'";
	$featured_post = $wpdb->get_col( $sql );
	if ( sizeof( $featured_post ) > 0 ) {
		return $featured_post;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is related media
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_related_media( $media_id ) {
	global $wpdb;

	$sql         = 'SELECT DISTINCT `post_parent` FROM `' . $wpdb->posts . "` WHERE `ID` = '" . $media_id . "' AND `post_parent` > 0 AND `post_type` = 'attachment'";
	$post_parent = $wpdb->get_col( $sql );
	if ( sizeof( $post_parent ) > 0 ) {
		return $post_parent;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is ACF media
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_ACF_media( $media_id ) {
	global $wpdb, $medias_meta_keys;
	//
	$sql       = '
        	SELECT DISTINCT `post_id`
        	FROM `' . $wpdb->postmeta . '`
        	WHERE
        		`meta_key` IN (' . implode( ',', $medias_meta_keys ) . ")
        	AND `meta_value` = '" . $media_id . "'
        ";
	$posts_acf = $wpdb->get_col( $sql );
	if ( sizeof( $posts_acf ) > 0 ) {
		return $posts_acf;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is media in content
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_media_in_content( $media_id ) {
	global $wpdb;
	//
	$lnjcm_post_types     = array();
	$lnjcm_get_post_types = get_post_types( '', 'names' );
	foreach ( $lnjcm_get_post_types as $lnjcm_post_type ) {
		$lnjcm_post_types[] = "'" . $lnjcm_post_type . "'"; }
	//
	$lnjcm_thumb_sizes = get_intermediate_image_sizes();
	// search if media URL or ID in content
	$media_full_url = wp_get_attachment_url( $media_id );
	$content_medias = array( $media_full_url );
	foreach ( $lnjcm_thumb_sizes as $thumb_size ) {
		$thumb = wp_get_attachment_image_src( $media_id, $thumb_size );
		if ( isset( $thumb[0] ) && ! empty( $thumb[0] ) ) {
			$content_medias[] = $thumb[0];
		}
	}
	//
	$sql                  = '
		SELECT DISTINCT `ID`
		FROM `' . $wpdb->posts . '`
		WHERE
			`post_type` IN (' . implode( ',', $lnjcm_post_types ) . ")
		AND (
				`post_content` REGEXP '" . implode( '|', $content_medias ) . "'
			OR  `post_content` REGEXP 'gallery(.*)ids=(\"|,){1}[ ,0-9]*(" . $media_id . ")(\"|,){1}'
		)
		AND `post_status` IN ('publish')
		ORDER BY `post_date_gmt` DESC
	";
	$posts_content_medias = $wpdb->get_col( $sql );
	if ( sizeof( $posts_content_medias ) > 0 ) {
		return $posts_content_medias;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is media used in postmeta
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_media_in_postmeta( $media_id ) {
	global $wpdb;
	//
	$lnjcm_thumb_sizes = get_intermediate_image_sizes();
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
	$sql              = '
			SELECT DISTINCT `post_id`
			FROM `' . $wpdb->postmeta . "`
			WHERE
				`meta_value` REGEXP '" . implode( '|', $content_medias ) . "'
			AND `meta_key` NOT IN ('_wp_attached_file', '_wp_attachment_metadata', '_original_filename', '_imagify_data')
		";
	$postmetas_medias = $wpdb->get_col( $sql );
	if ( sizeof( $postmetas_medias ) > 0 ) {
		return $postmetas_medias;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is media used in usermeta
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_media_in_usermeta( $media_id ) {
	global $wpdb;
	//
	$lnjcm_thumb_sizes = get_intermediate_image_sizes();
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
	$sql              = '
		SELECT DISTINCT `user_id`
		FROM `' . $wpdb->usermeta . "`
		WHERE
			`meta_value` REGEXP '" . implode( '|', $content_medias ) . "'
	";
	$usermetas_medias = $wpdb->get_col( $sql );
	if ( sizeof( $usermetas_medias ) > 0 ) {
		return $usermetas_medias;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// is media used in option
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_is_media_in_option( $media_id ) {
	global $wpdb;
	//
	$lnjcm_thumb_sizes = get_intermediate_image_sizes();
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
	$sql            = '
		SELECT DISTINCT `option_name`, `option_value`
		FROM `' . $wpdb->options . "`
		WHERE
			`option_value` REGEXP '" . implode( '|', $content_medias ) . "'
		AND `option_name` NOT IN ('_transient_jetpack_sitemap')
	";
	$options_medias = $wpdb->get_results( $sql );
	if ( sizeof( $options_medias ) > 0 ) {
		return $options_medias;
	} else {
		return false;
	}
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add admin js
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_add_admin_js( $hook ) {
	//
	$screen = get_current_screen();
	//
	if ( isset( $screen->id ) && in_array( $screen->id, array( 'media_page_clean-unused-medias', 'upload' ) ) ) {
		wp_enqueue_script( 'lnjcm_admin_js', WP_PLUGIN_URL . '/clean-unused-medias/admin.js', array( 'jquery' ) );
	}
	//
	$translation_array = array(
		'deleting_media_in_progress' => __( 'Deleting medias in progress...', 'clean-unused-medias' ),
		'relaunch_crawl'             => __( 'Relaunching crawl...', 'clean-unused-medias' ),
		'pause_crawl'                => __( 'Stopping crawl...', 'clean-unused-medias' ),
		'resume_crawl'               => __( 'Resuming crawl...', 'clean-unused-medias' ),
		'crawl_launch'               => __( 'Launching crawl...', 'clean-unused-medias' ),
		'loading'                    => __( 'Loading...', 'clean-unused-medias' ),
	);
	wp_localize_script( 'lnjcm_admin_js', 'lnjcm_tools_translate', $translation_array );
}
add_action( 'admin_print_scripts', 'lnjcm_add_admin_js' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add admin css
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_add_admin_css() {
	$screen = get_current_screen();
	if ( isset( $screen->id ) && in_array( $screen->id, array( 'media_page_clean-unused-medias', 'upload' ) ) ) {
		wp_enqueue_style( 'lnjcm_admin_css', WP_PLUGIN_URL . '/clean-unused-medias/admin.css' );
	}
}
add_action( 'admin_print_styles', 'lnjcm_add_admin_css' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_admin_footer() {
	//
	$screen = get_current_screen();
	//
	if ( isset( $screen->id ) && $screen->id == 'upload' ) {
	    //
		add_thickbox();
		//
		wp_nonce_field( 'lnjcm_get_medias_used_in', 'lnjcm_get_medias_used_in_nonce' );
		?>
		<div id="lnjcm-popin-details" class="lnjcm-popin"></div>
		<a href="#TB_inline?width=600&height=400&inlineId=lnjcm-popin-details" class="thickbox lnjcm-thickbox-launcher"><span><?php _e( 'Launch Thickbox', 'clean-unused-medias' ); ?></span></a>
		<?php
	}
}
add_action( 'admin_footer', 'lnjcm_admin_footer' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add submenu to medias
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_submenu_add_tools() {
	add_submenu_page( 'upload.php', __( 'Clean Unused Medias', 'clean-unused-medias' ), __( 'Clean Unused Medias', 'clean-unused-medias' ), 'manage_options', 'clean-unused-medias', 'lnjcm_tools' );
}
add_action( 'admin_menu', 'lnjcm_submenu_add_tools' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// add column in media list
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// Add col
function lnjcm_add_column( $cols ) {
	$cols['clean-unused-medias'] = __( 'Clean Unused Medias', 'clean-unused-medias' );

	return $cols;
}


// Display
function lnjcm_display_col_content( $column_name, $id ) {
	//
	if ( $column_name == 'clean-unused-medias' ) {
		//
		$lnjcm_used_in = get_post_meta( $id, 'lnjcm_used_in', 1 );
		$lnjcm_used_in = ( ! is_array( $lnjcm_used_in ) || empty( $lnjcm_used_in ) ) ? array() : $lnjcm_used_in;
		//
		if ( lnjcm_is_favicon( $id ) ) {
			$lnjcm_used_in[] = 'favicon';
		}
		if ( lnjcm_is_featured_media( $id ) ) {
			$lnjcm_used_in[] = 'featured';
		}
		if ( lnjcm_is_related_media( $id ) ) {
			$lnjcm_used_in[] = 'related';
		}
		if ( lnjcm_is_ACF_media( $id ) ) {
			$lnjcm_used_in[] = 'ACF';
		}
		//
		sort( $lnjcm_used_in );
		//
		$lnjcm_used_in = array_unique( $lnjcm_used_in );
		//
		if ( sizeof( $lnjcm_used_in ) > 0 ) {
			$lnjcm_used_in_display = array();
			foreach ( $lnjcm_used_in as $lnjcm_tag ) {
				$lnjcm_used_in_display[] = '<a href="#' . $lnjcm_tag . '" class="lnjcm-tag" data-media-id="' . $id . '">#' . $lnjcm_tag . '</a>';
			}
			echo implode( ' ', $lnjcm_used_in_display );
		} else {
			echo '<a href="#nope" class="lnjcm-tag" data-media-id="' . $id . '">' . __( 'Check it', 'clean-unused-medias' ) . '</a>';
		}
	}
}


// Hook actions to admin_init
function lnjcm_hook_add_column() {
	add_filter( 'manage_media_columns', 'lnjcm_add_column' );
	add_action( 'manage_media_custom_column', 'lnjcm_display_col_content', 10, 2 );
}
add_action( 'admin_init', 'lnjcm_hook_add_column' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// submenu cum tools page
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_tools() {
	global $wpdb;

	//
	add_thickbox();
	?>
		<div id="lnjcm-popin-details" class="lnjcm-popin"></div>
		<a href="#TB_inline?width=600&height=400&inlineId=lnjcm-popin-details" class="thickbox lnjcm-thickbox-launcher"><span><?php _e( 'Launch Thickbox', 'clean-unused-medias' ); ?></span></a>

	    <div class="wrap">  
		<?php
		if ( ! isset( $_COOKIE['lnjcm-warning-hide'] ) ) {
			?>
				<div class="lnjcm-notice notice notice-warning is-dismissible" id="lnjcm-warning">
					<p>
				<?php _e( 'Delete the medias is irreversible. Take precautions by making a backup and / or by being sure you really want to do it.', 'clean-unused-medias' ); ?>
					</p>
					<button type="button" class="notice-dismiss"></button>
				</div>
			<?php
		}
		?>
	        <form id="fcum" name="fcum" method="post" action="upload.php?page=clean-unused-medias">  
			<?php wp_nonce_field( 'lnjcm_get_medias_used_in', 'lnjcm_get_medias_used_in_nonce' ); ?>
			<?php wp_nonce_field( 'lnjcm_list_medias', 'lnjcm_list_medias_nonce' ); ?>
			<?php wp_nonce_field( 'lnjcm_do_delete_medias', 'lnjcm_do_delete_medias_nonce' ); ?>
			<?php wp_nonce_field( 'lnjcm_crawl_medias', 'lnjcm_crawl_medias_nonce' ); ?>
	            <h2><?php _e( 'Clean Unused Medias', 'clean-unused-medias' ); ?></h2>  
	            <fieldset>
	            	<legend><?php _e( 'Filters', 'clean-unused-medias' ); ?></legend>
		            <div class="lnjcm-filters">
		            	<ul>
		            		<li class="pagination">
							<?php _e( 'Medias per page', 'clean-unused-medias' ); ?>
		            			<select id="lnjcm-medias-per-page" name="lnjcm-medias-per-page">
		            				<!-- <option value="2">2</option> -->
		            				<!-- <option value="6">6</option> -->
		            				<option value="12">12</option>
		            				<option value="48">48</option>
		            				<option value="96">96</option>
		            			</select>
		            		</li>
		            		<li class="keyword">
		            			<input type="text" id="lnjcm_medias_keyword" name="lnjcm_media_keyword" value="" placeholder="<?php _e( 'Filter with keywords', 'clean-unused-medias' ); ?>" />
		            		</li>
		            		<li class="filters">
								<input type="button" class="button selectall" name="btn-select-filters" value="<?php _e( 'Check / Uncheck filters', 'clean-unused-medias' ); ?>" />
		            		</li>
		            		<li>
		            			<label><input type="checkbox" id="lnjcm_medias_not_theme_customise" name="lnjcm_medias_not_theme_customise" value="1" checked="checked" /><?php _e( 'Not the site favicon', 'clean-unused-medias' ); ?></label>
		            		</li>
		            		<li>
		            			<label><input type="checkbox" id="lnjcm_medias_not_thumb" name="lnjcm_medias_not_thumb" value="1" checked="checked" /><?php _e( 'Not a post thumbnail', 'clean-unused-medias' ); ?></label>
		            		</li>
		            		<li>
		            			<label><input type="checkbox" id="lnjcm_medias_not_related" name="lnjcm_medias_not_related" value="1" checked="checked" /><?php _e( 'Not related to a post', 'clean-unused-medias' ); ?></label>
		            		</li>
		            		<li>
		            			<label><input type="checkbox" id="lnjcm_medias_not_in_acf" name="lnjcm_medias_not_in_acf" value="1" checked="checked" /><?php _e( 'Not in ACF fields', 'clean-unused-medias' ); ?></label>
		            		</li>
		            		<li>
		            			<label>
		            				<input type="checkbox" id="lnjcm_medias_not_in_content" name="lnjcm_medias_not_in_content" value="1" checked="checked" /><?php _e( "Not in post's / page's / custom post type's content", 'clean-unused-medias' ); ?> <sup>(1)</sup>
		            			</label>		            			
		            		</li>
		            		<li>
		            			<label>
		            				<input type="checkbox" id="lnjcm_medias_not_in_postmeta" name="lnjcm_medias_not_in_postmeta" value="1" checked="checked" /><?php _e( "Not in post's / page's / custom post type's metas", 'clean-unused-medias' ); ?> <sup>(1)</sup>
		            			</label>		            			
		            		</li>
		            		<li>
		            			<label>
		            				<input type="checkbox" id="lnjcm_medias_not_in_usermeta" name="lnjcm_medias_not_in_usermeta" value="1" checked="checked" /><?php _e( "Not in user's metas", 'clean-unused-medias' ); ?> <sup>(1)</sup>
		            			</label>		            			
		            		</li>
		            		<li>
		            			<label>
		            				<input type="checkbox" id="lnjcm_medias_not_in_option" name="lnjcm_medias_not_in_option" value="1" checked="checked" /><?php _e( "Not in site's options", 'clean-unused-medias' ); ?> <sup>(1)</sup>
		            			</label>		            			
		            		</li>
		            	</ul>
        				<span id="lnjcm-crawling-status"> <sup>(1)</sup> <?php _e( "Those features need to crawl all the post's contents, post's metas, user's metas and site's options. It may takes some times.", 'clean-unused-medias' ); ?></span>
		            </div>
	            </fieldset>
	            <hr />
	            <div class="lnjcm-medias-content"><?php _e( 'Loading ...', 'clean-unused-medias' ); ?></div>
		        <hr class="clear" />
	            <p class="lnjcm-buttons clear">
					<input type="button" class="button" name="btn-select-medias" value="<?php _e( 'Check / Uncheck all', 'clean-unused-medias' ); ?>" />
	                <input type="submit" class="button button-primary" name="btn-delete-medias" value="<?php _e( 'Delete selected medias', 'clean-unused-medias' ); ?>" />
		            <span class="result"></span>
	            </p>
	        </form>
	    </div>  
	<?php
}


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// ajax : list medias
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_list_medias() {
	include LNJCM_PLUGIN_DIR . '/ws/list.medias.php';
	exit;
}
add_action( 'wp_ajax_lnjcm_list_medias', 'lnjcm_list_medias' );
add_action( 'wp_ajax_nopriv_lnjcm_list_medias', 'lnjcm_list_medias' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// ajax : delete medias
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_do_delete_medias() {
	include LNJCM_PLUGIN_DIR . '/ws/do.clean.medias.php';
	exit;
}
add_action( 'wp_ajax_lnjcm_do_delete_medias', 'lnjcm_do_delete_medias' );
add_action( 'wp_ajax_nopriv_lnjcm_do_delete_medias', 'lnjcm_do_delete_medias' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// ajax : crawl medias status
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_crawl_medias() {
	include LNJCM_PLUGIN_DIR . '/ws/crawl.medias.php';
	exit;
}
add_action( 'wp_ajax_lnjcm_crawl_medias', 'lnjcm_crawl_medias' );
add_action( 'wp_ajax_nopriv_lnjcm_crawl_medias', 'lnjcm_crawl_medias' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// ajax : crawl medias change status
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_crawl_medias_change_status() {
	include LNJCM_PLUGIN_DIR . '/ws/crawl.change.status.php';
	exit;
}
add_action( 'wp_ajax_lnjcm_crawl_medias_change_status', 'lnjcm_crawl_medias_change_status' );
add_action( 'wp_ajax_nopriv_lnjcm_crawl_medias_change_status', 'lnjcm_crawl_medias_change_status' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// ajax : get medias used in
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_get_medias_used_in() {
	include LNJCM_PLUGIN_DIR . '/ws/get.medias.used.in.php';
	exit;
}
add_action( 'wp_ajax_lnjcm_get_medias_used_in', 'lnjcm_get_medias_used_in' );
add_action( 'wp_ajax_nopriv_lnjcm_get_medias_used_in', 'lnjcm_get_medias_used_in' );


// -----------------------------------------------------------------------------------------------------------------------------------------------------------
// set link
// -----------------------------------------------------------------------------------------------------------------------------------------------------------
function lnjcm_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'upload.php?page=clean-unused-medias' ) . '">' . __( 'Dashboard', 'clean-unused-medias' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_clean-unused-medias/clean-unused-medias.php', 'lnjcm_add_settings_link' );
