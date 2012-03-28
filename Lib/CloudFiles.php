<?php
/**
* CloudFiles static library
*
*/
App::import('Vendor', 'CloudFiles.php-cloudfiles/cloudfiles');
class CloudFiles extends Object {
	
	public static $configs = array();
	public static $Authentication = null;
	public static $Connection = null;
	private static $server_to_auth_map = array(
		'US' => 'https://auth.api.rackspacecloud.com',
		'UK' => 'https://lon.auth.api.rackspacecloud.com'
	);
	
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
	* @return mixed false if failure, or string public_uri if success
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
		
		self::connect(); 
		$Container = self::$Connection->get_container($container);
		$filename = basename($file_path);

		// upload file to Rackspace
		if($filename && $Container){
			$object = $Container->create_object($filename);
			$object->content_type = mime_content_type($file_path);
			$object->load_from_filename($file_path);
	
			return $object->public_uri();
		}
		return false;
	}
	
	/**
	* Connect to the CloudFiles Service
	*/
	protected static function connect(){
		if(self::$Connection == null && $server = self::$server_to_auth_map[self::getConfig('server')]){
			self::$Authentication = new CF_Authentication(self::getConfig('username'), self::getConfig('api_key'), null, $server);
			self::$Authentication->ssl_use_cabundle();
			self::$Authentication->authenticate();
			self::$Connection = new CF_Connection(self::$Authentication);
		}
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
