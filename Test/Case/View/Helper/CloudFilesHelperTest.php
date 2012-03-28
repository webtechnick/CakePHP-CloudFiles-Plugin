<?php
App::uses('CloudFilesHelper', 'CloudFiles.View/Helper');
App::uses('View', 'View');
/**
 * CloudFilesHelper Test Case
 *
 */
class CloudFilesHelperTestCase extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$View = new View();
		$this->CloudFiles = new CloudFilesHelper($View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->CloudFiles);

		parent::tearDown();
	}

/**
 * testImage method
 *
 * @return void
 */
	public function testImage() {
		$html = $this->CloudFiles->image('jessica_k.png', 'images');
		$pos = strpos($html, 'jessica_k.png');
		$this->assertTrue(!empty($pos));
	}
	
	public function testUrl(){
		$html = $this->CloudFiles->url('jessica_k.png', 'images');
		$pos = strpos($html, 'jessica_k.png');
		$this->assertTrue(!empty($pos));
	}
/**
 * testStream method
 *
 * @return void
 */
	public function testStream() {
		$html = $this->CloudFiles->stream('jessica_k.png', 'images');
		$pos = strpos($html, 'jessica_k.png');
		$this->assertTrue(!empty($pos));
	}
}
