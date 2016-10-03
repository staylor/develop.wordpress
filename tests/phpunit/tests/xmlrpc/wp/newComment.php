<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_wp_newComment extends WP_XMLRPC_UnitTestCase {
	function test_new_comment_post_closed() {
		$this->make_user_by_role( 'administrator' );
		$post = self::factory()->post->create_and_get( array(
			'comment_status' => 'closed'
		) );

		$this->assertEquals( 'closed', $post->comment_status );

		$result = $this->myxmlrpcserver->call( 'wp.newComment', array( 1, 'administrator', 'administrator', $post->ID, array(
			'comment_content' => rand_str( 100 ),
			'status' => 'approved'
		) ) );

		$this->assertInstanceOf( 'WP\IXR\Error', $result );
		$this->assertEquals( 403, $result->code );
	}
}