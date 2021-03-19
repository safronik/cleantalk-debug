<?php
/**
 * Plugin Name:       Cleantalk Debug
 * Author:            CleanTalk team
 * Version:           1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'autoloader.php';                // Autoloader
require_once plugin_dir_path( __FILE__ ) . 'CleantalkLog.php';

// Activation of the plugin
register_activation_hook( __FILE__, 'activate_cleantalk_debug' );
function activate_cleantalk_debug() {
	global $wpdb;
	CleantalkLog::log_table_create( $wpdb );
	// Clear old items from the database by cron
	wp_clear_scheduled_hook( 'cleantalk_debug_clean_table' );
	wp_schedule_event( time(), 'daily', 'cleantalk_debug_clean_table');
}

// Deactivation of the plugin
register_deactivation_hook( __FILE__, 'deactivate_cleantalk_debug' );
function deactivate_cleantalk_debug() {
	global $wpdb;
	CleantalkLog::log_table_remove( $wpdb );
	wp_clear_scheduled_hook( 'cleantalk_debug_clean_table' );
}

// Cron event handler
add_action( 'cleantalk_debug_clean_table', 'do_cleantalk_debug_clean_table' );
function do_cleantalk_debug_clean_table() {
	global $wpdb;
	CleantalkLog::log_table_clean( $wpdb );
}

// Enqueue the script
add_action( 'admin_enqueue_scripts', 'cleantalk_debug_enqueue_script' );
function cleantalk_debug_enqueue_script( $hook ) {
	if( 'settings_page_cleantalk' == $hook ) {
		wp_enqueue_script('cleantalk_debug_script', plugins_url('/cleantalk-admin-export-logs.js', __FILE__), 'jquery', null, true);
		wp_localize_script(
			'cleantalk_debug_script',
			'cleantalk_debug',
			array( 'nonce' => wp_create_nonce() )
		);
	}
}

// Export the Logs ajax hook
add_action( 'wp_ajax_ct_export_logs', array( CleantalkLog::get_logger(), 'apbct_export_logs' ) );

/**
 * Check if cleantalk-spam-protect is active
 **/

if ( in_array( 'cleantalk-spam-protect/cleantalk.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	//date_default_timezone_set( 'Europe/Moscow' );

	add_action( 'apbct_skipped_request', 'do_logging', 10, 2 );

	// USING:
	// do_action( 'apbct_skipped_request', __FILE__ . ' -> ' . __FUNCTION__ . '():' . __LINE__, $_POST );
	
	function do_logging( $file, $post ) {
	    
	    $debugger = new \Cleantalk\Debug\BacktraceAnalyzer( debug_backtrace(), WP_PLUGIN_DIR );
        $func = $debugger
            ->selectElementByArgumentValue( 'apbct_skipped_request' )
            ->current;
            
		CleantalkLog::get_logger()->add_record(
			$file,
			array(
			'POST' => $post
		) );
	}

	add_filter( 'apbct_settings_action_buttons', 'add_action_button', 10, 1 );

	function add_action_button( $links ) {
		$debug_link = '<a href="#" class="ct_support_link" id="export-logs">' . __('Export skipped requests', 'cleantalk') . '</a>';
		array_push( $links, $debug_link );
		return $links;
	}

} else {

	// @ToDO we have to display a notice about cleantalk plugin required

}

//@ ToDo Сделать выгрузку логов и очистку таблицы от старых логов