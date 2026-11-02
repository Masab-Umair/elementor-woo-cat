<?php
/**
 * Fires on plugin deletion (not deactivation) to remove any leftover
 * transient caches. The plugin stores no options and no custom tables,
 * so there is nothing else to clean up.
 *
 * @package Elementor_Woo_Catalog_Builder
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_ewcb\_%'
	 OR option_name LIKE '\_transient\_timeout\_ewcb\_%'"
);
