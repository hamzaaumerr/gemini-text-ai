<?php
/*
Plugin Name: Gemini Text AI
Description: Text Only Input Gemini AI.
Version: 1.0
Author: Hamza Umer
Author URI: http://example.com
License: GPL2
*/

// Add a menu item to the dashboard for the plugin settings
function gemini_menu() {
    add_menu_page(
        'Gemini Ai Settings',
        'Gemini Ai',
        'manage_options',
        'gemini-settings',
        'gemini_settings_page'
    );
}
add_action('admin_menu', 'gemini_menu');

// Callback function for rendering the plugin settings page
function gemini_settings_page() {
    ?>
    <div class="wrap">
        <h2>Gemini Ai Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('gemini_options'); ?>
            <?php do_settings_sections('gemini-settings'); ?>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>">
        </form>
    </div>
    <?php
}

// Register and define the settings
function gemini_register_settings() {
    register_setting('gemini_options', 'gemini_api_key');
    add_settings_section(
        'gemini_section',
        '',
        '',
        'gemini-settings'
    );
    add_settings_field(
        'gemini_api_key',
        'Gemini API Key',
        'gemini_api_key_callback',
        'gemini-settings',
        'gemini_section'
    );
}
add_action('admin_init', 'gemini_register_settings');

// Callback function for rendering the API key field
function gemini_api_key_callback() {
    $apiKey = get_option('gemini_api_key');
    echo '<input type="password" id="gemini_api_key" name="gemini_api_key" value="' . esc_attr($apiKey) . '" />';
}

// Function to generate content using Google API
function generate_content_using_google_api($text) {
    // Get the Gemini API Key from settings
    $googleApiKey = get_option('gemini_api_key');

    // Check if API key is empty
    if (empty($googleApiKey)) {
        echo "Error: Gemini API Key is not set. Please set the API key in the plugin settings.";
        return;
    }

    // Prepare the request data
    $requestData = array(
        "contents" => array(
            array(
                "parts" => array(
                    array(
                        "text" => $text // Use the input text
                    )
                )
            )
        )
    );

    // Convert request data to JSON
    $jsonData = json_encode($requestData);

    // Set up the request arguments
    $args = array(
        'headers'     => array(
            'Content-Type' => 'application/json',
        ),
        'body'        => $jsonData,
        'timeout'     => 30,
    );

    // Set the URL with the API key
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $googleApiKey;

    // Make the request
    $response = wp_remote_post($url, $args);

    // Check if request was successful
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        // Get the response body
        $body = wp_remote_retrieve_body($response);
        // Decode the JSON response
        $data = json_decode($body, true);
        // Check if data is valid
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            // Extract and display the text
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
            echo $generatedText;
        } else {
            echo "Error: Unable to retrieve generated text from API response.";
        }
    } else {
        // Handle error
        if (is_wp_error($response)) {
            echo "Error: " . $response->get_error_message();
        } else {
            echo "Error: " . $response['response']['code'];
        }
    }
}

function enqueue_bootstrap() {
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap');

function enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Shortcode to display the form for inputting text
function gemini_form_shortcode() {
    ob_start();
    ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <form id="gemini-form" method="post">
                    <div class="form-group">
                        <label for="text">Enter your text</label>
                        <textarea class="form-control" id="text" name="text" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <div id="gemini-result"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#gemini-form').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: formData + '&action=generate_gemini_content',
                success: function(response) {
                    $('#gemini-result').html(response);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function generate_content_using_google_api_callback() {
    if (isset($_POST['text'])) {
        $text = sanitize_text_field($_POST['text']);
        generate_content_using_google_api($text);
    }
    exit;
}

add_shortcode('gemini_form', 'gemini_form_shortcode');
add_action('wp_ajax_generate_gemini_content', 'generate_content_using_google_api_callback');
add_action('wp_ajax_nopriv_generate_gemini_content', 'generate_content_using_google_api_callback');

?>
