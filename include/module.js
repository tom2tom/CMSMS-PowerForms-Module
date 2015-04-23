$(document).ready(function() {
    $('.updown').hide();
	$('.reordermsg').show()
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
				$('.pwfp_sort').val(sortstr);
				$('.saveordermsg').show();
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
