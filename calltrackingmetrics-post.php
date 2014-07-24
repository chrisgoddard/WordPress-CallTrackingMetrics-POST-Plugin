<?php

/*
 * Plugin Name: Call Tracking Metrics POST Plugin
 * Plugin URI: http://www.chrisgoddard.me
 * Description: Recieve webhook call (to domain.com/calltrackingmetrics/json) from calltrackingmetrics.com and post call info to another URL
 * Version: 0.1
 * Author: Chris Goddard
 * Author URI: http://www.chrisgoddard.me
 *
 */


class CallLogCrm
{

	const POST_URL = ''; // use http://requestb.in for testing

	private $api;

	function __construct()
	{

		include trailingslashit(plugin_dir_path(__FILE__)) . 'inc/wp-external-api.php';

		$this->api = CallLogCRM\WpExternalApi::get('calltrackingmetrics');

		$this->api->set_logic(array($this, 'process_call'));

	}


	function process_call($input)
	{

		$call = json_decode($input['body']);

		$this->send_crm_request($call);

		if (is_object($call)) {
			return array('status'=>'true', 'id'=>$call->id);
		} else {
			return array('status'=>'false');
		}


	}


	function send_crm_request($call)
	{

		$request = new WP_Http();

		$custom_vars = json_decode($call->cvars);

		$custom_vars = $custom_vars[0];

		$post_body = array( 'Phone' => $call->caller_number,
			'LeadSourceCategory' => $call->web_source ,
			'Keyword' => $call->keyword,
			'Campaign' => $call->campaign,
			'Medium' => $call->adgroup_id,
			'SourceURL' => $call->location,
			'GAID' => $custom_vars->gaid,
			'IP' => $call->visitor_ip,
			'LandingPageURL' => $call->location,
			'Referrer' => $call->last_location,
		);

		$response = $request->request( self::POST_URL , array( 'method' => 'POST', 'body' => $post_body, 'sslverify' => false ) );

		if ( !is_wp_error ($response) ) {
			error_log(print_r($response, true));
		}

	}


}


global $call_log_crm;

$call_log_crm = new CallLogCrm();
