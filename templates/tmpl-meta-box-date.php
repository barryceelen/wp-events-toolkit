<?php
/**
 * @package   Events_Toolkit
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-2.0+
 * @link      http://github.com/barryceelen/wp-events-toolkit
 * @copyright 2013 Barry Ceelen
 */
?>
<table>
	<?php do_action( 'events_toolkit_date_form_before' ); ?>
	<?php if ( $all_day_disable == false ) { ?>
		<tr>
			<td>
					<label for="event-all-day"><?php _e( 'All Day', 'prefix' ); ?></label>
			</td>
			<td colspan="2">
					<input id="event-all-day" name="event-all-day" type="checkbox" <?php checked( $all_day_event, 1 ); ?> />
			</td>
		</tr>
	<?php } ?>
	<tr>
		<td>
				<label for="event-start-string"><?php _e( 'From', 'prefix' ); ?></label>
		</td>
		<td>
				<input id="event-start-string" name="event-start-string" type="text" data-date="<?php echo esc_attr( mysql2date( 'Y/m/d', $start ) ); ?>" value="" readonly="true"  />
				<input id="event-start" name="event-start" type="hidden" value="" />
		</td>
		<td class="all-day-hidden <?php if ( ! $all_day_event ) { echo 'all-day-visible'; } ?>">
			<input class="integer" id="event-start-hh" name="event-start-hh" type="text" size="2" maxlength="2" min="0" max="23" value="<?php echo esc_attr( $start_time[0] ) ?>" />
			:
			<input class="integer" id="event-start-mm" name="event-start-mm" type="text" size="2" maxlength="2" min="0" max="59" value="<?php echo esc_attr( $start_time[1] ); ?>" />
			<?php if ( $clock == 12 ) { ?>
				<select name="event-start-ampm">
					<option value="am" <?php selected( $start_ampm, 'am' ); ?>>am</option>
					<option value="pm" <?php selected( $start_ampm, 'pm' ); ?>>pm</option>
				</select>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<td>
			<label for="event-end-string"><?php _e( 'To', 'prefix' ); ?></label>
		</td>
		<td>
			<input id="event-end-string" name="event-end-string" type="text" data-date="<?php echo esc_attr( mysql2date( 'Y/m/d', $end ) ); ?>" value="" readonly="true" />
			<input id="event-end" name="event-end" type="hidden" value="" />
		</td>
		<td class="all-day-hidden <?php if ( ! $all_day_event ) { echo 'all-day-visible'; } ?>">
			<input class="integer" id="event-end-hh" name="event-end-hh" type="text" size="2" maxlength="2" min="0" max="24" value="<?php echo esc_attr( $end_time[0] ); ?>" />
			:
			<input class="integer" id="event-end-mm" name="event-end-mm" type="text" size="2" maxlength="2" min="0" max="59" value="<?php echo esc_attr( $end_time[1] ); ?>" />
			<?php if ( $clock == 12 ) { ?>
				<select name="event-end-ampm">
					<option value="am" <?php selected( $end_ampm, 'am' ); ?>>am</option>
					<option value="pm" <?php selected( $end_ampm, 'pm' ); ?>>pm</option>
				</select>
			<?php } ?>
		</td>

	</tr>
	<?php do_action( 'events_toolkit_date_form_after' ); ?>
</table>
