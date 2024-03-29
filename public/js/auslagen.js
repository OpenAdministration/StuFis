/**
 * 
 */

(function(){
	var animate_delete = function($elm, callback){
		$elm.animate({ height: 0, opacity: 0 }, 500,function(){ 
			$(this).remove();
			if (typeof(callback) == 'function'){
				callback();
			}
		});
	};
	
	var get_projektdata = function ($elm){
		var $dlist = $elm.closest('.beleg-table').children('.datalists').children('.datalist-projekt').children('option');
		var map = {};
		$dlist.each(function(i, e){
			map[e.value] = e.dataset.alias;
		});
		return map;
	}
	
	var add_beleg_counter = 0;
	var add_beleg = function(){
		add_beleg_counter++;
		var $e = $(this);
		var k = $e.closest('.beleg-table');
		k.find('.no-belege-info').hide();
		var $_template = $e.closest('.add-button-row').prev();
		var $template = $_template.clone();
		$template.removeClass('add-button-row');
		$template.removeClass('hidden');
		$template.addClass('bt-dark');
		var $_bt = $template.children('div.beleg-template');
		$_bt.removeClass('beleg-template');
		$_bt.addClass('beleg-container');
		//update data-id
		$_bt[0].dataset.id = "new_"+add_beleg_counter;
		//update short
		$_bt.find('.beleg-nr').prepend('N'+add_beleg_counter);
		//set input names
			//beleg-date
			$_bt.find('.beleg-date input')[0].name = "belege[new_"+add_beleg_counter+"][datum]";
			// beleg-file
			$_bt.find('.beleg-file').html('<div class="col-xs-0 form-group"><div class="single-file-container"><input class="form-control single-file" type="file" name="belege[42][file]"></div></div>');
			$_bt.find('.beleg-file input')[0].name = "beleg_new_"+add_beleg_counter+'[0]';
			//beschreibung
			$_bt.find('.beleg-desc textarea')[0].name = "belege[new_"+add_beleg_counter+"][beschreibung]";
		//add delete handler
		$_bt.find('.beleg-nr .delete-row').on('click', remove_beleg);
		//append posten handling
			posten_handler($_bt.find('.posten-inner-list'));
		//append to box
		$template.insertBefore($_template);
		//update fileuplader
		update_fileinput($template.find('.beleg-file'));
		$template.find('.posten-entry-new').each(function(i, e){
			var $e = $(e);
			var $select = $e.find('.projekt-posten-select');
			posten_project_list($select, true);
			$select.siblings('.posten-in').hide();
			$select.siblings('.posten-out').hide();
		});
	};
		
	var remove_beleg = function(ev){
		ev.preventDefault();
		ev.stopPropagation();
		var $e = $(this);
		//test if data_id begins with new
		var k = $e.closest('.beleg-table');
		if (k.find('.beleg-container').length == 1){
			setTimeout(function(){
				k.find('.no-belege-info').show();
			}, 700);
		}
		var $container = $e.closest('.beleg-container');
		animate_delete($container.parent());
	};
	
	var delete_posten = function (){
		var $e = $(this);
		var list = $e.closest('.posten-inner-list');
		animate_delete($e.closest('.posten-entry'), 
			function() {
				update_posten_counter(list);
			}
		);
	}
	
	var update_posten_counter = function ($list){
		//update 'keine Angaben'
		var $l = $list.children('.posten-entry');
		var $empty = $list.children('.posten-empty');
		if ($l.length == 0 && $empty.hasClass('hidden')){
			$empty.removeClass('hidden');
		} else if ($l.length != 0 && !$empty.hasClass('hidden')){
			$empty.addClass('hidden');
		}
		//update in/out counter
		//get values
		var sum_in = 0.0;
		var sum_out = 0.0;
		for (var i = 0; i < $l.length; i++){
			var $tmp_in = $($l[i]).find('.posten-in');
			var $tmp_out = $($l[i]).find('.posten-out');
			//in
			if ($tmp_in.find('input').length > 0){
				sum_in += parseFloat($tmp_in.find('input').val());
			} else {
				sum_in += parseFloat($tmp_in.text());
			}
			 //out
			if ($tmp_out.find('input').length > 0){
				sum_out += parseFloat($tmp_out.find('input').val());
			} else {
				sum_out += parseFloat($tmp_out.text());
			}
		}
		//update counter
		var $sumline = $list.next().children('.posten-sum-line');
		$sumline.children('.posten-sum-in').find('span')[1].innerHTML =  sum_in.toFixed(2);
		$sumline.children('.posten-sum-out').find('span')[1].innerHTML = sum_out.toFixed(2);
	}
	
	var add_posten_counter = 0;
	var add_posten = function(ev, a,b,c,eee) {
		ev.stopPropagation();
		ev.preventDefault();
		add_posten_counter++;
		var $e = $(this);
		if (typeof(eee)!='undefined'){
			$e = eee;
		}
		var focus_index = $e.parent().parent().hasClass('posten-in')? 'in':'out';
		//clone line
		var $old = $e.closest('.posten-entry-new');
		var $new = $old.clone();
		//old -> remove values
		$e.val(0);
		//new class -> posten-entry-new -> posten-entry 
		$new.removeClass('posten-entry-new').addClass('posten-entry');
		//new -> remove this callback function on inputs
		$new.find('input').off('input', add_posten);
		//new show remove button + add remove callback
		$new.find('.posten-counter .fa-trash').removeClass('hidden').on('click', delete_posten);
		//remove plus
		$new.find('.posten-counter .fa-plus').remove();
		//set short name
		$new.find('.posten-short').text('NP'+add_posten_counter);
		//new add callback input (update_posten_counter)
		$new.find('input').on('input', function(){ update_posten_counter($old.closest('.posten-inner-list')); });
		//update input names
		var beleg_id = $old.closest('.beleg-container')[0].dataset.id;
		$new.find('.posten-in').show();
		$new.find('.posten-out').show();
		$new.find('.posten-in input')[0].name = "belege["+beleg_id+"][posten][new_"+add_posten_counter+"][in]";
		$new.find('.posten-out input')[0].name = "belege["+beleg_id+"][posten][new_"+add_posten_counter+"][out]";
		$new.find('.projekt-posten-select input')[0].name = "belege["+beleg_id+"][posten][new_"+add_posten_counter+"][projekt-posten]";
		//append new to inner list
		$new.insertBefore($old);
		// set focus to new
		$new.find('.posten-'+focus_index+' input').focus();
		// append editable select list for posten name
		posten_project_list($new.find('.projekt-posten-select'));
		//update counter
		update_posten_counter($new.closest('.posten-inner-list'));
		return true;
	};
	
	var posten_handler = function ($posten_inner_list) {
		// remove button
		$posten_inner_list.find('.posten-counter .fa-trash').on('click', delete_posten);
		// add behaviour
		$posten_inner_list.find('.posten-entry input').on('input', function(){ update_posten_counter($posten_inner_list); });
		$posten_inner_list.find('.posten-entry-new input').on('input', add_posten);
		// count -> hide show 'keine Angaben'
		update_posten_counter($posten_inner_list);
		// append editable select list for posten name
		$posten_inner_list.find('.posten-entry .projekt-posten-select').each(function(i,e){
			posten_project_list($(e));
		});

	};
	
	// append editable select list for posten name
	var posten_project_list = function ($target, trigger_addposten){
		var data = get_projektdata($target);
		var $select = $('<select/>', {
				 'class':"selectpicker"
		});
		//add options
		for (var idx in data) {
			if (!data.hasOwnProperty(idx)) continue;
			$select.append('<option value=' + idx + '>' + data[idx] + '</option>');
		}
		//set value
		$select[0].value = ($target[0].dataset.value);
		var $group;
		if ($target.children('.form-group').length == 1) {
			$group = $target.children('.form-group');
			$group.children('span').addClass('hidden');
		} else {
			$select.appendTo($target);
			$target.children('span').addClass('hidden');
		}
		setTimeout(function(){
			if ($target.children('.form-group').length == 1){
				$select.prependTo($group);
			}
			$select.selectpicker('refresh');
			//onchange listener TODO
			$target.find('select').on('change', function(ev){
				var $e = $(this);
				var $p = $e.closest('.projekt-posten-select');
				$p[0].dataset.value=$e.val();
				$p.find('input').val($e.val());
				$p.find('span.value').text($e[0].options[$e[0].selectedIndex].text);
				if (typeof(trigger_addposten)!='undefined' && trigger_addposten == true && $target.closest('.posten-entry-new').length > 0){
					add_posten(ev,false, false, false, $e);
					var $sibling = $e.closest('.posten-entry-new').prev();
					auslagen_einnahmen_disable($sibling);
				} else {
					auslagen_einnahmen_disable($e.closest('.posten-entry'));
				}
			});
			$target.find('.bootstrap-select').css({
				'display': 'table-caption',
				'width': '100%'
			});
		}, 100);
	};
	
	var auslagen_einnahmen_disable = function($t){
		var $select = $t.find('.projekt-posten-select');
		var $inp = $t.find('.posten-in input');
		var $outp = $t.find('.posten-out input');
		var $ina = $t.find('.posten-in .input-group-addon');
		var $outa = $t.find('.posten-out .input-group-addon');
		var ttt = $select.find('.value.hidden').text();
		if ($select.find('input').val() == 0){
			$inp.val(0);
			$outp.val(0);
			$inp.hide();
			$outp.hide();
			$ina.hide();
			$outa.hide();
		} else if (ttt.substr(0, 2)=='[E') {
			$outp.val(0);
			$inp.show();
			$outp.hide();
			$ina.show();
			$outa.hide();
		} else if (ttt.substr(0, 2)=='[A') {
			$inp.val(0);
			$inp.hide();
			$outp.show();
			$ina.hide();
			$outa.show();
		}
	};
	
	var update_fileinput = function($target){
		var $t = $target.find('input');
		var cfg = {
            'showUpload': false, // magically appears in fileinput
            'fileActionSettings': {
                'showUpload': false,
                'showZoom': false
            },
            'showPreview': false,
            'language': 'de',
            'theme': 'gly',
        };
        $t.fileinput(cfg);
	};
		
	$(document).ready(function(){
		$('.beleg-table .beleg-container .beleg-file input').each(function(i, e){
			//update name
			e.name = (e.name+'').replace(/^(files)(\[)(.*)(\])(\[\])$/, `$3$5`);
		});
		$('.beleg-table .beleg-container .beleg-file .file-delete').on('click', function(){
			var $e = $(this);
			var $f = $e.closest('form');
			var action = $f.attr("action");
			action = action.substring(0, action.lastIndexOf('/')) + '/filedelete'
			var dataset = {
				'nonce': $('input[name="nonce"]').val(),
				'fid': $e.closest('.beleg-file')[0].dataset.id,
				'auslagen-id': $f.children('input[name="auslagen-id"]').val(),
				'projekt-id': $f.children('input[name="projekt-id"]').val(),
				'etag': $f.children('input[name="etag"]').val()
			};
			jQuery.ajax({
		        url: action,
		        data: dataset,
		        type: "POST"
		    })
		    .done(function (values, status, req) {
		    	defaultPostModalHandler(values);
	        })
	        .fail(xpAjaxErrorHandler);
		});
		$('.state-links .state-changes-now').on('click', function(ev){
			var $e = $(this);
			var $f = $e.closest('.state-links');
			var dataset = {
				'nonce': $('input[name="nonce"]').val(),
				'state': this.dataset.newstate,
				'auslagen-id': $f.find('input[name="auslagen-id"]').val(),
				'projekt-id': $f.find('input[name="projekt-id"]').val(),
				'etag': $f.find('input[name="etag"]').val()
			};
			console.log(dataset);
			jQuery.ajax({
		        url: $f.find('input[name="action"]').val(),
		        data: dataset,
		        type: "POST"
		    })
		    .done(function (values, status, req) {
		    	defaultPostModalHandler(values);
	        })
	        .fail(xpAjaxErrorHandler);
		});
		$(document).on('svg-trigger-state-change', function(ev){
			var $f = $('.svg-statechanges');
			var dataset = {
				'nonce': $('input[name="nonce"]').val(),
				'state': ev.originalEvent.detail,
				'auslagen-id': $f.find('input[name="auslagen-id"]').val(),
				'projekt-id': $f.find('input[name="projekt-id"]').val(),
				'etag': $f.find('input[name="etag"]').val()
			};
			console.log(dataset);
			jQuery.ajax({
		        url: $f.find('input[name="action"]').val(),
		        data: dataset,
		        type: "POST"
		    })
		    .done(function (values, status, req) {
		    	defaultPostModalHandler(values);
	        })
	        .fail(xpAjaxErrorHandler);
		});
		$('.beleg-table .add-belege .btn').on( 'click', add_beleg);
		$('.beleg-table .beleg-container .beleg-nr .delete-row').on( 'click', remove_beleg);
		$('.beleg-table .beleg-container .posten-inner-list').each(function(i, e){
			posten_handler($(e));
		});
		$('.auslagen-form-submit-send').on('click', function(){
			var $e = $(this);
			var $target = $('form#'+this.dataset.for);
			$target.trigger('submit');
		});
		$('.auslagen-belege-pdf').on('click', function(ev){
			$("#please-wait-dlg").modal("show");
			var dataset = {
				'nonce': $('input[name="nonce"]').val(),
				'projekt-id': this.dataset.pfor,
				'auslagen-id': this.dataset.afor,
			};
			var action = this.dataset.action;
			jQuery.ajax({
		        url: action,
		        data: dataset,
		        type: "POST"
		    })
		    .done(function (values, status, req) {
		    	$("#please-wait-dlg").modal("hide");
		    	defaultPostModalHandler(values);
	        })
	        .fail(xpAjaxErrorHandler);
		});
		$('.posten-entry-new:visible').each(function(i, e){
			var $e = $(e);
			var $select = $e.find('.projekt-posten-select');
			posten_project_list($select, true);
			$select.siblings('.posten-in').hide();
			$select.siblings('.posten-out').hide();
		});
		$('.posten-entry:visible').each(function(i, e){
			var $e = $(e);
			auslagen_einnahmen_disable($e);
		});
	});
})();