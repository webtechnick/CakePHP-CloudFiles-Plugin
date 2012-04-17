<?php
/**
* CloudFilesException used to throw errors.
*/
class CloudFilesException extends Exception {}
/**
* CloudFiles static library
* @author Nick Baker
* @since 1.1.0
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
	* @example CloudFiles::getConfig('username');
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
	* @param string full path to file on local machine (required)
	* @param string container name to upload file to. (required)
	* @param string mime-type name to upload file to. (optional)
	* @return mixed false if failure, string public_uri if public, or true if success and not public
	* @example CloudFiles::upload('/home/nwb/image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws IOException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function upload($file_path = null, $container = null, $mimetype = null){
		if(empty($file_path) || empty($container)){
			self::error("File path and container required.");
			return false;
		}
		if(!file_exists($file_path)){
			self::error("File does not exist.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		
		$Container = self::$Connection->get_container($container);
		$filename = basename($file_path);

		// upload file to Rackspace
		if($filename && is_object($Container)){
			$Object = $Container->create_object($filename);
			if(is_object($Object)){
				if($mimetype){
					$Object->content_type = $mimetype;
				}
				$Object->load_from_filename($file_path);
				if($Container->is_public()){
					return $Object->public_uri();
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	* Download a file from a specific container to a local file
	* @param string filename on rackspace (required)
	* @param string container on rackspace (required)
	* @param string localpath to save file to (required)
	* @param boolean overwrite localfile if already exists (default true)
	* @return boolean success
	* @example CloudFiles::download('image.jpg', 'container_name', '/home/nwb/image.jpg');
	* @throws CloudFilesException
	* @throws IOException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function download($filename = null, $container = null, $localpath = null, $overwrite = true){
		if(empty($localpath) || empty($filename) || empty($container)){
			self::error("File path and container required.");
			return false;
		}
		if(file_exists($localpath) && !$overwrite){
			self::error("File exists already exists");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		
		$Container = self::$Connection->get_container($container);
		if(is_object($Container)){
			$Object = $Container->get_object($filename);
			if(is_object($Object)){
				return $Object->save_to_filename($localpath);
			}
		}
		return false;
	}
	
	/**
	* Delete a file from a container
	* @param string filename to delete (required)
	* @param string container to delete filename from (required)
	* @return boolean success
	* @example CloudFiles::delete('image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function delete($filename = null, $container = null){
		if(empty($filename) || empty($container)){
			self::error("Filename and container required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$Container = self::$Connection->get_container($container);
		return $Container->delete_object($filename, $container);
	}
	
	/**
	* Return a list of what is in a container
	* @param string container (required)
	* @param array options (optional)
	*  - path   (string) : only return results under path
	*  - prefix (string) : only return names starting with prefix
	*  - marker (int)    : starting with marker
	*  - limit  (int)    : only return limit names (default everything)
	* @return mixed false on failure or array of string names
	* @example CloudFiles::ls('container_name');
	* @example CloudFiles::ls('container_name', array('path' => 'animals/dogs', 'limit' => 10));
	* @throws CloudFilesException
	* @throws InvalidResponseException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	*/
	public static function ls($container = null, $options = array()){
		if(empty($container)){
			self::error("container name is required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$options = array_merge(array(
			'path' => null,
			'marker' => null,
			'limit' => 0,
			'prefix' => null,
		), $options);
		
		$Container = self::$Connection->get_container($container);
		return $Container->list_objects($options['limit'], $options['marker'], $options['prefix'], $options['path']);
	}
	
	/**
	* List all containers
	* @param array options array
	*  - marker      (int)  : starting with marker
	*  - limit       (int)  : only return limit containers (default everything)
	*  - only_public (bool) : only show public containers
	* @return array of container names
	* @throws CloudFilesException
	* @throws InvalidResponseException
	* @throws AuthenticationException
	*/
	public static function listContainers($options = array()){
		$options = array_merge(array(
			'limit' => 0,
			'marker' => null,
			'only_public' => false
		), $options);
		if(!self::connect()){
			return false;
		}
		if($options['only_public']){
			return self::$Connection->list_public_containers();
		}
		return self::$Connection->list_containers($options['limit'], $options['marker']);
	}
	
	/**
	* Create a container
	* @param string $container_name container name (required)
	* @param boolean make the new container public (default true)
	* @return CF_Container
	* @throws SyntaxException invalid name
	* @throws InvalidResponseException unexpected response
	*/
	public static function createContainer($container = null, $public = true){
		if(empty($container)){
			self::error("container name is required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$retval = self::$Connection->create_container($container);
		if(is_object($retval) && $public){
			$retval->make_public();
		}
		return $retval;
	}
	
	/**
	* Delete a container
	* @param string $container_name container name (required)
	* @return boolean <kbd>True</kbd> if successfully deleted
	* @throws CloudFilesException
	* @throws AuthenticationException
	* @throws SyntaxException missing proper argument
	* @throws InvalidResponseException invalid response
	* @throws NonEmptyContainerException container not empty
	* @throws NoSuchContainerException remote container does not exist
	*/
	public static function deleteContainer($container = null){
		if(empty($container)){
			self::error("container name is required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		return self::$Connection->delete_container($container);
	}
	
	/**
	* Get URL of an object
	* @param string filename (required)
	* @param string container (required)
	* @param boolean streaming if true return streaming url instead of public URL.
	* @return string public uri of object requested
	* @example CloudFiles::url('image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function url($filename = null, $container = null, $streaming = false){
		if(empty($filename) || empty($container)){
			self::error("Filename and container required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$Container = self::$Connection->get_container($container);
		if(is_object($Container)){
			$Object = $Container->get_object($filename);
			if(is_object($Object)){
				return $streaming ? $Object->public_streaming_uri() : $Object->public_uri();
			}
		}
		return null;
	}
	
	/**
	* Get Stream URL of an object
	* @param string filename (required)
	* @param string container (required)
	* @return string public stream url of object requested
	* @example CloudFiles::stream('image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function stream($filename = null, $container = null){
		return self::url($filename, $container, $streaming = true);
	}
	
	/**
	* Connect to the CloudFiles Service
	* @return boolean success
	* @throws CloudFilesException
	* @throws AuthenticationException
	* @throws InvalidResponseException
	*/
	protected static function connect(){
		if(self::$Connection == null && $server = self::$server_to_auth_map[self::getConfig('server')]){
			self::$Authentication = new CF_Authentication(self::getConfig('username'), self::getConfig('api_key'), null, $server);
			self::$Authentication->ssl_use_cabundle();
			self::$Authentication->authenticate();
			self::$Connection = new CF_Connection(self::$Authentication);
		}
		$retval = !!(self::$Connection);
		if(!$retval){
			self::error("Unable to connect to rackspace, check your settings.");
		}
		return $retval;
	}
	
	/**
	* Append a message to the static class error stream
	* @param string message
	* @throws CloudFilesException
	*/
	private static function error($message){
		self::$errors[] = $message;
		throw new CloudFilesException($message);
	}
}
?>
