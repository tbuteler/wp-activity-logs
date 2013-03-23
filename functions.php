<?php

/*

Plugin Name: Cookspin Activity Logs
Description: Logs and displays the activity of your WordPress blog. Easy to setup and extensible.
Version: 1.0
Author: Cookspin
Author URI: http://cookspin.com
License: GPLv2

Text Domain: ck_activity

*/

define('CK_LOG_VERSION', '1.1');
define('CK_LOG_DB_VERSION', '1.1');

# Include default loggers
include_once('loggers.php');

add_action('init', 'cookspin_log_install', 9);
function cookspin_log_install($blog_id = false) {

	$blog_id = !$blog_id && is_multisite() && $GLOBALS['blog_id'] != 1 ? $GLOBALS['blog_id'] : $blog_id;

	$current_db = $blog_id ? get_blog_option($blog_id, 'cookspin_log_db_version') : get_option('cookspin_log_db_version');
	$has_log = $blog_id ? get_blog_option($blog_id, 'cookspin_has_log') : get_option('cookspin_has_log');

	if(!$blog_id || ($blog_id && (CK_LOG_DB_VERSION != $current_db || !$has_log))) {

		global $wpdb;
		$suffix = $blog_id ? ($blog_id == 1 ? '' : $wpdb->escape($blog_id) . '_') : '';
		$table_name = $wpdb->base_prefix . $suffix . 'activity_log';
		
		$sql = "CREATE TABLE $table_name (
	  	  log_id bigint(20) unsigned NOT NULL auto_increment,
		  user_id bigint(20) unsigned NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  logger varchar(255) default NULL,
		  object_id bigint(20) unsigned NOT NULL,
		  object_type varchar(20) default NULL,
		  log_code bigint(20) unsigned NOT NULL,
		  logmeta LONGTEXT default NULL,
		  PRIMARY KEY  (log_id),
		  KEY user_id (user_id)
		) DEFAULT CHARACTER SET $wpdb->charset COLLATE $wpdb->collate;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		if($blog_id) {
			update_blog_option($blog_id, 'cookspin_log_db_version', CK_LOG_DB_VERSION);
			update_blog_option($blog_id, 'cookspin_has_log', 1);		
		}
		else {
			update_option('cookspin_log_db_version', CK_LOG_DB_VERSION);
			update_option('cookspin_has_log', 1);
		}
	}
}

add_action('init', 'cookspin_log_setup', 10); # Allow definition of constants in functions.php to override this settings
function cookspin_log_setup() {
	
	# Define default time difference (in seconds) that will cause our activity log to ignore repeat actions
	# Note: activity will still be logged, but won't be displayed until these settings change
	if(!defined('CK_LOG_TIME_IGNORE')) {
		define('CK_LOG_TIME_IGNORE', 60); # Logs 60 seconds apart will be treated as the same action (same type, same logger only)
	}

	# Define maximum number of records per table
	if(!defined('CK_LOG_MAX_ROWS')) {
		define('CK_LOG_MAX_ROWS', 0); # 0 means no limit;
	}

	# Define default log limit
	if(!defined('CK_LOG_DEFAULT_LIMIT')) {
		define('CK_LOG_DEFAULT_LIMIT', 25); # 25 rows are fetched at a time
	}

	# Define uninstall preference
	if(!defined('CK_LOG_FULL_UNINSTALL')) {
		define('CK_LOG_FULL_UNINSTALL', false); # False means tables and logs will persist on deactivation
	}
}

function cookspin_log_uninstall($blog_id = false) {
	global $wpdb;
	$table = !$blog_id ? $wpdb->base_prefix . 'activity_log' : $wpdb->base_prefix . $wpdb->escape($blog_id) . '_activity_log';
	$wpdb->query("DROP TABLE IF EXISTS $table");
	if($blog_id) {
		delete_blog_option($blog_id, 'cookspin_log_db_version');
		delete_blog_option($blog_id, 'cookspin_has_log');
	}
	else {
		delete_option('cookspin_log_db_version');
		delete_option('cookspin_has_log');
	}
}

class CK_Logger {
	private $name;

	function __construct($name, $category, $args) {
	
		$defaults = array(
			'hook'				=> false,
			'priority'			=> 10,
			'n_params'			=> 1,
			'cb'					=> false,
			'print_cb'			=> false,
			'hook_to_filter'	=> false
		);

		$args = wp_parse_args($args, $defaults);
		extract($args, EXTR_SKIP);
		
		$this->name 				= $name;
		$this->category			= $category;
		$this->hook					= $hook;
		$this->priority			= $priority;
		$this->n_params			= $n_params;
		$this->cb 					= $cb;
		$this->print_cb 			= $print_cb;
		$this->hook_to_filter	= $hook_to_filter;

		$this->register();
	}

	function register() {
		# Require name, hook and logging callback in order to register
		if(!$this->name || !$this->hook || !$this->cb) {
			return;
		}
		add_action('cookspin_activity_log_init', array(&$this, 'init'));

		# Add to global array of loggers
		global $cookspin_loggers;
		$cookspin_loggers[$this->name] = $this;		
	}

	function init() {
		if($this->hook_to_filter) {
			add_filter($this->hook, array(&$this, 'hook_to_filter'), $this->priority, $this->n_params);
			return;
		}
		add_action($this->hook, array(&$this, 'add_log'), $this->priority, $this->n_params);
	}

	function countrows($table_name) {
		global $wpdb;
		$query = "SELECT COUNT(1) FROM $table_name";
		$count = $wpdb->get_results($query, ARRAY_N);
		return $count[0][0];
	}
	
	function purge($table_name, $limit) {
		global $wpdb;
		$limit = $wpdb->escape($limit);
		$query = "
			DELETE FROM $table_name WHERE log_id NOT IN (
				SELECT * FROM (
					SELECT log_id
					FROM $table_name
					ORDER BY log_id DESC LIMIT 0, $limit
				)
			as t);";
		$wpdb->query($query);
	}
	
	function insert_row($args) {
		global $wpdb;
		$current_user = wp_get_current_user();
	
		$defaults = array(			
			'user_id'		=> $current_user->ID,
			'time' 			=> current_time('mysql'),
			'object_id' 	=> false,
			'object_type' 	=> false,
			'log_code' 		=> 1,
			'logmeta' 		=> null,
			'blog_id' 		=> false,
			'log_to_main' 	=> false,
		);
	
		$args = wp_parse_args($args, $defaults);
		extract($args, EXTR_SKIP);
		
		if(!$object_id || !$object_type) {
			return;
		}
		
		# Only allow certain loggers to add logs to the main site
		if(!in_array($this->name, array(
			'delete_blog',
			'user_create',
			'user_add_to_blog',
			'user_edit',
			'update_profile',
			'remove_user'
			))) {
			$log_to_main = false;
		}
		
		$suffix = $blog_id ? $wpdb->escape($blog_id) . '_' : '';
		$table_name = $wpdb->escape(($log_to_main ? $wpdb->base_prefix : $wpdb->prefix . $suffix) . 'activity_log');
	
		# Check current amount of records, and purge if we've reached the set limit
		if(CK_LOG_MAX_ROWS > 0 && $this->countrows($table_name) >= CK_LOG_MAX_ROWS) {
			$this->purge($table_name, (CK_LOG_MAX_ROWS - 1)); # -1 to make room for the new entry
		}
		
		$wpdb->insert($table_name, array(
			'logger' => $this->name,
			'user_id' => $user_id,
			'time' => $time,
			'object_id' => $object_id,
			'object_type' => $object_type,
			'log_code' => $log_code,
			'logmeta' => maybe_serialize($logmeta)
			)
		);
	}
	
	function hook_to_filter() {
		$cb_params = func_get_args();
		call_user_func_array(array($this, 'add_log'), $cb_params);
		return $cb_params[0];
	}
	
	# Note: for a logger function to abort adding a log row, it can return false or an empty array
	function add_log() {
		$cb_params = func_get_args();
		# Logger callback is expected to be an array of args (or an array of arrays in case of multiple insertions)
		$insert = call_user_func_array($this->cb, $cb_params);
		if($insert && sizeof($insert) > 0) {
			foreach($insert as $args) {
				$this->insert_row($args);		
			}
		}
	}
	
	function print_log($log, $previous_log = false, $wrap = false, $context = 'profile') {
		if(function_exists($this->print_cb)) {
			# If logs are closer together than what we're set to ignore, check object_id, object_type and logger
			if($previous_log && (date('U', strtotime($previous_log->time) - date('U', strtotime($log->time)))) < CK_LOG_TIME_IGNORE) {
				# If key parameters are the same, assume user corrected his actions; no need to log twice
				if($previous_log->logger == $log->logger && $previous_log->object_type == $log->object_type && $previous_log->object_id == $log->object_id && $previous_log->log_code == $log->log_code) {
					return $log;				
				}
			}

			# Append category in case plugins wish to check it
			$log->category = $this->category;

			# Allow plugins to establish their own rules for displaying a certain log: returning false on a filter will abort printing
			if(!apply_filters('cookspin_pre_print_log', true, $log, $context)) {
				return $log;
			}

			$current_user = wp_get_current_user();
			$df = get_option('date_format');
			$tf = get_option('time_format');

			$wrap = esc_attr(apply_filters('cookspin_print_log_wrap', $wrap, $context));

			$date = date_i18n($df, strtotime($log->time));
			$previous_date = $previous_log ? date_i18n($df, strtotime($previous_log->time)) : false;
			if($previous_date != $date) {
				if($date == date_i18n($df, strtotime('today'))) {
					$date = __('Today', 'ck_activity');
				}
				echo($wrap ? '<' . $wrap . ' class="activity_date_header' . ($previous_date == false ? ' first' : '') . '">' . $date . '</' . $wrap . '>' : $date . ' ');
			}
			
			$classes = array(
				'log_item',
				'log_category_' . esc_attr($this->category),
				'logger_' . esc_attr($log->logger),
				'log_type_' . esc_attr($log->object_type),
				'log_code_' . esc_attr($log->log_code)
			);
			
			if($current_user->ID == $log->user_id) {
				$classes[] = 'log_item_self';
			}
			
			# Log item classes
			echo($wrap ? '<' . $wrap . ' id="log_item_' . esc_attr($log->log_id) . '" class="' . esc_attr(implode(' ', apply_filters('cookspin_log_item_classes', $classes))) . '">' : '');
			
			# Log time
			echo(($wrap ? '<span class="log_item_time">' : '') . date_i18n($tf, strtotime($log->time)) . ($wrap ? '</span>' : ' '));

			# Log icon
			echo($wrap ? '<span class="log_item_icon"></span>' : '');
					
			$user = get_userdata($log->user_id);
			if($user) {
				$user_display = is_super_admin() && $GLOBALS['blog_id'] == '1' ? '<a href="' . add_query_arg(array('user_id' => esc_attr($log->user_id)), network_admin_url('user-edit.php')) . '">' . $user->display_name . '</a>' : $user->display_name;				
			}
			else {
				$user_display = sprintf(__('User #%s', 'ck_activity'), $log->user_id);
			}

			echo($user_display . ' ');

			$log->logmeta = maybe_unserialize($log->logmeta);
			echo(apply_filters("cookspin_print_log_{$log->logger}", call_user_func_array($this->print_cb, array($log, $user)), $log, $user));
			
			echo($wrap ? '</' . $wrap . '>' : '');
			
			return $log;
		}
	}
}

function cookspin_register_logger($name, $category, $args) {
	$logger = new CK_Logger($name, $category, $args);
	global $cookspin_loggers;
	return isset($cookspin_loggers[$name]);
}

function cookspin_get_log_categories() {
	global $cookspin_loggers;
	$categories = array();
	foreach($cookspin_loggers as $logger => $obj) {
		$categories[$obj->category][] = $logger;
	}

	if(!is_super_admin()) {
		unset($categories['blogs']);
	}

	if(!current_user_can('edit_theme_options')) {
		unset($categories['preferences']);
		unset($categories['appearance']);
	}

	if(!current_user_can('upload_files')) {
		unset($categories['media']);
	}	
	
	return apply_filters('cookspin_get_log_categories', $categories);
}

function cookspin_get_loggers($category = false) {
	$categories = cookspin_get_log_categories();
	if($category) {
		return isset($categories[$category]) ? $categories[$category] : array();
	}
	foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($categories)) as $value) {
		$loggers[] = $value;
	}	
	return $loggers;
}

function cookspin_get_log_categories_labels() {
	$default_labels = array(
		'blogs' 			=> __('Sites', 'ck_activity'),
		'posts' 			=> __('Posts', 'ck_activity'),
		'media' 			=> __('Media', 'ck_activity'),
		'users' 			=> __('Users', 'ck_activity'),
		'preferences' 	=> __('Settings', 'ck_activity'),			
		'comments' 		=> __('Comments', 'ck_activity'),
		'appearance' 	=> __('Appearance', 'ck_activity'),
	);
	
	return apply_filters('cookspin_get_log_categories_labels', $default_labels);
}

function cookspin_get_log_category_label($category) {
	$categories = cookspin_get_log_categories_labels();
	if(isset($categories[$category])) {
		return $categories[$category];
	}
	return false;
}

function cookspin_fetch_log($id, $prefix = false) {
	global $wpdb;
	if(is_numeric($id)) {
		if(!$prefix) {
			$prefix = $wpdb->prefix;
		}
		$table_name = $wpdb->escape($prefix . 'activity_log');
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE log_id = %d", $id));
	}
}

# Output activity log
function cookspin_log_output_activity($args) {

	$current_user = wp_get_current_user();
	$limit = get_user_option('cookspin_activity_log_limit', $current_user->ID);
	if(!$limit) {
		$limit = CK_LOG_DEFAULT_LIMIT;
	}
	
	$defaults = array(			
		'context' 		=> 'profile',
		'wrap' 			=> false,
		'previous_log' => false,
		'limit' 			=> $limit,
		'filter' 		=> false
	);

	$args = wp_parse_args($args, $defaults);
	extract($args, EXTR_SKIP);

	global $wpdb;
	$prefix = $wpdb->prefix;
	
	if(is_super_admin() && isset($_REQUEST['activity_blog_id']) && is_numeric($_REQUEST['activity_blog_id'])) {
		$prefix = 'wp_' . $wpdb->escape($_REQUEST['activity_blog_id']) . '_';
	}
	
	if($previous_log) {
		$previous_log = cookspin_fetch_log($previous_log, $prefix);
	}
	
	$query = "SELECT * FROM " . $prefix . 'activity_log logs WHERE 1=1';
	if($context == 'profile' || !current_user_can('delete_others_posts')) { # Check for Editor privileges
		$query .= " AND logs.user_id = $current_user->ID";
	}
	if($previous_log) {
		$query .= " AND logs.log_id < $previous_log->log_id";	
	}
	if((isset($_REQUEST['activity_log_filter'])) || $filter) {
		$categories = cookspin_get_log_categories();
		$filter = $filter ? $filter : $_REQUEST['activity_log_filter'];
		$filters = call_user_func_array('array_merge', array_map(create_function('$a', 'return cookspin_get_loggers($a);'), explode(',', $filter)));
		$query .= ' AND (';
		foreach($filters as $f) {
			$query .= "logs.logger = %s";
			$params[] = $f;
			if($f != end($filters)) {
				$query .= '	OR ';
			}
		}
		$query .= ')';
	}

	$params[] = $limit;
	$logs = $wpdb->get_results($wpdb->prepare($query . " ORDER BY logs.log_id DESC LIMIT %d", $params));
	if(sizeof($logs) > 0) {
		global $cookspin_loggers;
		foreach($logs as $log) {
			if(is_object($cookspin_loggers[$log->logger])) {
				$previous_log = $cookspin_loggers[$log->logger]->print_log($log, $previous_log, $wrap, $context);
			}		
		}
	}
	elseif(!$previous_log) {
		echo('<span class="empty_activity">' . __('No activity to display', 'ck_activity') . '</span>');
	}
	
	if($wrap && $limit && sizeof($logs) == $limit) { ?>
		<span class="logs_footer">
			<span class="ui_button load_more_logs">. . .</span>
			<span class="hide last_log"><?php $last_log = end($logs); echo($last_log->log_id); ?></span>
			<span class="hide log_wrap_tag"><?php echo($wrap); ?></span>
			<span class="hide log_active_filters"><?php echo($filter ? $filter : ''); ?></span>
			<span class="hide more_logs_nonce"><?php echo(wp_create_nonce('more_logs_nonce')); ?></span>
			<?php if(is_super_admin() && isset($_REQUEST['activity_blog_id']) && is_numeric($_REQUEST['activity_blog_id'])) : ?>
			<span class="hide log_fetch_blog"><?php echo($_REQUEST['activity_blog_id']); ?></span>
			<?php endif; ?>
		</span>
	<?php
	}
}

add_action('wp_loaded', 'cookspin_activity_log_main_hook');
function cookspin_activity_log_main_hook() {
	do_action('cookspin_activity_log_init');
}

# Main output
function cookspin_output_activity($context = 'profile', $id = 'activity', $wrap = 'li', $title = false) {
	$title = apply_filters('cookspin_output_activity_title', $title);
?>
<div id="<?php echo($id); ?>" class="activity_log">
	<?php echo($title ? '<h3 id="activity_header"><span>' . $title . '</span></h3>' : ''); ?>
	<ul>
		<?php
			cookspin_log_output_activity(array(
				'context' => $context,
				'wrap' => $wrap
			));
		?>
	</ul>
</div>
<?php
}

function cookspin_blog_activity_widget() {	
	# Check for Admin privileges, otherwise load editor activity log
	cookspin_output_activity((current_user_can('promote_users') ? 'blog' : 'editor'), 'main_activity', 'li');
}

function cookspin_blog_activity_widget_control() {

	$active_filters = array();
	if(isset($_REQUEST['activity_log_filter'])) {
		$active_filters = explode(',', $_REQUEST['activity_log_filter']);
	}

	global $current_user;
	get_currentuserinfo();
	$limit = get_user_option('cookspin_activity_log_limit', $current_user->ID);
	if(isset($_POST['activity_log_limit']) && $_POST['activity_log_limit'] != $limit && $_POST['activity_log_limit'] > 0) {
		$limit = $_POST['activity_log_limit'];
		if(!$current_user || !intval($limit)) {
			return;
		}
		# Remember the user's choice
		update_user_option($current_user->ID, 'cookspin_activity_log_limit', $limit, true);
	}
	elseif(isset($_POST['activity_log_limit'])) {
		$limit = CK_LOG_DEFAULT_LIMIT;
		delete_user_option($current_user->ID, 'cookspin_activity_log_limit', true);
	}
	elseif(!$limit) {
		$limit = CK_LOG_DEFAULT_LIMIT;		
	}
	$output = '<h5>' . __('Filter activity logs', 'ck_activity') . '</h5>';

	foreach(cookspin_get_log_categories() as $category => $loggers) {
		$checked = in_array($category, $active_filters) || sizeof($active_filters) == 0 ? 'checked="checked"' : '';
		$output .= '
			<input id="' . esc_attr($category) . '_input" type="checkbox" name="activity_log_filter[]" value="' . esc_attr($category) . '" ' . $checked. '/>
			<label for="' . esc_attr($category) . '_input">' . cookspin_get_log_category_label($category) . '</label>';
	}

	if(is_network_admin()) {
		$output .= '
		<h5>' . __('Review activity of another network site:', 'ck_activity') . '</h5>
		<div class="activity_filter_other_blog">
			<label for="activity_blog_id">' . __('Site ID', 'ck_activity') . ':</label>
			<input name="activity_blog_id" type="text" id="activity_blog_id" value="' . (isset($_REQUEST['activity_blog_id']) && is_numeric($_REQUEST['activity_blog_id']) ? $_REQUEST['activity_blog_id'] : '') . '" class="screen-per-page" />
		</div>';
	}
	
	$output .= '
		<h5>' . __('Logs to fetch at a time', 'ck_activity') . '</h5>
		<input type="text" class="screen-per-page" id="activity_log_limit_input" name="activity_log_limit" maxlength="3" value="' . $limit . '" />
		<label class="activity_log_limit_label" for="activity_log_limit_input">' . __('Logs', 'ck_activity') . '</label>
		<input type="hidden" name="activity_log_widget_control" value="1" />';
		
	echo($output);
}

add_action('init', 'cookspin_activity_log_persist_filters');
function cookspin_activity_log_persist_filters() {
	if(isset($_POST['activity_log_widget_control'])) {
		add_filter('wp_redirect', 'cookspin_append_activity_log_filters_to_url');
	}
}

function cookspin_append_activity_log_filters_to_url($url) {
	# Clean up URL
	$url = remove_query_arg(array('activity_log_filter'), $url);
	$url = add_query_arg(array('activity_log' => ''), $url);

	if(isset($_POST['activity_blog_id'])) {
		$url = add_query_arg(array('activity_blog_id' => $_POST['activity_blog_id']), $url);
	}

	if(isset($_POST['activity_log_filter'])) {
		$url = add_query_arg(array('activity_log_filter' => implode(',', $_REQUEST['activity_log_filter'])), $url);
	}
	
	return $url;
}

# Dashboard widget
add_action('wp_dashboard_setup', 'cookspin_dashboard_activity_widget', 1);
function cookspin_dashboard_activity_widget() {
	# Check for editor (or above) role
	if(current_user_can('delete_others_posts')) {
		wp_add_dashboard_widget('cookspin_blog_activity', __('Site activity', 'ck_activity'), 'cookspin_blog_activity_widget', 'cookspin_blog_activity_widget_control');
	}
}

# Network admin dashboard widget
add_action('wp_network_dashboard_setup', 'cookspin_network_admin_dashboard_activity_widget');
function cookspin_network_admin_dashboard_activity_widget() {
	
	$other = false;
	if(isset($_REQUEST['activity_blog_id']) && is_numeric($_REQUEST['activity_blog_id'])) {
		$blog = get_blog_details($_REQUEST['activity_blog_id']);
		if($blog) {
			$other = true;
		}
		else {
			add_action('network_admin_notices', 'cookspin_activity_invalid_blog');
		}
	}
	
	$name = $other ? sprintf(__('Site activity: %1$s (%2$s)', 'ck_activity'), $blog->blogname, $blog->blog_id) : __('Network activity', 'ck_activity');
	wp_add_dashboard_widget('cookspin_network_activity', $name, 'cookspin_blog_activity_widget', 'cookspin_blog_activity_widget_control');
}

function cookspin_activity_invalid_blog() {
	echo('<div class="updated"><p>' . __('The submitted ID does not correspond to a blog in this network.', 'ck_activity') . '</p></div>');
}

add_action('init', 'cookspin_activity_log_loader');
function cookspin_activity_log_loader() {

	wp_register_style('ck_activity', plugins_url('css/activity.css' , __FILE__), array(), CK_LOG_VERSION);
	wp_register_script('ck_activity', plugins_url('js/activity.min.js' , __FILE__), array('jquery'), CK_LOG_VERSION, true);

	global $pagenow;
	$activity_pages = apply_filters('cookspin_activity_log_pages', array('index.php'));
	if(in_array($pagenow, $activity_pages)) {
		wp_enqueue_style('ck_activity');
		wp_enqueue_script('ck_activity');
		wp_localize_script('ck_activity', 'ck_activity', array(
			'ajaxURL' => apply_filters('cookspin_acitvity_log_ajax_url', admin_url('admin-ajax.php')),
			'context' => apply_filters('cookspin_acitvity_log_context', 'blog')
		));
	}
}

add_action('plugins_loaded', 'cookspin_activity_load_textdomain');
function cookspin_activity_load_textdomain() {
	load_plugin_textdomain('ck_activity', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

# Function to load more entries to the Activity log
# Note: doesn't need to check for permissions because they are checked by the cookspin_log_output_activity() function; if a logged user
# doesn't have proper permissions he will get his profile activity only, regardless of request;
add_action('wp_ajax_cookspin_load_more_logs', 'cookspin_load_more_logs');
function cookspin_load_more_logs() {
	
	# Define log constants
	cookspin_log_setup();
	
	if(check_ajax_referer('more_logs_nonce', 'nonce')) {
		$args = array(
			'context' => $_POST['context'],
			'previous_log' => $_POST['last_log'],
			'wrap' => $_POST['wrap']
		);
		if(isset($_POST['filter']) && $_POST['filter'] != null) {
			$args['filter'] = $_POST['filter'];
		}
		cookspin_log_output_activity($args);
	}
	exit;
}

?>