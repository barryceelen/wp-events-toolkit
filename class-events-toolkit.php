<?php
/**
 * Events Toolkit.
 *
 * @package   Events_Toolkit
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-2.0+
 * @link      http://github.com/barryceelen/wp-events-toolkit
 * @copyright 2013 Barry Ceelen
 */

/**
 * Plugin class.
 *
 * @package Events_Toolkit
 * @author  Barry Ceelen <b@rryceelen.com>
 * @todo    Date and time validation when saving events
 */
class Events_Toolkit {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since    0.0.1
	 *
	 * @var      string
	 */
	const VERSION = '0.0.1';

	/**
	 * Unique identifier.
	 *
	 * @since    0.0.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'events-toolkit';

	/**
	 * Instance of this class.
	 *
	 * @since    0.0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Default settings.
	 *
	 * @since    0.0.1
	 *
	 * @var      array
	 */
	protected static $default_options = array(
		'settings_version'    => '1',     // Settings version number
		'event_post_type'     => 'event',
		'hierarchical'        => false,   // Create hierarchical event post type
		'all_day_disable'     => true,   // Disable all day events (Not yet implemented)
		'default_all_day'     => true,    // 'All day' is checked by default
		'default_start_time'  => '10:00',
		'default_end_time'    => '17:00',
		'clock'               => 'auto',  // 12, 24 or auto
		'register_categories' => false,   // Register categories (Not yet implemented)
		'register_tags'       => false,   // Register tags (Not yet implemented)
		'menu_position'       => 8        // Admin menu position
	);

	/**
	 * Filterable settings
	 *
	 * @since  0.0.1
	 *
	 * @var array
	 */
	public static $options = array();

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.0.1
	 */
	private function __construct() {

		self::$options = apply_filters( 'events_toolkit_options', self::$default_options );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Register custom post type and filter messages
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		// Add events to 'Right Now' dashboard widget
		add_action( 'right_now_content_table_end' , array( $this, 'right_now_content_table_end' ) );

		// Add meta box(es)
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_date' ), 10, 2 );

		// Add query vars for event filtering
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Modify requests to enable ordering and scope
		add_filter( 'request', array( $this, 'orderby_and_scope' ) );

		// Add filter to event list in admin
		add_action( 'restrict_manage_posts', array( $this, 'add_event_scope_select' ) );

		// Remove quick edit for non-hierarchical event post type
		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 1 );

		// Remove quick edit for hierarchical event post type
		add_filter( 'page_row_actions', array( $this, 'remove_quick_edit' ), 10, 1 );

		// Edit columns in admin table
		add_filter( 'manage_' . self::$options['event_post_type'] . '_posts_columns', array( $this, 'manage_columns' ) );
		add_action( 'manage_' . self::$options['event_post_type'] . '_posts_custom_column', array( $this, 'manage_columns_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::$options['event_post_type'] . '_sortable_columns', array( $this, 'make_columns_sortable' ) );

		// Event post type admin menu icon
		add_action( 'admin_head', array( $this, 'menu_icon' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.0.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide  ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}
				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}
				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.0.1
	 *
	 * @param	int	$blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) )
			return;

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.0.1
	 *
	 * @return	array|false	The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";
		return $wpdb->get_col( $sql );
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.0.1
	 */
	private static function single_activate() {
		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.0.1
	 */
	private static function single_deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.0.1
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		$screen = get_current_screen();

		if ( self::$options['event_post_type'] !== $screen->post_type ) {
			return;
		}

	  // Events overview page
		if ( 'edit' == $screen->base ) {
			wp_enqueue_style(
				$this->plugin_slug .'-admin-styles',
				plugins_url( 'css/admin.css', __FILE__ ),
				array(),
				self::VERSION
			);
		}

		// Event edit page
		if ( self::$options['event_post_type'] == $screen->id ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style(
				$this->plugin_slug .'-datepicker-styles',
				plugins_url( "/js/vendor/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom$suffix.css", __FILE__ ),
				array(),
				self::VERSION
			);
			wp_enqueue_style(
				$this->plugin_slug .'-admin-styles',
				plugins_url( 'css/admin.css', __FILE__ ),
				array(),
				self::VERSION
			);
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		$screen = get_current_screen();

		if ( self::$options['event_post_type'] !== $screen->id ) {
			return;
		}

		$regional = '';

		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Maybe load a localized version of jQuery UI
		// source: https://github.com/jquery/jquery-ui/tree/master/ui/i18n
		if ( 'en_US' != get_locale() ) {
			$regional = str_replace( '_', '-', get_locale() );
			$file = plugin_dir_path( __FILE__ ) . "js/vendor/jquery-ui/i18n/jquery.ui.datepicker-$regional.js";
			if ( is_readable( $file ) ) {
				wp_enqueue_script(
					$this->plugin_slug .'-datepicker-i18n',
					plugins_url( "/js/vendor/jquery-ui/i18n/jquery.ui.datepicker-$regional.js", __FILE__ ),
					array(),
					self::VERSION
				);
			}
		}

		// Enqueue admin.js
		wp_enqueue_script(
			$this->plugin_slug .'-admin-scripts',
			plugins_url( "/js/admin.js", __FILE__ ),
			array(),
			self::VERSION
		);

		// Add vars to page head
		wp_localize_script(
			$this->plugin_slug .'-admin-scripts',
			'eventsToolkitVars',
			array(
				'disableAllDay' => self::$options['all_day_disable'],
				'regional'      => $regional,
				'dateFormat'    => $this->date_format_php_to_jquery( get_option( 'date_format' ) )
			)
		);

	}

	/**
	 * Register post type.
	 *
	 * Arguments are filterable via 'events_toolkit_event_post_type_args'
	 * The default post type name 'event' is filterable via 'events_toolkit_event_post_type' in __construct.
	 *
	 * @since  0.0.1
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => __( 'Events', 'events-toolkit' ),
			'singular_name'      => __( 'Event', 'events-toolkit' ),
			'add_new'            => __( 'Add New', 'events-toolkit' ),
			'add_new_item'       => __( 'Add Event', 'events-toolkit' ),
			'edit_item'          => __( 'Edit Event', 'events-toolkit' ),
			'new_item'           => __( 'New Event', 'events-toolkit' ),
			'all_items'          => __( 'All Events', 'events-toolkit' ),
			'view_item'          => __( 'View Event', 'events-toolkit' ),
			'search_items'       => __( 'Search Events', 'events-toolkit' ),
			'not_found'          => __( 'No events found', 'events-toolkit' ),
			'not_found_in_trash' => __( 'No events found in the trash', 'events-toolkit' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Events', 'events-toolkit' )
			);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => self::$options['event_post_type'] ),
			'capability_type'     => 'post',
			'has_archive'         => 'events',
			'show_in_nav_menus'   => true,
			'hierarchical'        => self::$options['hierarchical'],
			'menu_position'       => self::$options['menu_position'],
			'exclude_from_search' => false,
			'supports'            => array( 'title', 'editor', 'thumbnail' )
			);

		$args = apply_filters( 'events_toolkit_event_post_type_args', $args );

		register_post_type( self::$options['event_post_type'], $args );
	}

	/**
	 * Filter display messages.
	 *
	 * Messages are filterable via 'post_updated_messages'.
	 *
	 * @since  0.0.1
	 */
	public function post_updated_messages( $messages ) {

		global $post, $post_ID;

		$messages[self::$options['event_post_type']] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Event updated. <a href="%s">View event</a>', 'events-toolkit' ), esc_url( get_permalink($post_ID) ) ),
			2 => __( 'Custom field updated.', 'events-toolkit' ),
			3 => __( 'Custom field deleted.', 'events-toolkit' ),
			4 => __( 'Event updated.', 'events-toolkit' ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s', 'events-toolkit' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Event published. <a href="%s">View event</a>', 'events-toolkit' ), esc_url( get_permalink($post_ID) ) ),
			7 => __( 'Event saved.', 'events-toolkit' ),
			8 => sprintf( __( 'Event submitted. <a target="_blank" href="%s">Preview event</a>', 'events-toolkit'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'events-toolkit' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __( 'Event draft updated. <a target="_blank" href="%s">Preview event</a>', 'events-toolkit' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			);

		return $messages;
	}

	/**
	 * Replace default admin menu and title icons.
	 *
	 * @since  0.0.1
	 */
	public function menu_icon() {
		$post_type = self::$options['event_post_type'];
		$images_url = plugins_url( 'images/', __FILE__ );
		require_once( plugin_dir_path( __FILE__ ) . 'templates/tmpl-css-menu-icon.php' );
	}

	/**
	 * Add events to 'Right Now' dashboard widget.
	 *
	 * via: http://wpsnipp.com/index.php/functions-php/include-custom-post-types-in-right-now-admin-dashboard-widget/
	 *
	 * @since 0.0.1
	 *
	 * @todo  If taxonomies are registered for events, show them as well
	 */
	function right_now_content_table_end() {

		$post_type = get_post_type_object( self::$options['event_post_type'] );

		$num_posts = wp_count_posts( $post_type->name );
		$num       = number_format_i18n( $num_posts->publish );
		$text      = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $num_posts->publish ) );

		if ( current_user_can( 'edit_posts' ) ) {
			$num = "<a href='edit.php?post_type=$post_type->name'>$num</a>";
			$text = "<a href='edit.php?post_type=$post_type->name'>$text</a>";
		}

		echo '<tr><td class="first b b-' . $post_type->name . '">' . $num . '</td>';
		echo '<td class="t ' . $post_type->name . '">' . $text . '</td></tr>';

	}

	/**
	 * Register query vars for the edit.php table filter
	 *
	 * @since 0.0.1
	 */
	function add_query_vars( $query_vars ) {
		if ( is_admin() )
			$query_vars[] = 'events_toolkit_event_scope';
		return $query_vars;
	}

	/**
	 * Set default order to '_event_start' in admin
	 *
	 * @since 0.0.1
	 *
	 * @todo  Is 'request' the correct filter?
	 */
	public function orderby_and_scope( $vars ) {

		if ( is_admin() ) {

			$screen = get_current_screen();

			if ( 'edit' == $screen->base && self::$options['event_post_type'] == $screen->post_type ) {

				if ( ! isset( $vars['orderby'] ) ) {
					$vars['meta_key'] = '_event_start';
					$vars['orderby']  = 'meta_value';
					$vars['order']    = 'DESC';
				} else {
					if ( in_array( $vars['orderby'], array( '_event_start', '_event_end' ) ) ) {
						$vars['meta_key'] = $vars['orderby'];
						$vars['orderby']  = 'meta_value';
					}
				}

				/**
				 * Show past, present, future or all events?
				 */
				if ( isset( $vars['events_toolkit_event_scope'] ) ) {
					$now = current_time( 'mysql', 0 );
					switch ( $vars['events_toolkit_event_scope'] ) {
						case 'past' :
							$vars['meta_key']     = '_event_end';
							$vars['meta_value']   = $now;
							$vars['meta_compare'] = '<';
							break;
						case 'current' :
							$vars['meta_query'] = array(
								array( 'key' => '_event_start', 'value' => $now, 'compare' => '<=' ),
								array( 'key' => '_event_end', 'value' => $now, 'compare' => '>' )
								);
							break;
						case 'upcoming' :
							$vars['meta_key']     = '_event_start';
							$vars['meta_value']   = $now;
							$vars['meta_compare'] = '>';
							break;
					}
				}
			}

			$vars = apply_filters( 'admin_orderby_and_scope', $vars );

		}

		return $vars;
	}

	/**
	 * Filter events in admin.
	 *
	 * Adds a filter which allows displaying upcoming, current, past or all events.
	 * Via: http://wordpress.stackexchange.com/questions/45/how-to-sort-the-admin-area-of-a-wordpress-custom-post-type-by-a-custom-field
	 *
	 * @since 0.0.1
	 */
	public function add_event_scope_select() {

		$screen = get_current_screen();

		if ( 'edit' == $screen->base && self::$options['event_post_type'] == $screen->post_type ) {

			$post_type_obj = get_post_type_object( self::$options['event_post_type'] );

			$options = array(
				'all'      => $post_type_obj->labels->all_items,
				'upcoming' => __( 'Upcoming', 'events-toolkit' ),
				'current'  => __( 'Current', 'events-toolkit' ),
				'past'     => __( 'Past', 'events-toolkit' ),
				);
			// Allow filtering of options
			$options     = apply_filters( 'events_toolkit_add_event_scope_select', $options );
			$event_scope = 'all';
			if ( get_query_var( 'events_toolkit_event_scope' ) ) {
				$event_scope = get_query_var( 'events_toolkit_event_scope' );
			}

			$html = '<select name="events_toolkit_event_scope">';
			foreach( $options as $k => $v ) {
				$selected = ( $k == $event_scope ) ? " selected='selected'" : '';
				$html .= "<option value='{$k}'{$selected}>{$v}</option>";
			}
			$html .= "</select>";

			echo $html;

		}
	}

	/**
	 * Remove 'quick edit' action.
	 *
	 * @since 0.0.1
	 */
	public function remove_quick_edit( $actions ) {
		global $post;
		if ( self::$options['event_post_type'] == $post->post_type )
			unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * Add start and end date columns.
	 *
	 * @since 0.0.1
	 */
	public function manage_columns( $columns ) {

		// Remove default date column
		if ( array_key_exists( 'date', $columns ) ) {
			unset( $columns['date'] );
		}

		$new_columns = array(
			'event-start' => __( 'Start Date', 'events-toolkit' ),
			'event-end' => __( 'End Date', 'events-toolkit' )
			);

		$new_columns = apply_filters( 'events_toolkit_manage_columns', $new_columns );

		return array_merge( $columns, $new_columns );
	}

	/**
	 * Content for custom columns.
	 *
	 * @since 0.0.1
	 */
	public function manage_columns_content( $column_name, $post_id ) {

		$datestr = '-';

		if ( $column_name == 'event-start' ) {
			$date = strtotime( get_post_meta( $post_id, '_event_start', true ) );
			if ( $date )
				$datestr = date_i18n( get_option( 'date_format' ) , $date );
			echo $datestr;
		}

		if ( $column_name == 'event-end' ) {
			$date = strtotime( get_post_meta( $post_id, '_event_end', true ) );
			if ( $date )
				$datestr = date_i18n( get_option( 'date_format' ) , $date );
			echo $datestr;
		}

	}

	/**
	 * Enable table sorting by date.
	 *
	 * Via: http://scribu.net/wordpress/custom-sortable-columns.html
	 *
	 * @since 0.0.1
	 */
	public function make_columns_sortable( $columns ) {
		$columns['event-start'] = '_event_start';
		$columns['event-end'] = '_event_end';
		return $columns;
	}

	/**
	 * Add meta boxes to the event edit screen.
	 *
	 * @since  0.0.1
	 */
	public function add_meta_boxes() {

		$post_type_object = get_post_type_object( self::$options['event_post_type'] );

		add_meta_box(
			// Note: $id is also used in events-toolkit.css, where it hides the screen options show/hide checkbox for this meta box
			'events-toolkit-event-date',
			sprintf( _x( '%s Date', 'Event date meta box title', 'events-toolkit' ), $post_type_object->labels->singular_name ),
			array( $this, 'meta_box_date' ),
			self::$options['event_post_type'],
			'normal',
			'high'
		);
	}

	/**
	 * Event date meta box.
	 *
	 * @since 0.0.1
	 *
	 */
	public function meta_box_date() {

		global $post;

		wp_nonce_field( plugin_basename( __FILE__ ), 'events_toolkit_save_date_' . $post->ID );

		$start = get_post_meta( $post->ID, '_event_start', true );
		$end   = get_post_meta( $post->ID, '_event_end', true );

		if ( self::$options['all_day_disable'] == false ) {
			if ( '' == $start || '' == $end ) {
				$all_day_event = self::$options['default_all_day'];
			} elseif ( substr( $start, 11, 8 ) == '00:00:00' && substr( $end, 11, 8 ) == '23:59:59' ) {
				$all_day_event = true;
			} else {
				$all_day_event = false;
			}
		} else {
			$all_day_event = false;
		}


		if ( '' == $start )
			$start = substr_replace( current_time( 'mysql', 0 ), self::$options['default_start_time'], 11 );
		if ( '' == $end )
			$end = substr_replace( current_time( 'mysql', 0 ), self::$options['default_end_time'], 11 );

		if ( $all_day_event ) {
			$start_time = explode( ':', self::$options['default_start_time'] );
			$end_time   = explode( ':', self::$options['default_end_time'] );
		} else {
			$start_time = explode( ':', substr( $start, 11, 5 ) );
			$end_time   = explode( ':', substr( $end, 11, 5 ) );
		}

		// 24 or 12 hour time?
		if ( self::$options['clock'] == 'auto' ) {
			// TODO This does not seem very solid...
			if ( strtolower( substr( trim( get_option( 'time_format' ) ), -1 ) ) == 'a' ) {
				$clock = 12;
			} else {
				$clock = 24;
			}
		} else {
			$clock = self::$options['clock'];
		}

		$start_ampm = $end_ampm = 'am';

		if ( $clock == 12 ) {
			if ( $start_time[0] > 12 ) {
				$start_time[0] = str_pad( $start_time[0] - 12, 2, '0', STR_PAD_LEFT );
				$start_ampm = 'pm';
			}
			if ( $end_time[0] > 12 ) {
				$end_time[0] = str_pad( $end_time[0] - 12, 2, '0', STR_PAD_LEFT );
				$end_ampm = 'pm';
			}
		}

		// Load date form template
		require_once( plugin_dir_path( __FILE__ ) . 'templates/tmpl-meta-box-date.php' );

	}

	/**
	 * Save event date.
	 *
	 * @since  0.0.1
	 *
	 * @todo   Error handling and date validation
	 */
	public function save_post_date( $post_id, $post ) {

		// Verify nonce
		if ( ! isset( $_POST['events_toolkit_save_date_' . $post_id] ) || ! wp_verify_nonce( $_POST['events_toolkit_save_date_' . $post_id], plugin_basename( __FILE__ ) ) )
			return $post_id;

		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;

		// Get the post type object
		$post_type = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit this post
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;

		// If no start date is set, delete meta values and return
		if ( ! isset( $_POST['event-start'] ) || '' == $_POST['event-start'] ) {
			delete_post_meta( $post_id, '_event_start' );
			delete_post_meta( $post_id, '_event_end' );
			return $post_id;
		}

		// Maybe turn 12 hour into 24 hour
		if ( isset( $_POST['event-start-ampm'] ) ) {
			if ( 'pm' == $_POST['event-start-ampm'] )
				$_POST['event-start-hh'] = $_POST['event-start-hh'] + 12;
			if ( 'pm' == $_POST['event-end-ampm'] )
				$_POST['event-end-hh'] = $_POST['event-end-hh'] + 12;
		}

		// Define start and end times depending on whether the 'All Day' option is selected
		$start_time = ( isset( $_POST['event-all-day'] ) ) ? ' 00:00:00' : sprintf( " %02d:%02d:00", $_POST['event-start-hh'], $_POST['event-start-mm'] );
		$end_time   = ( isset( $_POST['event-all-day'] ) ) ? ' 23:59:59' : sprintf( " %02d:%02d:00", $_POST['event-end-hh'], $_POST['event-end-mm'] );

		// Update metadata
		update_post_meta( $post_id, '_event_start', $_POST['event-start'] . $start_time );
		update_post_meta( $post_id, '_event_end', $_POST['event-end'] . $end_time );

		return $post_id;

	}

	/**
	 * Try to turn PHP date format into jQuery equivalent.
	 *
	 * via: http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
	 *
	 * @author   Tristan Jahier
	 * @since    0.0.1
	 */
	private function date_format_php_to_jquery( $php_format ) {
		$jqueryui_format = "";
		$escaping = false;
		$symbols_matching = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		for ( $i = 0; $i < strlen( $php_format ); $i++ ) {
			$char = $php_format[$i];
			if ( $char === '\\') { // PHP date format escaping character
				$i++;
				if ( $escaping ) {
					$jqueryui_format .= $php_format[$i];
				} else {
					$jqueryui_format .= '\'' . $php_format[$i];
				}
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping = false;
				}
				if ( isset($symbols_matching[$char] ) ) {
					$jqueryui_format .= $symbols_matching[$char];
				} else {
					$jqueryui_format .= $char;
				}
			}
		}
		return $jqueryui_format;
	}

}
