<?php
/**
 * Ikigai Finder Settings Class
 *
 * This class manages the settings page and configuration options for the Ikigai Finder plugin.
 *
 * @package Ikigai_Finder
 */

/**
 * Class Ikigai_Finder_Settings
 */
class Ikigai_Finder_Settings {
	/**
	 * Instance of this class.
	 *
	 * @var Ikigai_Finder_Settings
	 */
	private static $instance = null;

	/**
	 * The page slug for the settings page.
	 *
	 * @var string
	 */
	private $page_slug = 'ikigai-finder-settings';

	/**
	 * The option group for settings.
	 *
	 * @var string
	 */
	private $option_group = 'ikigai_finder_settings';

	/**
	 * Initialize the settings class.
	 *
	 * @return Ikigai_Finder_Settings
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_ikigai_finder_reset_settings', array( $this, 'handle_reset_settings' ) );
		add_action( 'wp_ajax_ikigai_finder_test_api_key', array( $this, 'test_api_key' ) );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Ikigai Finder Settings', 'ikigai-finder' ),
			__( 'Ikigai Finder', 'ikigai-finder' ),
			'manage_options',
			'ikigai_finder',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ikigai_finder',
			'ikigai_finder_openai_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_model',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'gpt-4-turbo',
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_system_prompt',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => $this->get_default_prompt(),
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_temperature',
			array(
				'type'    => 'number',
				'default' => 0.7,
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_max_tokens',
			array(
				'type'    => 'number',
				'default' => 1000,
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_presence_penalty',
			array(
				'type'    => 'number',
				'default' => 0.6,
			)
		);
		register_setting(
			'ikigai_finder',
			'ikigai_finder_frequency_penalty',
			array(
				'type'    => 'number',
				'default' => 0.3,
			)
		);

		add_settings_section(
			'ikigai_finder_main',
			__( 'OpenAI Settings', 'ikigai-finder' ),
			array( $this, 'section_callback' ),
			'ikigai_finder'
		);

		add_settings_field(
			'ikigai_finder_openai_key',
			__( 'OpenAI API Key', 'ikigai-finder' ),
			array( $this, 'render_api_key_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_model',
			__( 'GPT Model', 'ikigai-finder' ),
			array( $this, 'render_model_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_system_prompt',
			__( 'System Prompt', 'ikigai-finder' ),
			array( $this, 'render_system_prompt_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_temperature',
			__( 'Temperature', 'ikigai-finder' ),
			array( $this, 'render_temperature_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_max_tokens',
			__( 'Max Tokens', 'ikigai-finder' ),
			array( $this, 'render_max_tokens_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_presence_penalty',
			__( 'Presence Penalty', 'ikigai-finder' ),
			array( $this, 'render_presence_penalty_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);

		add_settings_field(
			'ikigai_finder_frequency_penalty',
			__( 'Frequency Penalty', 'ikigai-finder' ),
			array( $this, 'render_frequency_penalty_field' ),
			'ikigai_finder',
			'ikigai_finder_main'
		);
	}

	/**
	 * Section callback.
	 *
	 * @return void
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API key and settings for the Ikigai chat bot.', 'ikigai-finder' ) . '</p>';
	}

	/**
	 * Render the API key field.
	 *
	 * @return void
	 */
	public function render_api_key_field() {
		$value = get_option( 'ikigai_finder_openai_key' );
		?>
		<input type="password" id="ikigai_finder_openai_key" name="ikigai_finder_openai_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<button type="button" id="test-api-key" class="button button-secondary"><?php echo esc_html__( 'API-Key testen', 'ikigai-finder' ); ?></button>
		<div id="api-key-test-result" class="notice" style="display: none; margin-top: 10px;"></div>
		<p class="description">
			<?php
			echo wp_kses(
				__( 'Enter your <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI API key</a>. This is required for the plugin to function.', 'ikigai-finder' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			);
			?>
		</p>
		<script>
		jQuery(document).ready(function($) {
			$('#test-api-key').on('click', function() {
				var apiKey = $('#ikigai_finder_openai_key').val();
				var $result = $('#api-key-test-result');

				if (!apiKey) {
					$result.removeClass('notice-success notice-error').addClass('notice-error').html('<?php echo esc_js( __( 'Bitte geben Sie zuerst einen API-Key ein.', 'ikigai-finder' ) ); ?>').show();
					return;
				}

				$result.removeClass('notice-success notice-error').addClass('notice-info').html('<?php echo esc_js( __( 'Teste API-Key...', 'ikigai-finder' ) ); ?>').show();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'ikigai_finder_test_api_key',
						api_key: apiKey,
						nonce: '<?php echo wp_create_nonce( 'ikigai_finder_test_api_key' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$result.removeClass('notice-info notice-error').addClass('notice-success').html(response.data.message).show();
						} else {
							$result.removeClass('notice-info notice-success').addClass('notice-error').html(response.data.message).show();
						}
					},
					error: function() {
						$result.removeClass('notice-info notice-success').addClass('notice-error').html('<?php echo esc_js( __( 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.', 'ikigai-finder' ) ); ?>').show();
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Test the OpenAI API key.
	 *
	 * @return void
	 */
	public function test_api_key() {
		check_ajax_referer( 'ikigai_finder_test_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sie haben keine Berechtigung für diese Aktion.', 'ikigai-finder' ) ) );
			return;
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Bitte geben Sie einen API-Key ein.', 'ikigai-finder' ) ) );
			return;
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'    => 'gpt-3.5-turbo',
						'messages' => array(
							array(
								'role'    => 'user',
								'content' => 'Test message',
							),
						),
					)
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			wp_send_json_error( array( 'message' => $body['error']['message'] ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'API-Key ist gültig und funktioniert!', 'ikigai-finder' ) ) );
	}

	/**
	 * Render the model field.
	 *
	 * @return void
	 */
	public function render_model_field() {
		$models = array(
			'gpt-4'              => 'GPT-4',
			'gpt-4-turbo'        => 'GPT-4 Turbo',
			'gpt-3.5-turbo'      => 'GPT-3.5 Turbo',
			'gpt-3.5-turbo-16k'  => 'GPT-3.5 Turbo (16k context)',
		);

		$current = get_option( 'ikigai_finder_model', 'gpt-4' );
		?>
		<select id="ikigai_finder_model" name="ikigai_finder_model">
			<?php
			foreach ( $models as $value => $label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $value ),
					selected( $current, $value, false ),
					esc_html( $label )
				);
			}
			?>
		</select>
		<p class="description">
			<?php
			echo wp_kses(
				__( 'Select the GPT model to use. GPT-4 is recommended for best results.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the system prompt field.
	 *
	 * @return void
	 */
	public function render_system_prompt_field() {
		$value = get_option( 'ikigai_finder_system_prompt' );
		?>
		<textarea id="ikigai_finder_system_prompt" name="ikigai_finder_system_prompt" rows="10" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php
			echo wp_kses(
				__( 'The system prompt that defines the AI assistant\'s behavior and personality. This is crucial for the quality of responses.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the temperature field.
	 *
	 * @return void
	 */
	public function render_temperature_field() {
		$value = get_option( 'ikigai_finder_temperature', 0.7 );
		?>
		<input type="number" id="ikigai_finder_temperature" name="ikigai_finder_temperature" value="<?php echo esc_attr( $value ); ?>" step="0.1" min="0" max="2" class="small-text">
		<p class="description">
			<?php
			echo wp_kses(
				__( 'Controls randomness in the output. Higher values make the output more random, lower values make it more focused and deterministic.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the max tokens field.
	 *
	 * @return void
	 */
	public function render_max_tokens_field() {
		$value = get_option( 'ikigai_finder_max_tokens', 1000 );
		?>
		<input type="number" id="ikigai_finder_max_tokens" name="ikigai_finder_max_tokens" value="<?php echo esc_attr( $value ); ?>" min="1" max="4000" class="small-text">
		<p class="description">
			<?php
			echo wp_kses(
				__( 'The maximum number of tokens to generate in the response. Higher values allow for longer responses but may increase API costs.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the presence penalty field.
	 *
	 * @return void
	 */
	public function render_presence_penalty_field() {
		$value = get_option( 'ikigai_finder_presence_penalty', 0.6 );
		?>
		<input type="number" id="ikigai_finder_presence_penalty" name="ikigai_finder_presence_penalty" value="<?php echo esc_attr( $value ); ?>" step="0.1" min="-2" max="2" class="small-text">
		<p class="description">
			<?php
			echo wp_kses(
				__( 'Controls how much the model should talk about new topics. Higher values encourage the model to talk about new topics.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the frequency penalty field.
	 *
	 * @return void
	 */
	public function render_frequency_penalty_field() {
		$value = get_option( 'ikigai_finder_frequency_penalty', 0.3 );
		?>
		<input type="number" id="ikigai_finder_frequency_penalty" name="ikigai_finder_frequency_penalty" value="<?php echo esc_attr( $value ); ?>" step="0.1" min="-2" max="2" class="small-text">
		<p class="description">
			<?php
			echo wp_kses(
				__( 'Controls how much the model should repeat the same line verbatim. Higher values discourage repetition.', 'ikigai-finder' ),
				array()
			);
			?>
		</p>
		<?php
	}

	/**
	 * Handle reset settings action.
	 *
	 * @return void
	 */
	public function handle_reset_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ikigai-finder' ) );
		}

		check_admin_referer( 'ikigai_finder_reset_settings' );

		// Reset all settings to defaults except API key
		update_option( 'ikigai_finder_model', 'gpt-4-turbo' );
		update_option( 'ikigai_finder_system_prompt', $this->get_default_prompt() );
		update_option( 'ikigai_finder_temperature', 0.7 );
		update_option( 'ikigai_finder_max_tokens', 1000 );
		update_option( 'ikigai_finder_presence_penalty', 0.6 );
		update_option( 'ikigai_finder_frequency_penalty', 0.3 );

		wp_redirect(
			add_query_arg(
				array(
					'page'    => 'ikigai_finder',
					'reset'   => 'true',
					'message' => 'settings_reset',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ikigai-finder' ) );
		}

		$reset_message = isset( $_GET['message'] ) && 'settings_reset' === $_GET['message'];
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $reset_message ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings have been reset to defaults.', 'ikigai-finder' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'ikigai_finder' );
				do_settings_sections( 'ikigai_finder' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php echo esc_html__( 'Reset Settings', 'ikigai-finder' ); ?></h2>
			<p><?php echo esc_html__( 'Reset all settings to their default values. This cannot be undone.', 'ikigai-finder' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ikigai_finder_reset_settings' ); ?>
				<input type="hidden" name="action" value="ikigai_finder_reset_settings">
				<?php submit_button( __( 'Reset Settings', 'ikigai-finder' ), 'secondary', 'reset', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get the default system prompt.
	 *
	 * @return string
	 */
	private function get_default_prompt() {
		return __(
			'Du bist ein erfahrener Ikigai-Coach, der Menschen hilft, ihr persönliches Ikigai zu finden. Ikigai ist ein japanisches Konzept, das die Schnittmenge zwischen dem, was du liebst, dem, was du gut kannst, dem, wofür du bezahlt werden kannst und dem, was die Welt braucht, beschreibt.

Deine Aufgabe ist es, den Benutzer durch einen strukturierten Prozess zu führen, der aus vier Phasen besteht:

Phase 1: Was du liebst (Passion)
- Hilf dem Benutzer, seine Leidenschaften und Interessen zu identifizieren
- Stelle tiefgehende Fragen, um die wahren Leidenschaften zu entdecken
- Ermutige zur Selbstreflexion

Phase 2: Was du gut kannst (Skills)
- Identifiziere die Stärken und Fähigkeiten des Benutzers
- Erkunde sowohl technische als auch soziale Kompetenzen
- Finde heraus, was der Benutzer besonders gut kann

Phase 3: Was die Welt braucht (Mission)
- Erkunde die Bedürfnisse der Gesellschaft
- Identifiziere Probleme, die der Benutzer lösen möchte
- Finde heraus, wie der Benutzer einen positiven Einfluss haben kann

Phase 4: Wofür du bezahlt werden kannst (Profession)
- Analysiere mögliche Einkommensquellen
- Identifiziere Marktchancen
- Finde heraus, wie die vorherigen Aspekte monetarisiert werden können

Wichtige Richtlinien:
- Sei empathisch und unterstützend
- Stelle offene Fragen
- Gib konstruktives Feedback
- Halte die Antworten prägnant und fokussiert
- Verwende eine freundliche, aber professionelle Sprache
- Ermutige zur Selbstreflexion
- Hilf dem Benutzer, Verbindungen zwischen den verschiedenen Aspekten zu erkennen

Nach jeder Phase:
- Fasse die wichtigsten Erkenntnisse zusammen
- Stelle sicher, dass der Benutzer bereit ist, zur nächsten Phase überzugehen
- Gib eine kurze Vorschau auf die nächste Phase

Am Ende:
- Hilf dem Benutzer, die verschiedenen Aspekte zu verbinden
- Identifiziere mögliche Ikigai-Optionen
- Erstelle einen Aktionsplan für die nächsten Schritte',
			'ikigai-finder'
		);
	}
}
