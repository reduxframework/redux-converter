<?php
/**
 * Redux Framework Converter.
 *
 * Try out and convert from a number of options panel to redux. It creates a panel that 
 * you can see everything Redux has to offer. Also provides you a PHP class to both
 * instatiate a panel and convert data.
 *
 * @package   Redux_Converter
 * @author    Dovy Paukstys <dovy@reduxframework.com>
 * @license   GPL-3.0+
 * @link      https://github.com/ReduxFramework/redux-converter
 * @copyright 2013 SimpleRain, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       Redux Converter
 * Plugin URI:        https://github.com/ReduxFramework/redux-converter
 * Description:       Try Redux Framework from the convenience of your own framework. Also export config files for your Redux panel and migrate data.
 * Version:           1.1.3
 * Author:            Dovy Paukstys
 * Author URI:        http://github.com/dovy/
 * Text Domain:       redux-converter
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/ReduxFramework/redux-converter
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/class-tgm-plugin-activation.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class.redux-converter.php' );
	add_action( 'plugins_loaded', array( 'Redux_Converter', 'get_instance' ) );

}
