<?php

class BackupControllerTest extends FunctionalTest{
	

	public function setUp(){
		parent::setUp();

	}

	public function testBackUpNow(){

		$expected_element1 = 'Status';
		$expected_element2 = 'Data';

		$response = $this->get('backup/BackUpNow');

		$body = $response->getBody();
		
		$this->assertEquals(substr($body, 0, 1), '{', 'First character was expected to be "{" but instead it was '.substr($body, 0));
		$this->assertTrue(strpos($body, $expected_element1) !== false, 'Output was expected to contain "'.$expected_element1.'" but it did not.');
		$this->assertTrue(strpos($body, $expected_element2) !== false, 'Output was expected to contain "'.$expected_element2.'" but it did not.');
	}
}