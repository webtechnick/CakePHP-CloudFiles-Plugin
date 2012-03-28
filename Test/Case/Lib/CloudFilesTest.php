<?php
App::uses('CloudFiles','CloudFiles.Lib');
class CloudFilesTest extends CakeTestCase {
	
	function startTest(){
		CloudFiles::$errors = array();
	}

	function test_upload(){
		$retval = CloudFiles::upload(WWW_ROOT . 'img/hearing_aids_thumb.png','images');
		$this->assertTrue(!empty($retval));
	}
	
	function test_delete(){
		$retval = CloudFiles::delete('hearing_aids_thumb.png','images');
		$this->assertTrue($retval);
	}
	
	function test_ls(){
		$retval = CloudFiles::ls('images');
		$this->assertEqual(1, count($retval));
	}
	
	function test_upload_noexist(){
		$retval = CloudFiles::upload(WWW_ROOT . 'img/no_exist.png','images');
		$this->assertFalse($retval);
		$this->assertEqual('File does not exist.', CloudFiles::$errors[0]);
	}
	
	function test_upload_nocontainer(){
		$retval = CloudFiles::upload(WWW_ROOT . 'img/no_exist.png');
		$this->assertFalse($retval);
		$this->assertEqual('File path and container required.', CloudFiles::$errors[0]);
	}
	
	function test_upload_nofile(){
		$retval = CloudFiles::upload();
		$this->assertFalse($retval);
		$this->assertEqual('File path and container required.', CloudFiles::$errors[0]);
	}
	
	function test_url(){
		$retval = CloudFiles::url('jessica_k.png', 'images');
		$this->assertTrue(!empty($retval));
		
		$this->setExpectedException('NoSuchObjectException');
		$retval = CloudFiles::url('no_exist.png', 'images');
		$this->assertFalse($retval);
	}
}
?>
