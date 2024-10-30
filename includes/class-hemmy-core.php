<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 */
class Hemmy_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Hemmy_Core_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		
		$this->plugin_name = 'hemmy-core';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_hemmy_core_widget_hooks();
		$this->define_hemmy_core_metabox_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Hemmy_Core_Loader. Orchestrates the hooks of the plugin.
	 * - Hemmy_Core_i18n. Defines internationalization functionality.
	 * - Hemmy_Core_Admin. Defines all hooks for the admin area.
	 * - Hemmy_Core_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hemmy-core-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hemmy-core-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the widget area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'widgets/class-hemmy-core-widgets.php';
		
		/**
		 * The class responsible for defining all actions that occur in the metabox.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hemmy-core-metabox.php';


		$this->loader = new Hemmy_Core_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Hemmy_Core_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Hemmy_Core_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_hemmy_core_widget_hooks() {

		$plugin_admin = new Hemmy_Core_Widget( $this->get_hemmy_core(), $this->get_version() );
		
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_admin, 'widget_enqueue_scripts' );
		
		$this->loader->add_action('widgets_init', $plugin_admin, 'hemmy_widget_register');
		
	}
	
	
	private function define_hemmy_core_metabox_hooks() {
	
		$plugin_admin = new Hemmy_Core_MetaBox( $this->get_hemmy_core(), $this->get_version() );
		
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'metabox_enqueue_scripts' );		
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'hemmy_post_options_meta_box');
		$this->loader->add_action('save_post', $plugin_admin, 'hemmy_post_options_save');
	
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_hemmy_core() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Hemmy_Core_Loader  Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
