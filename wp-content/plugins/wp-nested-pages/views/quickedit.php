<?php 
	/**
	* See public inline_edit method of WP_Posts_List_Table class
	*/
	$post_type_object = get_post_type_object( 'page' );
	$can_publish = current_user_can( $post_type_object->cap->publish_posts );
?>

<form method="get" action="">
	<div class="form-interior">
	<h3><?php _e('Quick Edit'); ?></h3>

	<div class="np-quickedit-error" style="clear:both;display:none;"></div>

	<div class="fields">
	
	<div class="left">
		
		<div class="form-control">
			<label><?php _e( 'Title' ); ?></label>
			<input type="text" name="post_title" class="np_title" value="" />
		</div>
		<div class="form-control">
			<label><?php _e( 'Slug' ); ?></label>
			<input type="text" name="post_name" class="np_slug" value="" />
		</div>
		<div>
			<label><?php _e( 'Date' ); ?></label>
			<div class="dates"><?php touch_time( 1, 1, 0, 1 ); ?></div>
		</div>

		<?php 
		/*
		* Authors Dropdown
		*/
		$authors_dropdown = '';
		if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) :
			$users_opt = array(
				'hide_if_only_one_author' => false,
				'who' => 'authors',
				'name' => 'post_author',
				'id' => 'post_author',
				'class'=> 'authors',
				'multi' => 1,
				'echo' => 0
			);

			if ( $authors = wp_dropdown_users( $users_opt ) ) :
				$authors_dropdown  = '<div class="form-control np_author"><label>' . __( 'Author' ) . '</label>';
				$authors_dropdown .= $authors;
				$authors_dropdown .= '</div>';
			endif;
			echo $authors_dropdown;
		endif;
		?>

		<div class="form-control">
			<label><?php _e( 'Status' ); ?></label>
			<select name="_status" class="np_status">
			<?php if ( $can_publish ) : ?>
				<option value="publish"><?php _e( 'Published' ); ?></option>
				<option value="future"><?php _e( 'Scheduled' ); ?></option>
			<?php endif; ?>
				<option value="pending"><?php _e( 'Pending Review' ); ?></option>
				<option value="draft"><?php _e( 'Draft' ); ?></option>
			</select>
		</div>

	</div><!-- .left -->

	<div class="right">
		<div class="form-control">
			<label><?php _e( 'Template' ); ?></label>
			<select name="page_template" class="np_template">
				<option value="default"><?php _e( 'Default Template' ); ?></option>
				<?php page_template_dropdown() ?>
			</select>
		</div>

		<?php if ( $can_publish ) : ?>
		<div class="form-control password">
			<label><?php _e( 'Password' ); ?></label>
			<input type="text" class="post_password" name="post_password" value="" />
			<div class="private">
				<em style="margin:2px 8px 0 0" class="alignleft"><?php _e( '&ndash;OR&ndash;' ); ?></em>
				<label>
					<input type="checkbox" class="keep_private" name="keep_private" value="private" />
					<?php echo __( 'Private' ); ?>
				</label>
			</div>
		</div>
		<?php endif; ?>

		<div class="comments">
			<label>
				<input type="checkbox" name="comment_status" class="np_cs" value="open" />
				<span class="checkbox-title"><?php _e( 'Allow Comments' ); ?></span>
			</label>
		</div>
		
		<?php if ( current_user_can('edit_theme_options') ) : ?>
		<div class="comments">
			<label>
				<input type="checkbox" name="nested_pages_status" class="np_status" value="hide" />
				<span class="checkbox-title"><?php _e( 'Hide in Nested Pages', 'nestedpages' ); ?></span>
			</label>
		</div>
		<?php endif; // Edit theme options?>


		<?php if ( current_user_can('edit_theme_options') ) : // Menu Options Button ?>
		<div class="form-control np-toggle-options">
			<a href="#" class="np-btn np-btn-half np-toggle-menuoptions"><?php _e('Menu Options', 'nestedpages'); ?></a>
			<?php if ( !empty($this->h_taxonomies) ) : ?>
			<a href="#" class="np-btn np-btn-half btn-right np-toggle-taxonomies"><?php _e('Taxonomies', 'nestedpages'); ?></a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div><!-- .right -->

	<?php if ( !empty($this->h_taxonomies) ) : ?>
	<div class="np-taxonomies">
		<?php foreach ( $this->h_taxonomies as $taxonomy ) : ?>
			<div class="np-taxonomy">
				<span class="title"><?php echo esc_html( $taxonomy->labels->name ) ?></span>
				<input type="hidden" name="<?php echo ( $taxonomy->name == 'category' ) ? 'post_category[]' : 'tax_input[' . esc_attr( $taxonomy->name ) . '][]'; ?>" value="0" />
				<ul class="cat-checklist <?php echo esc_attr( $taxonomy->name )?>-checklist">
					<?php wp_terms_checklist( null, array( 'taxonomy' => $taxonomy->name ) ) ?>
				</ul>
			</div><!-- .np-taxonomy -->
		<?php endforeach; ?>

		<?php foreach ( $this->f_taxonomies as $taxonomy ) : ?>
			<div class="np-taxonomy">
				<span class="title"><?php echo esc_html( $taxonomy->labels->name ) ?></span>
				<textarea id="<?php echo esc_attr($taxonomy->name); ?>" cols="22" rows="1" name="tax_input[<?php echo esc_attr( $taxonomy->name )?>]" class="tax_input_<?php echo esc_attr( $taxonomy->name )?>" data-autotag data-taxonomy="<?php echo esc_attr($taxonomy->name); ?>"></textarea>
			</div><!-- .np-taxonomy -->
		<?php endforeach; ?>
	</div><!-- .taxonomies -->
	<?php endif; // if taxonomies ?>


	<?php if ( current_user_can('edit_theme_options') ) : // Menu Options?>
	<div class="np-menuoptions">
		<div class="menuoptions-left">
			<div class="form-control">
				<label><?php _e( 'Navigation Label' ); ?></label>
				<input type="text" name="np_nav_title" class="np_nav_title" value="" />
			</div>
			<div class="form-control">
				<label><?php _e( 'Title Attribute' ); ?></label>
				<input type="text" name="np_title_attribute" class="np_title_attribute" value="" />
			</div>
			<div class="form-control">
				<label><?php _e( 'CSS Classes' ); ?></label>
				<input type="text" name="np_nav_css_classes" class="np_nav_css_classes" value="" />
			</div>
		</div><!-- .menuoptions-left -->
		<div class="menuoptions-right">
			<div class="form-control">
				<label>
					<input type="checkbox" name="nav_status" class="np_nav_status" value="hide" />
					<span class="checkbox-title"><?php _e( 'Hide in Nav Menu', 'nestedpages' ); ?></span>
				</label>
			</div>
			<div class="form-control">
				<label>
					<input type="checkbox" name="link_target" class="link_target" value="_blank" />
					<span class="checkbox-title"><?php _e( 'Open link in a new window/tab' ); ?></span>
				</label>
			</div>
		</div><!-- .menuoptions-right -->
	</div>
	<?php endif; ?>

	</div><!-- .fields -->

	</div><!-- .form-interior -->

	<div class="buttons">
		<input type="hidden" name="post_id" class="np_id" value="<?php echo get_the_id(); ?>">
		<a accesskey="c" href="#inline-edit" class="button-secondary alignleft np-cancel-quickedit">
			<?php _e( 'Cancel' ); ?>
		</a>
		<a accesskey="s" href="#inline-edit" class="button-primary np-save-quickedit alignright">
			<?php _e( 'Update' ); ?>
		</a>
		<span class="np-qe-loading"></span>
	</div>
</form>