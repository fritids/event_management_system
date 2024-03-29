<?php

/**
 * @author Christoph Bessei
 * @version
 */
class Ems_Dhv_Jugend {

	public static function save_meta_data_of_event( $post_id ) {


		/*
   * We need to verify this came from the our screen and with proper authorization,
   * because save_post can be triggered at other times.
   */

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;


		// Check the user's permissions.
		if ( ( isset( $_POST['post_type'] ) && 'event' == $_POST['post_type'] ) ) {
			if ( ! current_user_can( 'edit_event', $post_id ) )
				return $post_id;
		}


		//Save form options first, then date stuff
		if ( isset( $_POST['ems_premium_field_nonce'] ) && wp_verify_nonce( $_POST['ems_premium_field_nonce'], 'ems_premium_field' ) ) {

			/* OK, its safe for us to save the data now. */
			if ( ! isset( $_POST['ems_premium_field'] ) ) {
				$_POST['ems_premium_field'] = 0;
			}
			$premium_field = sanitize_text_field( $_POST['ems_premium_field'] );
			update_post_meta( $post_id, 'ems_premium_field', $premium_field );
			$ID = preg_replace( "/[^0-9]/", "", $_POST['ems_event_leader'] );
			//Set mail address if "Benutzerdefiniert" was set as leader
			if ( "0" === $ID ) {
				update_post_meta( $post_id, 'ems_event_leader_mail', sanitize_text_field( $_POST['ems_event_leader_mail'] ) );
			}
			else {
				update_post_meta( $post_id, 'ems_event_leader_mail', "" );
			}
			update_post_meta( $post_id, 'ems_event_leader', $ID );
			if ( ! isset( $_POST['ems_inform_via_mail'] ) ) {
				$_POST['ems_inform_via_mail'] = 0;
			}
			$inform_via_mail = sanitize_text_field( $_POST['ems_inform_via_mail'] );
			update_post_meta( $post_id, 'ems_inform_via_mail', $inform_via_mail );

		}

		// Check if our nonce is set.
		if ( isset( $_POST['ems_calendar_meta_box_nonce'] ) && wp_verify_nonce( $_POST['ems_calendar_meta_box_nonce'], 'ems_calendar_meta_box' ) ) {

			/* OK, its safe for us to save the data now. */

			// Sanitize user input.
			$start_date = sanitize_text_field( $_POST['ems_start_date'] );
			$end_date   = sanitize_text_field( $_POST['ems_end_date'] );
			$start_date = explode( '.', $start_date );
			$end_date   = explode( '.', $end_date );

			if ( count( $start_date ) != 3 || count( $end_date ) != 3 ) {
				return $post_id;
			}

			$date_time_start_date = new DateTime();
			$date_time_start_date->setDate( $start_date[2], $start_date[1], $start_date[0] );
			$date_time_start_date->setTime( 0, 0, 0 );

			$date_time_end_date = new DateTime();
			$date_time_end_date->setDate( $end_date[2], $end_date[1], $end_date[0] );
			$date_time_end_date->setTime( 0, 0, 0 );


			//Convert Datetime back to $_POST values, to check if date is valid
			if ( date( "d.m.Y", $date_time_start_date->getTimestamp() ) == sanitize_text_field( $_POST['ems_start_date'] ) ) {
				// Update the meta field in the database.
				update_post_meta( $post_id, 'ems_start_date', $date_time_start_date );
			}
			else {
				return $post_id;
			}


			if ( date( "d.m.Y", $date_time_end_date->getTimestamp() ) == sanitize_text_field( $_POST['ems_end_date'] ) ) {
				// Update the meta field in the database.
				update_post_meta( $post_id, 'ems_end_date', $date_time_end_date );
			}
			else {
				return $post_id;
			}
		}
		return $post_id;
	}

	public static function add_calendar_meta_box( WP_Post $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'ems_calendar_meta_box', 'ems_calendar_meta_box_nonce' );

		/*
   * Use get_post_meta() to retrieve an existing value
   * from the database and use the value for the form.
   */

		/** @var DateTime $date_time_start_date */
		$date_time_start_date = get_post_meta( $post->ID, 'ems_start_date', true );
		/** @var DateTime $date_time_end_date */
		$date_time_end_date = get_post_meta( $post->ID, 'ems_end_date', true );

		if ( empty( $date_time_start_date ) ) {
			$start_date = '';
		}
		else {

			$start_timestamp = $date_time_start_date->getTimestamp();
			$start_date      = date( "d.m.Y", $start_timestamp );
		}

		if ( empty( $date_time_end_date ) ) {
			$end_date = '';
		}
		else {

			$end_timestamp = $date_time_end_date->getTimestamp();
			$end_date      = date( "d.m.Y", $end_timestamp );
		}

		?>


		<label for="ems_start_date">Anfangsdatum<br />
			<input type="text" id="ems_start_date" name="ems_start_date" value="<?php echo $start_date; ?>" class="datepicker_period_start" /></label>
		<br />
		<label for="ems_end_date">Enddatum<br />
			<input type="text" id="ems_end_date" name="ems_end_date" value="<?php echo $end_date; ?>" class="datepicker_period_end" /></label>

	<?php


	}

	public static function remove_metabox_layout() {
		remove_meta_box( 'layout_sectionid', 'event', 'normal' );
		remove_meta_box( 'slider_sectionid', 'event', 'normal' );
	}


	public static function add_meta_box_to_event() {
		global $wp_meta_boxes;

		add_meta_box( 'register_form', 'Optionen', array( 'Ems_Dhv_Jugend', 'add_registration_form_meta_box' ), 'event', 'normal', 'core' );
		add_meta_box( 'calendar_meta_box', 'Kalender', array( 'Ems_Dhv_Jugend', 'add_calendar_meta_box' ), 'event', 'side', 'core' );


		/*		echo '<pre>';
				print_r( $wp_meta_boxes );
				echo '</pre>';*/
	}

	public static function add_registration_form_meta_box( WP_Post $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'ems_premium_field', 'ems_premium_field_nonce' );
		$premium_field       = get_post_meta( $post->ID, 'ems_premium_field', true );
		$inform_via_mail     = get_post_meta( $post->ID, 'ems_inform_via_mail', true );
		$current_select_user = get_post_meta( $post->ID, 'ems_event_leader', true );
		$event_leader_mail   = get_post_meta( $post->ID, 'ems_event_leader_mail', true );

		/** @var WP_User[] $users */
		$users = get_users();
		foreach ( $users as $key => $user ) {
			if ( ! $user->has_cap( 'edit_event' ) ) {
				unset( $users[$key] );
			}
		}
		$user_defined     = new WP_User();
		$data             = new stdClass();
		$data->first_name = 'Benutzdefiniert...';
		$data->last_name  = '';
		$data->ID         = 0;
		$user_defined->init( $data );

		$users[] = $user_defined;
		?>
		<label for="ems_premium_field">Premiumevent<br />
			<input type="checkbox" id="ems_premium_field" name="ems_premium_field" value="1" <?php checked( 1, $premium_field ); ?> /></label>
		<br />
		<label for="ems_inform_via_mail">Per Mail über neue Anmeldungen informieren<br />
			<input type="checkbox" name="ems_inform_via_mail" id="inform_via_mail" value="1" <?php checked( 1, $inform_via_mail ); ?>/></label>
		<br />
		<label for="ems_event_leader">Eventleiter<br />
			<select id="ems_event_leader" name="ems_event_leader">
				<?php foreach ( $users as $user ): ?>
					<option value="ID_<?php echo $user->ID; ?>" <?php selected( $user->ID, $current_select_user ); ?>><?php echo self::get_name( $user ); ?></option>
				<?php endforeach; ?>
			</select></label>
		<br />
		<label for="ems_event_leader_mail">Eventleiter Mailadresse (nur wenn "Eventleiter" auf Benutzerdefiniert<br />
			<input type="text" id="ems_event_leader_mail" name="ems_event_leader_mail" value="<?php echo $event_leader_mail; ?>"></label>
	<?php
	}

	private static function get_name( $user ) {
		$author = get_userdata( $user->ID );

		if ( false === $author ) {
			return $user->first_name . ' ' . $user->last_name;
		}
		if ( $author->first_name && $author->last_name ) {
			$name = "$author->first_name $author->last_name";
		}
		else {
			$name = $author->display_name;
		}
		return $name;
	}


	/**
	 * Replaces the WP Submit Meta Box in Events (removes visibility setting)
	 *
	 * @param WP_Post $post
	 */
	public function post_submit_meta_box_fum( WP_Post $post ) {
		global $action;


		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish      = current_user_can( $post_type_object->cap->publish_posts );

		$args = null;
		if ( post_type_supports( $post_type, 'revisions' ) && 'auto-draft' != $post->post_status ) {
			$revisions = wp_get_post_revisions( $post->ID );

			// We should aim to show the revisions metabox only when there are revisions.
			if ( count( $revisions ) > 1 ) {
				reset( $revisions ); // Reset pointer for key()
				$args = array( 'revisions_count' => count( $revisions ), 'revision_id' => key( $revisions ) );
				add_meta_box( 'revisionsdiv', __( 'Revisions' ), 'post_revisions_meta_box', null, 'normal', 'core' );
			}
		}

		?>
		<div class="submitbox" id="submitpost">

			<div id="minor-publishing">

				<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
				<div style="display:none;">
					<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
				</div>

				<div id="minor-publishing-actions">
					<div id="save-action">
						<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status ) { ?>
							<input <?php if ('private' == $post->post_status) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft' ); ?>" class="button" />
						<?php
						}
						elseif ( 'pending' == $post->post_status && $can_publish ) {
							?>
							<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save as Pending' ); ?>" class="button" />
						<?php } ?>
						<span class="spinner"></span>
					</div>
					<?php if ( $post_type_object->public ) : ?>
						<div id="preview-action">
							<?php
							if ( 'publish' == $post->post_status ) {
								$preview_link   = esc_url( get_permalink( $post->ID ) );
								$preview_button = __( 'Preview Changes' );
							}
							else {
								$preview_link   = set_url_scheme( get_permalink( $post->ID ) );
								$preview_link   = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
								$preview_button = __( 'Preview' );
							}
							?>
							<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview"><?php echo $preview_button; ?></a>
							<input type="hidden" name="wp-preview" id="wp-preview" value="" />
						</div>
					<?php endif; // public post type ?>
					<div class="clear"></div>
				</div>
				<!-- #minor-publishing-actions -->

				<div id="misc-publishing-actions">

					<div class="misc-pub-section misc-pub-post-status"><label for="post_status"><?php _e( 'Status:' ) ?></label>
<span id="post-status-display">
<?php
switch ( $post->post_status ) {
	case 'private':
		_e( 'Privately Published' );
		break;
	case 'publish':
		_e( 'Published' );
		break;
	case 'future':
		_e( 'Scheduled' );
		break;
	case 'pending':
		_e( 'Pending Review' );
		break;
	case 'draft':
	case 'auto-draft':
		_e( 'Draft' );
		break;
}
?>
</span>
						<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) { ?>
							<a href="#post_status" <?php if ('private' == $post->post_status) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js"><?php _e( 'Edit' ) ?></a>

							<div id="post-status-select" class="hide-if-js">
								<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ( 'auto-draft' == $post->post_status ) ? 'draft' : $post->post_status ); ?>" />
								<select name='post_status' id='post_status'>
									<?php if ( 'publish' == $post->post_status ) : ?>
										<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e( 'Published' ) ?></option>
									<?php elseif ( 'private' == $post->post_status ) : ?>
										<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e( 'Privately Published' ) ?></option>
									<?php
									elseif ( 'future' == $post->post_status ) : ?>
										<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e( 'Scheduled' ) ?></option>
									<?php endif; ?>
									<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e( 'Pending Review' ) ?></option>
									<?php if ( 'auto-draft' == $post->post_status ) : ?>
										<option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e( 'Draft' ) ?></option>
									<?php else : ?>
										<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e( 'Draft' ) ?></option>
									<?php endif; ?>
								</select>
								<a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e( 'OK' ); ?></a>
								<a href="#post_status" class="cancel-post-status hide-if-no-js"><?php _e( 'Cancel' ); ?></a>
							</div>

						<?php } ?>
					</div>
					<!-- .misc-pub-section -->



					<?php
					// translators: Publish box date format, see http://php.net/date
					$datef = __( 'M j, Y @ G:i' );
					if ( 0 != $post->ID ) {
						if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
							$stamp = __( 'Scheduled for: <b>%1$s</b>' );
						}
						else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
							$stamp = __( 'Published on: <b>%1$s</b>' );
						}
						else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
							$stamp = __( 'Publish <b>immediately</b>' );
						}
						else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
							$stamp = __( 'Schedule for: <b>%1$s</b>' );
						}
						else { // draft, 1 or more saves, date specified
							$stamp = __( 'Publish on: <b>%1$s</b>' );
						}
						$date = date_i18n( $datef, strtotime( $post->post_date ) );
					}
					else { // draft (no saves, and thus no date specified)
						$stamp = __( 'Publish <b>immediately</b>' );
						$date  = date_i18n( $datef, strtotime( current_time( 'mysql' ) ) );
					}

					if ( ! empty( $args['args']['revisions_count'] ) ) :
						$revisions_to_keep = wp_revisions_to_keep( $post );
						?>
						<div class="misc-pub-section misc-pub-revisions">
							<?php
							if ( $revisions_to_keep > 0 && $revisions_to_keep <= $args['args']['revisions_count'] ) {
								echo '<span title="' . esc_attr( sprintf( __( 'Your site is configured to keep only the last %s revisions.' ),
												number_format_i18n( $revisions_to_keep ) ) ) . '">';
								printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '+</b>' );
								echo '</span>';
							}
							else {
								printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '</b>' );
							}
							?>
							<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $args['args']['revision_id'] ) ); ?>"><?php _ex( 'Browse', 'revisions' ); ?></a>
						</div>
					<?php endif;

					if ( $can_publish ) : // Contributors don't get to choose the date of publish
						?>
						<div class="misc-pub-section curtime misc-pub-curtime">
						<span id="timestamp">
	<?php printf( $stamp, $date ); ?></span>
						<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><?php _e( 'Edit' ) ?></a>

						<div id="timestampdiv" class="hide-if-js"><?php touch_time( ( $action == 'edit' ), 1 ); ?></div>
						</div><?php // /misc-pub-section ?>
					<?php endif; ?>

					<?php do_action( 'post_submitbox_misc_actions' ); ?>
				</div>
				<div class="clear"></div>
			</div>

			<div id="major-publishing-actions">
				<?php do_action( 'post_submitbox_start' ); ?>
				<div id="delete-action">
					<?php
					if ( current_user_can( "delete_post", $post->ID ) ) {
						if ( ! EMPTY_TRASH_DAYS )
							$delete_text = __( 'Delete Permanently' );
						else
							$delete_text = __( 'Move to Trash' );
						?>
						<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a><?php
					} ?>
				</div>

				<div id="publishing-action">
					<span class="spinner"></span>
					<?php
					if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) {
						if ( $can_publish ) :
							if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Schedule' ) ?>" />
								<?php submit_button( __( 'Schedule' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
							<?php else : ?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
								<?php submit_button( __( 'Publish' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
							<?php  endif;
						else : ?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ) ?>" />
							<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
						<?php
						endif;
					}
					else {
						?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ) ?>" />
						<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php esc_attr_e( 'Update' ) ?>" />
					<?php
					} ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
	<?php
	}
} 