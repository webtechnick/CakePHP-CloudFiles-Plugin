<?php
App::uses('AppShell', 'Console/Command');
App::uses('CloudFiles','CloudFiles.Lib');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class CloudFilesShell extends AppShell {
	public $tasks = array('CloudFiles.ProgressBar');
	public $file = null;
	public $container = null;
	
	function main(){
		$this->out("CloudFiles Shell");
		$this->hr();
		$this->help();
	}
	
	function getOptionParser(){
		$parser = parent::getOptionParser();
		return $parser->description("Cloud Files interact with Rackspace")
		->addOption('recursive', array(
			'help' => 'Set recursive to true. (upload subcommand only)',
			'short' => 'r',
			'boolean' => true
		))
		->addOption('type', array(
			'help' => 'file type to upload. (upload_file subcommand only)',
			'short' => 't',
		))
		->addOption('container', array(
			'help' => 'container name',
			'short' => 'c',
		))
		->addOption('file', array(
			'help' => 'file to upload',
			'short' => 'f',
		))
		->addOption('path', array(
			'help' => 'directory path to upload. (upload subcommand only)',
			'short' => 'p',
		))
		->addSubcommand('upload_file', array(
			'help' => 'Upload a single file to specific container'
		))
		->addSubcommand('upload', array(
			'help' => 'Upload all files in a path to specific container'
		))
		->addSubcommand('delete_file', array(
			'help' => 'Delete a single file to specific container'
		))
		->addSubcommand('create_container', array(
			'help' => 'Create a container'
		))
		->addSubcommand('delete_container', array(
			'help' => 'Delete a container and all it\'s files'
		));
	}
	
	function help(){
		$this->out(" cake cloud_files upload <directory> <container>   Upload a directory to specific container");
		$this->out(" cake cloud_files upload_file <file> <container>   Upload a single file to specific container");
		$this->out(" cake cloud_files delete_file <file> <container>   Delete a single file from specific container");
		$this->out(" cake cloud_files create_container 	 <container>   Create a remote container");
		$this->out(" cake cloud_files delete_container 	 <container>   Delete all files in a remote container and the container");
	}
	
	function upload_file(){
		$file = $this->getNextParam(null, 'file');
		$container = $this->getNextParam(null, 'container');
		
		if(empty($file) || empty($container)){
			$this->errorAndExit('File and Container required');
		}
		$File = new File($file);
		$file_path = $File->pwd();
		CloudFiles::upload($file_path, $container, $this->params['type']);
		$this->out($File->name . " uploaded to $container");
	}
	
	function delete_file(){
		$file = array_shift($this->args);
		$container = array_shift($this->args);
		if(empty($file) || empty($container)){
			$this->errorAndExit('File and Container required');
		}
		$result = CloudFiles::delete($file, $container);
		if($result){
			$this->out("$file deleted from $container");
		}
		else {
			$this->out("Unable to delete $file from $container");
		}
	}
	
	function upload(){
		$directory = $this->getNextParam(null, 'path');
		$container = $this->getNextParam(null, 'container');
		if(empty($directory) || empty($container)){
			$this->errorAndExit('Directory and Container required');
		}
		$Folder = new Folder($directory);
		if($this->params['recursive']){
			$files = $Folder->findRecursive();
		}
		else {
			$single_files = $Folder->find();
			$files = array();
			foreach($single_files as $file){
				$files[] = $Folder->pwd() . DS .  $file;
			}
		}
		
		$this->ProgressBar->start(count($files));
		foreach($files as $file){
			CloudFiles::upload($file, $container);
			$this->ProgressBar->next();
		}
		$this->out();
		$this->out("Finished.");
	}
	
	function create_container(){
		$container = $this->getNextParam(null, 'container');
		if(!$container){
			$this->errorAndExit("Container required");
		}
		CloudFiles::createContainer($container);
		$this->out("$container created.");
	}
	
	function delete_container(){
		$container = $this->getNextParam(null, 'container');
		if(!$container){
			$this->errorAndExit("Container required");
		}
		$files = CloudFiles::ls($container);
		$this->ProgressBar->start(count($files) + 1);
		foreach($files as $file){
			CloudFiles::delete($file, $container);
			$this->ProgressBar->next();
		}
		CloudFiles::deleteContainer($container);
		$this->ProgressBar->next();
		$this->out();
		$this->out("Finished");
	}
	
	/**
	* Set an error message and exit
	* @param message
	*/
	protected function errorAndExit($message = null){
		$this->out($message);
		exit();
	}
	
	/**
  * Returns the next paramater passed in through the command line
  * @param mixed default value
  * @param mixed param to check first
  * @return returns the default value or the next param.
  */
  protected function getNextParam($default = null, $param = null){
  	$retval = null;
  	if($param && isset($this->params[$param])){
  		$retval = $this->params[$param];
  	}
  	if(!$retval){
  		$retval = array_shift($this->args);
  	}
  	if(!$retval){
  		$retval = $default;
  	}
  	return $retval;
  }
}
?>