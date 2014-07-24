<?php

namespace CallLogCRM;

class WpExternalApi
{

	/*
	 *	Constants
	 */

	const VERSION = '0.1';
	const DEFAULT_XML_PARENT = 'response';
	const DEFAULT_REQUEST = 'xml';
	const DEFAULT_XML_SYNTAX = 'ucfirst';

	/*
	 *	Variables
	 */

	private $slug;
	private $name;
	private $default_request;
	private $xml_syntax;
	private $xml_parent_node;
	private $query_vars;
	private $request;


	/*
	 *	Static container for API instances
	 */

	static $instances;

	/*
	 *	Static function to create or retrieve instances
	 */
	static function get($slug)
	{

		if (isset(self::$instances[$slug])) {
			return self::$instances[$slug];
		} else {
			self::$instances[$slug] = new static($slug);
			return self::$instances[$slug];
		}

	}


	/*
	 *	Constructor: $slug required for ID, other properties can be set later
	 */
	function __construct($slug, $logic = null, $url_path = null, $options = array() )
	{
		// Set required variables
		$this->name = $slug;
		$this->logic = $logic;

		// Set URL Path
		if ($url_path) {
			$this->url_path = $url_path;
		} else {
			$this->url_path = str_replace('_', '-', $this->name);
		}

		// Set Defaults
		$this->xml_parent_node = self::DEFAULT_XML_PARENT;
		$this->default_request = self::DEFAULT_REQUEST;
		$this->xml_syntax = self::DEFAULT_XML_SYNTAX;

		// Set options
		if (0 < count($options) ) $this->set_options($options);

		// Actions
		add_action('init', array($this, 'rewrite_rules'));
		add_action('parse_request', array($this, 'parse_request'), 99);

		// Filters
		add_filter('query_vars', array($this, 'query_vars'));

	}


	/*
	 *	Set logic callback
	 */
	function set_logic($callback)
	{
		if ($this->logic) {
			throw new \Exception('Logic callback already defined');
		} else {
			$this->logic = $callback;
		}
	}


	/*
	 *	Set options
	 */
	function set_options($options = array())
	{
		// Override with options if set
		foreach ($options as $option_name => $option_value) {
			if ( $option_value ) {
				$this->{$option_name} = $option_value;
			}
		}
	}


	/*
	 *	Add API endpoint rewrite rules
	 */

	function rewrite_rules()
	{
		add_rewrite_rule( '^'.$this->url_path.'/?([^/]*)?/?([^/]*)/?' , 'index.php?'.$this->name.'=$matches[1]&'.$this->name.'_query=$matches[2]', 'top' );
	}


	/*
	 *	Parse request
	 */

	function parse_request(&$wp)
	{

		if (array_key_exists($this->name, $wp->query_vars)) {

			$this->query_vars = $wp->query_vars;

			$this->request['get'] = apply_filters($this->name.'_get', $_GET, $this->query_vars);
			$this->request['post'] = apply_filters($this->name.'_post', $_POST, $this->query_vars);
			$this->request['cookie'] = apply_filters($this->name.'_cookie', $_COOKIE, $this->query_vars);
			$this->request['body'] = http_get_request_body();

			logger($this->request);

			switch ($wp->query_vars[$this->name]) {
			case 'xml':

				$this->xml_request();
				break;
			case 'json':

				$this->json_request();
				break;
			default:

				$this->{$this->default_request.'_request'}();

				break;
			}

			exit;

		}
	}


	/*
	 *	Add query vars
	 */

	function query_vars($query_vars)
	{
		$query_vars[] = $this->name;
		$query_vars[] = $this->name.'_query';
		return $query_vars;
	}


	/*
	 *	Get response from API method
	 */

	function api_method()
	{
		if ( is_callable($this->logic) ) return call_user_func($this->logic, $this->request);
	}


	/*
	 *	XML Request Response
	 */

	function xml_request()
	{
		$response = apply_filters($this->name.'_xml_response', $this->api_method());

		$xmlcontainer = $this->process_xml_syntax($this->xml_parent_node);
		$responseXML = new \SimpleXMLElement('<'.$xmlcontainer.'></'.$xmlcontainer.'>');

		foreach ($response as $response_name => $response_content ) {

			$response_value = apply_filters($this->name.'_'.$response_name, $response_content['value'], $response);

			$responseMessage[$response_name] = $responseXML->addChild($this->process_xml_syntax($response_name), $response_content['value']);

			if (isset($response_content['attributes'])) {
				foreach ($response_content['attributes'] as $attribute_name => $attrubute_value) {
					$responseMessage[$response_name]->addAttribute($attribute_name, $attrubute_value);

				}

			}

		}
		// Response
		header('Content-type: text/xml');
		echo $responseXML->asXML();

	}


	/*
	 *	XML Syntax Processor
	 */

	function process_xml_syntax($variable)
	{
		switch ($this->xml_syntax) {
		case 'ucfirst':
			return ucfirst($variable);
			break;
		case 'uppercase':
			return strtoupper($variable);
			break;
		case 'lowercase':
			return strtolower($variable);
			break;
		case 'camelcase':
			return ucwords($variable);
			break;
		case 'callback':
			return apply_filters($this->name.'_xml_syntax', $variable);
			break;
		}
	}


	/*
	 *	JSON Request Response
	 */

	function json_request()
	{
		$response = apply_filters($this->name.'_json_response', $this->api_method());

		// Response
		header('Content-type: application/json');
		echo json_encode($response);
	}


}