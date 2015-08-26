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
 * Register default event post type.
 *
 * @package Events_Toolkit
 * @author  Barry Ceelen <b@rryceelen.com>
 */
class Events_Toolkit_Custom_Post_Type {

	/**
	 * Define arguments for this class.
	 *
	 * Arguments are filterable via 'events_toolkit_options'
	 * in the Events_Toolkit class.
	 *
	 * @since 0.0.1
	 */
	public function __construct( $post_type, $args = array() ) {

		$this->post_type = $post_type;

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

		$defaults = array(
			'labels'        => $labels,
			'public'        => true,
			'hierarchical'  => false,
			'rewrite'       => array( 'slug' => 'event' ),
			'has_archive'   => 'events',
			'menu_position' => 8,
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'     => 'dashicons-calendar',
			'glance_icon'   => '\f145',
		);

		$this->args = wp_parse_args( $args, $defaults );
	}

	public function init() {
		// Register custom post type
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Filter post updated messages
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		// Add events to 'At a Glance' dashboard widget
		add_action( 'dashboard_glance_items' , array( $this, 'dashboard_glance_items' ) );

		// Change 'At a Glance' icon
		add_action( 'admin_print_scripts', array( $this, 'dashboard_glance_items_style' ) );
	}

	/**
	 * Register post type.
	 *
	 * @since  0.0.1
	 */
	public function register_post_type() {

		if ( post_type_exists( $this->post_type ) ) {
			return new WP_Error( 'post_type_exists', sprintf( __( 'The %s custom post type has already been registered.', 'events-toolkit' ), $this->post_type ) );
		}

		register_post_type( $this->post_type, $this->args );
	}

	/**
	 * Filter display messages.
	 *
	 * These messages are filterable via 'post_updated_messages' like we're doing here.
	 * You'd need to register the filter with a higher priority than 10.
	 * See: http://codex.wordpress.org/Function_Reference/add_filter
	 *
	 * @since  0.0.1
	 */
	public function post_updated_messages( $messages ) {

		global $post, $post_ID;

		$messages[$this->post_type] = array(
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
	 * Add custom post type to 'At a Glance' dashboard widget.
	 *
	 * @since 0.0.1
	 *
	 * @todo Check for correct capability for edit link.
	 * @todo If taxonomies are registered for events, show them as well?
	 * @todo Is the post type edit link present in post_type_object?
	 */
	public function dashboard_glance_items() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post_type = get_post_type_object( $this->post_type );
		$num_posts = wp_count_posts( $post_type->name );
		if ( $num_posts && $num_posts->publish ) {
			$text = _n(
				'%s ' . $post_type->labels->singular_name,
				'%s ' . $post_type->labels->name,
				$num_posts->publish
			);
			$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
			printf(
				'<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>',
				$post_type->name,
				$text
			);
		}
	}

	/**
	 * Add inline style to change the 'At A Glance' link icon.
	 *
	 * @todo Load css file in stead?
	 *
	 * @since  0.0.2
	 */
	function dashboard_glance_items_style() {

		$screen = get_current_screen();

		if ( 'dashboard' == $screen->base && current_user_can( 'edit_posts' ) ) {
			echo "<style media='all'>#dashboard_right_now .{$this->post_type}-count a:before { content: '{$this->args['glance_icon']}'; }</style>";
		}
	}
}
