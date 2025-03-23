<?php
/**
 * Tests for the WP_Ikigai_Settings class.
 *
 * @package WP_Ikigai
 */

/**
 * Class WP_Ikigai_Settings_Test
 *
 * @package WP_Ikigai
 */
class WP_Ikigai_Settings_Test extends WP_UnitTestCase {

	/**
	 * Test instance creation.
	 */
	public function test_instance() {
		$settings = new WP_Ikigai_Settings();
		$this->assertInstanceOf( WP_Ikigai_Settings::class, $settings );
	}

	/**
	 * Test settings registration.
	 */
	public function test_register_settings() {
		$settings = new WP_Ikigai_Settings();
		$settings->register_settings();

		$this->assertNotFalse(
			get_option( 'wp_ikigai_openai_api_key' ),
			'API key option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_gpt_model' ),
			'GPT model option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_system_prompt' ),
			'System prompt option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_temperature' ),
			'Temperature option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_max_tokens' ),
			'Max tokens option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_presence_penalty' ),
			'Presence penalty option should be registered'
		);
		$this->assertNotFalse(
			get_option( 'wp_ikigai_frequency_penalty' ),
			'Frequency penalty option should be registered'
		);
	}

	/**
	 * Test settings page registration.
	 */
	public function test_add_settings_page() {
		$settings = new WP_Ikigai_Settings();
		$settings->add_settings_page();

		global $submenu;
		$settings_page_found = false;

		if ( isset( $submenu['options-general.php'] ) ) {
			foreach ( $submenu['options-general.php'] as $item ) {
				if ( 'wp-ikigai-settings' === $item[2] ) {
					$settings_page_found = true;
					break;
				}
			}
		}

		$this->assertTrue( $settings_page_found, 'Settings page should be registered.' );
	}

	/**
	 * Test default values.
	 */
	public function test_default_values() {
		$settings = new WP_Ikigai_Settings();

		$this->assertEquals(
			'gpt-4o-mini',
			get_option( 'wp_ikigai_gpt_model' ),
			'Default GPT model should be gpt-4o-mini'
		);

		$this->assertEquals(
			0.7,
			get_option( 'wp_ikigai_temperature' ),
			'Default temperature should be 0.7'
		);

		$this->assertEquals(
			1000,
			get_option( 'wp_ikigai_max_tokens' ),
			'Default max tokens should be 1000'
		);

		$this->assertEquals(
			0.6,
			get_option( 'wp_ikigai_presence_penalty' ),
			'Default presence penalty should be 0.6'
		);

		$this->assertEquals(
			0.3,
			get_option( 'wp_ikigai_frequency_penalty' ),
			'Default frequency penalty should be 0.3'
		);
	}

	/**
	 * Test settings sanitization.
	 */
	public function test_sanitize_settings() {
		$settings = new WP_Ikigai_Settings();

		// Test temperature sanitization.
		$sanitized_temp = $settings->sanitize_float( '2.5', 'wp_ikigai_temperature' );
		$this->assertEquals( 2.0, $sanitized_temp, 'Temperature should be capped at 2.0' );

		$sanitized_temp = $settings->sanitize_float( '-0.5', 'wp_ikigai_temperature' );
		$this->assertEquals( 0.0, $sanitized_temp, 'Temperature should be minimum 0.0' );

		// Test max tokens sanitization.
		$sanitized_tokens = $settings->sanitize_integer( '5000', 'wp_ikigai_max_tokens' );
		$this->assertEquals( 4096, $sanitized_tokens, 'Max tokens should be capped at 4096' );

		$sanitized_tokens = $settings->sanitize_integer( '-100', 'wp_ikigai_max_tokens' );
		$this->assertEquals( 1, $sanitized_tokens, 'Max tokens should be minimum 1' );
	}
}
