jQuery.fn.pwf_delete_field = function(message) {
	if (confirm(message)) {
		var url = jQuery(this).attr("href");
		var parent = jQuery(this).closest("tr");

		jQuery.ajax({
			type: "GET",
			url: url,
			error: function() {
				alert('Sorry. There was an error.');
			},
			success: function() {
				parent.fadeOut("1000", function() {
					parent.remove();
					var totalrows = jQuery(".pwf_table").find("tbody tr").size();
					jQuery(".pwf_table").find("tbody tr").removeClass();
					jQuery(".pwf_table").find("tbody tr:nth-child(2n+1)").addClass("row1");
					jQuery(".pwf_table").find("tbody tr:nth-child(2n)").addClass("row2");
				});
			}
		});
	}
};

jQuery.fn.pwf_get_template = function(message, url) {
	var value = jQuery(this).val();
	if (confirm(message)) {
		jQuery.ajax({
			type: "GET",
			url: url,
			data: '&m1_pwfp_tid='+value,
			error: function() {
				alert('Sorry. There was an error.');
			},
			success: function(data) {
				jQuery("#m1_pwfp_form_template").val(data); //CHECKME m1_ prefix
			}
		});
	}
};

jQuery.fn.pwf_admin_update_field_required = function() {
	var url = jQuery(this).attr("href");
	var current = jQuery(this);
	jQuery.ajax({
		type: "GET",
		url: url,
		error: function() {
			alert('Sorry. There was an error.');
		},
		success: function() {
			if(current.hasClass("true")) {
				var replaceurl = current.attr("href").replace('pwfp_active=off','pwfp_active=on');
				var replacepic = current.children().attr("src").replace('true', 'false');
				var replaceother = 'false';
				current.removeClass("true").addClass("false");
			} else {
				var replaceurl = current.attr("href").replace('pwfp_active=on','pwfp_active=off');
				var replacepic = current.children().attr("src").replace('false', 'true');
				var replaceother = 'true';
				current.removeClass("false").addClass("true");
			}
			current.attr({ href : replaceurl });
			current.children().attr({ src : replacepic, title : replaceother, alt : replaceother });
		}
	});
};

jQuery.fn.pwf_set_tab = function() {
	var active = jQuery('#page_tabs > .active');
	jQuery('#m1_pwfp_atab').val(active.attr('id'));
}
