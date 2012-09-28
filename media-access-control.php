<?php
/**
 * Plugin Name: Media Access Control
 * Plugin URI: http://www.blazersix.com/
 * Description: Implement custom access rules for uploaded files.
 * Version: 0.1
 * Author: Blazer Six, Inc.
 * Author URI: http://www.blazersix.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 59
 * Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Media Access Control
 * @author Brady Vercher <brady@blazersix.com>
 * @copyright Copyright (c) 2012, Blazer Six, Inc.
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since 0.1.0
 *
 * @todo Allow for additional paths to be registered.
 */


/**
 * Load the plugin.
 */
add_action( 'plugins_loaded', array( 'Media_Access_Control', 'load' ) );

/**
 * Main plugin class. Routes a specified list of file extensions that exist in
 * the upload directory through WordPress and provides a filter for hooking
 * into and implementing custom business rules for granting access.
 *
 * @since 0.1.0
 */
class Media_Access_Control {
	/**
	 * Load the plugin and language file.
	 */
	public static function load() {
		load_plugin_textdomain( 'media-access-control', false, 'media-access-control/languages' );
		
		add_action( 'init', array( __CLASS__, 'init' ) );
	}
	
	/**
	 * Initialize the plugin.
	 *
	 * @since 0.1.0
	 */
	public static function init() {
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ) );
		
		add_action( 'generate_rewrite_rules', array( __CLASS__, 'generate_rewrite_rules' ) );
		
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'update_option_media_access_control', array( __CLASS__, 'media_access_option_update' ), 10, 2 );
	}
	
	/**
	 * Add the custom query variable to the array of allowed keys.
	 *
	 * @since 0.1.0
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'media_access_control_file';
		
		return $vars;
	}
	
	/**
	 * Filter access to whitelisted files.
	 *
	 * Hook into the filter to implement custom business rules.
	 *
	 * It should be noted that this routes all whitelisted extensions in the
	 * uploads directory through WordPress instead of serving them directly,
	 * so there could potentially be performance implications.
	 *
	 * The current search for an attachment is the database is limited, so an
	 * attachment ID won't always be available in the filter. A more
	 * exhaustive search can be implemented by plugins.
	 *
	 * It's not recommended to whitelist images or rely on this plugin to
	 * protect them since WordPress generates various sizes.
	 *
	 * @since 0.1.0
	 *
	 * @param object $query WP_Query object, passed by reference.
	 */
	public static function parse_request( $query ) {
		global $wpdb;
		
		if ( isset( $query->query_vars['media_access_control_file'] ) ) {
			$query_var = $query->query_vars['media_access_control_file'];
			
			$wp_upload_dir = wp_upload_dir();
			$file_path = path_join( $wp_upload_dir['basedir'], $query_var );
			$file_url = trailingslashit( $wp_upload_dir['baseurl'] ) . $query_var;
			
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file' AND meta_value=%s", $query_var ) );
			$attachment_id = ( $attachment_id ) ? $attachment_id : 0;
			
			// Either return the path to the file to download, false, or redirect within the hook
			// Could also send the file directly to send different headers
			$file_path = apply_filters( 'media_access_control_allow_file_access', $file_path, $query_var, $attachment_id );
			if ( $file_path && file_exists( $file_path ) ) {
				self::send_file( $file_path );
			}
			
			// Return a 401 status code if the file actually exists, otherwise let WordPress serve a 404
			if ( file_exists( $file_path ) ) {
				wp_die( __( 'You do not have access to the requested file.', 'media-access-control' ), 'Unauthorized', array( 'response' => 401 ) );
			}
		}
	}
	
	/**
	 * Send a file to the browser and force download.
	 *
	 * @todo Look into using the 'X-Sendfile' header for servers with support.
	 *
	 * @since 0.1.0
	 * @uses nocache_headers()
	 *
	 * @param string $file Absolute path to a file.
	 */
	public static function send_file( $file ) {
		nocache_headers();
		
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '";');
		header( 'Content-Transfer-Encoding: binary');
		header( 'Content-Length: ' . filesize( $file ) );
		
		readfile( $file );
		wp_die();
	}
	
	/**
	 * Add rewrite rules for whitelisted file extensions whenever the rewrite
	 * rules are generated.
	 *
	 * @since 0.1.0
	 *
	 * @param object $wp_rewrite Rewrite object, passed by reference.
	 */
	public static function generate_rewrite_rules( $wp_rewrite ) {
		$options = get_option( 'media_access_control' );
		$file_extensions = ( isset( $options['extensions'] ) ) ? $options['extensions'] : '';
		$file_extensions = str_replace( ' ', '|', preg_quote( $file_extensions, '/' ) );
		
		if ( ! empty( $file_extensions ) ) {
			$upload_dir = wp_upload_dir();
			$relative_upload_path = str_replace( site_url( '/' ), '', trailingslashit( $upload_dir['baseurl'] ) );
			
			$wp_rewrite->add_external_rule( $relative_upload_path . '(.*\.(' . $file_extensions . '))$', 'index.php?media_access_control_file=$1' );
		}
	}
	
	/**
	 * Register media access settings.
	 *
	 * Registers a setting in the Settings->Meida screen to allow users to
	 * define file extensions that should be filtered.
	 *
	 * @since 0.1.0
	 */
	public static function register_settings() {
		register_setting( 'media', 'media_access_control', array( __CLASS__, 'sanitize_settings' ) );
		
		add_settings_field(
			'extensions',
			'<label for="media-access-control-extensions">' . __( 'Protected Extensions', 'media-access-control' ) . '</label>',
			array( __CLASS__, 'file_extensions_field' ),
			'media',
			'uploads'
		);
	}
	
	/**
	 * Sanitize media access settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array $value Array of settings saved from the settings screen.
	 */
	public static function sanitize_settings( $value ) {
		$extensions = ( isset( $value['extensions'] ) ) ? self::sanitize_file_extensions_list( $value['extensions'] ) : array();
		
		$value = array();
		if ( ! empty( $extensions ) ) {
			
			$value['extensions'] = join( ' ', $extensions );
		}
		
		return $value;
	}
	
	/**
	 * Sanitize a list of file extensions.
	 *
	 * @since 0.1.0
	 *
	 * @param array|string $extensions An array or space-separated string of file extensions.
	 */
	public static function sanitize_file_extensions_list( $extensions ) {
		if ( ! is_array( $extensions ) ) {
			$extensions = explode( ' ', $extensions );
		}
		
		$extensions = array_map( 'strtolower', array_map( 'trim', $extensions ) );
		$extensions = preg_replace( '/[^a-z0-9]/', '', $extensions );
		$extensions = array_filter( $extensions );
		
		return $extensions;
	}
	
	/**
	 * Flush rewrite rules when the file extension list setting is updated.
	 *
	 * @since 0.1.0
	 *
	 * @param array $oldvalue Old option value.
	 * @param array $newvalue New option value.
	 */
	public static function media_access_option_update( $oldvalue, $newvalue ) {
		flush_rewrite_rules();
	}
	
	/**
	 * Display the file extension settings field.
	 *
	 * @since 0.1.0
	 */
	public static function file_extensions_field() {
		$options = get_option( 'media_access_control' );
		$extensions = ( isset( $options['extensions'] ) ) ? $options['extensions'] : '';
		?>
		<input type="text" name="media_access_control[extensions]" id="media-access-control-extensions" value="<?php echo esc_attr( $extensions ); ?>" class="regular-text">
		<br><span class="description"><?php _e( 'Space-separated list of file extensions that should be filtered for access.', 'media-access-control' ); ?></span>
		<?php
	}
}
?>