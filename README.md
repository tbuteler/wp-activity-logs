Cookspin Activity Logs
======================

1. Unpackage contents to wp-content/plugins/ck_activity_logs
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! The plugin will configure itself on first run.


Additional configuration
------------------------

Although the idea is to allow users to install and forget, admins have some configuration options available. To do so, define the constants below in your `wp-config.php` file (or anywhere else, as long as they're defined before the `init` action takes place).

Values defined here are the plugins defaults, which can be overriden.

Time range, in seconds, that will cause our activity log to ignore repeat actions. Note that activity will still be logged, but won't be displayed in the dashboard:

	// Logs 60 seconds apart will be treated as the same action (same type, same logger only)
	define('CK_LOG_TIME_IGNORE', 60);

Maximum number of rows reserved for activity logs in custom table:

	// 0 means no limit
	define('CK_LOG_MAX_ROWS', 0);

Logs to load on dashboard at a time:

	define('CK_LOG_DEFAULT_LIMIT', 25);
	

Adding your own loggers
-----------------------

There are many filters and actions to hook into, but the default loggers take into account core functionality only. This includes custom post types, but not custom functionality which most plugins add to WordPress. Luckily, you can easily set your own loggers by using the following function:

	cookspin_register_logger(
		$name,		// A slug-like name for this logger, shorter than 255 characters
		$category,	// A slug-like name for the logger's category. For the default list, see below
		$args			// See below
	);

Here are the default categories (and slugs) for loggers. These are meant to mimick the default sections of the WordPress admin menu. Your custom logger can go into any of these, and you can create your own categories as well (though you will have to specify labels and CSS / icons via filters):

1. Sites: `blogs`
2. Posts: `posts` 
3. Media: `media`
4. Users: `users`
5. Settings: `preferences`
6. Appearance: `comments`
7. Plugins: `appearance`
8. Tools: `tools`

The third and final argument of the function is actually an array of arguments. Let's go through the options one by one. (Note that omitting "required" arguments might not cause the function to fail, but will prevent the log from displaying properly.)

`hook` (string)(required): Which action should we hook to? Every time it fires, our log will record an entry. **Important:** If you're hooking to filters and not actions, set the `hook_to_filter` argument to `true`
`priority` (integer)(optional): Priority of the logger function compared to all other functions hooked to this action.
`n_params` (integer)(optional): How many arguments does the logger function need from the action? If more than one, this has to be specified.
`cb` (string)(required): The actual callback which will define what information gets recorded on the database.
`print_cb` (string)(required): A callback which is used to generate a readable version of the log.
`hook_to_filter` (boolean)(optional): Do you want to log something everytime a *filter* is applied? You can do that too, but you **need to set this to true**. Failure to do so will prevent the filter you're hooking to from working properly, and can break stuff.
`ignore_cmp` (string or array of strings)(optional): Sometimes ignoring logs based on the time they were recorded is not enough to prevent you from showing actions which have no actual impact. Here you can specify any of the following, which correspond to the activity log's table columns: 'object_type', 'object_id', 'log_code', 'user_id'.


Feedback and Support
--------------------------

Comments are welcome. You can contact me directly via tbuteler@gmail.com.

If you think you've found a bug, or you feel like you can make this plugin better, go to [GitHub](https://github.com/tbuteler/ck_activity_logs).

Found this helpful? Did it save you precious programming time? Please consider making a donation:

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="5H36WT4G7XBKQ">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/pt_BR/i/scr/pixel.gif" width="1" height="1">
</form>