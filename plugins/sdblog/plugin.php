<?php
/*
Plugin Name: SimpleDB Logging
Plugin URI: http://virgingroupdigital.wordpress.com
Description: Pushes log updates to Amazon SimpleDB
Version: 0.1
Author: Ian Barber <ian.barber@gmail.com>
Author URI: http://phpir.com/
*/
include_once 'AWSSDKforPHP/sdk.class.php';

if( !class_exists( 'AmazonSDB' ) ) {
   yourls_die( 'This plugin requires with AWS SDK for PHP: http://aws.amazon.com/sdkforphp/' );
}

if(!defined('AWS_KEY') || !defined('AWS_SECRET_KEY')) {
    yourls_die( 'This plugin requires an amazon key. Define AWS_KEY and AWS_SECRET_KEY in your config.php' );
}

/* Set this in config.php to use a specific region */
if(!defined('SDB_REGION')) { 
    define("SDB_REGION", false);
} 

/* Can override the database name in SDB */
if(!defined('SDB_DOMAIN')) {
    define('SDB_DOMAIN', 'yourlslog');
}

yourls_add_filter( 'shunt_update_clicks', 'sdblog_shunt_update_clicks' );
yourls_add_filter( 'shunt_log_redirect', 'sdblog_shunt_log_redirect' );
yourls_add_filter( 'activated_plugin', 'sdblog_plugin_activate' );

/**
 * Activate the plugin by creating the SimpleDB queue. 
 *
 * @return void     
 **/
function sdblog_plugin_activate()
{
    $sdb = sdblog_get_database();

    $response = $sdb->create_domain(SDB_DOMAIN);
    if(!$response->isOK()) {
        yourls_die( 'Could not create domain for SimpleDB logging' );
    }
}

/**
 * Skip click updating - that will be processed out of the queue
 *
 * @return bool
 **/
function sdblog_shunt_update_clicks($false, $keyword)
{
    return true;
}


/**
 * Log clicks to SimpleDB
 *
 * @return bool
 **/
function sdblog_shunt_log_redirect($false, $keyword)
{
    $sdb = sdblog_get_database();
    $values = array(
		'keyword' => yourls_sanitize_string( $keyword ),
		'referer' => ( isset( $_SERVER['HTTP_REFERER'] ) ? yourls_sanitize_url( $_SERVER['HTTP_REFERER'] ) : 'direct' ),
		'ua' => yourls_get_user_agent(),
		'ip' => yourls_get_IP(),
		'time' => time()
	);
	
	$response = $sdb->put_attributes(SDB_DOMAIN, uniqid(), $values);

	return true;
}

/**
 * Return the SimpleDB object
 *
 * @return AmazonSDB    
 **/
function sdblog_get_database()
{
    $sdb = new AmazonSDB();
    if(defined('SDB_REGION') && SDB_REGION) {
        $sdb->set_region(SDB_REGION);
    }
    return $sdb;
}