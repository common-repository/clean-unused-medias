//
var sto;
var sto_crawl;
var sto_keyword;
var stop_crawl = false;
var lnjcm_list_medias_first = true;

//
jQuery(document).ready(function () {
	//
	jQuery('body').on('click', '#lnjcm-warning', function () {
		var date = new Date();
		date.setTime(date.getTime() + (7 * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
		document.cookie = encodeURIComponent('lnjcm-warning-hide') + '=yep' + expires + "; path=/";
	});
	//
	jQuery('body').on('click', '.lnjcm-tag', function () {
		var _href = jQuery(this).attr('href');
		var _type = _href.replace(/#/, '');
		var _id = jQuery(this).attr('data-media-id');

		lnjcm_display_details(_type, _id);

		return false;
	});
	//
	jQuery('body').on('click', '.lnjcm-notice button.notice-dismiss', function () {
		jQuery(this).parent('.lnjcm-notice').slideUp(400, function () {
			jQuery(this).remove();
		});
	});
	//
	jQuery('body').on('change', 'select[name="lnjcm-medias-per-page"]', function () {
		lnjcm_list_medias();
	});
	//
	jQuery('body').on('click', '#lnjcm_medias_not_theme_customise, #lnjcm_medias_not_thumb, #lnjcm_medias_not_related, #lnjcm_medias_not_in_content, #lnjcm_medias_not_in_postmeta, #lnjcm_medias_not_in_usermeta, #lnjcm_medias_not_in_option, #lnjcm_medias_not_in_acf, a.lnjcm-refresh-result', function () {
		lnjcm_list_medias();
	});
	//
	jQuery('body').on('click', 'a.lnjcm-recrawl', function () {
		//
		stop_crawl = false;
		//
		lnjcm_display_notice(lnjcm_tools_translate.relaunch_crawl, 'info', 'lnjcm_crawl_notice');
		//
		change_crawl_status('reset');
	});
	//
	jQuery('body').on('click', 'a.lnjcm-pause', function () {
		//
		lnjcm_display_notice(lnjcm_tools_translate.pause_crawl, 'info', 'lnjcm_crawl_notice');
		change_crawl_status('pause');
	});
	//
	jQuery('body').on('click', 'a.lnjcm-resume', function () {
		//
		stop_crawl = false;
		//
		lnjcm_display_notice(lnjcm_tools_translate.resume_crawl, 'info', 'lnjcm_crawl_notice');
		//
		change_crawl_status('resume');
	});
	//
	jQuery('body').on('keyup', '#lnjcm_medias_keyword', function () {
		clearTimeout(sto_keyword);
		sto_keyword = setTimeout(function () {
			lnjcm_list_medias();
		}, 1800);
	});
	//
	jQuery('body').on('click', 'input[name="btn-select-medias"]', function () {
		if (jQuery(this).hasClass('selectall')) {
			jQuery(this).removeClass('selectall');
			jQuery('#fcum').find('.lnjcm-medias-content input[type="checkbox"]').prop('checked', false);
		}
		else {
			jQuery(this).addClass('selectall');
			jQuery('#fcum').find('.lnjcm-medias-content input[type="checkbox"]').prop('checked', true);
		}
	});
	//
	jQuery('body').on('click', 'input[name="btn-select-filters"]', function () {
		if (jQuery(this).hasClass('selectall')) {
			jQuery(this).removeClass('selectall');
			jQuery('#fcum').find('.lnjcm-filters input[type="checkbox"]').prop('checked', false);
		}
		else {
			jQuery(this).addClass('selectall');
			jQuery('#fcum').find('.lnjcm-filters input[type="checkbox"]').prop('checked', true);
		}
		lnjcm_list_medias();
	});
	//
	jQuery('body').on('click', '.lnjcm-nav .page-numbers', function () {
		var _str = jQuery(this).attr('href');
		var test = _str.match(/\/([0-9]+)$/);
		if (test && test[1]) { lnjcm_list_medias(test[1]); }
		return false;
	});
	//
	jQuery('#fcum').submit(function () {
		//
		jQuery('input[name="btn-delete-medias"]').attr('disabled', 'disabled');
		jQuery('.result').html(lnjcm_tools_translate.deleting_media_in_progress);
		//
		var params = {};
		params.action = 'lnjcm_do_delete_medias';
		params.lnjcm_do_delete_medias_nonce = jQuery('#lnjcm_do_delete_medias_nonce').val();
		params.media_ids = [];
		jQuery('#fcum').find('.lnjcm-medias-content input[type="checkbox"]:checked').each(function () { params.media_ids.push(jQuery(this).val()); });

		//
		jQuery.ajax({
			type: 'POST',
			'url': ajaxurl,
			'data': params,
			'dataType': 'json',
			'success': function (data) {
				jQuery('input[name="btn-delete-medias"]').removeAttr('disabled');
				if (data.error == 0) {
					//
					jQuery('.result').html(data.message);
					//
					if (data.media_ids_deleted.length > 0) {
						for (var i in data.media_ids_deleted) {
							jQuery('input[type="checkbox"][value="' + data.media_ids_deleted[i] + '"]').parent('label').parent('h3').parent('.lnjcm-media').slideUp(300, function () {
								//
								jQuery(this).remove();

								//
								if (jQuery('.lnjcm-media').length == 0) {
									//
									jQuery('.lnjcm-medias').remove();
									//
									lnjcm_list_medias();
								}
							});
						}
					}
				}
				else {
					lnjcm_display_notice(data.message, 'error');
				}
			}
		});
		return false;
	});
	//
	get_lnjcm_crawl_medias();
});

//
function lnjcm_list_medias(paged) {
	//
	var params = {};
	params.action = 'lnjcm_list_medias';
	params.lnjcm_medias_per_page = jQuery('#lnjcm-medias-per-page').val();
	params.lnjcm_list_medias_nonce = jQuery('#lnjcm_list_medias_nonce').val();
	params.lnjcm_medias_keyword = jQuery('#lnjcm_medias_keyword').val();
	params.lnjcm_medias_not_theme_customise = (jQuery('#lnjcm_medias_not_theme_customise:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_thumb = (jQuery('#lnjcm_medias_not_thumb:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_related = (jQuery('#lnjcm_medias_not_related:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_in_acf = (jQuery('#lnjcm_medias_not_in_acf:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_in_content = (jQuery('#lnjcm_medias_not_in_content:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_in_postmeta = (jQuery('#lnjcm_medias_not_in_postmeta:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_in_usermeta = (jQuery('#lnjcm_medias_not_in_usermeta:checked').length > 0) ? 1 : 0;
	params.lnjcm_medias_not_in_option = (jQuery('#lnjcm_medias_not_in_option:checked').length > 0) ? 1 : 0;
	params.paged = (paged != undefined) ? paged : 0;
	jQuery('.lnjcm-medias-content, p.lnjcm-buttons').animate({ 'opacity': 0.4 }, 400);

	//
	jQuery.ajax({
		type: 'POST',
		'url': ajaxurl,
		'data': params,
		'dataType': 'json',
		'success': function (data) {
			jQuery('.lnjcm-medias-content, p.lnjcm-buttons').animate({ 'opacity': 1 }, 600);
			jQuery('.lnjcm-medias-content').html(data.message);
			if (data.error == 1) {
				lnjcm_display_notice(data.message, 'error');
			}
		}
	});
	return false;
}

//
function get_lnjcm_crawl_medias() {
	//
	clearTimeout(sto_crawl);

	//
	if (stop_crawl) { return false; };

	//
	var params = {};
	params.action = 'lnjcm_crawl_medias';
	params.lnjcm_crawl_medias_nonce = jQuery('#lnjcm_crawl_medias_nonce').val();

	//
	jQuery.ajax({
		type: 'POST',
		'url': ajaxurl,
		'data': params,
		'dataType': 'json',
		'success': function (data) {
			if (data.error == 0) {
				if (data && data.complete == 1) {
					//
					stop_crawl = true;
					//
					lnjcm_display_notice(data.message, 'success', 'lnjcm_crawl_notice');
					//
					lnjcm_list_medias();
				}
				else if (data && data.pause == 1) {
					//
					stop_crawl = true;
					//
					lnjcm_display_notice(data.message, 'warning', 'lnjcm_crawl_notice');
				}
				else {
					lnjcm_display_notice(data.message, 'info', 'lnjcm_crawl_notice');
				}
			}
			else {
				lnjcm_display_notice(data.message, 'error');
			}
		},
		'complete': function (data) {
			//
			sto_crawl = setTimeout(get_lnjcm_crawl_medias, 4000);

			//
			if (lnjcm_list_medias_first) {
				lnjcm_list_medias_first = false;
				lnjcm_list_medias();
			}
		}
	});

	return false;
}

//
function change_crawl_status(_action) {
	//
	var params = {};
	params.action = 'lnjcm_crawl_medias_change_status';
	params.lnjcm_crawl_medias_nonce = jQuery('#lnjcm_crawl_medias_nonce').val();
	if (_action != undefined && _action == 'reset') { params.lnjcm_crawl_medias_reset = 1; }
	if (_action != undefined && _action == 'pause') { params.lnjcm_crawl_medias_pause = 1; }
	if (_action != undefined && _action == 'resume') { params.lnjcm_crawl_medias_resume = 1; }

	//
	jQuery.ajax({
		type: 'POST',
		'url': ajaxurl,
		'data': params,
		'dataType': 'json',
		'success': function (data) {
			if (data.error == 0) {
				if (data.reset) {
					//
					stop_crawl = false;
					//
					get_lnjcm_crawl_medias();
				}
				else if (data.resume) {
					//
					stop_crawl = false;
					//
					get_lnjcm_crawl_medias();
				}
				else if (data.pause) {
					//
					// stop_crawl = true;
				}
			}
			else {
				lnjcm_display_notice(data.message, 'error');
			}
		},
	});

	return false;
}

function lnjcm_display_details(_type, _id) {
	jQuery('.thickbox.lnjcm-thickbox-launcher').click();

	jQuery('#TB_ajaxContent').html('<p>' + lnjcm_tools_translate.loading + '</p>');

	//
	var params = {};
	params.action = 'lnjcm_get_medias_used_in';
	params.media_id = _id;
	params.type = _type;
	params.lnjcm_get_medias_used_in_nonce = jQuery('#lnjcm_get_medias_used_in_nonce').val();

	//
	jQuery.ajax({
		type: 'POST',
		'url': ajaxurl,
		'data': params,
		'dataType': 'json',
		'success': function (data) {
			jQuery('#TB_ajaxContent').html(data.message);
		}
	});

	return false;
}

//
function lnjcm_display_notice(_msg, _type, _id) {
	var _type = (_type != undefined) ? _type : 'success';
	if (_id != undefined) {
		if (jQuery('#' + _id).length == 0) {
			var _html = '\
				<div class="lnjcm-notice notice notice-'+ _type + ' is-dismissible" id="' + _id + '">\
					<p>'+ _msg + '</p>\
					<button type="button" class="notice-dismiss"></button>\
				</div>\
			';
			jQuery(_html).insertAfter('#fcum > fieldset:first');
		}
		else {
			jQuery('#' + _id).removeClass('notice-warning notice-error notice-success notice-info');
			jQuery('#' + _id).addClass('notice-' + _type);
			jQuery('#' + _id).find('p:first').html(_msg);
			jQuery('#' + _id).stop().animate({ 'opacity': 0.4 }, 200, function () {
				jQuery('#' + _id).stop().animate({ 'opacity': 1 }, 200);
			});

		}
	}
	else {
		var _html = '\
			<div class="lnjcm-notice notice notice-'+ _type + ' is-dismissible">\
				<p>'+ _msg + '</p>\
				<button type="button" class="notice-dismiss"></button>\
			</div>\
		';
		jQuery(_html).insertAfter('#fcum > fieldset:first');
	}
}