<?php
/**
 * Tests für die Block-Klasse.
 *
 * @package Ikigai_Finder
 */

use PHPUnit\Framework\TestCase;

/**
 * Test-Klasse für Ikigai_Finder_Block.
 */
class Test_Ikigai_Finder_Block extends TestCase {

	/**
	 * Test-Instanz der Block-Klasse.
	 *
	 * @var Ikigai_Finder_Block
	 */
	private $block;

	/**
	 * Setzt die Test-Umgebung auf.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->block = new Ikigai_Finder_Block();
	}

	/**
	 * Testet die Initialisierung der Block-Klasse.
	 */
	public function test_block_initialization() {
		$this->assertInstanceOf( 'Ikigai_Finder_Block', $this->block );
	}

	/**
	 * Testet die Registrierung des Blocks.
	 */
	public function test_block_registration() {
		// Simuliere WordPress-Funktionen
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();

		// Teste die Block-Registrierung
		$this->block->register_block();

		// Überprüfe, ob die Assets registriert wurden
		$this->assertTrue( wp_script_is( 'ikigai-finder-block-script', 'registered' ) );
		$this->assertTrue( wp_style_is( 'ikigai-finder-block-style', 'registered' ) );
	}

	/**
	 * Testet die Chat-Anfrage-Verarbeitung.
	 */
	public function test_chat_request_handling() {
		// Simuliere eine AJAX-Anfrage
		$_POST['nonce'] = wp_create_nonce( 'ikigai_finder_chat' );
		$_POST['message'] = 'Test-Nachricht';
		$_POST['conversation'] = json_encode( array() );

		// Teste die Verarbeitung
		$this->block->handle_chat_request();

		// Überprüfe die Antwort
		$response = json_decode( ob_get_clean(), true );
		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'success', $response );
		$this->assertArrayHasKey( 'data', $response );
	}
}
