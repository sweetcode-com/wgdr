<?php


class Server_Responses extends PHPUnit_Framework_TestCase {

	public function test_server_response_200() {
		$response = $this->get( '/' );

		$response->assertStatus( 200 );
	}
}
