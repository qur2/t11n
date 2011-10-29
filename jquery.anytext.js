/**
 * jQuery anyText plugin.
 * 
 * Copyright (c) 2011 "qur2" Aurélien Scoubeau
 * Dual licensed under the MIT and GPL licenses.
 */
(function($) {
	/**
	 * Returns the text node index, relative to its tet node siblings.
	 */
	$.fn.textIndex = function() {
		i = -1;
		node = this[0];
		siblings = node.parentNode.childNodes;
		for (n in siblings) {
			if (siblings[n] && 3 == siblings[n].nodeType) {
				// if (node.length) i++;
				if (siblings[n].length == node.length && siblings[n].nodeValue == node.nodeValue)
					return i;
			}
		}
		return i;
	};
	
	/**
	 * Returns the node index, relative to its sibling having the same node name.
	 */
	$.fn.sameKindIndex = function() {
		el = $(this);
		return 3 == el.get(0).nodeType
			? el.textIndex()
			: el.parent().children(el.get(0).tagName.toLowerCase()).index(el);
	}
	
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
					panel.show();
					panel.setTextnodes(textnodes);
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
			if (domElem.find('.anyText-inner').is(':visible'))
				this.hide();
			else
				this.show();
		},

		show: function() {
			domElem.find('.anyText-inner').show();
		},

		hide: function() {
			domElem.find('.anyText-inner').hide();
		},

		toggleActive: function() {
			$.fn.anyText.active = !$.fn.anyText.active;
			if ($.fn.anyText.active)
				domElem.find('.active').removeClass('off').addClass('on');
			else
				domElem.find('.active').removeClass('on').addClass('off');
		},

		changeNode: function(inc) {
			this.currentNodeIndex = (this.currentNodeIndex + inc % this.nodes.length) % this.nodes.length;
			if (this.currentNodeIndex < 0)
				this.currentNodeIndex = this.nodes.length + this.currentNodeIndex;
			this.currentNode = this.nodes.eq(this.currentNodeIndex);
			text = this.currentNode.text();
			domElem.find('.edit').val(text.replace(/\s+/gi,' ')).focus().get(0).select();
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
			if (!this.currentNode) return;
			text = domElem.find('.edit').val();
			if ($.trim(this.currentNode[0].nodeValue) == $.trim(text)) return;
			// if (text.charAt(0).match(/\w/))
			// 	text = ' ' + text;
			// if (text.charAt(text.length-1).match(/\w/))
			// 	text = text + ' ';
			if (!this.currentNode.data('old'))
				this.currentNode.data('old', this.currentNode[0].nodeValue);
			this.currentNode[0].nodeValue = text;
			this.currentNode.data('anyText', true);
		},

		restoreNode: function() {
			if (!this.currentNode) return;
			text = this.currentNode.data('old');
			if (text) {
				this.currentNode[0].nodeValue = text;
				this.currentNode.removeData('anyText');
			}
		},
		},

		build: function() {
			self = this;
			domElem = $('<div>').attr('id', 'anyText-panel')
				.append($('<span class="active on">').text('active').bind('click', function() {self.toggleActive();}))
				.append($('<span class="toggle">').text('show/hide').bind('click', function() {self.toggle();}))
				.append($('<div class="anyText-inner">').hide()
					.append($('<span class="node-count">').text('-'))
					.append($('<a class="node-prev">').text('prev').bind('click', function() {self.prevNode();}))
					.append($('<span class="node-current">').text('-'))
					.append($('<a class="node-next">').text('next').bind('click', function() {self.nextNode();}))
					.append($('<textarea class="edit">').val('Pick a text node to load it.'))
					.append($('<a class="update">').text('update').click(function() {self.updateNode();}))
					.append($('<a class="restore">').text('restore').click(function() {self.restoreNode();}))
					.append($('<a class="save">').text('save').click(function() {self.saveNodes();}))
				)
				.appendTo('body')
			;
		}
	};

})(jQuery);
