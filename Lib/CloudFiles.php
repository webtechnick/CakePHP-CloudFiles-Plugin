<?php
/**
* CloudFiles static library
* @author Nick Baker
* @since 0.0.2
* @link http://www.webtechnick.com
*/
App::import('Vendor', 'CloudFiles.php-cloudfiles/cloudfiles');
class CloudFiles extends Object {
	
	/**
	* Configuration loaded from app/Config/cloud_files.php
	* @var array
	* @access public
	*/
	public static $configs = array();
	
	/**
	* Authenticatoin object
	* @var CF_Authentication
	* @access public
	*/
	public static $Authentication = null;
	
	/**
	* Connection object
	* @var CF_Connect
	* @access public
	*/
	public static $Connection = null;
	
	/**
	* Shorthand server to full AuthURL
	* @var array
	* @access private
	*/
	private static $server_to_auth_map = array(
		'US' => 'https://auth.api.rackspacecloud.com',
		'UK' => 'https://lon.auth.api.rackspacecloud.com'
	);
	
	/**
	* Errors
	* @var array
	* @access public
	*/
	public static $errors = array();
	
	
	/**
	* Getting a configuration option.
	* @param key to search for
	* @return mixed result of configuration key.
	* @access public
	*/
	public static function getConfig($key = null){
		if(empty(self::$configs)){
			Configure::load('cloud_files');
			self::$configs = Configure::read('CloudFiles');
		}
		if(empty($key)){
			return self::$configs;
		}
		if(isset(self::$configs[$key])){
			return self::$configs[$key];
		}
		return null;
	}
	
	/**
	* static method to upload a file to a specific container
	* @param string full path to file on server
	* @param string container name to upload file to.
	* @return mixed false if failure, string public_uri if public, or true if success and not public
	*/
	public static function upload($file_path = null, $container = null){
		if(empty($file_path) || empty($container)){
			self::error("File path and container required.");
			return false;
		}
		if(!file_exists($file_path)){
			self::error("File does not exist.");
			return false;
		}
		if(!self::connect()){
			self::error("Unable to connect to rackspace, check your settings.");
			return false;
		}
		
		$Container = self::$Connection->get_container($container);
		$filename = basename($file_path);

		// upload file to Rackspace
		if($filename && $Container){
			$object = $Container->create_object($filename);
			$object->content_type = mime_content_type($file_path);
			$object->load_from_filename($file_path);
			if($Container->is_public()){
				return $object->public_uri();
			}
			return true;
		}
		return false;
	}
	
	/**
	* Connect to the CloudFiles Service
	* @return boolean success
	*/
	protected static function connect(){
		if(self::$Connection == null && $server = self::$server_to_auth_map[self::getConfig('server')]){
			self::$Authentication = new CF_Authentication(self::getConfig('username'), self::getConfig('api_key'), null, $server);
			self::$Authentication->ssl_use_cabundle();
			self::$Authentication->authenticate();
			self::$Connection = new CF_Connection(self::$Authentication);
		}
		return !!(self::$Connection);
	}
	
	/**
	* Append a message to the static class error stream
	* @param string message
	*/
	private static function error($message){
		self::$errors[] = $message;
	}
}
?>
