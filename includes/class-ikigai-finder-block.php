<?php
/**
 * Ikigai Finder Block Class
 *
 * This class manages the Ikigai Chat Block.
 *
 * @package Ikigai_Finder
 */

/**
 * Class Ikigai_Finder_Block
 */
class Ikigai_Finder_Block {
	/**
	 * Initialize the block.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'wp_ajax_ikigai_finder_chat', array( __CLASS__, 'handle_chat_request' ) );
		add_action( 'wp_ajax_nopriv_ikigai_finder_chat', array( __CLASS__, 'handle_chat_request' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'ikigai-finder-block-editor',
			plugins_url( 'build/index.js', __DIR__ ),
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			IKIGAI_FINDER_VERSION,
			true
		);

		wp_register_style(
			'ikigai-finder-block-editor-style',
			plugins_url( 'css/editor.css', __DIR__ ),
			array(),
			IKIGAI_FINDER_VERSION
		);

		wp_register_style(
			'ikigai-finder-block-style',
			plugins_url( 'css/style.css', dirname( __FILE__ ) ),
			array(),
			IKIGAI_FINDER_VERSION
		);

		wp_register_script(
			'ikigai-finder-block-script',
			plugins_url( 'js/chat.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			IKIGAI_FINDER_VERSION,
			true
		);

		register_block_type(
			'ikigai-finder/chat-block',
			array(
				'editor_script'   => 'ikigai-finder-block-editor',
				'editor_style'    => 'ikigai-finder-block-editor-style',
				'style'           => 'ikigai-finder-block-style',
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ikigai-finder-block-editor', 'ikigai-finder' );
		}
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		if ( has_block( 'ikigai-finder/chat-block' ) ) {
			// Dashicons for copy buttons.
			wp_enqueue_style( 'dashicons' );

			// Marked.js for Markdown parsing.
			wp_enqueue_script(
				'marked',
				plugins_url( 'js/marked.min.js', __DIR__ ),
				array(),
				IKIGAI_FINDER_VERSION,
				true
			);

			wp_enqueue_script(
				'ikigai-finder-chat',
				plugins_url( 'js/chat.js', __DIR__ ),
				array( 'jquery', 'marked' ),
				IKIGAI_FINDER_VERSION,
				true
			);

			wp_localize_script(
				'ikigai-finder-chat',
				'ikigaiFinder',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'ikigai_finder_chat' ),
					'errorText' => __( 'Error: ', 'ikigai-finder' ),
				)
			);
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		// Register and load frontend assets.
		wp_register_script(
			'ikigai-finder-chat',
			plugins_url( 'js/chat.js', __DIR__ ),
			array( 'jquery' ),
			IKIGAI_FINDER_VERSION,
			true
		);

		wp_localize_script(
			'ikigai-finder-chat',
			'ikigaiFinder',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ikigai_finder_chat' ),
				'errorText' => __( 'Error: ', 'ikigai-finder' ),
			)
		);

		wp_enqueue_script( 'ikigai-finder-chat' );
		wp_enqueue_style( 'ikigai-finder-block-style' );

		ob_start();
		?>
		<div id="ikigai-finder-chat" class="ikigai-finder-chat-container">
			<div class="ikigai-finder-chat-messages"></div>
			<div class="ikigai-finder-chat-input">
				<textarea id="ikigai-finder-message" placeholder="<?php echo esc_attr__( 'Type your message here...', 'ikigai-finder' ); ?>"></textarea>
				<button class="ikigai-finder-send"><?php echo esc_html__( 'Send', 'ikigai-finder' ); ?></button>
			</div>
			<div class="ikigai-finder-loading" style="display: none;">
				<div class="ikigai-finder-typing-indicator">
					<span></span><span></span><span></span>
				</div>
			</div>
			<div class="ikigai-finder-feedback" style="display: none;">
				<button class="ikigai-finder-feedback-btn" data-value="yes"><?php echo esc_html__( '👍 Helpful', 'ikigai-finder' ); ?></button>
				<button class="ikigai-finder-feedback-btn" data-value="no"><?php echo esc_html__( '👎 Not helpful', 'ikigai-finder' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Debug logging function.
	 *
	 * @param string $message The message to log.
	 * @param mixed  $data    Optional data to log.
	 * @return void
	 */
	private static function debug_log( $message, $data = null ) {
		$log_message = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $message;
		if ( null !== $data && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_message .= "\nData: " . wp_json_encode( $data );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $log_message . "\n----------------------------------------\n" );
		}
	}

	/**
	 * Handle chat requests.
	 *
	 * @return void
	 */
	public static function handle_chat_request() {
		try {
			// Check nonce.
			if ( ! check_ajax_referer( 'ikigai_finder_chat', 'nonce', false ) ) {
				self::debug_log( 'Nonce validation failed' );
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ikigai-finder' ) ), 403 );
				return;
			}

			// Check API key.
			$api_key = get_option( 'ikigai_finder_openai_key' );
			if ( empty( $api_key ) ) {
				self::debug_log( 'API key not configured' );
				wp_send_json_error( array( 'message' => __( 'API key not configured. Please configure the API key in the settings.', 'ikigai-finder' ) ), 400 );
				return;
			}

			// Get GPT model from settings
			$gpt_model = get_option( 'ikigai_finder_model', 'gpt-4' );

			// Check system prompt.
			$system_prompt = get_option( 'ikigai_finder_system_prompt' );
			if ( empty( $system_prompt ) ) {
				self::debug_log( 'System prompt not configured' );
				wp_send_json_error( array( 'message' => __( 'System prompt not configured.', 'ikigai-finder' ) ), 400 );
				return;
			}

			// Check and process conversation.
			$conversation = isset( $_POST['conversation'] ) ? json_decode( wp_unslash( $_POST['conversation'] ), true ) : array();
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				self::debug_log(
					'JSON decode error',
					array(
						'error'    => json_last_error_msg(),
						'raw_data' => isset( $_POST['conversation'] ) ? wp_unslash( $_POST['conversation'] ) : '',
					)
				);
				wp_send_json_error( array( 'message' => __( 'Error processing conversation: ', 'ikigai-finder' ) . json_last_error_msg() ), 400 );
				return;
			}

			// Prepare messages for API.
			$messages = array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
			);

			// Process user message.
			$user_message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';

			// Extract current phase from message if present.
			$current_phase = 1;
			if ( preg_match( '/\[CURRENT_PHASE=(\d+)\]/', $user_message, $matches ) ) {
				$current_phase = intval( $matches[1] );
				// Remove phase tag from message.
				$user_message = trim( preg_replace( '/\[CURRENT_PHASE=\d+\]/', '', $user_message ) );
			}

			// If it's a start message, don't add user message.
			if ( 'start' === $user_message ) {
				// Add hint for phase 1.
				$messages[] = array(
					'role'    => 'system',
					'content' => 'Der Chat beginnt jetzt mit Phase 1. Bitte beginne mit einer freundlichen Begrüßung und erkläre kurz, dass wir das Ikigai in vier Phasen erkunden werden.',
				);
			} else {
				// Add user message to conversation.
				$messages[] = array(
					'role'    => 'user',
					'content' => $user_message,
				);
			}

			// Add conversation history.
			foreach ( $conversation as $msg ) {
				$messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}

			// Make API request.
			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'model'    => $gpt_model,
							'messages' => $messages,
						)
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				self::debug_log( 'API request failed', $response );
				wp_send_json_error( array( 'message' => $response->get_error_message() ), 500 );
				return;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
				self::debug_log( 'Invalid API response', $body );
				wp_send_json_error( array( 'message' => __( 'Invalid response from API.', 'ikigai-finder' ) ), 500 );
				return;
			}

			wp_send_json_success(
				array(
					'message' => $body['choices'][0]['message']['content'],
				)
			);

		} catch ( Exception $e ) {
			self::debug_log( 'Exception in handle_chat_request', $e );
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}
}
