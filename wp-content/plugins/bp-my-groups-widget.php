<?php
/*
Plugin Name: My Groups Widget
Description: This BuddyPress widget lists the groups the of which the logged in user is a member.
Version: 1.0
Revision Date: March 18, 2010
Requires at least: WPMU 2.8, BuddyPress 1.1
Tested up to: WPMU 2.9.2, BuddyPress 1.2.2.1
License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: Peter Anselmo, Studio66
Author URI: http://www.studio66design.com
*/


/* Register widgets for groups component */
function my_groups_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_My_Groups_Widget");') );
}
add_action( 'bp_register_widgets', 'my_groups_register_widgets' );

/*** GROUPS WIDGET *****************/

class BP_My_Groups_Widget extends WP_Widget {
	function bp_my_groups_widget() {
		parent::WP_Widget( false, $name = __( 'My Groups', 'buddypress' ) );
	}

	function widget($args, $instance) {
		global $bp;

	    extract( $args );

		$user_id = bp_displayed_user_id();
		$link = trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() );
		$instance['title'] = sprintf( __( '%s&#8217;s Groups', 'buddypress' ), bp_get_displayed_user_fullname() );

		if ( empty( $instance['friend_default'] ) ) {
			$instance['friend_default'] = 'active';
		}

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		echo $before_title
		   . $title
		   . $after_title; ?>

		<?php if ( bp_has_groups( 'type=random&max=3&user_id=' . $bp->displayed_user->id )&& is_user_logged_in()) : ?>

			<ul class="my-groups-list item-list">
				<?php while ( bp_groups() ) : bp_the_group(); ?>
					<li>
						<div class="item-avatar">
							<a href="<?php bp_group_permalink() ?>"><?php bp_group_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></div>
							<div class="item-meta"><span class="activity"><?php bp_group_member_count() ?></span></div>
						</div>
					</li>

				<?php endwhile; ?>
					<a id="all-friends-link" href="<?php echo $bp->displayed_user->domain?>groups">See all his/her groups</a>
			</ul>
			<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
			<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo attribute_escape( $instance['max_groups'] ); ?>" />

		<?php else: ?>
			
			<div class="widget-error">
				<?php if( is_user_logged_in() ) {
					_e('You have not joined any groups.','buddypress');
				} else {
					_e('Please log in to see your groups.', 'buddypress');
				} ?> 
			</div>

		<?php endif; ?>

		<?php $bp->displayed_user->fullname ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
		?>

		<p><label><?php _e('Title:','buddypress'); ?></label><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
	<?php
	}
}
