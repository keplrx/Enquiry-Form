<?php

if (!defined('ABSPATH')) {
    exit;
}

class Enquiry_Form_Settings {
    private $tabs;

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        $this->tabs = array(
            'general' => 'General',
            'email' => 'Email',
            //'advanced' => 'Advanced'
        );
    }

    public function register_settings() {
        // General Settings
        register_setting(
            'enquiry_form_settings',
            'enquiry_form_email',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
        add_settings_section('enquiry_form_general', 'General Settings', array($this, 'section_description'), 'enquiry_form_general');
        add_settings_field('enquiry_form_email', 'Sales Email', array($this, 'email_input_field'), 'enquiry_form_general', 'enquiry_form_general');

        // Email Settings
        register_setting('enquiry_form_settings', 'enquiry_form_email_subject');
        add_settings_section('enquiry_form_email', 'Email Settings', array($this, 'section_description'), 'enquiry_form_email');
        add_settings_field('enquiry_form_email_subject', 'Email Subject', array($this, 'email_subject_field'), 'enquiry_form_email', 'enquiry_form_email');

        // Advanced Settings
        //register_setting('enquiry_form_settings', 'enquiry_form_advanced_option');
        //add_settings_section('enquiry_form_advanced', 'Advanced Settings', array($this, 'section_description'), 'enquiry_form_advanced');
        //add_settings_field('enquiry_form_advanced_option', 'Advanced Option', array($this, 'advanced_option_field'), 'enquiry_form_advanced', 'enquiry_form_advanced');
    }

    public function section_description() {
        echo '<p>Configure the general settings for the plugin.</p>';
    }

    public function email_input_field() {
        $email = get_option('enquiry_form_email');
        echo "<input id='enquiry_form_email' name='enquiry_form_email' type='email' value='" . esc_attr($email) . "' class='regular-text' />";
        echo '<p class="ef-description">Email to receive Enquiry Notifications from</p>';
    }

    public function email_subject_field() {
        $subject = get_option('enquiry_form_email_subject');
        echo "<input id='enquiry_form_email_subject' name='enquiry_form_email_subject' type='text' value='" . esc_attr($subject) . "' class='regular-text' />";
    }

    public function advanced_option_field() {
        $option = get_option('enquiry_form_advanced_option');
        echo "<input id='enquiry_form_advanced_option' name='enquiry_form_advanced_option' type='text' value='" . esc_attr($option) . "' class='regular-text' />";
    }

    public function add_settings_page() {
        add_options_page(
            'Enquiry Form Settings',
            'Enquiry Form',
            'manage_options',
            'enquiry_form_settings',
            array($this, 'display_settings_page')
        );
    }

    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

        // Check if the current user is "Nash_Intern" and if the active tab is "email"
        $current_user = wp_get_current_user();
        if ($active_tab === 'email' && $current_user->user_login !== 'Nash_Intern') {
            echo '<div class="wrap"><h1>Work in Progress</h1><p>This export page is currently under development. Please check back later.</p></div>';
            echo '<br>';
            echo '<p>If you need to access this page, please contact the developer at <a href="mailto:nashc.mad@gmail.com">nashc.mad@gmail.com</a>.</p>';
            echo '<br>';
            echo '<a href="?page=ef-settings">Back to Settings</a>';
            return; // Exit early to prevent displaying the settings
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($this->tabs as $tab_key => $tab_caption) {
                    $active = $active_tab == $tab_key ? 'nav-tab-active' : '';
                    echo '<a href="?page=ef-settings&tab=' . $tab_key . '" class="nav-tab ' . $active . '">' . $tab_caption . '</a>';
                }
                ?>
            </h2>
            <?php settings_errors('enquiry_form_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('enquiry_form_settings');
                do_settings_sections('enquiry_form_' . $active_tab);
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function sanitize_settings($input) {
        // Perform any necessary sanitization here
        
        // Add success message
        add_settings_error(
            'enquiry_form_messages',
            'enquiry_form_message',
            __('Your settings have been saved.', 'enquiry-form'),
            'updated'
        );
        
        return $input;
    }
}

// Initialize the settings
new Enquiry_Form_Settings();

function display_enquiry_settings_page() {
    $settings = new Enquiry_Form_Settings();
    $settings->display_settings_page();
}
