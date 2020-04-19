<?php

class Foo_Test extends WP_UnitTestCase {

	public function test_foo_is_foo() {
		$this->assertTrue( 'foo' === 'foo' );
	}

	public function test_server_response_200(){
		$response = $this->get('/');

		$response->assertStatus(200);
	}
}
