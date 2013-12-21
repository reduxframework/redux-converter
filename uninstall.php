<?php
/**
 * Fired when the plugin is uninstalled.
 *
* @package   Redux_Converter
 * @author    Dovy Paukstys <dovy@reduxframework.com>
 * @license   GPL-3.0+
 * @link      https://github.com/ReduxFramework/redux-converter
 * @copyright 2013 SimpleRain, Inc.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// @TODO: Define uninstall functionality here