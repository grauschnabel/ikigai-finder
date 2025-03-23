<?php
/**
 * WP Ikigai Settings Class
 *
 * This class manages the settings page and configuration options for the WP Ikigai plugin.
 *
 * @package WP_Ikigai
 */

/**
 * Class WP_Ikigai_Settings
 */
class WP_Ikigai_Settings {
	/**
	 * Instance of this class.
	 *
	 * @var WP_Ikigai_Settings
	 */
	private static $instance = null;

	/**
	 * The page slug for the settings page.
	 *
	 * @var string
	 */
	private $page_slug = 'wp-ikigai-settings';

	/**
	 * The option group for settings.
	 *
	 * @var string
	 */
	private $option_group = 'wp_ikigai_settings';

	/**
	 * Initialize the settings class.
	 *
	 * @return WP_Ikigai_Settings
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
		add_action( 'admin_post_wp_ikigai_reset_settings', array( $this, 'handle_reset_settings' ) );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'options-general.php',
			__( 'WP Ikigai Settings', 'wp-ikigai' ),
			__( 'WP Ikigai', 'wp-ikigai' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wp_ikigai', 'wp_ikigai_openai_key' );
		register_setting( 'wp_ikigai', 'wp_ikigai_model' );
		register_setting( 'wp_ikigai', 'wp_ikigai_system_prompt' );
		register_setting(
			'wp_ikigai',
			'wp_ikigai_temperature',
			array(
				'type'    => 'number',
				'default' => 0.7,
			)
		);
		register_setting(
			'wp_ikigai',
			'wp_ikigai_max_tokens',
			array(
				'type'    => 'number',
				'default' => 1000,
			)
		);
		register_setting(
			'wp_ikigai',
			'wp_ikigai_presence_penalty',
			array(
				'type'    => 'number',
				'default' => 0.6,
			)
		);
		register_setting(
			'wp_ikigai',
			'wp_ikigai_frequency_penalty',
			array(
				'type'    => 'number',
				'default' => 0.3,
			)
		);

		add_settings_section(
			'wp_ikigai_main',
			__( 'OpenAI Settings', 'wp-ikigai' ),
			array( $this, 'section_callback' ),
			'wp_ikigai'
		);

		add_settings_field(
			'wp_ikigai_openai_key',
			__( 'OpenAI API Key', 'wp-ikigai' ),
			array( $this, 'render_api_key_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_model',
			__( 'GPT Model', 'wp-ikigai' ),
			array( $this, 'render_model_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_system_prompt',
			__( 'System Prompt', 'wp-ikigai' ),
			array( $this, 'render_system_prompt_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_temperature',
			__( 'Temperature', 'wp-ikigai' ),
			array( $this, 'render_temperature_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_max_tokens',
			__( 'Max Tokens', 'wp-ikigai' ),
			array( $this, 'render_max_tokens_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_presence_penalty',
			__( 'Presence Penalty', 'wp-ikigai' ),
			array( $this, 'render_presence_penalty_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);

		add_settings_field(
			'wp_ikigai_frequency_penalty',
			__( 'Frequency Penalty', 'wp-ikigai' ),
			array( $this, 'render_frequency_penalty_field' ),
			'wp_ikigai',
			'wp_ikigai_main'
		);
	}

	/**
	 * Render the settings section description.
	 *
	 * @return void
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API settings for the Ikigai coach here.', 'wp-ikigai' ) . '</p>';
	}

	/**
	 * Render the API key field.
	 *
	 * @return void
	 */
	public function render_api_key_field() {
		$api_key = get_option( 'wp_ikigai_openai_key' );
		?>
		<input type="password"
				id="wp_ikigai_openai_key"
				name="wp_ikigai_openai_key"
				value="<?php echo esc_attr( $api_key ); ?>"
				class="regular-text"
				autocomplete="new-password">
		<p class="description">
			<?php echo esc_html__( 'Enter your OpenAI API key. You can get one from', 'wp-ikigai' ); ?>
			<a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Website</a>
		</p>
		<?php
	}

	/**
	 * Render the model selection field.
	 *
	 * @return void
	 */
	public function render_model_field() {
		$model  = get_option( 'wp_ikigai_model', 'gpt-4o-mini' );
		$models = array(
			'gpt-4o-mini'         => __( 'GPT-4o Mini (Default)', 'wp-ikigai' ),
			'gpt-4o'              => __( 'GPT-4o', 'wp-ikigai' ),
			'gpt-4'               => __( 'GPT-4', 'wp-ikigai' ),
			'gpt-4-turbo-preview' => __( 'GPT-4 Turbo', 'wp-ikigai' ),
			'gpt-3.5-turbo'       => __( 'GPT-3.5 Turbo', 'wp-ikigai' ),
		);
		?>
		<select id="wp_ikigai_model" name="wp_ikigai_model">
			<?php foreach ( $models as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php echo esc_html__( 'Select the GPT model to use. GPT-4o Mini is optimized for Ikigai coaching.', 'wp-ikigai' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the system prompt field.
	 *
	 * @return void
	 */
	public function render_system_prompt_field() {
		$default_prompt = $this->get_default_prompt();
		$value          = get_option( 'wp_ikigai_system_prompt' );
		if ( empty( $value ) ) {
			$value = $default_prompt;
			update_option( 'wp_ikigai_system_prompt', $value );
		}
		?>
		<textarea id="wp_ikigai_system_prompt" name="wp_ikigai_system_prompt" rows="10" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'The system prompt defines the behavior and instructions for ChatGPT.', 'wp-ikigai' ); ?></p>
		<p>
			<button type="button" class="button" onclick="if(confirm('<?php echo esc_js( __( 'Do you really want to restore the default prompt?', 'wp-ikigai' ) ); ?>')) { document.getElementById('wp_ikigai_system_prompt').value = <?php echo wp_json_encode( $default_prompt ); ?>; }">
				<?php echo esc_html__( 'Restore Default Value', 'wp-ikigai' ); ?>
			</button>
		</p>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var promptField = document.getElementById('wp_ikigai_system_prompt');
			var defaultPrompt = <?php echo wp_json_encode( $default_prompt ); ?>;

			document.querySelector('button.button').addEventListener('click', function() {
				promptField.value = defaultPrompt;
				promptField.style.height = 'auto';
				promptField.style.height = promptField.scrollHeight + 'px';
			});
		});
		</script>
		<?php
	}

	/**
	 * Render the temperature field.
	 *
	 * @return void
	 */
	public function render_temperature_field() {
		$value = get_option( 'wp_ikigai_temperature', 0.7 );
		echo '<input type="number" id="wp_ikigai_temperature" name="wp_ikigai_temperature" value="' . esc_attr( $value ) . '" class="small-text" step="0.1" min="0" max="2">';
		echo '<p class="description">' . esc_html__( 'Controls the creativity of responses (0 = focused, 2 = creative). Recommended value: 0.7', 'wp-ikigai' ) . '</p>';
	}

	/**
	 * Render the max tokens field.
	 *
	 * @return void
	 */
	public function render_max_tokens_field() {
		$value = get_option( 'wp_ikigai_max_tokens', 1000 );
		echo '<input type="number" id="wp_ikigai_max_tokens" name="wp_ikigai_max_tokens" value="' . esc_attr( $value ) . '" class="small-text" step="100" min="100" max="4000">';
		echo '<p class="description">' . esc_html__( 'Maximum length of response. Recommended value: 1000', 'wp-ikigai' ) . '</p>';
	}

	/**
	 * Render the presence penalty field.
	 *
	 * @return void
	 */
	public function render_presence_penalty_field() {
		$value = get_option( 'wp_ikigai_presence_penalty', 0.6 );
		echo '<input type="number" id="wp_ikigai_presence_penalty" name="wp_ikigai_presence_penalty" value="' . esc_attr( $value ) . '" class="small-text" step="0.1" min="-2" max="2">';
		echo '<p class="description">' . esc_html__( 'Penalizes topic repetition (-2 to 2). Recommended value: 0.6', 'wp-ikigai' ) . '</p>';
	}

	/**
	 * Render the frequency penalty field.
	 *
	 * @return void
	 */
	public function render_frequency_penalty_field() {
		$value = get_option( 'wp_ikigai_frequency_penalty', 0.3 );
		echo '<input type="number" id="wp_ikigai_frequency_penalty" name="wp_ikigai_frequency_penalty" value="' . esc_attr( $value ) . '" class="small-text" step="0.1" min="-2" max="2">';
		echo '<p class="description">' . esc_html__( 'Penalizes word repetition (-2 to 2). Recommended value: 0.3', 'wp-ikigai' ) . '</p>';
	}

	/**
	 * Handle resetting settings.
	 *
	 * @return void
	 */
	public function handle_reset_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wp-ikigai' ) );
		}

		check_admin_referer( 'wp_ikigai_reset_settings' );

		// Save API key temporarily.
		$api_key = get_option( 'wp_ikigai_openai_key' );

		// Delete all plugin settings.
		delete_option( 'wp_ikigai_model' );
		delete_option( 'wp_ikigai_system_prompt' );
		delete_option( 'wp_ikigai_temperature' );
		delete_option( 'wp_ikigai_max_tokens' );
		delete_option( 'wp_ikigai_presence_penalty' );
		delete_option( 'wp_ikigai_frequency_penalty' );

		// Restore API key.
		update_option( 'wp_ikigai_openai_key', $api_key );

		// Redirect back to settings page with success message.
		wp_redirect(
			add_query_arg(
				array(
					'page'             => $this->page_slug,
					'settings-updated' => 'reset',
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-ikigai' ) );
		}

		$settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( $_GET['settings-updated'] ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( 'true' === $settings_updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings saved.', 'wp-ikigai' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( 'reset' === $settings_updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings reset to defaults.', 'wp-ikigai' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'wp_ikigai' );
				do_settings_sections( 'wp_ikigai' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php echo esc_html__( 'Reset Settings', 'wp-ikigai' ); ?></h2>
			<p><?php echo esc_html__( 'Reset all settings to their default values. Your API key will be preserved.', 'wp-ikigai' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wp_ikigai_reset_settings' ); ?>
				<input type="hidden" name="action" value="wp_ikigai_reset_settings">
				<?php submit_button( __( 'Reset Settings', 'wp-ikigai' ), 'secondary', 'submit', false ); ?>
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
		return 'Du bist ein Ikigai-Coach und hilfst Benutzern durch ein strukturiertes Gespräch, ihren Lebenszweck zu entdecken. Der Prozess ist in vier Phasen unterteilt:

1. Was du LIEBST (Leidenschaft)
2. Worin du GUT BIST (Beruf)
3. Was die Welt BRAUCHT (Mission)
4. Wofür man dich BEZAHLEN kann (Berufung)

Deine Aufgabe ist es, Benutzer mit durchdachten Fragen und Reflexionen durch jede Phase zu führen. Halte Antworten prägnant, ansprechend und auf die aktuelle Phase konzentriert. Verwende einen freundlichen, unterstützenden Ton und bleibe dabei professionell.

Für jede Phase:
- Stelle 2-3 spezifische Fragen
- Gib bei Bedarf kurze Erklärungen
- Ermutige zur Selbstreflexion
- Fasse wichtige Punkte zusammen, bevor du zur nächsten Phase übergehst

Denke daran:
- Bleibe innerhalb der aktuellen Phase
- Sei einfühlsam und ermutigend
- Halte Antworten fokussiert und relevant
- Verwende klare, einfache Sprache
- Vermeide direkte Ratschläge
- Lass Benutzer ihre eigenen Antworten finden';
	}
}
