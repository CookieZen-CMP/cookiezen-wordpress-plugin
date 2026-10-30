<?php
/**
 * @package CookieZen
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'cookiezen_site_key' );
delete_option( 'cookiezen_markup_version' );
delete_option( 'cookiezen_purge_lock' );
