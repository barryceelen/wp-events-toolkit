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
 * Event display order and filtering for edit.php.
 *
 * @package Events_Toolkit
 * @author  Barry Ceelen <b@rryceelen.com>
 */
class Events_Toolkit_Admin {

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since 0.0.1
	 */
	public function __construct( $post_type, $args = array() ) {

		$this->post_type = $post_type;

		$defaults = array(); // TODO

		$this->args = wp_parse_args( $args, $defaults );

	}

	public function init() {

		// Add query vars for event filtering
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Modify requests to enable ordering and scope
		add_filter( 'request', array( $this, 'orderby_and_scope' ) );

		// Add filter dropdown to event post type list in admin
		add_action( 'restrict_manage_posts', array( $this, 'add_event_scope_select' ) );

		// Remove quick edit for non-hierarchical event post type
		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 1 );

		// Remove quick edit for hierarchical event post type
		add_filter( 'page_row_actions', array( $this, 'remove_quick_edit' ), 10, 1 );

		// Edit columns in admin table
		add_filter(
			'manage_' . $this->post_type . '_posts_columns',
			array( $this, 'manage_columns' )
		);
		add_action(
			'manage_' . $this->post_type . '_posts_custom_column',
			array( $this, 'manage_columns_content' ),
			10,
			2
		);
		add_filter(
			'manage_edit-' . $this->post_type . '_sortable_columns',
			array( $this, 'make_columns_sortable' )
		);
	}

	/**
	 * Register query vars for the edit.php table filter.
	 *
	 * @since 0.0.1
	 */
	public function add_query_vars( $query_vars ) {
		if ( is_admin() ) {
			$query_vars[] = 'events_toolkit_event_scope';
		}
		return $query_vars;
	}

	/**
	 * Set default order to '_event_start' in admin.
	 *
	 * @since 0.0.1
	 *
	 * @todo Is 'request' the correct filter?
	 * @todo This looks like it could use a little more work.
	 */
	public function orderby_and_scope( $vars ) {

		if ( ! is_admin() ) {
			return $vars;
		}

		$screen = get_current_screen();

		// Return early if we are not on an edit page
		if ( 'edit' != $screen->base ) {
			return $vars;
		}

		// Return early if this screen is not about our post type
		if ( $this->post_type != $screen->post_type ) {
			return $vars;
		}

		// Seems like we're good to go, to the batmobile!
		// @todo Swap if else order
		if ( ! isset( $vars['orderby'] ) ) {
			$vars['meta_key'] = '_event_start';
			$vars['orderby'] = 'meta_value';
			$vars['order'] = 'DESC';
		} else {
			if ( in_array( $vars['orderby'], array( '_event_start', '_event_end' ) ) ) {
				$vars['meta_key'] = $vars['orderby'];
				$vars['orderby'] = 'meta_value';
			}
		}

		// Show past, present, future or all events?
		// @todo Use meta_query i all three cases?
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
		return $vars;
	}

	/**
	 * Output <select> element to filter events in admin.
	 *
	 * Adds a dropdown filter to edit.php which allows displaying upcoming, current, past or all events.
	 * Via: http://wordpress.stackexchange.com/questions/45/how-to-sort-the-admin-area-of-a-wordpress-custom-post-type-by-a-custom-field
	 *
	 * @since 0.0.1
	 */
	public function add_event_scope_select() {

		$screen = get_current_screen();

		// Return early if we are not on an edit page
		if ( 'edit' != $screen->base ) {
			return;
		}

		// Return early if this screen is not about our post type
		if ( $this->post_type != $screen->post_type ) {
			return;
		}

		$post_type_obj = get_post_type_object( $this->post_type );

		$options = array(
			'all'      => $post_type_obj->labels->all_items,
			'upcoming' => __( 'Upcoming', 'events-toolkit' ),
			'current'  => __( 'Current', 'events-toolkit' ),
			'past'     => __( 'Past', 'events-toolkit' ),
		);

		// Allow filtering of options
		// @todo Remove or improve (eg. allow different default scope) filter
		$options = apply_filters( 'events_toolkit_add_event_scope_select', $options );
		$event_scope = 'all';
		if ( get_query_var( 'events_toolkit_event_scope' ) ) {
			$event_scope = get_query_var( 'events_toolkit_event_scope' );
		}

		$html = '<select name="events_toolkit_event_scope">';
		foreach( $options as $k => $v ) {
			$html .= "<option value='{$k}' " . selected( $k, $event_scope, false ) . ">{$v}</option>";
		}
		$html .= "</select>";

		echo $html;
	}

	/**
	 * Remove 'quick edit' action.
	 *
	 * This is mostly done because I'm too lazy to do something useful
	 * with the quick edit action content.
	 *
	 * @since 0.0.1
	 */
	public function remove_quick_edit( $actions ) {
		global $post;
		if ( $this->post_type == $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}
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
			if ( $date ) {
				$datestr = date_i18n( get_option( 'date_format' ) , $date );
			}
			echo $datestr;
		}

		if ( $column_name == 'event-end' ) {
			$date = strtotime( get_post_meta( $post_id, '_event_end', true ) );
			if ( $date ) {
				$datestr = date_i18n( get_option( 'date_format' ) , $date );
			}
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
}
