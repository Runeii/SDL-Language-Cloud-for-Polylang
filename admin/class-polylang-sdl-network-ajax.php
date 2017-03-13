<?php 
/** 
WordPress does not as of yet offer a admin-ajax variant for network admin pages. This is based off of an in progress file, as well as with a few amends to meet the requirements of the current plugin. 

This should be replaced with the official file and process, once released.

*/

define( 'DOING_AJAX', true ); 
if ( ! defined( 'WP_NETWORK_ADMIN' ) ) { 
        define( 'WP_NETWORK_ADMIN', true ); 
} 
echo 'Huh?';
 
// Load WordPress Bootstrap. 
require_once(dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php'); 
 
echo 'Huh2?';
// Allow for cross-domain requests (from the front end). 
send_origin_headers(); 
 
echo 'Huh3?';
if ( ! is_multisite() ) { 
        die( '0' ); 
} 

echo 'Huh4?';
// Require an action parameter. 
if ( empty( $_REQUEST['action'] ) ) { 
        die( '0' ); 
} 
 
echo 'Huh5?';
// Load WordPress Administration APIs. 
require_once( ABSPATH . 'wp-admin/includes/admin.php' ); 
 
@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) ); 
@header( 'X-Robots-Tag: noindex' ); 
 
send_nosniff_header(); 
nocache_headers(); 
 
/** This action is documented in wp-admin/admin.php */ 
do_action( 'admin_init' ); 
 
if ( is_user_logged_in() ) { 
        if($_REQUEST['action'] == 'sdl_admin_updateoptions') {
                echo 'Yes you found me';
                global $wpdb;
                $blog_id = intval( $_POST['blog_id'] );
                $id = intval( $_POST['id'] );
                echo $id;
                echo $blog_id;
                echo 'what on ear';
                update_blog_option($blog_id, 'projectoptions', $id);

                wp_die();
        } else {
                echo 'No action found';
                wp_die();
        }
} 
// Default status. 
die( '0' ); 
?>