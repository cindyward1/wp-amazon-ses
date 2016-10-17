<?php
/*
Plugin Name: WP Amazon SES
Description: Send mail using Amazon SES
Author: Michael Lippold
Version: 0.0.1
*/

$GLOBALS['aws_meta']['amazon-ses']['version'] = '0.0.1';
$GLOBALS['aws_meta']['amazon-web-services']['supported_addon_versions']['amazon-ses'] = '0.0.1';

$aws_plugin_version_required = '1.0';

require dirname( __FILE__ ) . '/classes/wp-aws-compatibility-check.php';
global $amazon_ses_compat_check;
$amazon_ses_compat_check = new WP_AWS_Compatibility_Check(
	'WP Amazon SES',
	'amazon-ses',
	__FILE__,
	'Amazon Web Services',
	'amazon-web-services',
	$aws_plugin_version_required
);


require_once dirname( __FILE__ ) . '/include/functions.php';

function amazon_ses_init( $aws ) {
	global $amazon_ses_compat_check;
	if ( ! $amazon_ses_compat_check->is_compatible() ) {
		return;
	}

	global $amazon_ses;
	require_once dirname( __FILE__ ) . '/classes/amazon-ses.php';
	$amazon_ses = new Amazon_SES( __FILE__, $aws );
}
add_action( 'aws_init', 'amazon_ses_init' );
