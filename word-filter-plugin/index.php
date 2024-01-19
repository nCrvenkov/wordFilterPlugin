<?php

/*
    Plugin Name: Word Filter
    Description: Replaces a list of words.
    Version: 1.0
    Author: Nikola Crvenkov
    Author URI: https://github.com/nCrvenkov
*/

if( ! defined('ABSPATH')) exit; // Exit if accessed directly

class OurWordFilterPlugin{
    function __construct(){
        // to register a distinct plugin menu
        add_action('admin_menu', array($this, 'ourMenu'));
        // to handle the functionality of the plugin
        if(get_option('plugin_words_to_filter')) add_filter('the_content', array($this, 'filterLogic'));
        // to add settings WordPress way (for the second plugin options page)
        add_action('admin_init', array($this, 'ourSettings'));
    }

    // Actual functionality of the plugin
    function filterLogic($content){
        // all functions are PHP native
        $badWords = explode(',', get_option('plugin_words_to_filter'));
        $badWordsTrimmed = array_map('trim', $badWords);
        $replacementText = esc_html(get_option('replacementText', '***'));

        return str_ireplace($badWordsTrimmed, $replacementText, $content);
    }

    function ourMenu(){
        //              Page title,   Admin sidebar title, Capabilities,       Slug,               Function,                        Icon,                   Placing in sidebar
        //add_menu_page('Words To Filter', 'Word Filter', 'manage_options', 'ourwordfilter', array($this, 'wordFilterPage'), 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMCAyMEMxNS41MjI5IDIwIDIwIDE1LjUyMjkgMjAgMTBDMjAgNC40NzcxNCAxNS41MjI5IDAgMTAgMEM0LjQ3NzE0IDAgMCA0LjQ3NzE0IDAgMTBDMCAxNS41MjI5IDQuNDc3MTQgMjAgMTAgMjBaTTExLjk5IDcuNDQ2NjZMMTAuMDc4MSAxLjU2MjVMOC4xNjYyNiA3LjQ0NjY2SDEuOTc5MjhMNi45ODQ2NSAxMS4wODMzTDUuMDcyNzUgMTYuOTY3NEwxMC4wNzgxIDEzLjMzMDhMMTUuMDgzNSAxNi45Njc0TDEzLjE3MTYgMTEuMDgzM0wxOC4xNzcgNy40NDY2NkgxMS45OVoiIGZpbGw9IiNGRkRGOEQiLz4KPC9zdmc+', 100);
        $mainPageHook = add_menu_page('Words To Filter', 'Word Filter', 'manage_options', 'ourwordfilter', array($this, 'wordFilterPage'), plugin_dir_url(__FILE__) . 'custom.svg', 100);
        add_submenu_page('ourwordfilter', 'Words To Filter', 'Words List', 'manage_options', 'ourwordfilter', array($this, 'wordFilterPage')); // To change the first submenu link
        add_submenu_page('ourwordfilter', 'Word Filter Options', 'Options', 'manage_options', 'word-filter-options', array($this, 'optionsSubPage'));
        add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
    }

    // To load CSS from the plugin folder
    function mainPageAssets(){
        wp_enqueue_style('filterAdminCss', plugin_dir_url(__FILE__) . 'styles.css');
    }

    // HTMl of the Words List page
    function wordFilterPage(){ ?>
        <div class="wrap">
            <h1>Word Filter</h1>
            <?php
                    if($_POST['justsubmitted'] == true){
                        $this->handleForm();
                    } 
                ?>
            <form method="POST">
                <input type="hidden" name="justsubmitted" value="true">
                <?php wp_nonce_field('saveFilterWords', 'ourNonce'); ?> 
                <label for="plugin_words_to_filter"><p>Enter a <strong>comma-separated</strong> list of words to filter from your site's content.</p></label>
                <div class="word-filter__flex-container">
                    <textarea name="plugin_words_to_filter" id="plugin_words_to_filter" placeholder="bad, mean, awful"><?= esc_textarea(get_option('plugin_words_to_filter')); ?></textarea>
                </div>
                <input type="submit" id="submit" class="button button-primary" value="Save Changes">
            </form>
        </div>
    <?php }

    // function to handle the settings on the Words List page
    function handleForm(){
        if(wp_verify_nonce($_POST['ourNonce'], 'saveFilterWords') && current_user_can('manage_options')){
            update_option('plugin_words_to_filter', sanitize_text_field($_POST['plugin_words_to_filter'])); ?>
            <div class="updated">
                <p>Your filtered words were saved.</p>
            </div>
        
        <?php }
        else{ ?>
            <div class="error">
                <p>Sorry, you do not have permission to perform that action.</p>
            </div>
    <?php }
    }

    //////////////////////////////////////////////////////////////////////////////////////// Second plugin page handled down

    function ourSettings(){
        add_settings_section('replacement_text_section', null, null, 'word-filter-options');

        register_setting('replacementFields', 'replacementText');
        add_settings_field('replacement-text', 'Filtered Text', array($this, 'replacementFieldHTML'), 'word-filter-options', 'replacement_text_section');
    }

    function replacementFieldHTML(){ ?>
        <input type="text" name="replacementText" value="<?= esc_attr(get_option('replacementText', '***')); ?>">
        <p class="description">Leave blank to simply remove the filtered words.</p>
    <?php }

    function optionsSubPage(){ ?>
        <div class="wrap">
            <h1>Word Filter Options</h1>
            <form action="options.php" method="POST">
                <?php
                    // since we are not in the WP Settings menu, this function needs to be called
                    settings_errors();
                    // to handle all fields
                    settings_fields('replacementFields');
                    // to call the created section on the slug location
                    do_settings_sections('word-filter-options');
                    submit_button();
                ?>
            </form>
        </div>
    <?php }
}

$ourWordFilterPlugin = new OurWordFilterPlugin();