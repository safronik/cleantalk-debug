<?php

/**
 * Logging the skipped requests.
 * Compatible only with Wordpress.
 *
 * @copyright  (C) 2014 CleanTalk team (http://cleantalk.org)
 * @license    GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @see        https://github.com/CleanTalk/php-antispam
 * @since      5.122.4
 * @author     Cleantalk team (welcome@cleantalk.org)
 */

class CleantalkLog
{

	/**
	 * Collection of log records.
	 *
	 * @access   protected
	 * @var      array    $records    The array of the records info like  array( 'info' => array(), 'additional_info' => array() )
	 */
	protected static $records = array();

	/**
	 * Instance of this class.
	 *
	 * @access   protected
	 * @var      object    $logger    CleantalkLog instance
	 */
	protected static $logger;

	/**
	 * Table name.
	 *
	 * @access   protected
	 * @var      string    $table_name
	 */
	protected static $table_name = 'cleantalk_skipped_log';

	/**
	 * Instance of CleantalkDB_Wordpress class.
	 *
	 * @access   protected
	 * @var      object    $db_handler  CleantalkDB_Wordpress instance
	 */
	protected $db_handler;

	/**
	 * CleantalkLog constructor.
	 *
	 * @access   protected
	 * @param    $db_handler    wpdb instance
	 */
	protected function __construct( $db_handler )
	{
		$this->db_handler = $db_handler;
	}

	/**
	 * Create table to collect logs
	 *
	 * @param $db_handler  wpdb instance
	 *
	 * @return mixed       int or false (https://codex.wordpress.org/wpdb#Running_General_Queries)
	 */
	public static function log_table_create ( $db_handler )
	{
		$query = 'CREATE TABLE IF NOT EXISTS ' . $db_handler->prefix . self::$table_name . ' (
			id int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
			record_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			record_info text(65535),
			additional_info text(65535)
		)';

		$result = $db_handler->query( $query );

		return $result;
	}

	/**
	 * Remove table through uninstalling
	 *
	 * @param $db_handler  wpdb instance
	 *
	 * @return mixed       int or false (https://codex.wordpress.org/wpdb#Running_General_Queries)
	 */
	public static function log_table_remove ( $db_handler ) {
		$query = 'DROP TABLE IF EXISTS ' . $db_handler->prefix . self::$table_name;

		$result = $db_handler->query( $query );

		return $result;
	}

	public static function log_table_clean( $db_handler ) {
		$query = 'DELETE FROM ' . $db_handler->prefix . self::$table_name . ' WHERE record_date < NOW() - INTERVAL 7 DAY';

		$result = $db_handler->query( $query );

		return $result;
	}

	/**
	 * Get Instance of this class.
	 * (Singletone pattern)
	 *
	 * @return CleantalkLog|object
	 */
	public static function get_logger()
	{
		if( ! isset ( self::$logger ) ){
			global $wpdb;
			self::$logger = new CleantalkLog( $wpdb );
		}

		return self::$logger;
	}

	/**
	 * Add log data to collection
	 *
	 * @param      $function_name       - in which function was triggered log calling
	 * @param null $additional_info     - additional info to add to collection (like $_POST)
	 */
	public function add_record ( $path, $additional_info = null )
	{
		self::$records[] = array(
			'info'            => 'Request was skipped in ' . $path,
			'additional_info' => $additional_info
		);
	}

	/**
	 * Save collection to the DB
	 */
	public function __destruct()
	{
		if( empty( self::$records ) ) {
			return;
		}

		$query = 'INSERT INTO ' . $this->db_handler->prefix . self::$table_name . ' 
			(record_info, additional_info) 
			VALUES ';
		foreach ( self::$records as $record ) {
			$query .= '(\'' . json_encode( $record['info'], JSON_FORCE_OBJECT ) . '\', \'' . json_encode( $record['additional_info'], JSON_FORCE_OBJECT ) . '\'),';
		}
		$query = substr( $query,0,-1 );

		$this->db_handler->query( $query );
	}

	/**
	 * Export logs from DB to .log file
	 * This method was fired by AJAX
	 */
	public function apbct_export_logs() {

		// Check nonce
		if( empty( $_POST['nonce'] ) ) { wp_die( 'Error.' ); }
		if( ! wp_verify_nonce( $_POST['nonce'] ) ) { wp_die( 'ErrorError.' ); }

		$query = 'SELECT * FROM ' . $this->db_handler->prefix . self::$table_name;
		$result = $this->db_handler->get_results( $query, ARRAY_A );

		if( ! empty( $result ) ) {

			$text = 'record_date,file_info,additional_info' . PHP_EOL;

			foreach( $result as $item ) {
				$text .= $item['record_date'].',';
				$text .= $item['record_info'].',';
				$text .= $item['additional_info'];
				$text .=  PHP_EOL;
			}

			header('Content-Type: text/csv');
			wp_send_json_success( $text );

		} else {

			wp_send_json_error( 'No logs.' );

		}

		wp_die();

	}

}