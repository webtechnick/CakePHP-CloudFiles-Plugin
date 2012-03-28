# Rackspace CloudFiles CakePHP Plugin
* Author: Nick Baker
* Version: 0.2
* License: MIT
* Website: <http://www.webtechnick.com>

This plugin is used to interface with the Rackspace CloudFiles service.

## Changelog
* 0.0.2: 	Added CloudFiles Library -- utilizing the rackspace php-cloudfiles library and implemented upload function 
					CloudFiles::upload('/path/to/image.png','container_name');
					CloudFiles::delete('image.png', 'container_name');
* 0.0.1: 	Initial Commit -- Skeleton plugin

## Installation

After cloning the repository, you must run git submodule init and update to pull in the required vendor

	git clone git://github.com/webtechnick/CakePHP-CloudFiles-Plugin.git app/Plugin/CloudFiles
	cd Plugin/CloudFiles
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



## Usage

Basic Usage examples

### Upload

Uploads a local file to the specified container in rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	$cdn_url = CloudFiles::upload('/path/to/image.jpg','container_name');
	
### Delete

Delete a file from a specific container on rackspace

	App::uses('CloudFiles','CloudFiles.Lib');
	CloudFiles::delete('image.jpg','container_name');