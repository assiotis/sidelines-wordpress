<?php
/*
Plugin Name: Sidelines Widget
Plugin URI: http://sidelinesapp.com/
Description: The Sidelines widget allows you to increase engagement by embedding Sidelines discussions right on your content
Author: Marios <marios@sidelinesapp.com>
Version: 0.8
Author URI: http://sidelinesapp.com/
*/




/*
 * Actual code for setting up the widget and replacing the comments
 * section if necessary
 */
class Sidelines {
    protected $version = '0.8';
    protected $plugin_slug = 'sidelines';
    protected static $instance = null;

    // variable to avoid showing the widget more than once
    private $content_shown=false;

    const NONCE_STR = 'sidelines-rest-api-nonce';
    const METADATA_KEY = 'sidelines-post';
    const DEFAULT_DISPLAY_MODE = 'content';


    /**
     * Queue up all the JS using standard wordpress functions. Wordpress will 
     * take care of inserting the <script></script> tags where necessary. We 
     * also inject some metadata our JS loader needs as JSON using the 
     * localize_script technique. This will create a JSON structure that looks 
     * something like var SidelinesRestApi={"createPostNonce":"63c01aaf21","pubCode":"asdasdasd","postID":"1","permalink":"http:\/\/youriste.com\/wordpress\/2014\/hello-world\/","remoteUrl":"sidelinesapp.com","version":"0.8"};
     */
    function setup_js()
    {
        $current_post = get_post();

        wp_enqueue_script('lightningjs', plugin_dir_url( __FILE__ ) . 'js/lightningjs-embed.js', false, $this->version, true);
        wp_enqueue_script('sidelines-widget', plugin_dir_url( __FILE__ ) . 'js/sidelines.js', array('lightningjs'), $this->version, true);
        wp_localize_script('sidelines-widget', 'SidelinesRestApi', 
            array('createPostNonce' => wp_create_nonce(self::NONCE_STR),
            'pubCode' => get_option('sidelines_publisher_code'),
            'postID' => get_post()->ID,
            'permalink' => get_permalink(),
            'remoteUrl' => $this->get_sidelines_host(),
            'version' => $this->version
        ));
    }


    /**
     * Construct an instance of the plugin and initialize all necessary hooks.
     *
     */
    private function __construct() {
        add_filter('comments_template', array($this, 'sidelines_comments_template'), 11);
        add_filter('the_content', array($this, 'sidelines_content_template'));
        add_action('admin_menu', array($this, 'sidelines_admin_menu'));
        add_action('admin_init', array($this, 'sidelines_settings'));
        add_action('wp_enqueue_scripts', array($this, 'setup_js'));            
        add_action('init', array($this, 'activate_autoupdate'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate' ));    
    }


    /**
     * Singleton
     */
    public static function get_instance() {
        if (null == self::$instance) { 
            self::$instance = new self; 
        }

        return self::$instance;
    }

    /**
     * Older versions (< 0.6) relied on a cron job to poll Sidelines for new 
     * posts. If the cron job exists, clear it out
     */
    public function activate() 
    {
        wp_clear_scheduled_hook('sdlnscron');
    }

    public function deactivate() 
    {
        wp_clear_scheduled_hook('sdlnscron');
    }

    /**
     * Utility function that tries to detect the presence of popular WP plugins and clear the cache 
     * for the specific post. 
     */
    function clear_wp_caches($post_id) {
        if (function_exists('w3tc_pgcache_flush_post')) { w3tc_pgcache_flush_post($post_id); }
        if (function_exists ('wp_cache_post_change')) {
            $GLOBALS["super_cache_enabled"]=1;
            wp_cache_post_change($post_id);
        }
    }

    /*
     * Load the class responsible for the private repo auto-update
     */
    function activate_autoupdate() {
        require_once ('wp_autoupdate.php');
        $wptuts_plugin_current_version = $this->get_version();
        $wptuts_plugin_remote_path = 'http://' . $this->get_sidelines_host()  . '/api/widget';
        $wptuts_plugin_slug = plugin_basename(__FILE__);
        new wp_auto_update ($wptuts_plugin_current_version, $wptuts_plugin_remote_path, $wptuts_plugin_slug);
    }


    /*
     * Check if the post is eligible to have a Sidelines conversation shown. We 
     * don't want to show up on a feed or on drafts.
     * 
     *
     */
    function sidelines_replace_allowed() {
        $current_post = get_post();
        
        if (is_feed()) { 
            return false; 
        }

        if ('draft' == $current_post->post_status)   { 
            return false; 
        }

        if (!get_option('sidelines_enabled', true)) {
            return false;
        }
        return true;
    }


    /**
     * Sidelines has two modes of integration. In this mode, we replace 
     * whatever default commenting system by instructing Wordpress to use our 
     * comments.php when it needs a commenting template
     */
    function sidelines_comments_template($value) {
        global $post;
        global $comments;

        if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
            return;
        }

        if (get_option('sidelines_mode', self::DEFAULT_DISPLAY_MODE) != 'comments') {
            return $value;
        }

        if (!$this->sidelines_replace_allowed()) {
            return $value;
        }

        return dirname(__FILE__) . '/comments.php';
    }


    /**
     * In this mode of integration, Sidelines is inserted right at the end of 
     * the main article
     */
    function sidelines_content_template($content) {
        global $post;
        global $comments;

        if ( !( is_singular() )) {
            return $content;
        }

        if (!$this->sidelines_replace_allowed()) {
            return $content;
        }
        
        if (get_option('sidelines_mode', self::DEFAULT_DISPLAY_MODE) != 'content') {
            return $content;
        }

        # Disabled: 08/11/14 to deal with the_excerpt() also calling 
        # the_content filter
        # if ($this->content_shown) {
        #     return $content;
        # }

        $this->content_shown = true;

        ob_start();
        include(dirname(__FILE__) . '/comments.php');
        $page = ob_get_clean();

        $content .= $page;
        return $content;
    }

    /**
     * Use the standard wordpress settings API to define a few options
     */
    public function sidelines_settings($content) {
        register_setting('sidelines_options', 'sidelines_host');
        register_setting('sidelines_options', 'sidelines_publisher_code');
        register_setting('sidelines_options', 'sidelines_enabled');
        register_setting('sidelines_options', 'sidelines_mode');
        wp_register_style('sidelinesSettingsStyle', plugin_dir_url( __FILE__ ) . 'css/sidelines-admin.css');
    }

    public function sidelines_admin_menu() {
        $this->plugin_screen_hook_suffix = add_options_page(
            __('Sidelines Options', $this->plugin_slug),
            __('Sidelines', $this->plugin_slug),
            'manage_options', 
            $this->plugin_slug, 
            array($this, 'sidelines_admin')
        );

        add_action('admin_print_styles-'.$this->plugin_screen_hook_suffix, array($this, 'sidelines_admin_setup_header'));
    }

    /*
     * Print the styles for the settings page which is registered in 
     * `sidelines_settings`
     */
    public function sidelines_admin_setup_header() {
        wp_enqueue_style('sidelinesSettingsStyle');
    }

    public function sidelines_admin() {
        include_once('settings.php');
    }

    public function get_sidelines_host() {
        $host = get_option('sidelines_host');
        if (!isset($host) || trim($host)==='') {
            return 'sidelinesapp.com';
        } else {
            return $host;
        }
    }

    public function get_version() {
        return $this->version;
    }
}

Sidelines::get_instance();

?>
