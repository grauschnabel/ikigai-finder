<?php
/**
 * Tests for the WP_Ikigai_Block class.
 *
 * @package WP_Ikigai
 */

/**
 * Class WP_Ikigai_Block_Test
 *
 * @package WP_Ikigai
 */
class WP_Ikigai_Block_Test extends WP_UnitTestCase {

	/**
	 * Test instance creation.
	 */
	public function test_instance() {
		$block = new WP_Ikigai_Block();
		$this->assertInstanceOf( WP_Ikigai_Block::class, $block );
	}

	/**
	 * Test block registration.
	 */
	public function test_register_block() {
		$block = new WP_Ikigai_Block();
		$block->register_block();

		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'wp-ikigai/chat-block' ),
			'Block should be registered'
		);
	}

	/**
	 * Test asset registration.
	 */
	public function test_enqueue_frontend_assets() {
		$block = new WP_Ikigai_Block();
		$block->enqueue_frontend_assets();

		$this->assertTrue(
			wp_script_is( 'wp-ikigai-chat-frontend', 'registered' ),
			'Frontend script should be registered'
		);
		$this->assertTrue(
			wp_style_is( 'wp-ikigai-style', 'registered' ),
			'Frontend style should be registered'
		);
	}

	/**
	 * Test chat request handling with invalid nonce.
	 */
	public function test_handle_chat_request_invalid_nonce() {
		$_POST['_wpnonce']     = 'invalid_nonce';
		$_POST['conversation'] = '[]';

		$block = new WP_Ikigai_Block();

		// Expect exception or error response.
		$this->expectException( 'WPDieException' );
		$block->handle_chat_request();
	}

	/**
	 * Test chat request handling with invalid conversation format.
	 */
	public function test_handle_chat_request_invalid_conversation() {
		$_POST['_wpnonce']     = wp_create_nonce( 'wp_ikigai_chat' );
		$_POST['conversation'] = 'invalid_json';

		$block = new WP_Ikigai_Block();

		// Expect exception or error response.
		$this->expectException( 'WPDieException' );
		$block->handle_chat_request();
	}

	/**
	 * Test phase extraction.
	 */
	public function test_extract_current_phase() {
		$block = new WP_Ikigai_Block();

		$conversation = array(
			array(
				'role'    => 'assistant',
				'content' => 'Let\'s talk about what you love.',
			),
			array(
				'role'    => 'user',
				'content' => 'I love programming.',
			),
		);

		$phase = $block->extract_current_phase( $conversation );
		$this->assertEquals( 'love', $phase );
	}
}
