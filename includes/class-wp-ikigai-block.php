<?php

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
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n')
        );

        wp_register_style(
            'wp-ikigai-block-editor-style',
            plugins_url('css/editor.css', dirname(__FILE__))
        );

        wp_register_style(
            'wp-ikigai-block-style',
            plugins_url('css/style.css', dirname(__FILE__))
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
            // Dashicons für die Kopier-Buttons
            wp_enqueue_style('dashicons');
            
            // Marked.js für Markdown-Parsing
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
                'nonce' => wp_create_nonce('wp_ikigai_chat')
            ));
        }
    }

    public static function render_block($attributes) {
        wp_enqueue_style('wp-ikigai-style');
        wp_enqueue_script('wp-ikigai-chat');

        ob_start();
        ?>
        <div class="wp-block-wp-ikigai-chat">
            <div class="wp-ikigai-chat-messages"></div>
            <div class="wp-ikigai-chat-input-container">
                <textarea class="wp-ikigai-chat-input" placeholder="<?php echo esc_attr__('Type your message here...', 'wp-ikigai'); ?>"></textarea>
                <button class="wp-ikigai-chat-send"><?php echo esc_html__('Send', 'wp-ikigai'); ?></button>
            </div>
            <div class="wp-ikigai-phase-display">
                <?php echo esc_html__('Current Phase:', 'wp-ikigai'); ?> <span class="wp-ikigai-current-phase">1</span>
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
            // Überprüfe Nonce
            if (!check_ajax_referer('wp_ikigai_chat', 'nonce', false)) {
                self::debug_log('Nonce Validierung fehlgeschlagen');
                wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen.'], 403);
                return;
            }

            // Überprüfe API-Key
            $api_key = get_option('wp_ikigai_openai_key');
            if (empty($api_key)) {
                self::debug_log('API Key nicht konfiguriert');
                wp_send_json_error(['message' => 'API-Schlüssel nicht konfiguriert. Bitte konfigurieren Sie den API-Schlüssel in den Einstellungen.'], 400);
                return;
            }

            // Überprüfe System Prompt
            $system_prompt = get_option('wp_ikigai_system_prompt');
            if (empty($system_prompt)) {
                self::debug_log('System Prompt nicht konfiguriert');
                wp_send_json_error(['message' => 'System Prompt nicht konfiguriert.'], 400);
                return;
            }

            // Überprüfe und verarbeite die Konversation
            $conversation = isset($_POST['conversation']) ? json_decode(stripslashes($_POST['conversation']), true) : [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::debug_log('JSON Decode Fehler', [
                    'error' => json_last_error_msg(),
                    'raw_data' => $_POST['conversation']
                ]);
                wp_send_json_error(['message' => 'Fehler beim Verarbeiten der Konversation: ' . json_last_error_msg()], 400);
                return;
            }

            // Bereite die Nachrichten für die API vor
            $messages = [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ]
            ];

            // Verarbeite die Benutzernachricht
            $user_message = sanitize_text_field($_POST['message']);
            
            // Extrahiere die aktuelle Phase aus der Nachricht, wenn vorhanden
            $current_phase = 1;
            if (preg_match('/\[CURRENT_PHASE=(\d+)\]/', $user_message, $matches)) {
                $current_phase = intval($matches[1]);
                // Entferne das Phase-Tag aus der Nachricht
                $user_message = trim(preg_replace('/\[CURRENT_PHASE=\d+\]/', '', $user_message));
            }

            // Wenn es eine Start-Nachricht ist, füge keine Benutzernachricht hinzu
            if ($user_message === 'start') {
                // Füge einen Hinweis zur Phase 1 hinzu
                $messages[] = [
                    'role' => 'system',
                    'content' => "Der Chat beginnt jetzt mit Phase 1. Bitte starte mit einer freundlichen Begrüßung und erkläre kurz, dass wir das Ikigai in vier Phasen erkunden werden."
                ];
            } else {
                // Füge einen Hinweis zur aktuellen Phase hinzu
                $messages[] = [
                    'role' => 'system',
                    'content' => "Wir befinden uns aktuell in Phase {$current_phase}. Bitte behalte das im Gespräch im Auge."
                ];
                $messages[] = [
                    'role' => 'user',
                    'content' => $user_message
                ];
            }

            // Füge die bisherige Konversation hinzu
            foreach ($conversation as $message) {
                if (!isset($message['role']) || !isset($message['content'])) {
                    self::debug_log('Ungültiges Nachrichtenformat', $message);
                    wp_send_json_error(['message' => 'Ungültiges Nachrichtenformat in der Konversation.'], 400);
                    return;
                }
                $messages[] = [
                    'role' => sanitize_text_field($message['role']),
                    'content' => sanitize_textarea_field($message['content'])
                ];
            }

            // API-Anfrage vorbereiten
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

            // Überprüfe HTTP-Fehler
            if (is_wp_error($response)) {
                self::debug_log('WordPress HTTP Fehler', [
                    'error' => $response->get_error_message(),
                    'data' => $response->get_error_data()
                ]);
                wp_send_json_error([
                    'message' => 'HTTP Fehler: ' . $response->get_error_message(),
                    'details' => $response->get_error_data()
                ], 500);
                return;
            }

            // Überprüfe HTTP Status
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                self::debug_log('API Fehler', [
                    'status' => $status_code,
                    'body' => $body
                ]);
                wp_send_json_error([
                    'message' => 'API Fehler: ' . $status_code,
                    'details' => json_decode($body, true)
                ], $status_code);
                return;
            }

            // Verarbeite die API-Antwort
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::debug_log('JSON Decode Fehler bei API-Antwort', [
                    'error' => json_last_error_msg(),
                    'body' => wp_remote_retrieve_body($response)
                ]);
                wp_send_json_error(['message' => 'Fehler beim Verarbeiten der API-Antwort'], 500);
                return;
            }

            if (!isset($body['choices'][0]['message']['content'])) {
                self::debug_log('Ungültiges Antwortformat von der API', $body);
                wp_send_json_error(['message' => 'Ungültiges Antwortformat von der API'], 500);
                return;
            }

            $assistant_message = $body['choices'][0]['message'];
            
            // Wenn es eine Start-Nachricht war, füge keine Benutzernachricht zur Konversation hinzu
            if ($user_message !== 'start') {
                $conversation[] = [
                    'role' => 'user',
                    'content' => $user_message
                ];
            }
            
            // Füge die Antwort des Assistenten zur Konversation hinzu
            $conversation[] = $assistant_message;

            wp_send_json_success([
                'message' => $assistant_message['content'],
                'conversation' => $conversation
            ]);

        } catch (Exception $e) {
            self::debug_log('Unerwarteter Fehler', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            wp_send_json_error([
                'message' => 'Ein unerwarteter Fehler ist aufgetreten.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
} 