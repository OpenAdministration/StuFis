(function(){
	// helper functions ---------------------------------
	Function.prototype.debounce = function(threshold){
        var callback = this;
        var timeout;
        return function() {
            var context = this, params = arguments;
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function() {
                callback.apply(context, params);
            }, threshold);
        };
    };
	
	if(typeof($.fn.isOnScreen) == 'undefined'){
	    $.fn.isOnScreen = function(y){
	        if(y == null || typeof y == 'undefined') y = 1;
	        var win = $(window);
	        var viewport = { top : win.scrollTop() };
	        viewport.bottom = viewport.top + win.height();
	        var height = this.outerHeight();
	        if(!height){ return false; }
	        var bounds = this.offset();
	        bounds.bottom = bounds.top + height;
	        
	        var visible = (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
	        if(!visible){
	            return false;   
	        }
	        var deltas = {
	            top : Math.min( 1, ( bounds.bottom - viewport.top ) / height),
	            bottom : Math.min(1, ( viewport.bottom - bounds.top ) / height),
	        };
	        
	        return (deltas.top * deltas.bottom) >= y;    
	    };
	}
	
	/**
	 * parse post data
	 */
	var parseData = function (data, reload) {
		var r = {}
		if (typeof(data.success) != 'undefined' ){
			r = data;
		} else {
			try {
				r = JSON.parse(data);
			} catch(e) {
				r.success=false;
				r.eMsg = ('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...');
				if (typeof (reload) != 'undefined' && reload == true){
					setTimeout(function() { window.location.replace(window.location); }, 5000);
				}
			}
		}
		return r;
	}
	
	  
	var stringToDate = function(datestr2){
   		var datestr = datestr2;
   		var pattern = /(\d{2})\.(\d{2})\.(\d{4})/;
   		return date = new Date(datestr.replace(pattern,'$3-$2-$1'));
   	}
	
	// --------------------------------------------------------------
	
	/**
	 * append message to container
	 */
	var appendChatMessage = function ($t, data, message_counter, append_method){
		// may add comments
		var add = [];
		var $to = $t.find('.chat-section');
		var $before = $to.find('.chat-loading');
		var counter = message_counter;
		for(dd in data){
			if (data.hasOwnProperty(dd)){
				var d = data[dd];
				var $c = $('<div/>');
				counter+=d.count;
				$c.addClass("chat-"+d.pos);
				$c.addClass("chat-container");
				if (d.hasOwnProperty('class')){
					$c.addClass(d['class']);
				}
				$c.css({'background-color' : '#'+d.color[0]});
				$c.css({'color' : '#'+d.color[1]});
				switch (d.pos){
					case 'middle':{
						$c.attr('title', d.creator_alias + ' am ' + stringToDate(d.timestamp).toLocaleString());
						$c.html(d.text);
					} break;
					default:{
						$c.addClass("chat-"+d.pos);
						$c.addClass("chat-container");
						$c.html('<span class="chat-time">'+stringToDate(d.timestamp).toLocaleString()+'</span><span class="chat-count" style="border-color: '+d.color[1]+'">'+counter+'</span><label>'+d.creator_alias+'</label>'+'<p>'+d.text+'</p>');
					} break;
				}
				add.push($('<div/>', {'class': 'clearfix'}));
				add.push($c);
			}
		}
		var chainPrepend = function (){
			if (add.length > 0){
				$to.prepend(add.shift());
			}
			if (add.length > 0){
				var $o = add.shift()
				$to.prepend($o)
				$o.hide().slideToggle(120, function(){
					if (add.length > 0){
						chainPrepend();
					}
				});
 			}
		}
		var chainAppend = function (){
			if (add.length > 0){
				var $o = add.pop();
				$before.before($o);
				$o.hide().slideToggle(120, function(){
					if (add.length > 0){
						chainAppend();
					}
				});
			}
			if (add.length > 0){
				$before.before(add.pop());
 			}
		}
		if (append_method){
			chainPrepend();
		} else {
			chainAppend();
		}
		return counter;
	}
	
	var $chats = null;
	var initChat = function($e){
		var timeout = null;
		var lastKnown = 0;
		var message_counter = 0;
		var ajax_blocked = false;
		var append_method = 0;
		var updateChat = function($e){
			var t = $e.find('.new-chat-comment');
			var dataset = {};
			for(prop in t[0].dataset){
				if (t[0].dataset.hasOwnProperty(prop)){
					dataset[prop] = t[0].dataset[prop];
				}
			}
			dataset['nonce'] = $e.find('input[name="nonce"]').val();
			dataset['last'] = lastKnown;
			dataset['action'] = 'gethistory';
			// do ajax
			if (!ajax_blocked){
				ajax_blocked = true;
				jQuery.ajax({
			        url: dataset['url'],
			        data: dataset,
			        type: "POST",
			    }).done(function (values, status, req) {
			    	data = parseData(values);
			    	lastKnown = data.last;
			    	//add comments
			    	message_counter = appendChatMessage($e, data.data, message_counter, append_method);
			    	append_method = 1;
			    	$e.find('.chat-loading').fadeOut(1000);
			    	ajax_blocked = false;
		        }).fail(function (values) {
		        	data = parseData(values.responseText);
		        	console.log('error', data);
		        	// unset Interval on error
			    	if (timeout != null){
			    		(window).clearInterval(timeout);
						timeout = null;
					}
			    	ajax_blocked = false;
		        });
			}
		};
		timeout = (window).setInterval(function(){ updateChat($e); }, 20000);
		updateChat($e);
		var evtListener = function(){ updateChat($e); };
		$e.on('chat-evt-update', evtListener);
		
		// ---------------------------
		//button click listener
		$e.find('.new-chat-comment .chat-submit').on('click', function(){
			var t = $e.find('.new-chat-comment');
			var dataset = {};
			for(prop in t[0].dataset){
				if (t[0].dataset.hasOwnProperty(prop)){
					dataset[prop] = t[0].dataset[prop];
				}
			}
			dataset['nonce'] = $e.find('input[name="nonce"]').val();
			dataset['action'] = 'newcomment';
			dataset['type'] = ($(this).data('type'));
			dataset['text'] = t.find('textarea').val().trim();
			
			if (dataset['text'] != ''){
				jQuery.ajax({
			        url: dataset['url'],
			        data: dataset,
			        type: "POST",
				}).done(function (values, status, req) {
					t.find('textarea').val('');
					updateChat($e);
		        }).fail(function (values) {
		        	data = parseData(values.responseText);
		        	console.log('error', data);
		        	// unset Interval on error
		        });
			}
		});
	}

	// --------------------------------------------------------------
	
	$(document).ready(function(){
		$chats = $('.chat-panel');
		//start chat updates only when chat is on screen
		$chats.each(function(i, e){
			var $object = $(e);
			var debounced = null;
			var screenChecker = function() {
	          	if ($object.isOnScreen(0.2)){
	          		$(window).off('scroll', debounced);
	          		initChat($object);
	          	}
	        }
	        debounced = screenChecker.debounce(3);
	        $(window).on('scroll',debounced);
	        screenChecker();
		});
	});
})();