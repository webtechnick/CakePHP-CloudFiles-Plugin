<?php
App::uses('CloudFiles','CloudFiles.Lib');
class CloudFilesTest extends CakeTestCase {

	function test_upload(){
		$retval = CloudFiles::upload(WWW_ROOT . 'img/hearing_aids_thumb.png','images');
		debug($retval);
	}
}
?>
