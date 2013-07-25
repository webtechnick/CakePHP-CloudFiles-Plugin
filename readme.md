# Rackspace CloudFiles CakePHP Plugin
* Author: Nick Baker
* Version: 1.4.0
* License: MIT
* Website: <http://www.webtechnick.com>

This plugin is used to interface with the Rackspace CloudFiles service.  This plugin utilizes the php-cloudfiles provided by rackspace

## Requirements

* CakePHP 2.x, PHP 5.x
* Any requirements defined by <https://github.com/rackspace/php-opencloud>

## Changelog
* 1.4.0:  Upgraded to https://github.com/rackspace/php-opencloud instead of deprecated https://github.com/rackerlabs/php-cloudfiles.  Update your submodules.
* 1.3.0:  Added CloudFiles::exists and CloudFiles::upload now will check if file exists before uploading (default off)
* 1.2.0:  Added CloudFiles.cloud_files shell for basic upload/delete of files to your CDN
* 1.1.0:  Added CloudFiles::listContainer, CloudFiles::createContainer, CloudFiles::deleteContainer
* 1.0.1:  Added CloudFiles::download
* 1.0.0:  Initial Release -- More polish, added CloudFilesHelper, all basic REST actions on cloud files implemented.
* 0.0.2: 	Added CloudFiles Library -- utilizing the rackspace php-cloudfiles library and implemented upload function 
					CloudFiles::upload, CloudFiles::delete, CloudFiles::ls
* 0.0.1: 	Initial Commit -- Skeleton plugin

## Installation

There are two ways to install the plugin, via GIT with submodules or manually by downloading two repositories

### Git Installation (recommended)

After cloning the repository, you must run git submodule init and update to pull in the required vendor

	git clone git://github.com/webtechnick/CakePHP-CloudFiles-Plugin.git app/Plugin/CloudFiles
	cd app/Plugin/CloudFiles
	git submodule init
	git submodule update
	
### Manual Installation

* Download this plugin into `app/Plugin/CloudFiles`
* Download <https://github.com/rackspace/php-opencloud> into `app/Plugin/CloudFiles/Vendor/php-opencloud`

## Setup and Configuration

Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('CloudFiles');`

Create a file `app/Config/cloud_files.php` with the following:

	$config = array(
		'CloudFiles' => array(
			'server' => 'US', //UK
			'username' => 'your_username', //your username
			'api_key' => 'API_KEY', //your api key
			'region' => 'ORD', //ORD, DFW, LON
			'url_type' => 'publicURL',
			'tenant_name' => ''
		)
	);

Example of this configuration file is in `app/Plugin/CloudFiles/Config/cloud_files.php.default`

## Usage Examples

Basic Usage examples below

### Upload a file to rackspace

Uploads a local file to the specified container in rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	$cdn_url = CloudFiles::upload('/path/to/image.jpg','container_name');
	//Will not re-upload the same image if it's already in the CDN
	CloudFiles::upload('/path/to/image.jpg', 'container_name', array('overwrite' => false));
	
*TIP:* There is also a built in shell to help upload directories and files

	//Rerusively upload a directory to a container on rackspace
	$ cake CloudFiles.cloud_files -r upload /path/to/directory container_name
	
	//Upload a single file to a container on rackspace
	$ cake CloudFiles.cloud_files upload_file /path/to/file.ext container_name
	
### Download a file from rackspace

Download a remote file in a specific container to a local file

	App::uses('CloudFiles','CloudFiles.Lib');
	CloudFiles::download('image.jpg', 'container_name', '/local/path/to/image.jpg');
	
### Delete a file from rackspace

Delete a file from a specific container on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	CloudFiles::delete('image.jpg','container_name');
	
*TIP:* There is also a built in shell to help delete files on rackspace

	//Delete a single file in a container on rackspace
	$ cake CloudFiles.cloud_files delete_file file.ext container_name
	
	//Delete all files in a container as well as the container
	$ cake CloudFiles.cloud_files delete_container container_name
	
### List files on rackspace

List files in a specified container on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	//Get all files in container
	$files = CloudFiles::ls('container_name');
	
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
	echo $this->CloudFiles->image('image.jpg','container_name');
	echo $this->CloudFiles->stream('movie.mov', 'container_name');
	echo $this->CloudFiles->url('some_file.txt', 'container_name');
	
### List containers on rackspace

List all containers on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	//Get all containers
	$containers = CloudFiles::listContainers();
	//Limit the containers returned
	$containers = CloudFiles::listContainers(array(
		'limit' => 2
	));
	
### Create container on rackspace

Created a container on rackspace, defaults to public container (CDN)

	App::uses('CloudFiles','CloudFiles.Lib');
	$Container = CloudFiles::createContainer('css');
	
*TIP:* There is a shell to help create containers.

	$ cake CloudFiles.cloud_files create_container container_name
	
### Delete a container on rackspace

Delete a container on rackspace, notice container must be empty.

	App::uses('CloudFiles', 'CloudFiles.Lib');
	CloudFiles::deleteContainer('container_name');
	
*TIP:* There is a shell to help delete containers. Note, this also deletes all files prior to deleting the container

	$ cake CloudFiles.cloud_files delete_container container_name
