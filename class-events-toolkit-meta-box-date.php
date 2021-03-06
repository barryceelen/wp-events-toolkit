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
 * Create date meta box for a post type.
 *
 * @package Events_Toolkit
 * @author  Barry Ceelen <b@rryceelen.com>
 * @todo    Date and time validation when saving events
 */
class Events_Toolkit_Meta_Box_Date {

	/**
	 * Initialize the class.
	 *
	 * @since 0.0.1
	 */
	public function __construct( $post_type, $args = array() ) {

		$this->post_type = $post_type;

		$defaults = array(
			'all_day_disable'    => false,
			'all_day_checked'    => true, // 'All day' is checked by default
			'default_start_time' => '10:00',
			'default_end_time'   => '17:00',
			'clock'              => 'auto', // 12 (Shows am/pm select), 24 or auto
			'title'              => '',
			'context'            => 'normal',
			'priority'           => 'high',
		);

		$this->args = wp_parse_args( $args, $defaults );

	}

	/**
	 * Enqueue scripts and styles, add date meta box.
	 *
	 * @since 0.0.2
	 */
	public function init() {
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post_date' ), 10, 2 );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_admin_styles() {

		$screen = get_current_screen();

		if ( $this->post_type != $screen->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue styles for the jquery ui date picker
		wp_enqueue_style(
			Events_Toolkit::PLUGIN_SLUG .'-jquery-ui-datepicker-skins',
			EVENTS_TOOLKIT_PLUGIN_URL . "/js/vendor/jquery-ui/css/smoothness/jquery-ui-1.10.4.custom$suffix.css",
			array(),
			Events_Toolkit::VERSION
		);

		// Enqueue WP themed styles for the jquery ui date picker by X-Team
		// https://github.com/x-team/wp-jquery-ui-datepicker-skins
		wp_enqueue_style(
			Events_Toolkit::PLUGIN_SLUG .'-wp-jquery-ui-datepicker-skins',
			EVENTS_TOOLKIT_PLUGIN_URL . "/css/vendor/wp-jquery-ui-datepicker-skins/datepicker.css",
			array(),
			Events_Toolkit::VERSION
		);

		// Enqueue Events Toolkit styles
		wp_enqueue_style(
			Events_Toolkit::PLUGIN_SLUG .'-meta-box-date-styles',
			EVENTS_TOOLKIT_PLUGIN_URL . '/css/meta-box-date.css',
			array(),
			Events_Toolkit::VERSION
		);
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 0.0.1
	 *
	 * @return null Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		$screen = get_current_screen();

		if ( $this->post_type !== $screen->id ) {
			return;
		}

		$regional = '';

		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Maybe load a localized version of jQuery UI
		// source: https://github.com/jquery/jquery-ui/tree/master/ui/i18n
		if ( 'en_US' != get_locale() ) {
			$enqueue = false;
			$regional = str_replace( '_', '-', get_locale() );
			$path = "js/vendor/jquery-ui/i18n/jquery.ui.datepicker-$regional.js";
			if ( is_readable( EVENTS_TOOLKIT_PLUGIN_PATH . $file ) ) {
				$enqueue = true;
			}
			if ( false == $enqueue ) {
				$regional = substr( $regional, 0, 2 );
				$path = "js/vendor/jquery-ui/i18n/jquery.ui.datepicker-$regional.js";
				if ( is_readable( EVENTS_TOOLKIT_PLUGIN_PATH . $file ) ) {
					$enqueue = true;
				}
			}
			if ( true == $enqueue ) {
				wp_enqueue_script(
					Events_Toolkit::PLUGIN_SLUG .'-datepicker-i18n',
					EVENTS_TOOLKIT_PLUGIN_URL . $path,
					array(),
					Events_Toolkit::VERSION
				);
			}
		}

		// Enqueue admin.js
		wp_enqueue_script(
			Events_Toolkit::PLUGIN_SLUG .'-admin-scripts',
			EVENTS_TOOLKIT_PLUGIN_URL . '/js/admin.js',
			array(),
			Events_Toolkit::VERSION
		);

		// Add vars to page head
		wp_localize_script(
			Events_Toolkit::PLUGIN_SLUG .'-admin-scripts',
			'eventsToolkitVars',
			array(
				'disableAllDay' => $this->args['all_day_disable'],
				'dateFormat'    => $this->date_format_php_to_jquery( get_option( 'date_format' ) ),
				'firstDay'      => get_option( 'start_of_week' ),
				'regional'      => $regional,
			)
		);
	}

	/**
	 * Add a date meta box to the custom post type edit screen.
	 *
	 * Filter the meta box title via 'events_meta_box_title', eg. in case a plugin
	 * adds fields to the meta box and the default title would not be appropriate.
	 *
	 * @since 0.0.1
	 */
	public function add_meta_box() {

		// If the meta box title is not set,
		// use the singular name via the custom post type object
		if ( '' == $this->args['title'] ) {

			$post_type_object = get_post_type_object( $this->post_type );
			$this->args['title'] = sprintf(
				_x( '%s Date', 'Date meta box title', 'events-toolkit' ),
				$post_type_object->labels->singular_name
			);

		}

		// Note: 'events-toolkit-date' is also used in events-toolkit.css,
		// where it hides the screen options show/hide checkbox for this meta box
		add_meta_box(
			'events-toolkit-date',
			$this->args['title'],
			array( $this, 'meta_box_date' ),
			$this->post_type,
			$this->args['context'],
			$this->args['priority']
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

		$all_day_disable = $this->args['all_day_disable'];
		$start = get_post_meta( $post->ID, '_event_start', true );
		$end = get_post_meta( $post->ID, '_event_end', true );

		if ( $this->args['all_day_disable'] == false ) {
			if ( '' == $start || '' == $end ) {
				$all_day_event = $this->args['all_day_checked'];
			} elseif ( substr( $start, 11, 8 ) == '00:00:00' && substr( $end, 11, 8 ) == '23:59:59' ) {
				$all_day_event = true;
			} else {
				$all_day_event = false;
			}
		} else {
			$all_day_event = false;
		}

		if ( '' == $start ) {
			$start = substr_replace(
				current_time( 'mysql', 0 ),
				$this->args['default_start_time'],
				11
			);
		}

		if ( '' == $end ) {
			$end = substr_replace(
				current_time( 'mysql', 0 ),
				$this->args['default_end_time'],
				11
			);
		}

		if ( $all_day_event ) {
			$start_time = explode( ':', $this->args['default_start_time'] );
			$end_time   = explode( ':', $this->args['default_end_time'] );
		} else {
			$start_time = explode( ':', substr( $start, 11, 5 ) );
			$end_time   = explode( ':', substr( $end, 11, 5 ) );
		}

		// 24 or 12 hour time?
		if ( 'auto' != $this->args['clock'] ) {
			$clock = $this->args['clock'];
		} else {
			// TODO This does not seem very solid...
			if ( strtolower( substr( trim( get_option( 'time_format' ) ), -1 ) ) == 'a' ) {
				$clock = 12;
			} else {
				$clock = 24;
			}
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
		require_once( EVENTS_TOOLKIT_PLUGIN_PATH . 'templates/tmpl-meta-box-date.php' );

	}

	/**
	 * Save event date.
	 *
	 * @since 0.0.1
	 *
	 * @todo Error handling and date validation
	 */
	public function save_post_date( $post_id, $post ) {

		// Verify nonce
		if (
			! isset( $_POST['events_toolkit_save_date_' . $post_id] )
			||
			! wp_verify_nonce( $_POST['events_toolkit_save_date_' . $post_id], plugin_basename( __FILE__ ) )
		) {
			return $post_id;
		}

		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Get the post type object
		$post_type = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit this post
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		// If no start date is set, delete meta values and return
		// TODO Better force save as draft and notify user
		if ( ! isset( $_POST['event-start'] ) || '' == $_POST['event-start'] ) {
			delete_post_meta( $post_id, '_event_start' );
			delete_post_meta( $post_id, '_event_end' );
			return $post_id;
		}

		// Maybe turn 12 hour into 24 hour
		if ( isset( $_POST['event-start-ampm'] ) ) {
			if ( 'pm' == $_POST['event-start-ampm'] ) {
				$_POST['event-start-hh'] = $_POST['event-start-hh'] + 12;
			}
			if ( 'pm' == $_POST['event-end-ampm'] ) {
				$_POST['event-end-hh'] = $_POST['event-end-hh'] + 12;
			}
		}

		// Define start and end times depending on whether the 'All Day' option is selected
		if ( isset( $_POST['event-all-day'] ) ) {
			$start_time = ' 00:00:00';
			$end_time   = ' 23:59:59';
		} else {
			$start_time = sprintf( " %02d:%02d:00", $_POST['event-start-hh'], $_POST['event-start-mm'] );
			$end_time   = sprintf( " %02d:%02d:00", $_POST['event-end-hh'], $_POST['event-end-mm'] );
		}

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
