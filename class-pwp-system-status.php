<?php defined( 'ABSPATH' ) or die;

if ( ! class_exists( 'PWP_System_Status' ) ):
	/**
	 * System status
	 *
	 * @package    PWP_System_Status
	 * @author     Knitkode <dev@knitkode.com> (https://knitkode.com)
	 * @copyright  2017 Knitkode
	 * @license    GPL-2.0+
	 * @version    Release: 1.0.0
	 * @link       https://github.com/knitkode/wp-system-status
	 */
	class PWP_System_Status {

		/**
		 * Let to number
		 * Credits to @link https://github.com/reduxframework/redux-framework/blob/master/ReduxCore/inc/class.redux_helpers.php#L670
		 *
		 * @since  1.0.0
		 * @param  let $size
		 * @return number
		 */
		private static function let_to_num( $size ) {
			$l = substr( $size, - 1 );
			$ret = substr( $size, 0, - 1 );
			switch ( strtoupper( $l ) ) {
				case 'P':
					$ret *= 1024;
				case 'T':
					$ret *= 1024;
				case 'G':
					$ret *= 1024;
				case 'M':
					$ret *= 1024;
				case 'K':
					$ret *= 1024;
			}
			return $ret;
		}

		/**
		 * Is local host
		 * Credits to @link(https://github.com/reduxframework/redux-framework/blob/master/ReduxCore/inc/class.redux_helpers.php#L68, Redux)
		 *
		 * @since  1.0.0
		 * @return boolean
		 */
		private static function is_local_host() {
			return ( $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === 'localhost' ) ? 1 : 0;
		}

		/**
		 * Boolean to string
		 * Credits to @link(https://github.com/reduxframework/redux-framework/blob/master/ReduxCore/inc/class.redux_helpers.php#L359, Redux)
		 *
		 * @since  1.0.0
		 * @param  mixed $var A to be boolean value
		 * @return string Either 'true' or 'false'
		 */
		private static function bool_to_string( $var ) {
			if ( $var == false || $var == 'false' || $var == 0 || $var == '0' || $var == '' || empty( $var ) ) {
				return 'false';
			} else {
				return 'true';
			}
		}

		/**
		 * Get status
		 * Credits to @link(https://github.com/reduxframework/redux-framework/blob/master/ReduxCore/inc/class.redux_helpers.php#L367)
		 *
		 * @since  1.0.0
		 * @global  $wpdb | WordPress database object
		 * @param  boolean $output_as_json
		 * @param  boolean $remote_checks
		 * @return array
		 */
		public static function get_status( $output_as_json = false, $remote_checks = true ) {
			global $wpdb;
			$data = array();

			// WordPress
			$data['home_url'] = home_url();
			$data['site_url'] = site_url();
			$data['wp_content_url'] = WP_CONTENT_URL;
			$data['wp_ver'] = get_bloginfo( 'version' );
			$data['wp_multisite'] = is_multisite();
			$data['permalink_structure'] = get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default';
			$data['front_page_display'] = get_option( 'show_on_front' );
			if ( $data['front_page_display'] == 'page' ) {
				$front_page_id = get_option( 'page_on_front' );
				$blog_page_id  = get_option( 'page_for_posts' );
				$data['front_page'] = $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset';
				$data['posts_page'] = $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset';
			}
			$data['wp_mem_limit_raw'] = self::let_to_num( WP_MEMORY_LIMIT );
			$data['wp_mem_limit_size'] = size_format( $data['wp_mem_limit_raw'] );
			$data['db_table_prefix'] = 'Length: ' . strlen( $wpdb->prefix ) . ' - Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' );
			$data['wp_debug'] = 'false';
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$data['wp_debug'] = 'true';
			}
			$data['wp_lang'] = get_locale();

			// server
			$data['server_info'] = esc_html( $_SERVER['SERVER_SOFTWARE'] );
			$data['localhost'] = self::bool_to_string( self::is_local_host() );
			$data['php_ver'] = function_exists( 'phpversion' ) ? esc_html( phpversion() ) : 'phpversion() function does not exist.';
			$data['abspath'] = ABSPATH;
			if ( function_exists( 'ini_get' ) ) {
				$data['php_mem_limit'] = size_format( self::let_to_num( ini_get( 'memory_limit' ) ) );
				$data['php_post_max_size'] = size_format( self::let_to_num( ini_get( 'post_max_size' ) ) );
				$data['php_time_limit'] = ini_get( 'max_execution_time' );
				$data['php_max_input_var'] = ini_get( 'max_input_vars' );
				$data['php_display_errors'] = self::bool_to_string( ini_get( 'display_errors' ) );
			}
			$data['suhosin_installed'] = extension_loaded( 'suhosin' );
			$data['mysql_ver'] = $wpdb->db_version();
			$data['max_upload_size'] = size_format( wp_max_upload_size() );
			$data['def_tz_is_utc'] = 'true';
			if ( date_default_timezone_get() !== 'UTC' ) {
				$data['def_tz_is_utc'] = 'false';
			}
			$data['fsockopen_curl'] = 'false';
			if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
				$data['fsockopen_curl'] = 'true';
			}

			// remote
			if ( $remote_checks == true ) {
				$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
					'sslverify' => false,
					'timeout' => 60,
					// 'user-agent' => 'ReduxFramework/' . ReduxFramework::$_version,
					'body' => array(
						'cmd' => '_notify-validate'
					)
				) );
				if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
					$data['wp_remote_post'] = 'true';
					$data['wp_remote_post_error'] = '';
				} else {
					$data['wp_remote_post'] = 'false';
					$data['wp_remote_post_error'] = $response->get_error_message();
				}
			}

			// custom
			$custom_key = apply_filters( 'PWP_System_Status\custom_key', 'custom' );
			$custom_array = apply_filters( 'PWP_System_Status\custom_array', array() );
			$data[ $custom_key ] = $custom_array;
			// $data['redux_ver']      = esc_html( ReduxFramework::$_version );
			// $data['redux_data_dir'] = ReduxFramework::$_upload_dir;
			// Only is a file-write check
			// $f                         = 'fo' . 'pen';
			// $data['redux_data_writeable'] = self::bool_to_string( @$f( ReduxFramework::$_upload_dir . 'test-log.log', 'a' ) );

			// browser
			if ( ! class_exists( 'Browser' ) ) {
				require_once __DIR__ . '/Browser.php';
				// require_once __DIR__ . '/vendor/cbschuld/browser.php/lib/Browser.php'; // @@todo \\
			}
			$browser = new Browser();
			$data['browser']['agent'] = $browser->getUserAgent();
			$data['browser']['browser'] = $browser->getBrowser();
			$data['browser']['version'] = $browser->getVersion();
			$data['browser']['platform'] = $browser->getPlatform();
			$data['browser']['mobile'] = $browser->isMobile() ? 'true' : 'false';

			// theme
			$active_theme = wp_get_theme();
			$data['theme']['name'] = $active_theme->Name;
			$data['theme']['version'] = $active_theme->Version;
			$data['theme']['author_uri'] = $active_theme->{'Author URI'};
			$data['theme']['is_child'] = self::bool_to_string( is_child_theme() );
			if ( is_child_theme() ) {
				$parent_theme = wp_get_theme( $active_theme->Template );
				$data['theme']['parent_name'] = $parent_theme->Name;
				$data['theme']['parent_version'] = $parent_theme->Version;
				$data['theme']['parent_author_uri'] = $parent_theme->{'Author URI'};
			}

			// plugins
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
			$data['plugins'] = array();
			foreach ( $active_plugins as $plugin ) {
				$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$plugin_name = esc_html( $plugin_data['Name'] );
				$data['plugins'][ $plugin_name ] = $plugin_data;
			}

			return $data;
		}

		/**
		 * Print
		 *
		 * @since  1.0.0
		 * @param  string $output_type
		 * @return string
		 */
		public static function get_printable( $output_type = '', $pretty_print = false ) {
			$data = self::get_status();
			$begin = $pretty_print ? '<pre style="word-break:break-word">' : '';
			$end = $pretty_print ? '</pre>' : '';
			switch ( $output_type ) {
				case 'html':
					$html = '<table>' . self::array_to_html_table( $data ) . '</table>';
					return $html;
					break;
				case 'json':
					return $begin . wp_kses( wp_json_encode( $data ), array() ) . $end;
					break;
				default:
					return $begin . var_dump( $data ) . $end;
					break;
			}
		}

		/**
		 * Array to HTML table
		 *
		 * @since  1.0.0
		 * @param  array $data
		 * @param  string $ouput
		 * @return string
		 */
		private static function array_to_html_table( $data, $ouput = '' ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$ouput .= "<tr><th colspan='2' bgcolor='#e6e6e6'>$key</th></tr>";
					$ouput .= self::array_to_html_table( $value );
				} else {
					$key = (string) $key;
					$value = (string) $value;
					$ouput .= "<tr><th align='right'>$key</th><td align='left' style='padding-left:10px'>$value</td></tr>";
				}
			}
			return $ouput;
		}

		/**
		 * Retrieve the status remotely through ajax call
		 *
		 * @since  1.0.0
		 */
		public static function remote_get() {
			$output_as = $_POST['output'];

			if ( 'html' === $output_as ) {
				$output = self::get_printable( 'html' );
			} else {
				$output = self::get_printable( 'json' );
			}

			wp_send_json_success( $ouput );

			die();
		}
	}

	add_action( 'wp_ajax_PWP_System_Status\get', 'PWP_System_Status::remote_get' );

	// Instantiate
	// new PWP_System_Status;

endif;