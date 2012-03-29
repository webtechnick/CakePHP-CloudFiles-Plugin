# Rackspace CloudFiles CakePHP Plugin
* Author: Nick Baker
* Version: 1.0.1
* License: MIT
* Website: <http://www.webtechnick.com>

This plugin is used to interface with the Rackspace CloudFiles service.  This plugin utilizes the php-cloudfiles provided by rackspace

## Requirements

* CakePHP 2.x, PHP 5.x
* Any requirements defined by <https://github.com/rackspace/php-cloudfiles>

## Changelog
* 1.0.1:  Added CloudFiles::download
* 1.0.0:  Initial Release -- More polish, added CloudFilesHelper, all basic REST actions on cloud files implemented.
* 0.0.2: 	Added CloudFiles Library -- utilizing the rackspace php-cloudfiles library and implemented upload function 
					CloudFiles::upload, CloudFiles::delete, CloudFiles::ls
* 0.0.1: 	Initial Commit -- Skeleton plugin

## Installation

After cloning the repository, you must run git submodule init and update to pull in the required vendor

	git clone git://github.com/webtechnick/CakePHP-CloudFiles-Plugin.git app/Plugin/CloudFiles
	cd app/Plugin/CloudFiles
	git submodule init
	git submodule update
	
Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('CloudFiles');`
	
Next you'll need to configure the plugin to work with your RackSpace CloudFiles service.

## Configuration

Create a file `app/Config/cloud_files.php` with the following:

	$config = array(
		'CloudFiles' => array(
			'server' => 'US', //UK
			'username' => 'your_username', //your username
			'api_key' => 'API_KEY', //your api key
		)
	);



## Usage Examples

Basic Usage examples below

### Upload a file to rackspace

Uploads a local file to the specified container in rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	$cdn_url = CloudFiles::upload('/path/to/image.jpg','container_name');
	
### Download a file from rackspace

Download a remote file in a specific container to a local file

	App::uses('CloudFiles','CloudFiles.Lib');
	CloudFiles::download('image.jpg', 'container_name', '/local/path/to/image.jpg');
	
### Delete a file from rackspace

Delete a file from a specific container on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	CloudFiles::delete('image.jpg','container_name');
	
### List files on rackspace

List files in a specified container on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	//Get all files in container
	$files = CloudFiles::ls('container_name');
	
	//Get files in subfolder
	$files = CloudFiles::ls('container_name', array(
		'path' => 'pictures/animals'
	));
	
	//Get files starting with a prefix
	$files = CloudFiles::ls('container_name', array(
		'prefix' => 'cake'
	));
	
	//Limit the files returned
	$files = CloudFiles::ls('container_name', array(
		'limit' => 10
	));
	
	//Limit the files returned, starting at marker
	$files = CloudFiles::ls('container_name', array(
		'limit' => 10,
		'marker' => 30
	));
	
### Public or Streaming URL of a file on rackspace

Get the URL of an object in rackspace (streaming or public)

	App::uses('CloudFiles','CloudFiles.Lib');
	$url = CloudFiles::url('image.jpg','container_name');
	
	$stream = CloudFiles::stream('movie.mov', 'container_name');
	
There is also a helper class to assist image and streaming retrieval

	//Some Controller
	public $helpers = array('CloudFiles.CloudFiles');
	
	//Some View
	echo $this->CloudFiles->image('image.jpg,'container_name');
	echo $this->CloudFiles->stream('movie.mov', 'container_name');
	echo $this->CloudFiles->url('some_file.txt', 'container_name');
