<?php
/**
* CloudFilesException used to throw errors.
*/
class CloudFilesException extends Exception {}
/**
* CloudFiles static library
* @author Nick Baker
* @since 1.4.0
* @link http://www.webtechnick.com
*/
App::import('Vendor', 'CloudFiles.php-opencloud/lib/php-opencloud');
class CloudFiles extends Object {

	/**
	* Configuration loaded from app/Config/cloud_files.php
	* @var array
	* @access public
	*/
	public static $configs = array();

	/**
	* Connection object
	* @var \OpenCloud\RackSpace
	* @access public
	*/
	public static $Connection = null;

	/**
	* ObjectStore Object
	* @var ObjectStore
	* @access public
	*/
	public static $ObjectStore = null;

	/**
	* Shorthand server to full AuthURL
	* @var array
	* @access private
	*/
	private static $server_to_auth_map = array(
		'US' => RACKSPACE_US,
		'UK' => RACKSPACE_UK,
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
	* @param array of options
	*   - filename: name to upload file to. (optional)
	*   - mimetype: custom mimetype of file.  (optional)
	*   - overwrite: if false, will check if the file exists first (optional) (default true).
	* @return mixed false if failure, string public_uri if public, or true if success and not public
	* @example CloudFiles::upload('/home/nwb/image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws IOException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function upload($file_path = null, $container = null, $options = array()){
		if(is_string($options)){
			$mimetype = $options;
			$options = array();
			$options['mimetype'] = $mimetype;
		}
		$options = array_merge(array(
			'mimetype' => null,
			'filename' => null,
			'overwrite' => true,
		),$options);

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
		if(!$options['filename']){
			$options['filename'] = basename($file_path);
		}
		//Check if file already exists unless we're overwriting (default true)
		if(!$options['overwrite'] && self::exists($container, $options['filename'])){
			return true;
		}
		// upload file to Rackspace
		$Container = self::$ObjectStore->Container($container);
		if($options['filename'] && is_object($Container)){
			$Object = $Container->DataObject();
			if(is_object($Object)){
				if($options['mimetype']){
					$Object->content_type = $options['mimetype'];
				}
				$Object->Create(
					array('name' => $options['filename']),
					$file_path
				);

				if($retval = $Object->PublicURL()){
					return $retval;
				}
				return true;
			}
		}
		return false;
	}

	/**
	* Check if a file exists in a container
	* @param string container
	* @param string filename
	* @return boolean if the file exists.
	* @example CloudFiles::exists('container', 'image.jpg');
	* @throws CloudFilesException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function exists($container = null, $filename = null){
		if(empty($filename) || empty($container)){
			self::error("Filename and container required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$result = self::ls($container, array(
			'prefix' => $filename,
			'limit' => 1,
		));
		return !empty($result);
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

		$Container = self::$ObjectStore->Container($container);
		if(is_object($Container)){
			$Object = $Container->DataObject($filename);
			if(is_object($Object)){
				return $Object->SaveToFilename($localpath);
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
		$Container = self::$ObjectStore->Container($container);
		$Object = $Container->DataObject($filename);
		if(is_object($Object)){
			return !!($Object->Delete());
		}
		return false;
	}

	/**
	* Return a list of what is in a container
	* @param string container (required)
	* @param array options (optional)
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
			'marker' => '',
			'prefix' => '',
		), $options);

		$Container = self::$ObjectStore->Container($container);
		$retval = array();
		if(is_object($Container)){
			$objects = $Container->ObjectList($options);
			while($object = $objects->Next()){
				$retval[$object->Name()] = array(
					'name' => $object->Name(),
					'bytes' => $object->bytes,
					'content_type' => $object->content_type
				);
			}
		}
		return $retval;
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

		$containers = self::$ObjectStore->ContainerList();
		$retval = array();
		while($container = $containers->Next()){
			$retval[$container->name] = array(
				'name' => $container->name,
				'count' => $container->count,
				'bytes' => $container->bytes,
			);
		}
		return $retval;
	}

	/**
	* Create a container
	* @param string $container_name container name (required)
	* @return Container
	* @throws SyntaxException invalid name
	* @throws InvalidResponseException unexpected response
	*/
	public static function createContainer($container = null){
		if(empty($container)){
			self::error("container name is required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$Container = self::$ObjectStore->Container();
		if(is_object($Container)){
			$Container->Create(array('name' => $container));
		}
		return $Container;
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
		$Container = self::$ObjectStore->Container($container);
		if(is_object($Container)){
			return $Container->Delete();
		}
		return false;
	}

	/**
	* Get URL of an object
	* @param string filename (required)
	* @param string container (required)
	* @return string public uri of object requested
	* @example CloudFiles::url('image.jpg', 'container_name');
	* @throws CloudFilesException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function url($filename = null, $container = null){
		if(empty($filename) || empty($container)){
			self::error("Filename and container required.");
			return false;
		}
		if(!self::connect()){
			return false;
		}
		$Container = self::$ObjectStore->Container($container);
		if(is_object($Container)){
			$Object = $Container->DataObject($filename);
			if(is_object($Object)){
				return $Object->PublicURL();
			}
		}
		return null;
	}

	/**
	* Get Stream URL of an object
	* @param string filename (required)
	* @param string container (required)
	* @return string public stream url of object requested
	* @throws CloudFilesException
	* @throws SyntaxException
	* @throws NoSuchContainerException thrown if no remote Container
	* @throws InvalidResponseException unexpected response
	*/
	public static function stream($filename = null, $container = null){
		return self::url($filename, $container);
	}

	/**
	* Connect to the CloudFiles Service
	* @return boolean success
	* @throws CloudFilesException
	* @throws AuthenticationException
	* @throws InvalidResponseException unexpected response 
	*/
	public static function connect(){
		if(self::$Connection == null && $server = self::$server_to_auth_map[self::getConfig('server')]){
			self::$Connection = new \OpenCloud\Rackspace($server, array(
				'username' => self::getConfig('username'),
				'apiKey' => self::getConfig('api_key')
			));
			self::$ObjectStore = self::$Connection->ObjectStore('cloudFiles', self::getConfig('region'), self::getConfig('url_type'));
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
	* @throws CloudFilesException exception
	*/
	private static function error($message){
		self::$errors[] = $message;
		throw new CloudFilesException($message);
	}
}
