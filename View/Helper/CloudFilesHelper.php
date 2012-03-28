<?php
/**
* CloudFiles Helper
* @author Nick Baker
* @since 1.0
*/
App::uses('CloudFiles','CloudFiles.Lib');
App::uses('AppHelper','View/Helper');
class CloudFilesHelper extends AppHelper {
	
	/**
	* Helpers
	* @var array
	* @access public
	*/
	public $helpers = array('Html');

	/**
	* Return the image 
	* @param string name of file (required)
	* @param string name of container (required)
	* @param array options to pass into HtmlHelper::image (optional)
	* @return string HTML image
	*/
	public function image($name, $container, $options = array()){
		$url = $this->url($name, $container);
		if($url){
			return $this->Html->image($url, $options);
		}
		return null;
	}
	
	/**
	* Return the public url of an object in rackspace
	* @param string name of file (required)
	* @param string name of container (required)
	* @return string url of requested object
	*/
	public function url($name, $container){
		return CloudFiles::url($name, $container);
	}
	
	/**
	* Get the stream url of a file
	* @param string name of file
	* @param string name of container
	* @return string stream url
	*/
	public function stream($name, $container){
		return CloudFiles::stream($name, $container);
	}
}
?>
