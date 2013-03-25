<?php

# If we're doing bootstrap AJAX, register requests right after including the logging functions
add_action((defined('DOING_BOOTSTRAP') && DOING_BOOTSTRAP ? 'cookspin_include' : 'init'), 'cookspin_register_default_loggers', 1);
function cookspin_register_default_loggers() {
	# Log new blog registrations (main site + blog)
	cookspin_register_logger('new_blog', 'blogs',
		array(
			'hook' => 'wpmu_new_blog',
			'priority' => 99,
			'n_params' => 2,
			'cb' => 'cookspin_log_new_blog_callback',
			'print_cb' => 'cookspin_log_print_new_blog'
		)
	);

	# Log blog deletions
	cookspin_register_logger('delete_blog', 'blogs',
		array(
			'hook' => 'delete_blog',
			'priority' => 99,
			'cb' => 'cookspin_log_delete_blog_callback',
			'print_cb' => 'cookspin_log_print_delete_blog'			
		)
	);

	# Log post modifications
	cookspin_register_logger('posts_transitions', 'posts',
		array(
			'hook' => 'transition_post_status',
			'n_params' => 3,
			'cb' => 'cookspin_log_posts_transitions_callback',
			'print_cb' => 'cookspin_log_print_posts_transitions'			
		)
	);

	# Log user creation
	cookspin_register_logger('user_create', 'users',
		array(
			'hook' => 'wpmu_new_user',
			'cb' => 'cookspin_log_user_create_callback',
			'print_cb' => 'cookspin_log_print_user_create'
		)
	);

	# Log user addition to blog
	# Note: we must first remove the action to avoid add_new_user_to_blog actions firing twice
	remove_action('wpmu_activate_user', 'add_new_user_to_blog', 10, 3 );
	cookspin_register_logger('add_user_to_blog', 'users',
		array(
			'hook' => 'add_user_to_blog',
			'n_params' => 3,
			'cb' => 'cookspin_log_user_add_to_blog_callback',
			'print_cb' => 'cookspin_log_print_user_add_to_blog'
		)
	);

	# Log user edits
	cookspin_register_logger('user_edit', 'users',
		array(
			'hook' => 'edit_user_profile_update',
			'cb' => 'cookspin_log_user_edit_callback',
			'print_cb' => 'cookspin_log_print_user_edit'
		)
	);

	# Log self profile updates
	cookspin_register_logger('update_profile', 'users',
		array(
			'hook' => 'personal_options_update',
			'cb' => 'cookspin_log_update_profile_callback',
			'print_cb' => 'cookspin_log_print_update_profile'
		)
	);

	# Log user removal from blog
	cookspin_register_logger('remove_user', 'users',
		array(
			'hook' => 'remove_user_from_blog',
			'n_params' => 2,
			'cb' => 'cookspin_log_remove_user_callback',
			'print_cb' => 'cookspin_log_print_remove_user'
		)
	);
	
	# Log user deletions
	cookspin_register_logger('delete_user', 'users',
		array(
			'hook' => 'wpmu_delete_user',
			'cb' => 'cookspin_log_user_delete_callback', # Uses the same callback as user creation
			'print_cb' => 'cookspin_log_print_delete_user'
		)
	);

	# Log WP Preferences updates
	cookspin_register_logger('wp_preferences', 'preferences',
		array(
			'hook' => 'cookspin_wp_preferences',
			'n_params' => 2,
			'cb' => 'cookspin_log_update_preferences_callback',
			'print_cb' => 'cookspin_log_print_self_preferences'
		)
	);
	
	# Log Attachment uploads
	cookspin_register_logger('add_attachment', 'media',
		array(
			'hook' => 'add_attachment',
			'cb' => 'cookspin_log_attachment_callback',
			'print_cb' => 'cookspin_log_print_attachment'
		)
	);

	# Log Attachment edits
	cookspin_register_logger('edit_attachment', 'media',
		array(
			'hook' => 'edit_attachment',
			'cb' => 'cookspin_log_attachment_callback',
			'print_cb' => 'cookspin_log_print_attachment'
		)
	);

	# Same as above, but detect changes on the actual image, not just the post object
	cookspin_register_logger('update_attachment', 'media',
		array(
			'hook' => 'wp_update_attachment_metadata',
			'n_params' => 2,
			'cb' => 'cookspin_update_attachment_callback',
			'print_cb' => 'cookspin_log_print_attachment',
			'hook_to_filter' => true
		)
	);

	# Log Attachment deletion
	cookspin_register_logger('delete_attachment', 'media',
		array(
			'hook' => 'delete_attachment',
			'cb' => 'cookspin_log_attachment_callback',
			'print_cb' => 'cookspin_log_print_attachment'
		)
	);

	# Log comment transitions
	cookspin_register_logger('comment_transitions', 'comments',
		array(
			'hook' => 'transition_comment_status',
			'n_params' => 3,
			'cb' => 'cookspin_log_comment_transitions_callback',
			'print_cb' => 'cookspin_log_print_comment_transitions'
		)
	);

	# Log comment editing
	cookspin_register_logger('edit_comment', 'comments',
		array(
			'hook' => 'edit_comment',
			'cb' => 'cookspin_log_edit_comment_callback',
			'print_cb' => 'cookspin_log_print_comment_transitions'
		)
	);

	# Log comment additions
	cookspin_register_logger('comment_add', 'comments',
		array(
			'hook' => 'wp_insert_comment',
			'n_params' => 2,
			'cb' => 'cookspin_log_comment_add_callback',
			'print_cb' => 'cookspin_log_print_comment_add'
		)
	);

	# Log theme changes
	cookspin_register_logger('theme_switched', 'appearance',
		array(
			'hook' => 'switch_theme',
			'n_params' => 2,
			'cb' => 'cookspin_log_theme_switched_callback',
			'print_cb' => 'cookspin_log_print_theme_switched'
		)
	);

	# Log theme modifications
	$theme = get_option('stylesheet');
	cookspin_register_logger('theme_mods_changed', 'appearance',
		array(
			'hook' => 'update_option_theme_mods_' . $theme,
			'n_params' => 2,
			'cb' => 'cookspin_log_theme_modified_callback',
			'print_cb' => 'cookspin_log_print_theme_modified'
		)
	);

	cookspin_register_logger('theme_options_changed', 'appearance',
		array(
			'hook' => 'update_option_' . $theme . '_theme_options',
			'n_params' => 2,
			'cb' => 'cookspin_log_theme_modified_callback',
			'print_cb' => 'cookspin_log_print_theme_modified'
		)
	);
	
	# Log widget changes / reordering
	global $pagenow;
	if($pagenow == 'admin-ajax.php' && $_REQUEST['action'] == 'widgets-order') {
		do_action('cookspin_widgets_updated'); # Due to lack of hooks, this log requires JS to run
	}
	cookspin_register_logger('widgets_updated', 'appearance',
		array(
			'hook' => 'cookspin_widgets_updated',
			'cb' => 'cookspin_log_widgets_updated_callback',
			'print_cb' => 'cookspin_log_print_widgets_updated'
		)
	);

}

# Supporting hooks
# Note: must be added via hooks that load after wp_loaded is called, otherwise logs won't run

add_action('admin_init', 'cookspin_wp_preferences_insert_actions', 99);
function cookspin_wp_preferences_insert_actions() {
	$screens = apply_filters('cookspin_activity_preferences_screens', array('general', 'media', 'permalink', 'writing', 'reading', 'discussion'));
	foreach($screens as $screen) {
		# Dummy option must exist for WP not to launch our hooks twice
		update_option('cookspin_wppref_dummy_' . $screen, '');
		$function = 'do_action(\'cookspin_wp_preferences\', $GLOBALS[\'blog_id\'], ' . "'$screen'" . ');';
		register_setting($screen, 'cookspin_wppref_dummy_' . $screen, create_function('', $function));
	}
}

add_action('admin_init', 'add_widget_log_action', 99);
function add_widget_log_action() {
	global $pagenow;
	if($pagenow == 'admin-ajax.php' && $_REQUEST['action'] == 'widgets-order') {
		do_action('cookspin_widgets_updated', $GLOBALS['blog_id']); # Due to lack of hooks, this log requires JS to run
	}
}

# Supporting functions

function cookspin_user_on_log($log, $user) {
	$user = get_userdata($log->object_id);
	if($user) {
		$display_name = null;
		if($user->first_name != '') {
			$display_name .= $user->first_name;
		}
		if($user->last_name != '') {
			$display_name .= ($user->first_name != '' ? ' ' : '') . $user->last_name;
		}
		
		if($display_name == null) {
			$display_name = $user->display_name;
		}
		
		$display_name = apply_filters('cookspin_user_on_log_display_name', $display_name, $log, $user);
		
		$edit_link = current_user_can('edit_user', $log->object_id) ? (is_network_admin() ? add_query_arg(array('user_id' => $log->object_id), network_admin_url('user-edit.php')) : add_query_arg(array('user_id' => $log->object_id), admin_url('user-edit.php'))) : false;
		$edited = $edit_link ? '<a href="' . $edit_link . '">' . $display_name . '</a>' : $display_name;
	}
	else {
		$meta = $log->logmeta;
		$display_name = sprintf(__('user #%s', 'ck_activity'), $log->object_id . ' (' . $meta['username'] . ')');

		$display_name = apply_filters('cookspin_user_on_log_deleted_user_display_name', $display_name, $log, $user);

		$edited = '<span class="deadlog">' . $display_name . '</span>';
	}
	return $edited;
}

function cookspin_post_title_on_log($log) {
	$post = get_post($log->object_id);

	$title = apply_filters('cookspin_post_title_on_log', get_the_title($log->object_id), $log);
	$link = apply_filters('cookspin_post_title_on_log_link', get_permalink($log->object_id), $log);

	if(!$title || $title == '--' || trim($title) == null) {
		if($title != '--' && $log->logmeta['title'] != null && $log->logmeta['title'] != ' ') {
			$title = '<span class="deadlog">' . $log->logmeta['title'] . '</span>'; # DB fallback, no link
		}
		else {
			$post_type = get_post_type_object($log->object_type);
			if($post_type && $post_type->name != 'post') {
				$title = sprintf(__('an untitled %s', 'ck_activity'), $post ? '<a href="' . $link . '">' . $post_type->labels->singular_name . '</a>' : $post_type->labels->singular_name);
			}
			else {
				$label = __('Post', 'ck_activity');
				$title = sprintf(__('an untitled %s', 'ck_activity'), $post ? '<a href="' . get_permalink($log->object_id) . '">' . $label . '</a>' : $label);
			}
		}
	}
	else {
		$title = '<a href="' . $link . '">' . $title . '</a>';
	}
	return $title;
}

# Callbacks and references


# New blog

function cookspin_log_new_blog_callback($blog_id, $user_id) {
	# Get blog name
	$details = get_blog_details($blog_id);
	# Log to main blog
	$log[] = array(
		'object_id' => $blog_id,
		'object_type' => 'blog',
		'logmeta' => array('title' => $details->blogname)
	);
	# Install log table on new blog
	cookspin_log_install($blog_id);
	# Log to new blog
	$log[] = array(
		'object_id' => $blog_id,
		'object_type' => 'blog',
		'logmeta' => array('title' => $details->blogname),
		'user_id' => $user_id,
		'blog_id' => $blog_id,
	);
	return $log;
}

function cookspin_log_print_new_blog($log, $user) {
	$details = get_blog_details($log->object_id);
	$title = $details ? '<a href="' . $details->siteurl . '">' . $details->blogname . '</a>' : '<span class="deadlog">' . $log->logmeta['title'] . '</span>';
	return sprintf(__('created a new site: %s', 'ck_activity'), $title);
}

# Delete blog

function cookspin_log_delete_blog_callback($blog_id) {
	$details = get_blog_details($blog_id);
	# Log to main blog
	$log[] = array(
		'object_id' => $blog_id,
		'object_type' => 'blog',
		'log_code' => 2,
		'logmeta' => array('title' => $details->blogname),
		'blog_id' => $blog_id,
		'log_to_main' => true
	);
	# Delete log table
	cookspin_log_uninstall($blog_id);
	return $log;
}

function cookspin_log_print_delete_blog($log, $user) {
	$title = '<span class="deadlog">' . $log->logmeta['title'] . '</span>';
	return sprintf(__('deleted the site: %s', 'ck_activity'), $title);
}

# Post transitions

function cookspin_log_posts_transitions_callback($new_status, $old_status, $post) {
	$log_code = false;
	if($old_status == $new_status) {
		$log_code = 2; # Modified
	}
	elseif($old_status != $new_status) {
		if($old_status == 'new' || $old_status == 'auto-draft') {
			if($new_status == 'draft') {
				$log_code = 3; # Created a new draft / Created archived version
			}
			elseif($new_status == 'pending') {
				$log_code = 4; # Created a post pending revision / Created work in progress
			}
			elseif($new_status == 'publish') {
				$log_code = 1; # Published
			}			
		}
		elseif($old_status == 'trash') {
			$log_code = 5; # Restored from trash
		}
		else {
			if($new_status == 'draft') {
				$log_code = 6; # Saved as draft / Archived
			}
			elseif($new_status == 'pending') {
				$log_code = 7; # Saved as pending / Marked as Work in progress
			}
			elseif($new_status == 'future') {
				$log_code = 8; # Scheduled publication
			}
			elseif($new_status == 'trash') {
				$log_code = 9; # Deleted
			}
			elseif($new_status == 'private') {
				$log_code = 10; # Marked as private
			}
			elseif($new_status == 'inherit') {
				$log_code = 11; # Saved a revision
			}
			elseif($new_status == 'publish') {
				$log_code = 1; # Published
			}
		}
	}

	$log_code = apply_filters('cookspin_log_post_transitions_log_code', $log_code, $new_status, $old_status, $post);
	
	if($log_code) {
		$log[] = array(
			'object_id' => $post->ID,
			'object_type' => $post->post_type,
			'log_code' => $log_code,
			'logmeta' => array('title' => apply_filters('cookspin_post_title_on_logmeta', $post->post_title, $post))
		);
		return $log;
	}
	return false;
}

function cookspin_log_print_posts_transitions($log, $user) {
	$df = get_option('date_format') . ' @ ' . get_option('time_format');
	$post = get_post($log->object_id);
	$title = cookspin_post_title_on_log($log);
	$log_codes = array(
		1	=> sprintf(__('published %s.', 'ck_activity'), $title),
		2	=> sprintf(__('modified %s.', 'ck_activity'), $title),
		3	=> sprintf(__('created a new draft called %s.', 'ck_activity'), $title),
		4	=> sprintf(__('created %s, pending review.', 'ck_activity'), $title),
		5	=> sprintf(__('restored %s from the trash.', 'ck_activity'), $title),
		6	=> sprintf(__('saved %s as draft.', 'ck_activity'), $title),
		7	=> sprintf(__('saved %s as pending review.', 'ck_activity'), $title),
		8	=> $post != null ? sprintf(__('scheduled %1$s to be published on %2$s.', 'ck_activity'), $title, get_the_time($df, $log->object_id)) : __('scheduled %1$s for future publication.', 'ck_activity'),
		9	=> sprintf(__('deleted %s.', 'ck_activity'), $title),
		10 => sprintf(__('marked %s as private.', 'ck_activity'), $title),
		11 => sprintf(__('saved a new revision of %s.', 'ck_activity'), $title),
	);
	
	$log_codes = apply_filters('cookspin_log_print_post_transitions_log_codes', $log_codes, $log, $user);

	return $log_codes[$log->log_code];
}

# User creation

function cookspin_log_user_create_callback($user_id) {
	if(!defined('CK_LOG_ADDING_USER')) {
		define('CK_LOG_ADDING_USER', TRUE);
	}
	$user = get_userdata($user_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login),
		'log_to_main' => true		
	);
	return $log;
}

function cookspin_log_print_user_create($log, $user) {
	return sprintf(__('created a new network user: %s', 'ck_activity'), cookspin_user_on_log($log, $user));
}

function cookspin_log_user_delete_callback($user_id) {
	$user = get_userdata($user_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login),
		'log_to_main' => true		
	);
	return $log;
}

function cookspin_log_print_delete_user($log, $user) {
	return sprintf(__('permanently removed %s from the network.', 'ck_activity'), cookspin_user_on_log($log, $user));	
}

# User add to blog

function cookspin_log_user_add_to_blog_callback($user_id, $role, $blog_id) {

	# When installing a new blog, user will be added before the hooks which install the log tables on db are fired, therefore this
	# will always fail if WP_INSTALLING is true
	if(defined('WP_INSTALLING')) {
		return false;
	}

	if(!defined('CK_LOG_ADDING_USER')) {
		define('CK_LOG_ADDING_USER', TRUE);
	}
	$user = get_userdata($user_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login)
	);
	# Log to main site
	$details = get_blog_details($blog_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login, 'blog_id' => $blog_id, 'title' => $details->blogname),
		'log_to_main' => true
	);
	return $log;
}

function cookspin_log_print_user_add_to_blog($log, $user) {
	if(is_cookspin_main()) {
		$meta = $log->logmeta;
		if(!$meta) {
			return sprintf(__('added %s to a site in the network.', 'ck_activity'), cookspin_user_on_log($log, $user));
		}
		elseif($meta && isset($meta['blog_id'])) {
			$details = get_blog_details($meta['blog_id']);
			$site = $details ? '<a href="' . $details->siteurl . '">' . $details->blogname . '</a>' : '<span class="deadlog">' . $meta['title'] . '</span>';
			return sprintf(__('added %1$s to %2$s.', 'ck_activity'), cookspin_user_on_log($log, $user), $site);
		}
	}
	return sprintf(__('added %s to this site.', 'ck_activity'), cookspin_user_on_log($log, $user));
}

# User edit

function cookspin_log_user_edit_callback($user_id) {
	$user = get_userdata($user_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login)
	);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login),
		'log_to_main' => true
	);
	return $log;
}

function cookspin_log_print_user_edit($log, $user) {
	return sprintf(__('updated %s\'s profile.', 'ck_activity'), cookspin_user_on_log($log, $user));
}

# Profile update

function cookspin_log_update_profile_callback($user_id) {
	$user = get_userdata($user_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login)
	);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user',
		'logmeta' => array('username' => $user->user_login),
		'log_to_main' => true
	);
	return $log;
}

function cookspin_log_print_update_profile($log, $user) {
	return __('updated his/her profile.', 'ck_activity');
}

function cookspin_log_remove_user_callback($user_id, $blog_id) {
	if(defined('CK_LOG_ADDING_USER') && CK_LOG_ADDING_USER) {
		return;
	}
	$user = get_userdata($user_id);
	if($blog_id == '1') { # Check for main site, otherwise WP will call remove_user_from_blog twice on main site
		return;
	}
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => apply_filters('cookspin_log_user_object_type', 'user', $user),
		'logmeta' => array('username' => $user->user_login)
	);
	# Log to main site
	$details = get_blog_details($blog_id);
	$log[] = array(
		'object_id' => $user_id,
		'object_type' => 'user', # On the main site they're all users
		'logmeta' => array('username' => $user->user_login, 'blog_id' => $blog_id, 'title' => $details->blogname),		
		'log_to_main' => true
	);
	return $log;
}

function cookspin_log_print_remove_user($log, $user) {
	if(is_cookspin_main()) {
		$meta = $log->logmeta;
		if(!$meta) {
			return sprintf(__('removed %s from a site in the network.', 'ck_activity'), cookspin_user_on_log($log, $user));
		}
		elseif($meta && isset($meta['blog_id'])) {
			$details = get_blog_details($meta['blog_id']);
			$site = $details ? '<a href="' . $details->siteurl . '">' . $details->blogname . '</a>' : '<span class="deadlog">' . $meta['title'] . '</span>';
			return sprintf(__('removed %1$s from %2$s.', 'ck_activity'), cookspin_user_on_log($log, $user), $site);
		}
	}
	return sprintf(__('removed %s from this site.', 'ck_activity'), cookspin_user_on_log($log, $user));
}

function cookspin_log_update_preferences_callback($blog_id, $type) {
	$log[] = array(
		'object_id' => $blog_id,
		'object_type' => $type,
	);
	return $log;
}

function cookspin_log_print_self_preferences($log, $user) {
	$settings = array(
		'general' => sprintf(__('updated this site\'s <a href="%s">General</a> settings', 'ck_activity'), admin_url('options-general.php')),
		'media' => sprintf(__('updated this site\'s <a href="%s">Media</a> settings', 'ck_activity'), admin_url('options-media.php')),
		'reading' => sprintf(__('updated this site\'s <a href="%s">Presentation</a> settings', 'ck_activity'), admin_url('options-reading.php')),
		'writing' => sprintf(__('updated this site\'s <a href="%s">Writing</a> settings', 'ck_activity'), admin_url('options-writing.php')),
		'discussion' => sprintf(__('updated this site\'s <a href="%s">Discussion</a> settings', 'ck_activity'), admin_url('options-discussion.php'))
	);
	
	$settings = apply_filters('cookspin_log_print_preferences_types', $settings);
	
	if(isset($settings[$log->object_type])) {
		return $settings[$log->object_type];
	}
	return sprintf(__('updated this site\'s settings', 'ck_activity'));
}

function cookspin_log_attachment_callback($id) {
	$att = get_post($id);
	$log[] = array(
		'object_id' => $id,
		'object_type' => $att->post_mime_type,
	);
	return $log;
}

function cookspin_update_attachment_callback($data, $id) {
	$att = get_post($id);
	$log[] = array(
		'object_id' => $id,
		'object_type' => $att->post_mime_type,
	);
	return $log;
}

function cookspin_log_print_attachment($log, $user) {
	$att = get_post($log->object_id);
	$title = $att ? sprintf(_x('an %s', 'an image, with link', 'ck_activity'), '<a title="' . $att->post_title . '" href="' . get_edit_post_link($log->object_id) . '">' . __('image', 'ck_activity') . '</a>') : sprintf(__('the image #%s', 'ck_activity'), $log->object_id);
	if(preg_match('/image/', $log->object_type)) {
		if($log->logger == 'add_attachment') {
			return sprintf(__('added %s.', 'ck_activity'), $title);
		}
		elseif($log->logger == 'edit_attachment' || $log->logger == 'update_attachment') {
			return sprintf(__('edited %s.', 'ck_activity'), $title);
		}
		elseif($log->logger == 'delete_attachment') {
			return sprintf(__('removed %s.', 'ck_activity'), $title);
		}		
	}
	else {
		if($log->logger == 'add_attachment') {
			return sprintf(__('added media to the site.', 'ck_activity'));
		}
		elseif($log->logger == 'delete_attachment') {
			return sprintf(__('removed media from the site.', 'ck_activity'));
		}
		elseif($log->logger == 'edit_attachment' || $log->logger == 'update_attachment') {
			return sprintf(__('edited media from this site.', 'ck_activity'));
		}
	}
}

function cookspin_log_comment_transitions_callback($new_status, $old_status, $comment) {
	$log_code = false;
	if($old_status != $new_status) {
		if($old_status == 'trash') {
			$log_code = 5; # Restored from trash
		}
		elseif($old_status == 'spam') {
			$log_code = 6; # Not spam
		}
		elseif($new_status == 'approved') {
				$log_code = 3; # Approved
		}
		elseif($new_status == 'unapproved') {
				$log_code = 4; # Unapproved
		}
		elseif($new_status == 'spam') {
			$log_code = 7; # Spam
		}
		elseif($new_status == 'trash') {
			$log_code = 8; # Trashed
		}
	}
	if($log_code) {
		$log[] = array(
			'object_id' => $comment->comment_ID,
			'object_type' => 'comment',
			'log_code' => $log_code,
		);
		return $log;
	}
}

function cookspin_log_edit_comment_callback($comment_ID) {
	$log[] = array(
		'object_id' => $comment_ID,
		'object_type' => 'comment',
		'log_code' => 2, # For consistency with function above / posts transitions
	);
	return $log;
}

function cookspin_log_print_comment_transitions($log, $user) {
	$output = array(
		2 => __('edited a comment.', 'ck_activity'),
		3 => __('approved a comment.', 'ck_activity'),
		4 => __('marked a comment as unapproved.', 'ck_activity'),
		5 => __('restored a comment from the trash.', 'ck_activity'),
		6 => __('marked a comment as not spam.', 'ck_activity'),
		7 => __('marked a comment as spam.', 'ck_activity'),
		8 => __('sent a comment to the trash.', 'ck_activity'),
	);
	return $output[$log->log_code];
}

function cookspin_log_comment_add_callback($id, $comment) {
	$log[] = array(
		'object_id' => $comment->comment_post_ID,
		'object_type' => 'comment',
		'logmeta' => array('comment_id' => $id, 'post_title' => get_post($id)->post_title)
	);
	return $log;
}

function cookspin_log_print_comment_add($log, $user) {
	$post_title = cookspin_post_title_on_log($log);
	return sprintf(__('added a new comment to %s', 'ck_activity'), $post_title);
}

function cookspin_log_widgets_updated_callback($blog_id) {
	$log[] = array(
		'object_id' => $blog_id,
		'object_type' => 'widgets',
	);
	return $log;
}

function cookspin_log_print_widgets_updated($log, $user) {
	return __('modified the theme\'s widgets settings.', 'ck_activity');
}

function cookspin_log_theme_switched_callback($theme_name, $theme) {
	$log[] = array(
		'object_id' => $theme->stylesheet,
		'object_type' => 'theme',
		'logmeta' => array('theme_name' => $theme_name)
	);
	return $log;
}

function cookspin_log_print_theme_switched($log, $user) {
	$theme = '<a href="' . admin_url('themes.php') . '">' . $log->logmeta['theme_name'] . '</a>';
	return sprintf(__('switched the site\'s theme to %s.', 'ck_activity'), $theme);
}

function cookspin_log_theme_modified_callback($oldvalue, $_newvalue) {
	
	# Avoid duplicate logging of the same action (since we're listening to theme_mods and theme_options modifications)
	# Don't update this if switching between themes without using customizer -- options are updated in order for WP to remember theme settings,
	# not because a user actually modified them
	if(defined('CK_LOG_MODIFIED_THEME') || (isset($_GET['action']) && $_GET['action'] == 'activate')) {
		return false;
	}
	
	define('CK_LOG_MODIFIED_THEME', true);
	
	$theme = wp_get_theme(get_option('stylesheet'));
	$log[] = array(
		'object_id' => $theme->stylesheet,
		'object_type' => 'theme',
		'logmeta' => array('theme_name' => $theme->get('Name'))
	);
	return $log;	
}

function cookspin_log_print_theme_modified($log, $user) {
	$theme = '<a href="' . admin_url('themes.php') . '">' . $log->logmeta['theme_name'] . '</a>';
	return sprintf(__('customized theme %s.', 'ck_activity'), $theme);	
}

?>