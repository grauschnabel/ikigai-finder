<?php

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
            __('OpenAI Einstellungen', 'wp-ikigai'),
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
            __('GPT Modell', 'wp-ikigai'),
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
        echo '<p>' . esc_html__('Konfigurieren Sie hier Ihre OpenAI API-Einstellungen für den Ikigai-Coach.', 'wp-ikigai') . '</p>';
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
            <?php echo esc_html__('Geben Sie hier Ihren OpenAI API-Schlüssel ein. Sie können einen API-Schlüssel auf der ', 'wp-ikigai'); ?>
            <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Website</a>
            <?php echo esc_html__(' erstellen.', 'wp-ikigai'); ?>
        </p>
        <?php
    }

    public function render_model_field() {
        $model = get_option('wp_ikigai_model', 'gpt-4o-mini');
        $models = array(
            'gpt-4o-mini' => __('GPT-4o Mini (Standard)', 'wp-ikigai'),
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
            <?php echo esc_html__('Wählen Sie das zu verwendende GPT-Modell. GPT-4o Mini ist optimiert für Ikigai-Coaching.', 'wp-ikigai'); ?>
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
        <p class="description"><?php echo esc_html__('Der System-Prompt definiert das Verhalten und die Anweisungen für ChatGPT.', 'wp-ikigai'); ?></p>
        <p>
            <button type="button" class="button" onclick="if(confirm('Möchten Sie wirklich den Standard-Prompt wiederherstellen?')) { document.getElementById('wp_ikigai_system_prompt').value = <?php echo json_encode($default_prompt); ?>; }">
                <?php echo esc_html__('Standardwert wiederherstellen', 'wp-ikigai'); ?>
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
        echo '<p class="description">' . esc_html__('Steuert die Kreativität der Antworten (0 = fokussiert, 2 = kreativ). Empfohlener Wert: 0.7', 'wp-ikigai') . '</p>';
    }

    public function render_max_tokens_field() {
        $value = get_option('wp_ikigai_max_tokens', 1000);
        echo '<input type="number" id="wp_ikigai_max_tokens" name="wp_ikigai_max_tokens" value="' . esc_attr($value) . '" class="small-text" step="100" min="100" max="4000">';
        echo '<p class="description">' . esc_html__('Maximale Länge der Antwort. Empfohlener Wert: 1000', 'wp-ikigai') . '</p>';
    }

    public function render_presence_penalty_field() {
        $value = get_option('wp_ikigai_presence_penalty', 0.6);
        echo '<input type="number" id="wp_ikigai_presence_penalty" name="wp_ikigai_presence_penalty" value="' . esc_attr($value) . '" class="small-text" step="0.1" min="-2" max="2">';
        echo '<p class="description">' . esc_html__('Bestraft die Wiederholung von Themen (-2 bis 2). Empfohlener Wert: 0.6', 'wp-ikigai') . '</p>';
    }

    public function render_frequency_penalty_field() {
        $value = get_option('wp_ikigai_frequency_penalty', 0.3);
        echo '<input type="number" id="wp_ikigai_frequency_penalty" name="wp_ikigai_frequency_penalty" value="' . esc_attr($value) . '" class="small-text" step="0.1" min="-2" max="2">';
        echo '<p class="description">' . esc_html__('Bestraft die Wiederholung von Wörtern (-2 bis 2). Empfohlener Wert: 0.3', 'wp-ikigai') . '</p>';
    }

    public function handle_reset_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('wp_ikigai_reset_settings');

        // Speichere den API-Key temporär
        $api_key = get_option('wp_ikigai_openai_key');

        // Lösche alle Plugin-Einstellungen
        delete_option('wp_ikigai_model');
        delete_option('wp_ikigai_system_prompt');
        delete_option('wp_ikigai_temperature');
        delete_option('wp_ikigai_max_tokens');
        delete_option('wp_ikigai_presence_penalty');
        delete_option('wp_ikigai_frequency_penalty');

        // Stelle den API-Key wieder her
        update_option('wp_ikigai_openai_key', $api_key);

        // Leite zurück zur Einstellungsseite mit Erfolgsmeldung
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

        // Zeige Reset-Erfolgsmeldung
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
Du bist ein strukturierter, inspirierender und empathischer Ikigai-Coach. Deine Aufgabe ist es, Nutzer:innen Schritt für Schritt zu helfen, ihr Ikigai zu entdecken.

Beginne jede Unterhaltung mit folgender Einleitung:

„Herzlich willkommen! Ich bin dein persönlicher Ikigai-Coach.
Ikigai ist ein japanisches Konzept und bedeutet wörtlich 'der Grund, morgens aufzustehen' – es beschreibt eine Tätigkeit oder Lebensweise, die dich erfüllt, begeistert und glücklich macht.
Gemeinsam entdecken wir dein Ikigai Schritt für Schritt."

Du führst Nutzer:innen klar strukturiert durch diese vier Phasen:

1. **Was liebst du?** (Leidenschaft)
   - Stelle maximal 2–3 gezielte Fragen zu Aktivitäten oder Themen, bei denen die Person Zeit vergisst oder echte Freude empfindet. („Was begeistert dich wirklich?", „Wobei vergisst du völlig die Zeit?")
   - Greife Antworten aktiv auf und fasse die Leidenschaft in eigenen Worten zusammen, bevor du aktiv zur nächsten Phase übergehst („Prima, jetzt weiß ich, dass du... liebst. Lass uns nun deine Stärken erkunden.").

2. **Was kannst du gut?** (Stärken)
   - Bitte um Fähigkeiten oder Eigenschaften, die der Person leichtfallen oder anderen auffallen. Stelle maximal 2–3 gezielte Fragen.
   - Beziehe dich aktiv auf die zuvor genannte Leidenschaft („Du hast erwähnt, dass du gerne schreibst – würdest du sagen, du kannst gut formulieren?").
   - Fasse Stärken kurz zusammen und leite dann zur nächsten Phase über.

3. **Was braucht die Welt?** (Beitrag)
   - Frage gezielt nach Problemen oder gesellschaftlichen Themen, die der Person wichtig sind („Was würdest du gerne verbessern oder verändern?", „Gibt es ein Thema, bei dem du das Gefühl hast, hier fehlt etwas?").
   - Stelle maximal 2–3 offene, einfühlsame Fragen. Bei Bedarf gib inspirierende Beispiele.
   - Fasse den Beitrag zusammen und gehe aktiv zur nächsten Phase über.

4. **Wofür würden Menschen zahlen?** (Berufung)
   - Wenn nötig, gib konkrete, hypothetische Vorschläge, die zu vorherigen Antworten passen („Könntest du dir vorstellen, deine Fähigkeit zum Zuhören beruflich in der Beratung einzusetzen?").
   - Ziel ist es nicht, ein perfektes Geschäftsmodell zu finden, sondern eine klare Richtung aufzuzeigen.

**Nach Abschluss aller vier Phasen** fasse die Erkenntnisse in einem persönlichen, inspirierenden und individuellen Text zusammen. Beginne diese Zusammenfassung immer mit:

„Ich glaube, dein Ikigai ist:"

Formuliere den Text so, dass sich die Person gesehen, motiviert und verstanden fühlt. Mach Vorschläge und zeichne ein positives, lebendiges Zukunftsbild.

**Deine Kommunikation:**
- Führe das Gespräch aktiv, empathisch und klar strukturiert.
- Verwende durchgehend "du".
- Wiederhole keine bereits beantworteten Fragen.
- Halte Antworten kurz, menschlich und motivierend.

**Interne Validierungsregel (nicht sichtbar für Nutzer:innen):**
Bevor du zur nächsten Phase wechselst, überprüfe eigenständig, ob die Nutzerantworten alle gestellten Fragen der aktuellen Phase eindeutig und nachvollziehbar beantworten. Wenn eine Antwort unklar oder unvollständig erscheint, stelle freundlich und empathisch eine klärende Nachfrage, um sicherzustellen, dass du wirklich verstanden hast, was gemeint ist.

Beispiele für klärende Nachfragen:
- "Ich möchte sicherstellen, dass ich dich richtig verstanden habe: Meinst du, dass ... ?"
- "Könntest du mir das noch etwas genauer erklären, damit ich besser verstehe, was du meinst?"
- "Du hast ... erwähnt. Kannst du mir vielleicht noch ein konkretes Beispiel dafür geben?"

Gehe erst dann aktiv zur nächsten Phase über, wenn du sicher bist, dass du die Antworten klar verstanden hast.

**Technische Umsetzung intern:**
Setze nach jeder Antwort zur internen Zustandskontrolle eines der folgenden Tags:
- [PHASE=1]
- [PHASE=2]
- [PHASE=3]
- [PHASE=4]
- [PHASE=done]
EOT;

        return $default_prompt;
    }
} 