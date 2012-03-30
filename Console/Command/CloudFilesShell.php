<?php
App::uses('CloudFiles','CloudFiles.Lib');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class CloudFilesShell extends Shell {
	public $tasks = array('CloudFiles.ProgressBar');
	
	function main(){
		$this->out("CloudFiles Shell");
		$this->hr();
		$this->help();
	}
	
	function help(){
		$this->out(" cake cloud_files upload <directory> <container>   Upload a directory to specific container");
		$this->out(" cake cloud_files upload_file <file> <container>   Upload a single file to specific container");
		$this->out(" cake cloud_files delete_file <file> <container>   Delete a single file from specific container");
		$this->out(" cake cloud_files create_container 	 <container>   Create a remote container");
		$this->out(" cake cloud_files delete_container 	 <container>   Delete all files in a remote container and the container");
	}
	
	function upload_file(){
		$file = array_shift($this->args);
		$container = array_shift($this->args);
		if(empty($file) || empty($container)){
			$this->errorAndExit('File and Container required');
		}
		$File = new File($file);
		$file_path = $File->pwd();
		CloudFiles::upload($file_path, $container);
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
		$directory = array_shift($this->args);
		$container = array_shift($this->args);
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
		$container = array_shift($this->args);
		if(!$container){
			$this->errorAndExit("Container required");
		}
		CloudFiles::createContainer($container);
		$this->out("$container created.");
	}
	
	function delete_container(){
		$container = array_shift($this->args);
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
	
	function getOptionParser(){
		$parser = parent::getOptionParser();
		return $parser->description("Interact with the CloudFiles CDN")
		->addOption('recursive', array(
			'help' => 'Set recursive to true',
			'short' => 'r',
			'boolean' => true
		));
	}
	
	/**
  * Set an error message and exit
  * @param message
  */
  protected function errorAndExit($message = null){
  	$this->out($message);
  	exit();
  }
}
?>