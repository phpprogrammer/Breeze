/* JavaScript DOM Selecting Library */

(function(window) {
	"use strict";
	
	var $ = function(n, n2, n3) {
		if(!(this instanceof $)) {
			return new $(n, n2, n3);
		}

		if(arguments[0] == null) arguments[0] = document.body;
		this.selector = '';
		
		return this.init.apply(this, arguments);
	};
	
	$.extend = function() {
		for(var i = 1; i < arguments.length; i++) {
			for(var j in arguments[i]) {
				arguments[0][j] = arguments[i][j];
			}
		}
		return arguments[0];
	};
	
	$.prototype = $.extend([], {
		constructor: $,
		g: undefined,
		
		init: function() {
			for(var i = 0; i < arguments.length; i++) {
				var n = arguments[i];
				if(n === undefined) continue;
				
				if(n.nodeType === 1 || n.nodeType === 3 || n.nodeType === 9) {
					this.push(n);
				} else if(n instanceof Array) {
					this.push.apply(this, n);
				} else if(typeof n === "string") {
					this.selector = n;
					n = document.querySelectorAll(n);
					this.push.apply(this, n);
				} else if(n instanceof Function) {
					$.ready(window, n);
					return $.window;
				} else if(n instanceof Object && n.hasOwnProperty('url')) {
					$.ajax(n);
					return $.window; 
				} 
			}
			return this;
		},
		get: function(g) {
			g = g % this.length;
			if(g < 0) {
				g = this.length + g;
			}
			this.g = g;
			return this;
		},
		eq: function(q) {
			q = q % this.length;
			if(q < 0) {
				q = this.length + q;
			}
			return $(this[q]);
		},
		item: function(i) {
			if(i == null) i = 0;
			i = i % this.length;
			if(i < 0) {
				i = this.length + i;
			}
			return this[i];
		},
		include: function(o) {
			if(o instanceof Array)
				this.push.apply(this, o);
			else if(typeof o === "string")
				this.push.apply(this, $(o));
			else 
				this.push(o);
			return this;
		},
		delete: function(n,c) {
			if(!n && n != 0) var n = this.g;
			if(!c) c = 1;
			if(n >= 0) {
				this.splice(n, c);
			}
			return this;
		},
		getAll: function() {
			this.g = undefined;
			return this;
		},
		active: function() {
			if(! this.g)
				return this.item(0);
			return this.item(this.g);
		},
		indexOf: function(o) {
			var a = this.active();
			for(var i = 0; i < this.length; i++) {
				if(this.item(i) === o)
					return i;
			}
			return -1;
		},
		first: function() { return this.item(0);	},
		second: function() { return this.item(1); },
		last: function() { return this.item(-1); },
		
		getNext: function() { return this.get(this.g + 1); },
		getPrev: function() { return this.get(this.g - 1); },
		
		next: function(s) {
			var x = this.active().nextSibling;
			while(x.nodeType !== 1 || !$.checkElement(x, s)) {
				if(x.nextSibling != null)
					x = x.nextSibling;
				else 
					x = x.parentNode.childNodes[0];
			}
			return $(x);
		},
		prev: function(s) {
			var x = this.active().previousSibling;
			while(x.nodeType !== 1 || !$.checkElement(x, s)) {
				if(x.previousSibling != null)
					x = x.previousSibling;
				else
					x = x.parentNode.childNodes[x.parentNode.childNodes.length-1];
			}
			return $(x);
		},
		parent: function() {
			return $(this.active().parentNode);
		},
        parents: function(s) {
            var a = [], p = $.parseSelector(s);
            $.each(this, function() {
                var element = this;
                while(element.parentNode && !$.checkElement(element.parentNode, p)) {
                    element = element.parentNode;
                }
                if (element.parentNode) {
                    a.push(element.parentNode);
                }
            });
            return $(a);
        },
		children: function(s) {
			var a = [], p = $.parseSelector(s);
			$.each(this, function() {
				for(var i = 0; i < this.childNodes.length; i++) {
					if(this.childNodes[i].nodeType === 1 || this.childNodes[i].nodeType === 11) {
						if(p && !$.checkElement(this.childNodes[i], p))
							continue;
						a.push(this.childNodes[i]);
					}
				}
			});
			return $(a);
		},
		contents: function() {
			var a = [];
			$.each(this, function() {
				for(var i = 0; i < this.childNodes.length; i++) {
					if(this.childNodes[i].nodeType === 3) {
						a.push(this.childNodes[i]);
					} else if(this.childNodes[i].nodeType === 1 && this.childNodes[i].tagName.toLowerCase() !== 'script') {
						a.push.apply(a, $(this.childNodes[i]).contents());
					}
				}
			});
			return $(a);
		},
		nodes: function(all) {
			var a = [];
			if(all) {
				$.each(this, function() {
					for(var i = 0; i < this.childNodes.length; i++) {
						a.push(this.childNodes[i]);
						if(this.childNodes[i].nodeType === 1)
							a.push.apply(a, $(this.childNodes[i]).nodes(true));
					}
				});
			} else {
				$.each(this, function() {
					for(var i = 0; i < this.childNodes.length; i++) {
						a.push(this.childNodes[i]);
					}
				});
			}
			return $(a);
		},
		clone: function() {
			var a = [];
			$.each(this, function() {
				if(this.cloneNode)
					a.push(this.cloneNode(true));
			});
			return $(a);
		},
		filter: function(f) {
			var a = [];
			if(f instanceof Function) {
				$.each(this, function() {
					if(f.call(this) === true)
						a.push(this);
				});
			} else if(typeof f === "string") {
				var p = $.parseSelector(f);
				if(!p) return this;
				
				$.each(this, function() {
					if($.checkElement(this, p))
						a.push(this);
				});
			}
			return new $(a);
		},
		not: function(s) {
			if(typeof s === "string") {
				var p = $.parseSelector(s);
				if(!p) return this;
				
				$.each(this, function() {
					if($.checkElement(this, p))
						arguments[2].delete(arguments[1]);
				});
			} else {
				if(!(s instanceof $)) s = [s];
				else s = s.n;
				for(var j = 0; j < s.length; j++) {
					i = this.indexOf(s[j]);
					if(i !== -1) {
						this.delete(j);
					}
				}
			}
			return this;
		},
		find: function(s) {
			var a = [];
			$.each(this, function() {
				var n = this.querySelectorAll(s);
				for(var i = 0; i < n.length; i++) {
					a.push(n[i]);
				}
			});
			return $(a);
		},
		each: function(c) {
			return $.each(this, c);
		},
		hasClass: function(c) {
			return $.each(this, function() {
				if(! this.hasClass(c))
					return false;
			}) ? true : false;
		},
		addClass: function(c) {
			if(!c) return this;
			$.each(this, function() {
				this.addClass(c);
			});
			return this;
		},
		removeClass: function(c) {
			if(!c) return this;
			$.each(this, function() {
				this.removeClass(c);
			});
			return this;
		},
		toggleClass: function(c) {
			if(!c) return this;
			$.each(this, function() {
				this.toggleClass(c);
			});
			return this;
		},
		html: function(h) {
			if(h == null) {
				return this.active().innerHTML;
			} else if(h === true) {
				return this.active().outerHTML;
			} else {
                if (this.g) {
                    this.active().innerHTML = h+'';
                } else {
                    $.each(this, function() {
                        this.innerHTML = h+'';
                    });
                }
				return this;
			} 
		},
		text: function(t) {
			if(!t) {
				return this.active().outerText;
			} else {
				$.each(this, function() {
					this.outerText = t;
				});
				return this;
			}
		},
		value: function(v) {
			if(!v) {
				return this.active().value;
			} else {
				$.each(this, function() {
					this.value = v;
				});
				return this;
			}
		},
		offset: function() {
			var a = this.active();
			return {
				height: a.offsetHeight,
				left: a.offsetLeft,
				parent: a.offsetParent,
				top: a.offsetTop,
				width: a.offsetWidth,
                right: window.outerWidth - a.clientWidth - a.offsetLeft,
                bottom: window.outerHeight - a.clientHeight - a.offsetTop
			};
		},
		origin: function() {
			var el = this.active(), x = 0, y = 0;
			while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
				x += el.offsetLeft - el.scrollLeft;
    			y += el.offsetTop - el.scrollTop;
    			el = el.offsetParent;
    		}
    		return { x: x, y: y };
		},
        prop: function(p,v) {
            if (p != undefined && v != undefined) {
                var t = p;
                p = {};
                p[t] = v;
            }
            if (p instanceof Object) {
                $.each(this, function() {
                    for (var s in p) {
                        this[s] = p;
                    }
                });
            } else {
                return this.active()[p];
            }
            return this;
        },
		attr: function(p,v) {
			if(p != undefined && v != undefined) {
				var t = p;
				p = {};
				p[t] = v.toString();
			}
			if(p instanceof Object) {
				for (var s in p) {
					$.each(this, function() {
						this.setAttribute(s, p[s]);
					});
				}
			} else {
				var a = this.active().getAttribute(p);
                if (a == null)
                    return '';
                return a;
			}
			return this;
		},
		removeAttr: function(p) {
			if(typeof p === "string") {
				$.each(this, function() {
					this.removeAttribute(p);
				});
			}
			return this;
		},
		hasAttr: function(o) {
			if(o instanceof Object) {
				return $.each(this, function() {
					if(! this.hasAttr(o))
						return false;
				}) ? true : false;
			}
			return true;
		},
        is: function(s) {
            s = $.parseSelector(s);
            return $.checkElement(this.active(), s);
        },
        tag: function() {
            return this.active().tagName.toLowerCase();
        },
		data: function(p,v,d) {
			if(p && v) {
				p = p.removeChars('-');
				if(!this.dataset || d)
					$.each(this, function() { this['data_'+p] = v; });
				else
					$.each(this, function() { this.dataset[p] = v; });
			} else if(p) {
				return this.active().dataset[p] || this.active()['data_'+p];
			}
			return this;
		},
		sort: function(a,b) {
			var self = this, n = this.length, el;
			
			do {
				for(var i = 0; i < n-1; i++) {
					el = this.eq(i+1);
					if(this.get(i).html() > el.html()) {
						this.insertAfter(el);
						this.splice(i, 2, el.active(), this.active());
					}
				}
				n--;
			} while(n > 1);
			
			return this;
		},
		reverse: function() {
			var a = [];
			for(var i = this.length-1; i >= 0; i--) {
				a.push(this.item(i));
				this.get(i).insertBefore(this.eq(0));
			}
			return $(a);
		},
		getStyle: function(p) {
			return getComputedStyle(this.active(), null).getPropertyValue(p);
		},
		css: function(p,v) {
			if(p instanceof Object) {
				var prefix = $.browser.prefix,
					px = ['top','right','bottom','left','width','height','minWidth','minHeight','maxWidth','maxHeight',
						'paddingTop','paddingRight','paddingBottom','paddingLeft','marginTop','marginRight','marginBottom','marginLeft',
						'borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth'],
					t_px = ['translateX','translateY','translateZ'],
					t_deg = ['rotateX','rotateY','rotateZ','skewX','skewY','skewZ'],
					t_other = ['translate','translate3d','rotate','rotate3d','skew','scale','scale3d','scaleX','scaleY','scaleZ'],
					t_v = '';
				
				for(var s in p) {
					if(px.indexOf(s) !== -1) {
						p[s] = parseInt(p[s])+'px';
					} else if(t_px.indexOf(s) !== -1) {
						t_v += s+'('+parseInt(p[s])+'px)';
						delete p[s];
					} else if(t_deg.indexOf(s) !== -1) {
						t_v += s+'('+parseInt(p[s])+'deg)';
						delete p[s];
					} else if(t_other.indexOf(s) !== -1) {
						t_v += s+'('+p[s]+')';
						delete p[s];
					}
				}
				if(t_v.length > 0) {
					p['transform'] = t_v;
					p[prefix+'transform'] = t_v;
				}
				if(p.origin) {
					p['transform-origin'] = p.origin;
					p[prefix+'transform-origin'] = p.origin;
					delete p.origin;
				}
				
				for(var s in p) {
					$.each(this, function() {
						this.style[s] = p[s];
					});
				}
			} else {
				if(v !== undefined) {
					$.each(this, function() {
						this.style[p] = v;
					});
					return this;
				} else {
					return (this.active().style[p] ? this.active().style[p] : this.getStyle(p));
				}
			}
			return this;
		},
		animate: function(p, o, c, a) {
			var def = {
				duration: 300,
				function: 'ease-in-out',
				delay: 0,
				additive: false,
			}, 	self = this, prefix = $.browser.prefix, n;
			
			if(o >= 0) o = {duration:o};
			if(o) def.extend(o);
			
			if(a === true) def.additive = true;
			
			if($.animations) {
				n = {};
				n['transition'] = 'all '+def.duration+'ms '+def.function+' '+def.delay+'ms';
				n[prefix+'transition'] = n['transition'];
			}
			
			if(def.additive && this.transformMap) {
				p = this.transformMap.extend(p);
			}
			
			setTimeout(function() {
				if($.animations) self.css(n);
				self.css(p);
			}, 25);
			
			this.transformMap = p;
			
            if (c) {
                setTimeout(function() {
                    c.call(self);
                }, def.duration + def.delay);
                //self.on($.browser.prefix.split('-').join('')+'TransitionEnd TransitionEnd', $.invoke(c, self));
            }
			return this;
		},
		clearAnimation: function() {
			var p = {};
			p[$.browser.prefix+'transform'] = null;
			p[$.browser.prefix+'transition'] = null;
			this.transformMap = undefined;
			this.css(p);
			return this;
		},
		scrollTo: function(x, y, t) {
			var self = this, el = this.active();
			if(t === undefined) t = 500;

			function interval(el, t, tick, ax) {
				var itv = setInterval(function() {
					if(ax == 'top')
						el.scrollTop = el.scrollTop + tick;
					else if(ax == 'left')
						el.scrollLeft = el.scrollLeft + tick;
					t -= 5;
					if(t <= 0) clearInterval(itv);
				}, 5);
			}
			if(!isNaN(x)) {
				var diff = x - el.scrollLeft;
				interval(el, t, diff / t * 5, 'left');
			}
			if(!isNaN(y)) {
				var diff = y - el.scrollTop;
				interval(el, t, diff / t * 5, 'top');
			}
			return this;
		},
		delay: function(t) {
			$.delay(t);
			return this;
		},
		show: function(d) {
			$.each(this, function(e) {
				e.style.display = d || null;
			});
			return this;
		},
		hide: function() {
			return this.show('none');
		},
		fadeIn: function(t,c) {
			return this.fadeTo(1, t, c);
		},
		fadeOut: function(t,c) {
			return this.fadeTo(0, t, c);
		},
		fadeTo: function(v,t,c) {
			if(v === undefined) return false;
			if(!(t >= 0)) t = 500;
			this.animate({ opacity: v }, t, function() {
				if(c instanceof Function)
					c.apply(this, arguments);
			});
			return this;
		},
		
		remove: function(d) {
			if(this.g != null) {
				if(this.active().parentNode)
					this.active().parentNode.removeChild(this.active());
				if(d) this.delete();
			} else {
				for(var i = 0; i < this.length; i++) {
					if(this[i].parentNode !== null)
						this[i].parentNode.removeChild(this[i]);
					if(d) {
						this.delete(i);
						i--;
					}
				}
			}
			return this;
		},
		
		on: function(n, f) {
			if(!f) return this.active().emit(n);
			$.each(this, function() {
				this.on(n,f);
			});
			return this;
		},
		off: function(n, f) {
			if(!n) return false;
			$.each(this, function() {
				this.off(n, f);
			});
			return this;
		},
		emit: function(n, a) {
			if(!n) return null;
			$.each(this, function() {
				this.emit(n, a);
			});
			return this;
		},
		rightclick: function(f) {
			return this.active().on('contextmenu', f);
		},
		focus: function(f) {
            if (this.active())
                this.active().on('focus', f);
            return this;
		},
        load: function(f) {
            return this.on('load', f);
        },
        submit: function() {
            if (this.active())
                this.active().submit();
            return this;
        },
        ajaxSubmit: function(c) {
            if (!this.active() || this.active().tagName.toLowerCase() !== 'form')
                return this;
            
            var form = $(this.active());
            
            if (XMLHttpRequest) {
                var href = form.attr('action'),
                    method = form.attr('method'),
                    uri = '',
                    elms = form.find('*[name]');
                
                if (!href)
                    href = '';
                if (!method)
                    method = 'post';
                
                elms.each(function(e,i) {
                    if (this.type !== 'checkbox' || this.checked != false) {
                        if (i !== 0)
                            uri += '&';
                        uri += this.name + '=' + this.value;
                    }
                });
                
                $.ajax({ url: href+'?'+uri, type: method }, c);
            } else {
                var frame = $.create('iframe'),
                    href = form.attr('action');
                
                if (!href)
                    href = '';
                
                $().append(frame);
                frame.attr({ name: 'ajaxFrame', src: href }).css({ width: 0, height: 0, opacity: 0 }).hide();
                form.attr({ target: 'ajaxFrame'}).submit();
                
                if (c instanceof Function)
                    frame.load(function(e) {
                        c.call(form, frame.active().contentWindow.document.body.innerHTML, e);
                    });
            }
            return this;
        },
		wrap: function(p) {
			if(!(p instanceof $)) return false;
			p.active().wrapAll(this);
			return this;
		},
		prepend: function() {
			for(var i = 0; i < arguments.length; i++) {
				this.active().insertBefore($.node(arguments[i]), this.active().firstChild);
			}
			this.emit('prepended', arguments);
			return this;
		},
		prependTo: function(c) {
			return c.prepend.apply(c, this);	
		},
		append: function() {
			for(var i = 0; i < arguments.length; i++) {
				this.active().appendChild($.node(arguments[i]));
			}
			this.emit('appended', arguments);
			return this;
		},
		appendTo: function(c) {
			return c.append.apply(c, this);
		},
		before: function() {
			for(var i = 0; i < arguments.length; i++) {
				this.active().parentNode.insertBefore($.node(arguments[i]), this.active());
			}
			return this;
		},
		after: function() {
			for(var i = 0; i < arguments.length; i++) {
				this.active().parentNode.insertBefore($.node(arguments[i]), this.active().nextSibling);
			}
			return this;
		},
		insertBefore: function(c) {
			$.node(c).parentNode.insertBefore(this.active(), $.node(c));
			return this;
		},
		insertAfter: function(c) {
			$.node(c).parentNode.insertBefore(this.active(), $.node(c).nextSibling);
			return this;
		},
		newLine: function() {
			$.each(this, function() { this.appendChild(document.createElement('br')); }); return this;
		}
	});
	
	$.extend($, {
		localValues: true,
		animations: true,
		
		create: function(t) {
			var e, p = $.parseSelector(t || 'div');
			if(!p.tag) p.tag = 'div';

			e = $(document.createElement(p.tag));

			if(p.id)
				e.attr('id', p.id);
			if(p.clss)
				e.addClass(p.clss);
			if(p.attr)
				e.attr(p.attr);
			return e;
		},
		text: function(t) {
			if(typeof t === "string")
				return document.createTextNode(t);
		},
		node: function(a) {
			if(a.nodeType) {
				return a;
			} else if(a instanceof $) {
				return a.active();
			} else {
				return document.createTextNode(a+'');
			}
		},
		each: function(t,c) {
			if(t instanceof $) {
				var l = t.length;
				for(var i = 0; i < t.length; i++) {
					t.get(i);
					if(c.call(t[i], t[i], i, t) === false)
						return false;
					
						if(l !== t.length) {
						i = i + (t.length - l);
						l = t.length;
					}
				}
				t.getAll();
			} else if(t instanceof Array) {
				for(var z = 0; z < t.length; z++) {
					if(c.call(t[z], t[z], z, t) === false)
						return false;
				}
			} else {
				if(c.call(t, t, 0, t) === false)
					return false;
			}
			return t;
		},
		parseSelector: function(s) {
			if(!s) return false;
			var z = s.indexOf('!'),
				x = s.indexOf('#'),
				y = s.indexOf('.'),
				a = s.indexOf('['),
                d = s.indexOf(':'),
				tag = '', id = '', cl = '', attr = {}, not = '';
            
            if (d !== -1) {
                s = s.replace(/\:(\w*)/g, "[$1=$1]");
                return $.parseSelector(s);
            }
            
			while(a !== -1) {
				var g = s.indexOf(']') || s.length,
					b = s.substring(a+1, g).removeChars('"', "'"),
					e = b.indexOf('=');
				if(e !== -1) {
					attr[b.substring(0, e)] = b.substring(e+1);
				} else {
					attr[b] = true;
				}
				s = s.substring(0, a) + s.substring(g+1);
				a = s.indexOf('[');
			}

			if(z !== -1) {
				not = s.substring(z+1);
				s = s.substring(0, z);
			}
			if(s.length > 0) {
				if(x === -1 && y === -1) {
					tag = s;
				} else if(x === -1) {
					tag = s.substring(0, y);
					cl = s.substring(y+1).split('.');
				} else if(y === -1) {
					tag = s.substring(0, x);
					id = s.substring(x+1);
				} else {
					if(x < y) {
						tag = s.substring(0, x);
						id = s.substring(x+1, y);
						cl = s.substring(y+1).split('.');
					} else {							 
						tag = s.substring(0, y);		 
						cl = s.substring(y+1, x).split('.');
						id = s.substring(x+1);
					}
				}
			}
			return { clss: cl, id: id, tag: tag, attr: attr, not: not };
		},
		checkElement: function(el, p) {
			if(!el) return false;
			if(!p) return true;
			if(typeof p === "string")
				p = $.parseSelector(p);
			return ((!p.tag || el.tagName.toLowerCase() === p.tag.toLowerCase()) 
				&& (!p.id || el.id === p.id) 
				&& (!p.clss || el.hasClass(p.clss)) 
				&& (el.hasAttr(p.attr)) 
				&& (!p.not || !$.checkElement(el, p.not)));
		},
		invoke: function(w, x, a) {
			if(!w || !x) return false;
			a = a instanceof Array ? a : a != undefined ? [a] : [];
			return function() {
				a.push($(this));
				a.push.apply(a, arguments);
				return w.apply(x, a);
			};
		},
		browser: {
			userAgent: navigator.userAgent,
			mobile: false,
			
			init: function() {
				var u = this.userAgent.toLowerCase(), d;
					
				if(u.indexOf('ie') !== -1) {
					d = detectVersion('msie ', ';');
					d.name = 'Internet Explorer';
					this.ie = true;
					this.prefix = '-ms-';
				} else if(u.indexOf('opera') !== -1) {
					d = detectVersion('version/');
					d.name = 'Opera';
					this.opera = true;
					this.prefix = '-o-';
				} else if(u.indexOf('chrome') !== -1) {
					d = detectVersion('chrome/');
					d.name = 'Chrome';
					this.chrome = true;
					this.webkit = true;
					this.prefix = '-webkit-';
				} else if(u.indexOf('safari') !== -1) {
					d = detectVersion('version/');
					d.name = 'Safari';
					this.safari = true;
					this.webkit = true;
					this.prefix = '-webkit-';
				} else if(u.indexOf('firefox') !== -1) {
					d = detectVersion('firefox/');
					d.name = 'Firefox';
					this.firefox = true;
					this.prefix = '-moz-';
				} else if(u.indexOf('webkit') !== -1) {
					d.name = 'WebKit';
					this.webkit = true;
					this.prefix = '-webkit-';
				}
				
				if(u.indexOf('windows') !== -1) {
					d.system = 'Windows';
				} else if(u.indexOf('mac os') !== -1) {
					if(u.indexOf('mobile') !== -1)
						d.system = 'iOS';
					else
						d.system = 'Mac OS X';
				} else if(u.indexOf('android') !== -1) {
					d.system = 'Android';
				} else if(u.indexOf('linux') !== -1) {
					d.system = 'Linux';
				}
				
				if(u.indexOf('mobile') !== -1) {
					d.mobile = true;
				}
				
				$.extend(this, d);
				
				function detectVersion(h, s) {
					if(!s) s = ' ';
					var x, y;
					x = u.substr(u.indexOf(h) + h.length);
					y = x.substring(0, x.indexOf('.'));
					s = x.indexOf(s);
					x = x.substring(0, s !== -1 ? s : x.length);
					return { version: y, fullversion: x };
				}
			},
			requirements: function(r) {
				if(!r) return null;

				if(r.mobile === false && $.browser.mobile === true) {
					return false;
				}
				
				if(r[$.browser.name]) {
					r = r[$.browser.name];
					
					if(r.version && r.version > $.browser.version) {
						return false;
					}
					if(r.fullversion) {
						var k = r.fullversion.split('.'),
							l = $.browser.fullversion.split('.');
							
						for(var i = 0, j = (k.length > l.length ? k.length : l.length); i < j; i++) {
							if(k[i] > l[i] || (!l[i] && k[i] != 0) || (!l[i] && k[i+1])) {
								return false;
							}
						}
					}
				}
			}
		},
		stylesheet: {
			lastIndex: undefined,
			insert: function(s, c) {
				var sh = document.styleSheets[0];
				if(sh.insertRule) 
					sh.insertRule(s+'{'+c+'}', sh.cssRules.length);
				else
					sh.addRule(s, c);
				this.lastIndex = sh.cssRules.length - 1;
			},
			delete: function(i) {
				if(i === undefined) i = this.lastIndex;
				var sh = document.styleSheets[0];
				sh.deleteRule(i);
			}
		},
		ajax: function(n, c) {
			if(!n.url) return false;
			if(!n.type) n.type = 'post';
				
			var hr;
				
			if (window.XMLHttpRequest) {
				hr = new XMLHttpRequest();
			} else if (window.ActiveXObject) {
				hr = new ActiveXObject("Microsoft.XMLHTTP");
			}
				
			hr.onreadystatechange = function() {
				if(c && c instanceof Function) {
	    			if(hr.readyState === 4) {
		    			if(hr.status === 200) {
			    			c.call(null, hr.responseText);
			    		}
		    		}
	    		}
			};
			var i = n.url.indexOf('?'),
				d = '';
			
			if(i !== -1) {
				d = n.url.substr(i+1);
				n.url = n.url.substr(0, i);
			}
			hr.open(n.type, n.url);
			hr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			hr.send(d);
			return true;
		},
		ready: function(w, f) {
			var DOMContentLoaded = function() {
				if(document.addEventListener) {
					w.removeEventListener("DOMContentLoaded", DOMContentLoaded, false);
					f.apply($.window);
				} else if(document.readyState == 'complete') {
					w.detachEvent("onreadystatechange", DOMContentLoaded);
					f.apply($.window);
				}
			};
				
			if(document.addEventListener)
				w.addEventListener("DOMContentLoaded", DOMContentLoaded, false);
			else
				w.attachEvent("onreadystatechange", DOMContentLoaded);
			
			return w;
		},
		require: function(p) {
			if(!p) return false;
			var s = document.createElement('script');
			s.async = true;
			s.type = 'text/javascript';
			s.src = p;
			var l = document.body.childNodes.last();
			l.parentNode.insertBefore(s, l);
		},
		delay: function(t) {
			if(!t) t = 50;
				var start = new Date().getTime();
				for (var i = 0; i < 1e7; i++) {
					if ((new Date().getTime() - start) > t) {
						break;
				}
			}
			return this;
		}
	});
		
	$.each(['click','mouseup','mousedown','mouseout','mouseover','mouseleave','mouseenter','keydown','keyup','keypress','blur','submit','change','scroll'], function(n,i) {
		$.prototype[n] = function(f) { return this.on(n, f); };
	});
	$.each(['width', 'height'], function(n,i) {
		$.prototype[n] = function(v) { return v === undefined ? parseInt(this.css(n)) : this.css(n,v); };
	});
	$.each(['clientWidth','clientHeight','scrollWidth','scrollHeight'], function(n,i) {
		$.prototype[n] = function() { return this.active()[n]; };
	});
	$.each(['scrollLeft','scrollTop'], function(n,i) {
		$.prototype[n] = function(l) { if(l) $.each(this, function() { this[n] = l; }); else return this.active()[n]; return this; };
	});
	$.each(['translateX','translateY','translateZ','rotateX','rotateY','rotateZ','skewX','skewY','skewZ','scale','scaleX','scaleY','scaleZ','opacity'], function(n,i) {
		$.prototype[n] = function() { 
			var prop = {};
			prop[n] = arguments[0];
			this.animate(prop, arguments[1], arguments[2], true);
			return this;
		};
	});
	
	$.document = $(document);
	$.window = $(window);
	$.browser.init();
	window.$ = $;
	window.DOM = $;
	
	
	String.prototype.trim = function() {
		var s = this, whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000',
		i = 0;
		
		for(i = 0; i < s.length; i++) {
			if (whitespace.indexOf(s.charAt(i)) === -1) {
				s = s.substring(i);
				break;
			}
		}
		for(i = s.length - 1; i >= 0; i--) {
			if (whitespace.indexOf(s.charAt(i)) === -1) {
				s = s.substring(0, i + 1);
				break;
			}
		}
		return whitespace.indexOf(s.charAt(0)) === -1 ? s : '';
	};
    String.prototype.break = function(n) {
        return this.lbreak(n).rbreak(n);
    };
    String.prototype.lbreak = function(n) {
        var s = this, index = this.indexOf(n);
        if (index === 0) {
            s = s.substring(n.length);
        }
        return s;
    };
    String.prototype.rbreak = function(n) {
        var s = this, index = this.indexOf(n);
        if (index === s.length - n.length) {
            s = s.substring(0, index);
        }
        return s;
    };
	String.prototype.removeChars = function() {
		var n = this;
		for(var i = 0; i < arguments.length; i++) {
			n = n.split(arguments[i]).join('');
		}
		return n;
	};
	String.prototype.replaceAll = function(a,b) {
		var s = this;
		if(a instanceof Array && b instanceof Array) {
			for(var i = 0; i < a.length && i < b.length; i++) {
				s = s.split(a[i]).join(b[i]);
			}
		} else {
			var r = arguments[arguments.length-1];
			for(var i = 0; i < arguments.length-1; i++) {
				s = s.split(arguments[i]).join(r);
			}
		}
		return s.toString();
	};
	HTMLElement.prototype.hasClass = function(c) {
		if(!c) return true;
		if(typeof c === "string") {
			if(this.classList)
				return this.classList.contains(c);
			else if(c && (this.className.indexOf(c) === 0 || this.className.indexOf(' '+c+' ') !== -1 || this.className.lastIndexOf(c) === 0))
				return true;
			return false;
		} else {
			var self = this;
			return $.each(c, function(e) {
				if(! self.hasClass(e))
					return false;
			}) ? true : false;
		}
		return false;
	};
	HTMLElement.prototype.addClass = function(c) {
		c = (typeof c === "string" ? c.split(' ') : c);
		for(var i = 0; i < c.length; i++) {
			if(this.classList)
				this.classList.add(c[i]);
			else if(! this.hasClass(c[i]))
				this.className += " "+c[i];
		}
	};
	HTMLElement.prototype.removeClass = function(c) {
		c = (typeof c === "string" ? c.split(' ') : c);
		for(var i = 0; i < c.length; i++) {
			if(this.classList)
				this.classList.remove(c[i]);
			else if(this.hasClass(c[i]))
				this.className = this.className.replace(c[i], '').trim();
		}
	};
	HTMLElement.prototype.toggleClass = function(c) {
		if(this.classList)
			this.classList.toggle(c);
		else if(this.hasClass(c))
			this.removeClass(c);
		else
			this.addClass(c);
	};
	HTMLElement.prototype.hasAttr = function(o) {
		if(!o) return true;
		for(var k in o) {
			if(this.getAttribute(k) != o[k])
				return false;
		}
		return true;
	};
	HTMLElement.prototype.wrapAll = function(elms) {
	    var el = elms.length ? elms[0] : elms,
	    	parent  = el.parentNode,
	    	sibling = el.nextSibling;
	    	
	    this.appendChild(el);
	    
	    for(var i = 0; i < elms.length; i++) {
	        this.appendChild(elms[i]);
	    }
	    
	    if (sibling) {
	        parent.insertBefore(this, sibling);
	    } else if(parent) {
	        parent.appendChild(this);
	    }
	};
	Object.prototype.on = function(n, f) {
        if (!n) return false;
        if (!f) return this.emit(n);
		n = n.split(' ').clean('');
		if(! this._observers) {
			this._observers = {};
			this.hideProperties(['_observers']);
		}
			
		for(var i = 0; i < n.length; i++) {
			if(!this._observers[n[i]])
				this._observers[n[i]] = [];
		
			for(var j = 1; j < arguments.length; j++) {
				if(! arguments[j] instanceof Function) continue; 
				if(this._observers[n[i]].searchFunc(arguments[j]) !== -1)
					this.off(n[i]);
				this._observers[n[i]].push(arguments[j]);
				
				if(this.addEventListener)
					this.addEventListener(n[i], arguments[j], false);
			}
		}
		return this;
	};
	Object.prototype.off = function(n, f) {
		if(!n || !this._observers) return false;
		n = n.split(' ').clean('');
	
		function removeListener(a, b, c) {
			if(a.removeEventListener)
				a.removeEventListener(b, a._observers[b][c]);
			a._observers[b][c] = null;
		}
		for(var i = 0; i < n.length; i++) {
			if(!this._observers[n[i]]) continue;
			
			var j = 0, m = this._observers[n[i]].length;
			
			if(f instanceof Function) {
				j = this._observers[n[i]].searchFunc(f);
				
				if(j !== -1) {
					removeListener(this, n[i], j);
				} else {
					this.off(n[i]);
				}
			} else {
				for(; j < m; j++) {
					removeListener(this, n[i], j);
				}
			}
			this._observers[n[i]].clean(null);
		}
		return this;
	};
	Object.prototype.emit = function(n, a) {
        if (!n) return false;

		if (a) {
			a = Array.prototype.slice.call(arguments);
			a.shift();
		}
		if (this.nodeType > 0) {
			var e;
            if (n === 'click') {
                this.click();
            } else if (n === 'focus') {
                this.focus();
            } else if (document.createEvent) {
				e = document.createEvent("HTMLEvents");
				e.initEvent(n, true, true);
				if(a) e.extend(a);
				this.dispatchEvent(e);
			} else {
				e = document.createEventObject();
				e.eventType = n;
				if(a) e.extend(a);
				this.fireEvent("on"+n, e);
			}
		} else if (this._observers && this._observers[n]) {
            for(var i = 0; i < this._observers[n].length; i++) {
                this._observers[n][i].apply(this, a);
            }
		}
		return this;
	};
	Object.prototype.extend = function() {
		for(var i = 0; i < arguments.length; i++) {
			if(!(arguments[i] instanceof Object)) continue;
			var keys = Object.keys(arguments[i]);
			for(var j = 0; j < keys.length; j++) {
				this.emit('set:'+keys[j], arguments[i][keys[j]], this[keys[j]]);
				if(this[keys[j]] instanceof Object && arguments[i][keys[j]] instanceof Object && 
					!(this[keys[j]] instanceof Function) && !(arguments[i][keys[j]] instanceof Function) && 
					!(this[keys[j]] instanceof Array) && !(arguments[i][keys[j]] instanceof Array))
						this[keys[j]].extend(arguments[i][keys[j]]);
				else
					this[keys[j]] = arguments[i][keys[j]];
			}
		}
		this.emit('set');
		return this;
	};
	Object.prototype.make = function() {
		if(arguments.length == 0) return null;
		var a = {};
		for(var i = 0; i < arguments.length; i++) {
			if(typeof arguments[i] === "string" && (typeof arguments[i+1] === "string" || typeof arguments[i+1] === "number")) {
				a[arguments[i]] = arguments[i+1];
				i++;
			} else if(arguments[i] instanceof Object) {
				a.extend(arguments[i]);
			}
		}
		this.extend(a);
		return this;
	};
	Object.prototype.gets = function(str) {
		if(typeof str !== "string" || str.length === 0) return null;
		var parts = str.trim().split('.'), 
			r = this;
		
		for(var i = 0; i < parts.length; i++) {
			if(eval('r.'+parts[i]) == null)
				return null;
			r = eval('r.'+parts[i]);
		}
			
		return r;
	};
	Object.prototype.last = function() {
		return this[this.length-1];
	};
	Object.prototype.hideProperties = function(a,x) {
		if (!a) { return null; }
		if (!x) { x = { enumerable: false }.extend(x); }
		if (!(a instanceof Array)) { a = [a]; }
		var props = {};
		for(var i = 0; i < a.length; i = i + 1) {
			if(this[a[i]])
				props[a[i]] = x;
		}
		Object.defineProperties(this, props);
		return this;
	};
	Object.prototype.each = function(func, forbid) {
		if(!forbid) forbid = [];
		var keys = Object.keys(this).diff(forbid);
		for(var i = 0; i < keys.length; i = i + 1) {
			func.call(this, this[keys[i]], keys[i], i, keys.length);
		}
		return this;
	};
	if(!Object.prototype.watch) {
	    Object.prototype.watch = function(prop, handler, interval) {
	    	if (prop instanceof Function) {
		    	this.each(function (v, k) {
			    	this.watch(k, prop, handler);
		    	});
		    	return this;
	    	}
	        var oldval = this[prop], newval = oldval,
	        getter = function () {
	            return newval;
	        },
	        setter = function (val) {
	            oldval = newval;
	            return newval = handler.call(this, prop, oldval, val);
	        };
	        if(this != window && delete this[prop]) {
	            if (Object.defineProperty) {
	                Object.defineProperty(this, prop, {
	                    get: getter,
	                    set: setter
	                });
	                return this;
	            } else if (Object.prototype.__defineGetter__ && Object.prototype.__defineSetter__) {
	                Object.prototype.__defineGetter__.call(this, prop, getter);
	                Object.prototype.__defineSetter__.call(this, prop, setter);
	                return this;
	            }
	        }
        	var self = this, oldValue = self[prop];
        	if(interval == null) interval = 200;
        	if(!self._watchers) self._watchers = [];
        	
	        self._watchers[prop] = setInterval(function() {
		        var value = self[prop];
    			if(value != '' && oldValue != value) {
    				handler.call(self, prop, oldValue, value);
    				oldValue = value;
    			}
    		}, interval);
    		
    		return this;
	    };
	}
	if(!Object.prototype.unwatch) {
    	Object.prototype.unwatch = function(prop) {
        	if(this._watchers && this._watchers[prop]) {
	        	clearInterval(this._watchers[prop]);
        	} else {
        		var val = this[prop];
        		delete this[prop];
        		this[prop] = val;
        	}
        }
    }
    Array.prototype.contains = function(a) {
	    return this.indexOf(a) !== -1;
  	};
	Array.prototype.diff = function(a) {
		return this.filter(function (el) { return !a.contains(el); });
    };
    Array.prototype.clean = function(dV) {
		for(var i = 0; i < this.length; i++) {
			if(this[i] == dV) {
				this.splice(i, 1);
				i--;
			}
		}
		return this;
	};
	Array.prototype.searchFunc = function(f) {
		for(var i = 0; i < this.length; i++) {
			if(this[i] == f || this[i]+"" == f+"") 
				return i;
		}
		return -1;
	};
	Storage.prototype.setObject = function(key, value) {
	    this.setItem(key, JSON.stringify(value));
	};
	Storage.prototype.getObject = function(key) {
	    return JSON.parse(this.getItem(key));
	};
	
	Object.prototype.hideProperties(['on','off','emit','extend','make','gets','last','hideProperties','each','watch','unwatch']);
	Array.prototype.hideProperties(['contains','diff','clean','searchFunc']);
	String.prototype.hideProperties(['trim','removeChars','replaceAll']);
	HTMLElement.prototype.hideProperties(['hasClass','addClass','removeClass','toggleClass','hasAttr','wrapAll']);
	Storage.prototype.hideProperties(['setObject','getObject']);
})(window);