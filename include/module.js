var errmsg = 'Sorry. There was an error.';

function delete_field (message) {
	if (confirm(message)) {
		var url = $(this).attr('href');
		var parent = $(this).closest('tr');
		$.ajax({
			type: 'GET',
			url: url,
			error: function() {
				alert(errmsg);
			},
			success: function() {
				parent.fadeOut('1000', function() {
					parent.remove();
					var odd = true;
					var oddclass = 'row1';
					var evenclass = 'row2';
					$('.pwf_table').find('tbody tr').each(function() {
						var name = odd ? oddclass : evenclass;
						$(this).removeClass().addClass(name)
						.removeAttr('onmouseover').mouseover(function() {
							$(this).attr('class',name+'hover');
						}).removeAttr('onmouseout').mouseout(function() {
							$(this).attr('class',name);
						});
						odd = !odd;
					});
				});
			}
		});
	}
};

function update_field_required () {
	var url = $(this).attr('href');
	var current = $(this);
	$.ajax({
		type: 'GET',
		url: url,
		error: function() {
			alert(errmsg);
		},
		success: function() {
			if(current.hasClass('true')) {
				var replaceurl = current.attr('href').replace('active=off','active=on');
				var replacepic = current.children().attr('src').replace('true', 'false');
				var replaceother = 'false';
				current.removeClass('true').addClass('false');
			} else {
				var replaceurl = current.attr('href').replace('active=on','active=off');
				var replacepic = current.children().attr('src').replace('false', 'true');
				var replaceother = 'true';
				current.removeClass('false').addClass('true');
			}
			current.attr({ href : replaceurl });
			current.children().attr({ src : replacepic, title : replaceother, alt : replaceother });
		}
	});
};

function get_template (message, url) {
	if (confirm(message)) {
		var value = $(this).val();
		$.ajax({
			type: 'GET',
			url: url,
			data: '&m1_tid='+value,
			error: function() {
				alert(errmsg);
			},
			success: function(data) {
				$('#m1_form_template').val(data);
			}
		});
	}
};

function set_tab () {
	var active = $('#page_tabs > .active');
	$('#m1_active_tab').val(active.attr('id'));
}

$(document).ready(function() {
	$('.updown').hide();
	$('.showhelp').hide();
	$('img.tipper').css({'display':'inline','padding-left':'10px'}).click(function() {
		$(this).parent().parent().find('.showhelp').slideToggle();
	});
	$('.reordermsg').show()
	$('#addslow').hide();
	$('#addfast').show();
	if($('input[name="m1_opt_submit_action"]:checked').val() == 'redir') {
      $('#tplobjects').hide();
	} else {
      $('#pageobjects').hide();
	}
	$('input[name="m1_opt_submit_action"]').change(function() {
	 if($(this).val() == 'redir') {
	  $('#tplobjects').hide();
	  $('#pageobjects').show();
	 } else {
	  $('#pageobjects').hide();
	  $('#tplobjects').show();
	 }
	});
	$('.tabledrag').tableDnD({
		dragClass: 'row1hover',
		onDrop: function(table,droprows) {
				var odd = true;
				var oddclass = 'row1';
				var evenclass = 'row2';
				var droprow = $(droprows)[0];
				var rowids = [];
				$(table).find('tbody tr').each(function() {
					rowids[rowids.length] = this.id;
					var name = odd ? oddclass : evenclass;
					if (this === droprow) {
						name = name+'hover';
					}
					$(this).removeClass().addClass(name);
					odd = !odd;
				});

				var sortstr = rowids.join(",");;
				$('#m1_sort_order').val(sortstr);
				$('#m1_saveordermsg').show();
		}
	}).find('tbody tr').removeAttr('onmouseover').removeAttr('onmouseout').mouseover(function() {
		var now = $(this).attr('class');
		$(this).attr('class', now+'hover');
	}).mouseout(function() {
		var now = $(this).attr('class');
		var to = now.indexOf('hover');
		$(this).attr('class', now.substring(0,to));
	});
});
