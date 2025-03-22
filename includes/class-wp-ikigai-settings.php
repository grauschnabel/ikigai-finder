<?php
/**
 * WP Ikigai Settings Class
 *
 * This class manages the settings page and configuration options for the WP Ikigai plugin.
 *
 * @package WP_Ikigai
 */

class WP_Ikigai_Settings {
    private static $instance = null;
    private $page_slug = 'wp-ikigai-settings';
    private $option_group = 'wp_ikigai_settings';

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_wp_ikigai_reset_settings', array($this, 'handle_reset_settings'));
    }

    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            __('WP Ikigai Settings', 'wp-ikigai'),
            __('WP Ikigai', 'wp-ikigai'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('wp_ikigai', 'wp_ikigai_openai_key');
        register_setting('wp_ikigai', 'wp_ikigai_model');
        register_setting('wp_ikigai', 'wp_ikigai_system_prompt');
        register_setting('wp_ikigai', 'wp_ikigai_temperature', array(
            'type' => 'number',
            'default' => 0.7
        ));
        register_setting('wp_ikigai', 'wp_ikigai_max_tokens', array(
            'type' => 'number',
            'default' => 1000
        ));
        register_setting('wp_ikigai', 'wp_ikigai_presence_penalty', array(
            'type' => 'number',
            'default' => 0.6
        ));
        register_setting('wp_ikigai', 'wp_ikigai_frequency_penalty', array(
            'type' => 'number',
            'default' => 0.3
        ));

        add_settings_section(
            'wp_ikigai_main',
            __('OpenAI Settings', 'wp-ikigai'),
            array($this, 'section_callback'),
            'wp_ikigai'
        );

        add_settings_field(
            'wp_ikigai_openai_key',
            __('OpenAI API Key', 'wp-ikigai'),
            array($this, 'render_api_key_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_model',
            __('GPT Model', 'wp-ikigai'),
            array($this, 'render_model_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_system_prompt',
            __('System Prompt', 'wp-ikigai'),
            array($this, 'render_system_prompt_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_temperature',
            __('Temperature', 'wp-ikigai'),
            array($this, 'render_temperature_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_max_tokens',
            __('Max Tokens', 'wp-ikigai'),
            array($this, 'render_max_tokens_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_presence_penalty',
            __('Presence Penalty', 'wp-ikigai'),
            array($this, 'render_presence_penalty_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );

        add_settings_field(
            'wp_ikigai_frequency_penalty',
            __('Frequency Penalty', 'wp-ikigai'),
            array($this, 'render_frequency_penalty_field'),
            'wp_ikigai',
            'wp_ikigai_main'
        );
    }

    public function section_callback() {
        echo '<p>' . esc_html__('Configure your OpenAI API settings for the Ikigai coach here.', 'wp-ikigai') . '</p>';
    }

    public function render_api_key_field() {
        $api_key = get_option('wp_ikigai_openai_key');
        ?>
        <input type="password" 
               id="wp_ikigai_openai_key" 
               name="wp_ikigai_openai_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text"
               autocomplete="new-password">
        <p class="description">
            <?php echo esc_html__('Enter your OpenAI API key. You can get one from', 'wp-ikigai'); ?>
            <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Website</a>
        </p>
        <?php
    }

    public function render_model_field() {
        $model = get_option('wp_ikigai_model', 'gpt-4o-mini');
        $models = array(
            'gpt-4o-mini' => __('GPT-4o Mini (Default)', 'wp-ikigai'),
            'gpt-4o' => __('GPT-4o', 'wp-ikigai'),
            'gpt-4' => __('GPT-4', 'wp-ikigai'),
            'gpt-4-turbo-preview' => __('GPT-4 Turbo', 'wp-ikigai'),
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'wp-ikigai')
        );
        ?>
        <select id="wp_ikigai_model" name="wp_ikigai_model">
            <?php foreach ($models as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($model, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php echo esc_html__('Select the GPT model to use. GPT-4o Mini is optimized for Ikigai coaching.', 'wp-ikigai'); ?>
        </p>
        <?php
    }

    public function render_system_prompt_field() {
        $default_prompt = $this->get_default_prompt();
        $value = get_option('wp_ikigai_system_prompt');
        if (empty($value)) {
            $value = $default_prompt;
            update_option('wp_ikigai_system_prompt', $value);
        }
        ?>
        <textarea id="wp_ikigai_system_prompt" name="wp_ikigai_system_prompt" rows="10" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php echo esc_html__('The system prompt defines the behavior and instructions for ChatGPT.', 'wp-ikigai'); ?></p>
        <p>
            <button type="button" class="button" onclick="if(confirm('Do you really want to restore the default prompt?')) { document.getElementById('wp_ikigai_system_prompt').value = <?php echo json_encode($default_prompt); ?>; }">
                <?php echo esc_html__('Restore Default Value', 'wp-ikigai'); ?>
            </button>
        </p>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var promptField = document.getElementById('wp_ikigai_system_prompt');
            var defaultPrompt = <?php echo json_encode($default_prompt); ?>;
            
            document.querySelector('button.button').addEventListener('click', function() {
                promptField.value = defaultPrompt;
                promptField.style.height = 'auto';
                promptField.style.height = promptField.scrollHeight + 'px';
            });
        });
        </script>
        <?php
    }

    public function render_temperature_field() {
        $value = get_option('wp_ikigai_temperature', 0.7);
        echo '<input type="number" id="wp_ikigai_temperature" name="wp_ikigai_temperature" value="' . esc_attr($value) . '" class="small-text" step="0.1" min="0" max="2">';
        echo '<p class="description">' . esc_html__('Controls the creativity of responses (0 = focused, 2 = creative). Recommended value: 0.7', 'wp-ikigai') . '</p>';
    }

    public function render_max_tokens_field() {
        $value = get_option('wp_ikigai_max_tokens', 1000);
        echo '<input type="number" id="wp_ikigai_max_tokens" name="wp_ikigai_max_tokens" value="' . esc_attr($value) . '" class="small-text" step="100" min="100" max="4000">';
        echo '<p class="description">' . esc_html__('Maximum length of response. Recommended value: 1000', 'wp-ikigai') . '</p>';
    }

    public function render_presence_penalty_field() {
        $value = get_option('wp_ikigai_presence_penalty', 0.6);
        echo '<input type="number" id="wp_ikigai_presence_penalty" name="wp_ikigai_presence_penalty" value="' . esc_attr($value) . '" class="small-text" step="0.1" min="-2" max="2">';
        echo '<p class="description">' . esc_html__('Penalizes topic repetition (-2 to 2). Recommended value: 0.6', 'wp-ikigai') . '</p>';
    }

    public function render_frequency_penalty_field() {
        $value = get_option('wp_ikigai_frequency_penalty', 0.3);
        echo '<input type="number" id="wp_ikigai_frequency_penalty" name="wp_ikigai_frequency_penalty" value="' . esc_attr($value) . '" class="small-text" step="0.1" min="-2" max="2">';
        echo '<p class="description">' . esc_html__('Penalizes word repetition (-2 to 2). Recommended value: 0.3', 'wp-ikigai') . '</p>';
    }

    public function handle_reset_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('wp_ikigai_reset_settings');

        // Save API key temporarily
        $api_key = get_option('wp_ikigai_openai_key');

        // Delete all plugin settings
        delete_option('wp_ikigai_model');
        delete_option('wp_ikigai_system_prompt');
        delete_option('wp_ikigai_temperature');
        delete_option('wp_ikigai_max_tokens');
        delete_option('wp_ikigai_presence_penalty');
        delete_option('wp_ikigai_frequency_penalty');

        // Restore API key
        update_option('wp_ikigai_openai_key', $api_key);

        // Redirect back to settings page with success message
        wp_redirect(add_query_arg(
            array('page' => $this->page_slug, 'settings-updated' => 'reset'),
            admin_url('options-general.php')
        ));
        exit;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Show reset success message
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'reset') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Settings have been reset to default values.', 'wp-ikigai'); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WP Ikigai Settings', 'wp-ikigai'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_ikigai');
                do_settings_sections('wp_ikigai');
                submit_button();
                ?>
            </form>

            <hr style="margin: 30px 0;">
            
            <h2><?php echo esc_html__('Reset Settings', 'wp-ikigai'); ?></h2>
            <p><?php echo esc_html__('Click here to reset all settings (except API key) to default values:', 'wp-ikigai'); ?></p>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="wp_ikigai_reset_settings">
                <?php wp_nonce_field('wp_ikigai_reset_settings'); ?>
                <?php submit_button(
                    __('Reset to Default Values', 'wp-ikigai'),
                    'secondary',
                    'submit',
                    false
                ); ?>
            </form>
        </div>
        <?php
    }

    private function get_default_prompt() {
        $default_prompt = <<<EOT
You are a structured, inspiring, and empathetic Ikigai coach. Your task is to help users discover their Ikigai step by step.

Start each conversation with the following introduction:

"Welcome! I am your personal Ikigai coach.
Ikigai is a Japanese concept that literally means 'a reason for being' – it describes an activity or way of life that fulfills you, excites you, and makes you happy.
Together, we will discover your Ikigai step by step."

You will guide users through these four phases in a clear structure:

1. **What do you love?** (Passion)
   - Ask a maximum of 2-3 targeted questions about activities or topics where the person loses track of time or feels genuine joy. ("What truly excites you?", "When do you completely lose track of time?")
   - Actively acknowledge answers and summarize the passion in your own words before actively moving to the next phase ("Great, now I know that you love... Let's explore your strengths.").

2. **What are you good at?** (Strengths)
   - Ask about skills or qualities that come naturally to the person or that others notice. Ask a maximum of 2-3 targeted questions.
   - Actively refer to the previously mentioned passion ("You mentioned you enjoy writing – would you say you're good at expressing yourself?").
   - Briefly summarize strengths and then transition to the next phase.

3. **What does the world need?** (Contribution)
   - Ask specifically about problems or societal issues that matter to the person ("What would you like to improve or change?", "Is there an issue where you feel something is missing?").
   - Ask a maximum of 2-3 open, empathetic questions. Provide inspiring examples if needed.
   - Summarize the contribution and actively move to the next phase.

4. **What would people pay for?** (Vocation)
   - If necessary, provide concrete, hypothetical suggestions that match previous answers ("Could you imagine using your listening ability professionally in counseling?").
   - The goal is not to find a perfect business model, but to show a clear direction.

**After completing all four phases** summarize the insights in a personal, inspiring, and individual text. Always begin this summary with:

"I believe your Ikigai is:"

Formulate the text so that the person feels seen, motivated, and understood. Make suggestions and paint a positive, vivid picture of the future.

**Your Communication:**
- Lead the conversation actively, empathetically, and with clear structure.
- Use "you" consistently.
- Don't repeat questions that have already been answered.
- Keep answers short, human, and motivating.

**Internal Validation Rule (not visible to users):**
Before moving to the next phase, independently verify that the user's responses clearly and comprehensibly answer all questions of the current phase. If an answer appears unclear or incomplete, kindly and empathetically ask a clarifying question to ensure you truly understand what is meant.

Examples of clarifying questions:
- "I want to make sure I understand you correctly: Do you mean that ... ?"
- "Could you explain that a bit more specifically so I can better understand what you mean?"
- "You mentioned ... Could you perhaps give me a concrete example of that?"

Only actively move to the next phase when you are sure you have clearly understood the answers.

**Technical Implementation Internal:**
After each answer, set one of the following tags for internal state control:
- [PHASE=1]
- [PHASE=2]
- [PHASE=3]
- [PHASE=4]
- [PHASE=done]
EOT;

        return $default_prompt;
    }
} 