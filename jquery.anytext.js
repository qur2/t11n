// TODO keep the array hierarchy in a property

// function domPath(el) {
// 	hierarchy = new Array();
// 	while (!el.parent().is('html')) {
// 		hierarchy.unshift([
// 			el.get(0).tagName.toLowerCase()
// 			, el.parent().children(el.get(0).tagName.toLowerCase()).index(el)
// 		]);
// 		el = el.parent();
// 	}
// 	selector = 'body';
// 	$.each(hierarchy, function(index, value) {
// 		selector += ' > '+value[0]+':eq('+value[1]+')';
// 	});
// 	return selector;
// }

// jQuery(document).bind('click', function(e) {
// 	console.log($(e.target));
// 	console.log(domPath($(e.target)));
// 	console.log($(domPath($(e.target))));
// 	return false;
// });

/*!
 * jQuery domPath
 * 
 * Copyright (c) 2010 "qur2" Aurélien Scoubeau
 * Dual licensed under the MIT and GPL licenses.
 */

(function($) {

	$.fn.hasText = function() {
		return $(this).getText().length;
	};
	$.fn.getText = function(options) {
		var opts = $.extend({}, $.fn.getText.defaults, options);
		textNodes = [];
		node = this[0];
		// console.log('this: ', node);
		// console.log('childNodes: ', node.childNodes);
		for (p in node.childNodes) {
			// console.log(p, node.childNodes[p]);
			if (node.childNodes[p] && node.childNodes[p].nodeType == 3) {
				if (opts.emptyNodes || $.trim(node.childNodes[p].nodeValue).length > 0)
					textNodes.push(node.childNodes[p]);
			}
		}
		// console.log('textNodes: ', textNodes);
		return $(textNodes);
	}

	// plugin defaults
	$.fn.getText.defaults = {
		emptyNodes : false
	};

	$.fn.anyText = function(options) {
		// build main options before element iteration by extending the default ones
		var opts = $.extend({}, $.fn.anyText.defaults, options);

		panel.build();

		// for each side note, do the magic.
		return this.each(function() {
			var ancestor = this.nodeName.toLowerCase();
			$(this).bind('click', function(e) {
				if ($.fn.anyText.active) {
					var textnodes = $(e.target).getText();
					panel.setTextnodes(textnodes);
					panel.show();
					return false;
				}
			});
		});
	};

	// plugin defaults
	$.fn.anyText.defaults = {
		sideNoteToggleText : 'Side note:'
	};
	$.fn.anyText.active = true;

	function domPath(el) {
		hierarchy = [];
		while (!el.parent().is('html')) {
			hierarchy.unshift([
				el.get(0).nodeName.toLowerCase()
				, el.parent().children(el.get(0).tagName.toLowerCase()).index(el)
			]);
			el = el.parent();
		}
		selector = 'body';
		$.each(hierarchy, function(index, value) {
			selector += ' > '+value[0]+':eq('+value[1]+')';
		});
		return selector;
	};

	var panel = {
		nodes: null,
		currentNode: null,
		domElem: null,
		currentNodeIndex: 0,

		setTextnodes: function(textnodes) {
			this.nodes = textnodes;
			domElem.find('.node-count').text(this.nodes.length);
			this.currentNodeIndex = -1;
			this.nextNode();
		},

		toggle: function() {
			if (this.domElem.is(':visible'))
				this.show();
			else
				this.hide();
		},

		show: function() {
			domElem.show();
		},

		hide: function() {
			domElem.hide();
		},

		toggleActive: function() {
			$.fn.anyText.active = !$.fn.anyText.active;
			if ($.fn.anyText.active)
				domElem.find('.active').removeClass('off').addClass('on').text('on');
			else
				domElem.find('.active').removeClass('on').addClass('off').text('off');
		},

		changeNode: function(inc) {
			this.currentNodeIndex = (this.currentNodeIndex + inc % this.nodes.length) % this.nodes.length;
			if (this.currentNodeIndex < 0)
				this.currentNodeIndex = this.nodes.length + this.currentNodeIndex;
			this.currentNode = this.nodes.eq(this.currentNodeIndex);
			text = this.currentNode.text();
			domElem.find('.edit').val(text.replace(/\s+/gi,' '));
			text = this.currentNode.length ? this.currentNodeIndex+1 : '-';
			domElem.find('.node-current').text(text);
		},

		nextNode: function() {
			this.changeNode(1);
		},

		prevNode: function() {
			this.changeNode(-1);
		},

		updateNode: function() {
			text = domElem.find('.edit').val();
			if ($.trim(this.currentNode[0].nodeValue) == $.trim(text)) return;
			// if (text.charAt(0).match(/\w/))
			// 	text = ' ' + text;
			// if (text.charAt(text.length-1).match(/\w/))
			// 	text = text + ' ';
			this.currentNode.data('old', this.currentNode[0].nodeValue);
			this.currentNode[0].nodeValue = text;
		},

		restoreNode: function() {
			text = this.currentNode.data('old');
			if (text)
				this.currentNode[0].nodeValue = text;
		},

		build: function() {
			self = this;
			domElem = $('<div>').attr('id', 'anyText-panel')
				.appendTo('body')
				.append($('<span class="node-count">').text('-'))
				.append($('<a class="node-prev">').text('prev').bind('click', function() {self.prevNode();}))
				.append($('<span class="node-current">').text('-'))
				.append($('<a class="node-next">').text('next').bind('click', function() {self.nextNode();}))
				.append($('<span class="active on">').text('on').bind('click', function() {self.toggleActive();}))
				.append($('<span class="toggle">').text('hide').bind('click', function() {self.hide();}))
				.append($('<textarea class="edit">').val('Pick a text node to load it.'))
				.append($('<a class="update">').text('update').click(function() {self.updateNode();}))
				.append($('<a class="restore">').text('restore').click(function() {self.restoreNode();}))
			;
		}
	};

})(jQuery);
