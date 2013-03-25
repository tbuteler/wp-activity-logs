jQuery(function($) {
	$('body').on('click', '#load_more_logs', function(){
		$(this).addClass('loading');
		$.post(ck_activity.ajaxURL,
		{
			action: 'cookspin_load_more_logs',
			nonce: $('#more_logs_nonce').text(),
			last_log: $('#last_log').text(),
			wrap: $('#log_wrap_tag').text(),
			filter: $('#log_active_filters').text(),
			activity_blog_id: $('#log_fetch_blog').length ? $('#log_fetch_blog').text() : false,
			context: ck_activity.context
		},
		function(data){
			$('#logs_footer').replaceWith(data);
			$(this).removeClass('loading');
		});
	});
});