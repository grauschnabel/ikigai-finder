<?php
/**
 * WP Ikigai Block Class
 *
 * This class manages the Ikigai Chat Block.
 *
 * @package WP_Ikigai
 */

class WP_Ikigai_Block {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_block'));
        add_action('wp_ajax_wp_ikigai_chat', array(__CLASS__, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_wp_ikigai_chat', array(__CLASS__, 'handle_chat_request'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
    }

    public static function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'wp-ikigai-block-editor',
            plugins_url('build/index.js', dirname(__FILE__)),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            WP_IKIGAI_VERSION,
            true
        );

        wp_register_style(
            'wp-ikigai-block-editor-style',
            plugins_url('css/editor.css', dirname(__FILE__))
        );

        wp_register_style(
            'wp-ikigai-block-style',
            plugins_url('css/style.css', dirname(__FILE__)),
            array(),
            WP_IKIGAI_VERSION
        );

        register_block_type('wp-ikigai/chat-block', array(
            'editor_script' => 'wp-ikigai-block-editor',
            'editor_style' => 'wp-ikigai-block-editor-style',
            'style' => 'wp-ikigai-block-style',
            'render_callback' => array(__CLASS__, 'render_block')
        ));

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wp-ikigai-block-editor', 'wp-ikigai');
        }
    }

    public static function enqueue_frontend_assets() {
        if (has_block('wp-ikigai/chat-block')) {
            // Dashicons for copy buttons
            wp_enqueue_style('dashicons');
            
            // Marked.js for Markdown parsing
            wp_enqueue_script(
                'marked',
                'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
                array(),
                '9.0.0',
                true
            );

            wp_enqueue_script(
                'wp-ikigai-chat',
                plugins_url('js/chat.js', dirname(__FILE__)),
                array('jquery', 'marked'),
                WP_IKIGAI_VERSION,
                true
            );

            wp_localize_script('wp-ikigai-chat', 'wpIkigai', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_ikigai_chat'),
                'errorText' => __('Error: ', 'wp-ikigai')
            ));
        }
    }

    public static function render_block($attributes) {
        // Register and load frontend assets
        wp_register_script(
            'wp-ikigai-chat',
            plugins_url('js/chat.js', dirname(__FILE__)),
            array('jquery'),
            WP_IKIGAI_VERSION,
            true
        );

        wp_localize_script('wp-ikigai-chat', 'wpIkigai', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_ikigai_chat'),
            'errorText' => __('Error: ', 'wp-ikigai')
        ));

        wp_enqueue_script('wp-ikigai-chat');
        wp_enqueue_style('wp-ikigai-block-style');

        ob_start();
        ?>
        <div id="wp-ikigai-chat" class="wp-ikigai-chat-container">
            <div class="wp-ikigai-chat-messages"></div>
            <div class="wp-ikigai-chat-input">
                <textarea id="wp-ikigai-message" placeholder="<?php echo esc_attr__('Type your message here...', 'wp-ikigai'); ?>"></textarea>
                <button class="wp-ikigai-send"><?php echo esc_html__('Send', 'wp-ikigai'); ?></button>
            </div>
            <div class="wp-ikigai-loading" style="display: none;">
                <div class="wp-ikigai-typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
            <div class="wp-ikigai-feedback" style="display: none;">
                <button class="wp-ikigai-feedback-btn" data-value="yes"><?php echo esc_html__('👍 Helpful', 'wp-ikigai'); ?></button>
                <button class="wp-ikigai-feedback-btn" data-value="no"><?php echo esc_html__('👎 Not helpful', 'wp-ikigai'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function debug_log($message, $data = null) {
        $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($data !== null) {
            $log_message .= "\nData: " . print_r($data, true);
        }
        error_log($log_message . "\n----------------------------------------\n");
    }

    public static function handle_chat_request() {
        try {
            // Check nonce
            if (!check_ajax_referer('wp_ikigai_chat', 'nonce', false)) {
                self::debug_log('Nonce validation failed');
                wp_send_json_error(['message' => __('Security check failed.', 'wp-ikigai')], 403);
                return;
            }

            // Check API key
            $api_key = get_option('wp_ikigai_openai_key');
            if (empty($api_key)) {
                self::debug_log('API key not configured');
                wp_send_json_error(['message' => __('API key not configured. Please configure the API key in the settings.', 'wp-ikigai')], 400);
                return;
            }

            // Check system prompt
            $system_prompt = get_option('wp_ikigai_system_prompt');
            if (empty($system_prompt)) {
                self::debug_log('System prompt not configured');
                wp_send_json_error(['message' => __('System prompt not configured.', 'wp-ikigai')], 400);
                return;
            }

            // Check and process conversation
            $conversation = isset($_POST['conversation']) ? json_decode(stripslashes($_POST['conversation']), true) : [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::debug_log('JSON decode error', [
                    'error' => json_last_error_msg(),
                    'raw_data' => $_POST['conversation']
                ]);
                wp_send_json_error(['message' => __('Error processing conversation: ', 'wp-ikigai') . json_last_error_msg()], 400);
                return;
            }

            // Prepare messages for API
            $messages = [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ]
            ];

            // Process user message
            $user_message = sanitize_text_field($_POST['message']);
            
            // Extract current phase from message if present
            $current_phase = 1;
            if (preg_match('/\[CURRENT_PHASE=(\d+)\]/', $user_message, $matches)) {
                $current_phase = intval($matches[1]);
                // Remove phase tag from message
                $user_message = trim(preg_replace('/\[CURRENT_PHASE=\d+\]/', '', $user_message));
            }

            // If it's a start message, don't add user message
            if ($user_message === 'start') {
                // Add hint for phase 1
                $messages[] = [
                    'role' => 'system',
                    'content' => "The chat now begins with phase 1. Please start with a friendly greeting and briefly explain that we will explore the Ikigai in four phases."
                ];
            
                // Add hint for current phase
                $messages[] = [
                    'role' => 'system',
                    'content' => "We are currently in phase {$current_phase}. Please keep this in mind during the conversation."
                ];
                $messages[] = [
                    'role' => 'user',
                    'content' => $user_message
                ];
            }

            // Add previous conversation
            foreach ($conversation as $message) {
                if (!isset($message['role']) || !isset($message['content'])) {
                    self::debug_log('Invalid message format', $message);
                    wp_send_json_error(['message' => __('Invalid message format in conversation.', 'wp-ikigai')], 400);
                    return;
                }
                $messages[] = [
                    'role' => sanitize_text_field($message['role']),
                    'content' => sanitize_textarea_field($message['content'])
                ];
            }

            // Prepare API request
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'model' => get_option('wp_ikigai_model', 'gpt-4'),
                    'messages' => $messages,
                    'temperature' => floatval(get_option('wp_ikigai_temperature', 0.7)),
                    'max_tokens' => intval(get_option('wp_ikigai_max_tokens', 1000)),
                    'presence_penalty' => floatval(get_option('wp_ikigai_presence_penalty', 0.6)),
                    'frequency_penalty' => floatval(get_option('wp_ikigai_frequency_penalty', 0.3))
                ]),
                'timeout' => 60,
            ]);

            // Check HTTP errors
            if (is_wp_error($response)) {
                self::debug_log('WordPress HTTP error', [
                    'error' => $response->get_error_message(),
                    'data' => $response->get_error_data()
                ]);
                wp_send_json_error([
                    'message' => __('HTTP Error: ', 'wp-ikigai') . $response->get_error_message(),
                    'details' => $response->get_error_data()
                ], 500);
                return;
            }

            // Check HTTP status
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                self::debug_log('API error', [
                    'status' => $status_code,
                    'body' => $body
                ]);
                wp_send_json_error([
                    'message' => __('API Error: ', 'wp-ikigai') . $status_code,
                    'details' => json_decode($body, true)
                ], $status_code);
                return;
            }

            // Process API response
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::debug_log('JSON decode error in API response', [
                    'error' => json_last_error_msg(),
                    'body' => wp_remote_retrieve_body($response)
                ]);
                wp_send_json_error(['message' => __('Error processing API response', 'wp-ikigai')], 500);
                return;
            }

            if (!isset($body['choices'][0]['message']['content'])) {
                self::debug_log('Invalid response format from API', $body);
                wp_send_json_error(['message' => __('Invalid response format from API', 'wp-ikigai')], 500);
                return;
            }

            $assistant_message = $body['choices'][0]['message'];
            
            // If it was a start message, don't add user message to conversation
            if ($user_message !== 'start') {
                $conversation[] = [
                    'role' => 'user',
                    'content' => $user_message
                ];
            }
            
            // Add assistant's response to conversation
            $conversation[] = $assistant_message;

            wp_send_json_success([
                'message' => $assistant_message['content'],
                'conversation' => $conversation
            ]);

        } catch (Exception $e) {
            self::debug_log('Unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            wp_send_json_error([
                'message' => __('An unexpected error occurred.', 'wp-ikigai'),
                'details' => $e->getMessage()
            ], 500);
        }
    }
} 