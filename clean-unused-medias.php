<?php
	/*
	Plugin Name: 	Clean Unused Medias
	Version:		1.10
	Date:			2021/05/18
	Plugin URI:		https://xuxu.fr/2016/09/28/supprimer-les-fichiers-non-utilises-sous-wordpress/
	Description:	Clean Unused Medias : Simple way to delete medias not attached to any posts or pages
	Author:			Xuan NGUYEN
	Text Domain:	clean-unused-medias
	Domain Path:	/languages/
	Author URI:		https://xuxu.fr
	*/

	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	/*
	 +---------------------------------------------------------------------------------------------------+
	   | CONSTANTES
	   +---------------------------------------------------------------------------------------------------+ */
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	$lnjcm_plugin_dir = str_replace( 'clean-unused-medias/', '', dirname( __FILE__ ) );
	define( 'WP_PLUGIN_DIR', $lnjcm_plugin_dir );
}
	define( 'LNJCM_PLUGIN_DIR', WP_PLUGIN_DIR . '/clean-unused-medias' );

	/*
	 +---------------------------------------------------------------------------------------------------+
	   | INCLUDES
	   +---------------------------------------------------------------------------------------------------+ */
	require_once LNJCM_PLUGIN_DIR . '/library/includes/compat.php';
	require_once LNJCM_PLUGIN_DIR . '/library/install.php';
	require_once LNJCM_PLUGIN_DIR . '/library/functions.php';

	/*
	 +---------------------------------------------------------------------------------------------------+
	   | REGISTER ACTIVATION
	   +---------------------------------------------------------------------------------------------------+ */
	register_activation_hook( __FILE__, 'lnjcm_install' );
	register_deactivation_hook( __FILE__, 'lnjcm_uninstall' );


	/*
	 +---------------------------------------------------------------------------------------------------+
	   | TEXT DOMAIN
	   +---------------------------------------------------------------------------------------------------+ */
function lnjcm_load_textdomain() {
	load_plugin_textdomain( 'clean-unused-medias', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
	add_action( 'init', 'lnjcm_load_textdomain' );
