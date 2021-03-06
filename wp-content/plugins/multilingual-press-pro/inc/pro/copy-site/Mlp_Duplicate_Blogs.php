<?php # -*- coding: utf-8 -*-
/**
 * Class Mlp_Duplicate_Blogs
 *
 * Create new blogs based on an existing one.
 *
 * @version 2014.09.28
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Duplicate_Blogs {

	/**
	 * MLP Link Table
	 *
	 * @static
	 * @access	public
	 * @since	0.1
	 * @var		string
	 */
	public $link_table = FALSE;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var Mlp_Table_Duplicator_Interface
	 */
	private $duplicator;

	/**
	 * Constructor
	 *
	 * @param string                         $link_table
	 * @param wpdb                           $wpdb
	 * @param Mlp_Table_Duplicator_Interface $duplicator
	 */
	public function __construct(
		                               $link_table,
		wpdb                           $wpdb,
		Mlp_Table_Duplicator_Interface $duplicator
	) {

		$this->link_table = $link_table;
		$this->wpdb       = $wpdb;
		$this->duplicator = $duplicator;
	}

	/**
	 * Register callbacks.
	 *
	 * @return void
	 */
	public function setup() {

		add_filter( 'wpmu_new_blog', array ( $this, 'wpmu_new_blog' ), 10, 2 );
		add_filter( 'mlp_after_new_blog_fields', array ( $this, 'display_fields' ) );
	}

	/**
	 * Duplicates the old blog to the new blog
	 *
	 * @global    wpdb $wpdb WordPress Database Wrapper
	 * @param	int $blog_id the new blog id
	 * @return	void
	 */
	public function wpmu_new_blog( $blog_id ) {

		// Return if we don't have a blog
		if ( ! isset ( $_POST[ 'blog' ][ 'basedon' ] ) || 1 > $_POST[ 'blog' ][ 'basedon' ] )
			return;

		$source_blog_id = (int) $_POST[ 'blog' ][ 'basedon' ];

		// Hook information
		$context = array (
			'source_blog_id' => $source_blog_id,
			'new_blog_id'    => $blog_id
		);

		// Switch to the base blog
		switch_to_blog( $source_blog_id );

		$old_prefix = $this->wpdb->prefix;
		$domain     = $this->get_mapped_domain();
		$tables     = $this->get_table_names( $context );

		// Switch to our new blog
		restore_current_blog();
		switch_to_blog( $blog_id );

		// Set the stuff
		$current_admin_email = get_option( 'admin_email' );
		$url                 = get_option( 'siteurl' );

		// truncate all tables
		foreach ( $tables as $table ) {
			$this->duplicator->replace_content(
				$this->wpdb->prefix . $table,
				$old_prefix . $table,
				TRUE
			);
		}

		$this->update_admin_email( $current_admin_email );

		// if an url was used in the old blog, we set it to this url to change all content elements
		// change siteurl -> will start url rename plugin
		if ( '' != $domain )
			update_option( 'siteurl', $domain );

		update_option( 'blogname', stripslashes( $_POST [ 'blog' ][ 'title' ] ) );
		update_option( 'home', $url );

		// change siteurl -> will start url rename plugin
		update_option( 'siteurl', $url );

		$this->wpdb->update(
			$this->wpdb->options,
			array( 'option_name' => $this->wpdb->prefix . 'user_roles' ),
			array( 'option_name' => $old_prefix . 'user_roles' )
		);

		$this->insert_post_relations( $source_blog_id, $blog_id );
		$this->copy_attachments( $source_blog_id, $blog_id, $blog_id );

		restore_current_blog();

		/**
		 * Called after successful blog duplication.
		 *
		 * @param array $context Two blog ids: 'source_blog_id' and 'new_blog_id'.
		 */
		do_action( 'mlp_duplicated_blog', $context );
	}

	/**
	 * Update the admin email option.
	 *
	 * We cannot use update_option(), because that would trigger a
	 * confirmation email to the new address.
	 *
	 * @param  string $admin_email
	 * @return void
	 */
	private function update_admin_email( $admin_email ) {

		$this->wpdb->update(
				   $this->wpdb->options,
				   array( 'option_value' => $admin_email ),
				   array( 'option_name'  => 'admin_email' )
		);
	}

	/**
	 * Get the primary domain if domain mapping is active
	 *
	 * @return string
	 */
	private function get_mapped_domain() {

		if ( empty ( $this->wpdb->dmtable ) )
			return '';

		$sql    = 'SELECT domain FROM ' . $this->wpdb->dmtable . ' WHERE active = 1 AND blog_id = %s LIMIT 1';
		$sql    = $this->wpdb->prepare( $sql, get_current_blog_id() );
		$domain = $this->wpdb->get_var( $sql );

		if ( '' === $domain )
			return '';

		return ( is_ssl() ? 'https://' : 'http://' ) . $domain;
	}

	/**
	 * Tables to copy.
	 *
	 * @param array $context
	 * @return array
	 */
	private function get_table_names( Array $context ) {

		$table_names = new Mlp_Table_Names( $this->wpdb, $context[ 'new_blog_id' ] );
		$tables      = $table_names->get_core_site_tables( FALSE );

		/**
		 * Filter tables to copy.
		 *
		 * Use this if you want to add custom tables to the copy process.
		 *
		 * @param  array $tables
		 * @param  array $context Two blog ids: 'source_blog_id' and 'new_blog_id'.
		 * @return array
		 */
		$tables = apply_filters( 'mlp_tables_to_duplicate', $tables, $context );

		return $tables;
	}

	/**
	 * Get all linked elements from source blog and set links to those in our new blog.
	 *
	 * @param int $source_blog_id
	 * @param int $target_blog_id
	 * @return int|false Number of rows affected/selected or false on error
	 */
	private function insert_post_relations( $source_blog_id, $target_blog_id ) {

		if ( $this->has_related_blogs( $source_blog_id ) )
			return $this->copy_post_relationships( $source_blog_id, $target_blog_id );

		return $this->create_post_relationships( $source_blog_id, $target_blog_id );
	}


	/**
	 * Copy post relationships from source blog to target blog.
	 *
	 * @param int $source_blog_id
	 * @param int $target_blog_id
	 * @return int|FALSE Number of rows affected or FALSE on error
	 */
	private function copy_post_relationships( $source_blog_id, $target_blog_id ) {

		$query = "INSERT INTO `{$this->link_table}`
		(
			`ml_source_blogid`,
			`ml_source_elementid`,
			`ml_blogid`,
			`ml_elementid`,
			`ml_type`
		)
		SELECT
			`ml_source_blogid`,
			`ml_source_elementid`,
			$target_blog_id,
			`ml_elementid`,
			`ml_type`
		FROM `{$this->link_table}`
		WHERE  `ml_blogid` = $source_blog_id";

		return $this->wpdb->query( $query );
	}

	/**
	 * Create post relationships between all posts from source blog and target blog.
	 *
	 * @param int $source_blog_id
	 * @param int $target_blog_id
	 * @return int|FALSE Number of rows affected or FALSE on error
	 */
	private function create_post_relationships( $source_blog_id, $target_blog_id ) {

		$blogs  = array ( $source_blog_id, $target_blog_id );
		$result = FALSE;

		foreach( $blogs as $blog ) {
			$result = $this->wpdb->query(
				"INSERT INTO {$this->link_table}
				(
					`ml_source_blogid`,
					`ml_source_elementid`,
					`ml_blogid`,
					`ml_elementid`,
					`ml_type`
				)
				SELECT $source_blog_id, `ID`, $blog, ID, `post_type`
					FROM {$this->wpdb->posts}
					WHERE `post_status` IN('publish', 'future', 'draft', 'pending', 'private')"
			);
		}

		return $result;
	}

	/**
	 * Check if there are any registered relations for the source blog.
	 *
	 * @param  int $source_blog_id
	 * @return boolean
	 */
	private function has_related_blogs( $source_blog_id ) {

		$sql = "SELECT `ml_id` FROM {$this->link_table} WHERE `ml_blogid` = $source_blog_id LIMIT 2";

		return 2 == $this->wpdb->query( $sql );
	}

	/**
	 * Copy all attachments from source blog to new blog.
	 *
	 * @param int $from_id
	 * @param int $to_id
	 * @param int $final_id
	 * @return void
	 */
	private function copy_attachments( $from_id, $to_id, $final_id ) {

		$copy_files = new Mlp_Copy_Attachments( $from_id, $to_id, $final_id );

		if ( $copy_files->copy_attachments() )
			$this->update_file_urls( $copy_files );
	}

	/**
	 * Replace file URLs in new blog.
	 *
	 * @param Mlp_Copy_Attachments $copy_files
	 * @return int|false Number of rows affected/selected or false on error
	 */
	private function update_file_urls( $copy_files ) {

		$tables = array (
			$this->wpdb->posts         => array (
				'guid',
				'post_content',
				'post_excerpt',
				'post_content_filtered',
			),
			$this->wpdb->term_taxonomy => array (
				'description'
			),
			$this->wpdb->comments      => array (
				'comment_content'
			)
		);

		$db_replace = new Mlp_Db_Replace(
			$tables,
			$copy_files->source_url,
			$copy_files->dest_url,
			$this->wpdb
		);

		return $db_replace->replace();
	}

	/**
	 * Add copy field at "Add new site" screen
	 *
	 * @return	void
	 */
	public function display_fields() {

		$blogs   = (array) $this->get_all_sites();
		$options = '<option value="0">' . __( 'Choose site', 'multilingualpress' ) . '</option>';

		foreach ( $blogs as $blog ) {

			if ( '/' === $blog[ 'path' ] )
				$blog[ 'path' ] = '';

			$options .= '<option value="' . $blog[ 'blog_id' ] . '">'
				. $blog[ 'domain' ] . $blog[ 'path' ]
				. '</option>';
		}

		?>
		<tr class="form-field">
			<td>
				<label for="inpsyde_multilingual_based">
					<?php
					esc_html_e( 'Based on site', 'multilingualpress' );
					?>
				</label>
			</td>
			<td>
				<select id="inpsyde_multilingual_based" name="blog[basedon]"><?php echo $options; ?></select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get all existing blogs.
	 *
	 * @return array
	 */
	private function get_all_sites() {

		$sql = "SELECT `blog_id`, `domain`, `path`
			FROM {$this->wpdb->blogs}
			WHERE deleted = 0 AND site_id = '{$this->wpdb->siteid}' ";

		return $this->wpdb->get_results( $sql, ARRAY_A );
	}
}