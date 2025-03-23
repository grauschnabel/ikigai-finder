<?php
/**
 * Tests für die Settings-Klasse.
 *
 * @package Ikigai_Finder
 */

use PHPUnit\Framework\TestCase;

/**
 * Test-Klasse für Ikigai_Finder_Settings.
 */
class Test_Ikigai_Finder_Settings extends TestCase {

	/**
	 * Test-Instanz der Settings-Klasse.
	 *
	 * @var Ikigai_Finder_Settings
	 */
	private $settings;

	/**
	 * Setzt die Test-Umgebung auf.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->settings = Ikigai_Finder_Settings::get_instance();
	}

	/**
	 * Testet die Initialisierung der Settings-Klasse.
	 */
	public function test_settings_initialization() {
		$this->assertInstanceOf( 'Ikigai_Finder_Settings', $this->settings );
	}

	/**
	 * Testet die Registrierung der Einstellungen.
	 */
	public function test_settings_registration() {
		// Simuliere WordPress-Funktionen
		global $wp_settings_sections, $wp_settings_fields;
		$wp_settings_sections = array();
		$wp_settings_fields = array();

		// Teste die Settings-Registrierung
		$this->settings->register_settings();

		// Überprüfe, ob die Settings registriert wurden
		$this->assertArrayHasKey( 'ikigai_finder_settings', $wp_settings_sections );
		$this->assertArrayHasKey( 'ikigai_finder_settings', $wp_settings_fields );
	}

	/**
	 * Testet die Validierung der API-Einstellungen.
	 */
	public function test_api_settings_validation() {
		// Teste ungültige API-Einstellungen
		$invalid_settings = array(
			'openai_api_key' => '',
			'gpt_model' => 'invalid-model',
			'temperature' => 2.5,
			'max_tokens' => -1,
			'presence_penalty' => 3,
			'frequency_penalty' => 3
		);

		$validated = $this->settings->validate_api_settings( $invalid_settings );

		// Überprüfe die Validierung
		$this->assertEmpty( $validated['openai_api_key'] );
		$this->assertEquals( 'gpt-4o-mini', $validated['gpt_model'] );
		$this->assertEquals( 0.7, $validated['temperature'] );
		$this->assertEquals( 1000, $validated['max_tokens'] );
		$this->assertEquals( 0.6, $validated['presence_penalty'] );
		$this->assertEquals( 0.3, $validated['frequency_penalty'] );
	}
}
