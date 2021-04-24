<?php
/*
Plugin Name:  Cloudflare Page Cache Add-on
Plugin URI:   https://github.com/ph4ntomiki/worker-examples/tree/master/examples/edge-cache-html
Description:  Cache HTML pages on the Cloudflare CDN when used with the page cache Worker.
Version:      1.5
Author:       Mihael Mutic - Miki
Author URI:   https://mikitvba.com/
License:      GPLv2 or later
License URI:  http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  cloudflare-page-cache-addon
Domain Path:  /languages
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Callbacks that something changed
function cloudflare_page_cache_init_action() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	
	// Add the edge-cache headers
  if (!is_user_logged_in() ) {
    header( 'X-HTML-Edge-Cache: cache,bypass-cookies=wp-|wordpress|comment_|woocommerce_' );
  } else {
    header( 'X-HTML-Edge-Cache: nocache' );
	}

	// Post ID is received
	add_action( 'wp_trash_post', 'cloudflare_page_cache_purge1', 0 );
	add_action( 'publish_post', 'cloudflare_page_cache_purge1', 0 );
	add_action( 'edit_post', 'cloudflare_page_cache_purge1', 0 );
	add_action( 'delete_post', 'cloudflare_page_cache_purge1', 0 );
	add_action( 'publish_phone', 'cloudflare_page_cache_purge1', 0 );
	// Coment ID is received
	add_action( 'trackback_post', 'cloudflare_page_cache_purge2', 99 );
	add_action( 'pingback_post', 'cloudflare_page_cache_purge2', 99 );
	add_action( 'comment_post', 'cloudflare_page_cache_purge2', 99 );
	add_action( 'edit_comment', 'cloudflare_page_cache_purge2', 99 );
	add_action( 'wp_set_comment_status', 'cloudflare_page_cache_purge2', 99, 2 );
	// No post_id is available
	add_action( 'switch_theme', 'cloudflare_page_cache_purge1', 99 );
	add_action( 'edit_user_profile_update', 'cloudflare_page_cache_purge1', 99 );
	add_action( 'wp_update_nav_menu', 'cloudflare_page_cache_purge0' );
	add_action( 'clean_post_cache', 'cloudflare_page_cache_purge1' );
	add_action( 'transition_post_status', 'cloudflare_page_cache_post_transition', 10, 3 );

	// My hooks
	add_action( 'activated_plugin', 'cloudflare_page_cache_purge0' );
	add_action( 'deactivated_plugin', 'cloudflare_page_cache_purge0' );
	add_action( 'attachment_updated', 'cloudflare_page_cache_purge0' );
	add_action( 'automatic_updates_complete', 'cloudflare_page_cache_purge0' );
	add_action( '_core_updated_successfully', 'cloudflare_page_cache_purge0' );

	if ( isset( $_REQUEST['cf_clear_cache'] ) ) {
		check_admin_referer( 'cf_clear_cache' );
		cloudflare_page_cache_purge();
		add_action( 'admin_notices', 'cf_clear_cache_success' );
	}
}
add_action( 'init', 'cloudflare_page_cache_init_action' );

// Add the response header to purge the cache. send_headers isn't always called
// so set it immediately when something changes.
function cloudflare_page_cache_purge() {
  static $purged = false;
  if (!$purged) {
    $purged = true;
    header( 'X-HTML-Edge-Cache: purgeall' );
  }
}

function cloudflare_page_cache_purge0() {
  cloudflare_page_cache_purge();
}
function cloudflare_page_cache_purge1( $param1 ) {
  cloudflare_page_cache_purge();
}
function cloudflare_page_cache_purge2( $param1, $param2="" ) {
  cloudflare_page_cache_purge();
}
function cloudflare_page_cache_post_transition( $new_status, $old_status, $post ) {
  if ( $new_status != $old_status ) {
    cloudflare_page_cache_purge();
  }
}

/**
 * add a link to the WP Toolbar
 */
function cf_toolbar_link( $wp_admin_bar ) {
    $url = add_query_arg( '_wpnonce', wp_create_nonce( 'cf_clear_cache' ), admin_url() . '?cf_clear_cache=1' );
    $args = array(
        'id' => 'cf-clear-cache-link',
        'title' => 'Cloudflare Clear Cache', 
        'href' => $url, 
        'meta' => array(
            'title' => 'Cloudflare Clear Cache'
        )
    );
    $wp_admin_bar->add_node( $args );
}
add_action( 'admin_bar_menu', 'cf_toolbar_link', 999 );

function cf_clear_cache_success() { ?>
    <div class="updated">
        <p><?php _e( 'Cache cleared!', 'cloudflare-page-cache-addon' ); ?></p>
    </div>
<?php
}