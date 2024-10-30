<?php
/*
Plugin Name: Contact Form Counter
Plugin URI:https://en-ca.wordpress.org/plugins/contact-form-counter/
Description:A discrete plugin to add counter/time/timestamp to mails received with shortcodes that can be added anywhere on your mail
Version: 1.0.4
Author: StorePro
Author URI:https://storepro.io/
Text Domain: contact-form-counter
Domain Path: /languages
*/
// If this file is called firectly, abort!!!
defined('ABSPATH') or die('Hey, what are you doing here?');

/**
 * Check if Contact Form 7 is active. if it isn't, disable Contact Form Counter.
 */

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {

    function mcf_is_cf7_plugin_active()
    {
    ?>
        <div class="error notice">
            <p><?php esc_html_e('Contact Form Counter is inactive.Contact Form 7 plugin must be active for Contact Form Counter to work. Please install & activate Contact Form 7 '); ?></p>
        </div>
    <?php
    }
    add_action('admin_notices', 'mcf_is_cf7_plugin_active');
    deactivate_plugins(plugin_basename(__FILE__));
    unset($_GET['activate']);
    return;
}

//Add plugin settings link for ease access
function mcf__action_links( $links ) {

	$links = array_merge( array(
		'<a href="' . esc_url( admin_url( '/options-general.php?page=contact-form-counter' ) ) . '" >' . __( 'Settings', 'contact-form-counter' ) . '</a>'
	), $links );

	return $links;

}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mcf__action_links' );

//define a constant
define('CONTACTFORM_COUNTER', untrailingslashit(dirname(__FILE__)));

//require the listing the file
require_once CONTACTFORM_COUNTER . '/includes/contact-list_table.php';


/**
 * Here starting plugin main functionality
 */


//Main class start here
class ContactFormCounter
{
    private $options;
    const OPTION_SAVE_FILE = 'cfccf7_options.txt';
    const DOMAIN = 'contact-form-counter';
    
    public function __construct()
    {
        $this->options = $this->get_plugin_options();

        if (function_exists('register_activation_hook'))
            register_activation_hook(__FILE__, array(&$this, 'mcf_activation'));
        if (function_exists('register_deactivation_hook'))
            register_deactivation_hook(__FILE__, array(&$this, 'mcf_deactivation'));

            
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_filter('wpcf7_special_mail_tags', array(&$this, 'mcf_special_mail_tags'), 10, 2);
        add_filter('wpcf7_posted_data',       array(&$this, 'mcf_add_serial_number_to_posted_data'), 10, 1);
        add_shortcode('cfc_view_count', array(&$this, 'mcf_view_serial_number'));
        add_action('admin_enqueue_scripts', array($this, 'mail_conter_add_style'));
        add_action('wpcf7_mail_sent', array(&$this, 'mcf_increment_count'));
        // load_plugin_textdomain(self::DOMAIN, false, basename(dirname(__FILE__)) . '/languages');
    }

    //data submission here
    public function handle_form_submission()
    {
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'mcf_cf7_settings') {
            // Exit for non-capable users
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
    
            check_admin_referer('mcf_cf7_settings-options');
    
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    
            if ($form_id > 0) {
            
    
                // Update individual options only if they are set
                $settings_prefix = 'mcf_cf7_settings';
    
                if (isset($_POST[$settings_prefix]['type_' . $form_id])) {
                    update_option('mcf_cf7_type' . $form_id, intval($_POST[$settings_prefix]['type_' . $form_id]));
                }
    
                $count = null;
                if (isset($_POST[$settings_prefix]['count_' . $form_id])) {
                    $count = intval($_POST[$settings_prefix]['count_' . $form_id]);
                    update_option('mcf_cf7_count' . $form_id, $count);
                }
    
                $digits = null;
                if (isset($_POST[$settings_prefix]['digits_' . $form_id])) {
                    $digits = intval($_POST[$settings_prefix]['digits_' . $form_id]);
                }
    
                // Check if count is set and digits is less than count length
                if ($count !== null) {
                    $count_length = strlen((string)$count);
                    if ($digits === null || $digits < $count_length) {
                        $digits = $count_length;
                    }
                    update_option('mcf_cf7_digits' . $form_id, $digits);
                } elseif ($digits !== null) {
                    update_option('mcf_cf7_digits' . $form_id, $digits);
                }
    
                if (isset($_POST[$settings_prefix]['prefix_' . $form_id])) {
                    update_option('mcf_cf7_prefix' . $form_id, sanitize_text_field($_POST[$settings_prefix]['prefix_' . $form_id]));
                }
    
                // Set a transient to show the success message
                set_transient('mcf_cf7_settings_updated', true, 30);
    
                // Redirect to the same page to prevent form resubmission
                wp_redirect(add_query_arg('page', 'contact-form-counter', admin_url('options-general.php')));
                exit;
            } 
        }
    }

    //show settings
    public function display_settings_page()
    {
        if (get_transient('mcf_cf7_settings_updated')) {
            add_settings_error('mcf_cf7_settings', 'settings_updated', __('Settings saved.'), 'updated');
            delete_transient('mcf_cf7_settings_updated');
        }
        settings_errors('mcf_cf7_settings');
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <span class="sp_contact_branding">
        <span class="me-2"> <a href="https://wordpress.org/plugins/contact-form-counter/" style="text-decoration: none" target="_blank">Contact Form Counter </a> | Plugin Developed By</span>
        <a href="http://storepro.io/" target="_blank"> <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/img/storepro-logo.png"></a></span>
            <?php
            $list_table = new MCF_WPCF7_Listings();
            $list_table->prepare_items();
            $list_table->display();
            ?>
        </div>
<?php
    }
    //register settings here
    public function register_settings()
    {
        register_setting(
            'mcf_cf7_settings',
            'mcf_cf7_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }
    //we sanitize before register-as always!
    public function sanitize_settings($input)
    {
        // Implement sanitization logic here
        return $input;
    }
    // plugin activation
    public function mcf_activation() {
        $option_file = dirname( __FILE__ ) . '/' . self::OPTION_SAVE_FILE;
        if ( file_exists( $option_file ) ) {
            $wk_options = unserialize( file_get_contents( $option_file ) );
            if ( $wk_options != $this->options ) {
                
                $this->options = $wk_options;

                foreach ( $this->options as $key=>$value ) {
                    update_option( $key, $value );
                }

                unlink( $option_file );
            }
        }
    }
    // plugin deactivation
    public function mcf_deactivation() {
        $option_file = dirname( __FILE__ ) . '/' . self::OPTION_SAVE_FILE;
        $wk_options = serialize( $this->options );
        if ( file_put_contents( $option_file, $wk_options ) && file_exists( $option_file ) ) {
            foreach( $this->options as $key=>$value ) {
                delete_option( $key );
            }
        }
    }
    // get plugin options
    public  function get_plugin_options()
    {
        global $wpdb;
        $values = array();
        $results = $wpdb->get_results("
            SELECT *
              FROM $wpdb->options
             WHERE 1 = 1
               AND option_name like 'mcf_cf7_%'
             ORDER BY option_name
        ");
        foreach ($results as $result) {
            $values[$result->option_name] = $result->option_value;
        }
        return $values;
    }
    public function mail_conter_add_style($hook)
    {
        $current_screen = get_current_screen();
        if (strpos($current_screen->base, 'contact-form-counter') === false) {
            return;
        } else {
            wp_enqueue_style('contact-form-counter', plugin_dir_url(__FILE__) . 'assets/css/style.css');
            wp_enqueue_script('contact-form-counter-admin', plugin_dir_url(__FILE__) . 'assets/js/mcf_js.js', array('jquery'), '1.0', true);
        }
    }
    // admin menu call here
    public function admin_menu()
    {
        add_options_page(
            __('Contact Form Counter', self::DOMAIN),
            __('Contact Form Counter', self::DOMAIN),
            'manage_options',
            'contact-form-counter',
            array($this, 'display_settings_page')
        );
    }
    // increment count
    public  function mcf_increment_count($contactform)
    {
        $id = intval($contactform->id());

        //count
        $count= get_option('mcf_cf7_count' . $id, 1);
        $count = isset($count) ? intval($count) : 1;
       
        $options = get_option('mcf_cf7_settings', array());
        if (isset($options['type_' . $id]) && $options['type_' . $id] == 1) {
            $count = isset($options['count_' . $id]) ? intval($options['count_' . $id]) : 0;
            $options['count_' . $id] = $count + 1;
            update_option('mcf_cf7_settings', $options);
        }
        $incrmeneted_count = $count + 1;
            update_option( 'mcf_cf7_count' . $id, intval($incrmeneted_count) );

    }
    // is active plugin
    public  function is_active_plugin($plugin)
    {
        if (function_exists('is_plugin_active')) {
            return is_plugin_active($plugin);
        } else {
            return in_array($plugin, get_option('active_plugins'));
        }
    }
    // special mail tags
    public function mcf_special_mail_tags($output, $name)
    {
        if (!isset($_POST['_wpcf7_unit_tag']) || empty($_POST['_wpcf7_unit_tag'])) {
            return $output;
        }
    
        $name = preg_replace('/^wpcf7\./', '_', $name);
        if ('cfc_serial_number_' !== substr($name, 0, 18)) {
            return $output;
        }
    
        $id = intval(substr($name, 18));
    
        $type = intval(get_option('mcf_cf7_type' . $id, 1));
        $digits = max(1, intval(get_option('mcf_cf7_digits' . $id, 1)));
        $prefix = sanitize_text_field(get_option('mcf_cf7_prefix' . $id, ''));
        $count = max(1, intval(get_option('mcf_cf7_count' . $id, 1)));
    
        switch ($type) {
            case 1:
                // Serial Number
                $count_length = strlen((string)$count);
                if ($digits < $count_length) {
                    // Update the digits option to match the count length
                    update_option('mcf_cf7_digits' . $id, $count_length);
                    $digits = $count_length;
                }
                $output = str_pad($count, $digits, '0', STR_PAD_LEFT);
                break;
            case 2:
                // Timestamp (ms)
                $output = round(microtime(true) * 10000);
                break;
            case 3:
                // Date & Time
                $output = date("Y/m/d H:i:s");
                break;
            default:
                $output = '';
        }
    
        $output = $prefix . $output;
    
        // Store the output for potential use in shortcode
        if (isset($_SESSION)) {
            $_SESSION['cfc_output_' . $id] = $output;
        } else {
            setcookie('cfc_output_' . $id, $output, time() + 60, '/', '', true, true);
        }
    
        return $output;
    }
    // add serial number to posted data
    public function mcf_add_serial_number_to_posted_data($posted_data)
    {
        if (!empty($posted_data)) {
            $id = intval($_POST['_wpcf7']);
            $digits = (get_option('mcf_cf7_digits' . $id)) ? intval(get_option('mcf_cf7_digits' . $id)) : 0;
            $type   = (get_option('mcf_cf7_type' . $id)) ? intval(get_option('mcf_cf7_type' . $id)) : 1;
            $prefix = (get_option('mcf_cf7_prefix' . $id)) ? get_option('mcf_cf7_prefix' . $id) : '';
            $count  = (get_option('mcf_cf7_count' . $id)) ? intval(get_option('mcf_cf7_count' . $id)) : 0;
            switch ($type) {
                case 1:
                    $output = $count + 1;
                    if ($digits) {
                        $output = sprintf("%0" . $digits . "d", $output);
                    }
                    break;
                case 2:
                    $output = microtime(true) * 10000;
                    break;
                case 3:
                    $output = date("Y/m/d h:i:s");
                    break;
                default:
                    $output = '';
            }
            $output = $prefix . $output;
            $posted_data['Serial Number'] = $output;
        }
        return $posted_data;
    }
    // ShortCode
    public function mcf_view_serial_number($atts)
    {
        extract(shortcode_atts(array(
            'id' => 0,
        ), $atts));
        if (isset($_SESSION['cfc_output_' . $id])) {
            $output = sanitize_text_field($_SESSION['cfc_output_' . $id]);
        } else {
            if (isset($_COOKIE['cfc_output_' . $id])) {
                $output = sanitize_text_field($_COOKIE['cfc_output_' . $id]);
            } else {
                $digits = (get_option('mcf_cf7_digits' . $id)) ? intval(get_option('mcf_cf7_digits' . $id)) : 0;
                $type   = (get_option('mcf_cf7_type' . $id)) ? intval(get_option('mcf_cf7_type' . $id)) : 1;
                $prefix = (get_option('mcf_cf7_prefix' . $id)) ? get_option('mcf_cf7_prefix' . $id) : '';
                $count  = (get_option('mcf_cf7_count' . $id)) ? intval(get_option('mcf_cf7_count' . $id)) : 0;
                switch ($type) {
                    case 1:
                        $output = $count;
                        if ($digits) {
                            $output = sprintf("%0" . $digits . "d", $output);
                        }
                        break;
                    case 2:
                        $output = microtime(true) * 10000;
                        break;
                    case 3:
                        $output = date("Y/m/d h:i:s");
                        break;
                    default:
                        $output = '';
                }
                $output = $prefix . $output;
            }
        }
        return "$output";
    }
}
//initialise the plugin here
$MCF_WPCF7_MailCounter = new ContactFormCounter();

//thats all now