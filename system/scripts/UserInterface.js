"use strict";

(function(window) {
	var UserInterface = function() {
		this.observers = {};
	};
	
	UserInterface.prototype = {
		
		setOptions: function(o) {
			this.options = this.options.extend(o);
			this.insertToDOM();
			if(this.prepare)
				this.prepare();
			if(this.setPosition)
				this.setPosition();
			this.removeFromDOM();
		},
		inDOM: function() {
			return this.object.item(0).parentNode ? true : false;
		},
		insertToDOM: function(p) {
			if(! this.inDOM()) {
				if(!p) p = $();
				p.append(this.object);
			}
		},
		removeFromDOM: function() {
			if(this.inDOM()) {
				this.object.remove();
			}
		},
		destroy: function() {
			this.object.emit('destroy');
			delete this;
		}
	}
	
	window.UserInterface = UserInterface;
})(window);

(function(window) {
	
	var Stack = [null, null, null, null];
	
	Stack.extend({
		collection: [0, 0, 0, 0],
		save: function(o) {
			var i = this.collection.indexOf(0);
			if(i === -1) {
				var m = Math.max.apply(Math, this.collection), i = this.collection.indexOf(m);
				this[i].close();
			}
			this.increment();
			this[i] = o;
			this.collection[i] = 1;
			return i;
		},
		delete: function(i) {
			this[i] = null;
			this.collection[i] = 0;
		},
		reload: function() {
			var i = this.collection.indexOf(Math.max.apply(Math, this.collection));
			if(i !== -1) {
				this.collection[i] = 0;
				this[i].close();
			}
		},
		increment: function() {
			for(var i = 0; i < this.collection.length; i++) {
				if(this.collection[i] !== 0)
					this.collection[i]++;
			}
		},
		last: function() {
			var i = this.collection.indexOf(Math.max.apply(Math, this.collection));
			return (i !== -1 ? this[i] : false);
		},
		lastAdded: function() {
			var i = this.collection.indexOf(Math.min.apply(Math, this.collection));
			return (i !== -1 ? this[i] : false);
		},
		len: function() {
			var c = 0;
			for(var i = 0; i < this.length; i++) {
				if(this[i] !== null)
					c++;
			}
			return c;
		}
	});
		
	var Notification = function(header, content, opt) {
		if(!(this instanceof Notification)) {
			return new Notification(header,content, opt);;
		}
		var self = this;

		this.options = {}.extend(Notification.prototype.defaults, opt);
		if(this.options.isAlert)
			this.options.autoexpire = false;
			
		this.object = $.create('.notification');
		this.prepare(header, content);
		this.insertToDOM();
		this.setPosition();
		this.show();
		
		return this;
	};
	
	Notification.prototype = {}.extend(UserInterface.prototype, {
		timeout: undefined,
		expired: false,
		defaults: {
			duration: 800,
			timer: 7000,
			margin: 40,
            position: 'top-right',
			autoexpire: true,
			isAlert: false
		},
		
		prepare: function(h, c) {
			this.object.css({ opacity:0 });
			this.object.append($.create('header').append($.create('h1').html(h))).append($.create('section').append($.create('p').html(c)));
			
			if(this.options.isAlert) {
				var foo = $.create('footer');
				foo.html('<button type="submit">Details</button><button type="cancel">Cancel</button>');
				this.object.append(foo);
			} else {
				this.object.mousedown($.invoke(this.close, this))
					.mouseover($.invoke(this.object.fadeTo, this.object, [0.6]))
					.mouseout($.invoke(this.object.fadeIn, this.object));
			}
		},
		
		setPosition: function() {
			var posX = 0, posY = 0;
			if(Stack.len() === Stack.length)
				Stack.last().close();
		
			this.index = Stack.save(this);
            
            if (this.options.position === 'top-right') {
                posX = this.options.margin;
                posY = (this.object.clientHeight() + this.options.margin) * this.index + this.options.margin + 10;
                
                if(posY + this.object.clientHeight() + this.options.margin*3 > $().clientHeight()) {
                    posX = posX + this.object.clientWidth() + this.options.margin;
                    posY = this.options.margin + 10;
                }
            } else if (this.options.position === 'bottom-right') {
                posX = this.options.margin;
                posY = $().clientHeight() - (this.object.clientHeight() + this.options.margin) * (this.index+1);
                
            } else if (this.options.position === 'top-left') {
                posX = $().clientWidth() - this.object.clientWidth() - this.options.margin;
                posY = (this.object.clientHeight() + this.options.margin) * this.index + this.options.margin + 10;
                
            } else if (this.options.position === 'bottom-left') {
                posX = $().clientWidth() - this.object.clientWidth() - this.options.margin;
                posY = $().clientHeight() - (this.object.clientHeight() + this.options.margin) * (this.index+1);
            }
			
			this.object.css({ position:'fixed', top: posY, right: posX, opacity:0, skewY:15, translateY: -this.object.clientHeight() });
		},
		
		show: function() {
			var self = this;

			self.object.animate({ opacity:1, skewY:0, translateY:0 }, self.options.duration, function() {
				this.emit('disappear');
			});
			
			if(self.options.autoexpire)
				self.timer();
		},
		close: function() {
			var self = this;
			
			if(this.timeout)
				clearTimeout(this.timeout);
				
			this.expired = true;
			this.object.off('mouseup mouseover mouseout');			
			Stack.delete(self.index);
			
			this.object.animate({ opacity:0, skewX:-40, translateX: (self.object.clientWidth() + this.options.margin*2) }, this.options.duration, function() {
				this.emit('disappear');
				self.removeFromDOM();
				self.destroy();
			});
		},
		timer: function() {
			var self = this;
			
			this.timeout = setTimeout(function() {
				if(! self.expired)
					self.close();
			}, this.options.timer);
		}
	});
	window.Notification = Notification;
	
	var PopOver = function(obj, handler, opt) {
		if(!(this instanceof PopOver)) {
			return new PopOver(obj, handler, opt);
		}
		var self = this, data = handler.data('popover'), values = handler.data('values');
		
		this.options = {}.extend(PopOver.prototype.defaults, opt);
		this.handler = handler;
		
		if(data) {
			if(data.indexOf('no-arrow') !== -1) {
				this.options.arrow = false;
			}
			if(data.indexOf('no-round-corner') !== -1) {
				this.options.roundCorner = false;
			}
			if(data.indexOf('inherit-width') !== -1) {
				this.options.inheritWidth = true;
			}
			if(data.indexOf('overlay') !== -1) {
				this.options.overlay = true;
			}
			if(data.indexOf('refresh') !== -1) {
				this.options.refreshPosition = true;
			}
            if (data.indexOf('change-on-select') !== -1) {
                this.options.changeValueOnSelect = true;
            }
		}
		
		if(obj && obj.length > 0) {
			if(obj.item(0).tagName.toLowerCase() == 'ul') {
				this.object = $.create('div.popover.selectable');
				this.selectList = obj;
				obj.wrap(this.object);
				this.options.selectList = true;
			} else if(this.options.selectList) {
				this.object = obj;
				this.selectList = $.create('ul');
				this.object.prepend(this.selectList).addClass('selectable');
			} else if(obj.children('ul').length > 0) {
				this.object = obj.addClass('selectable');
				this.selectList = obj.children('ul');
				this.options.selectList = true;
			} else {
				this.object = obj;
				this.body = this.object.children('section');
			}
		} else {
			this.object = $.create('.popover');
			if(!values) {
				this.body = $.create('section');
				this.object.append(this.body);
			} else {
				this.selectList = $.create('ul');
				this.object.prepend(this.selectList).addClass('selectable');
				this.options.selectList = true;
			}
		}
		
		if(values) {
			var arr = values.split(','), li = $.create('li');
			for(var i = 0; i < arr.length; i++) {
				this.selectList.append(li.clone().html(arr[i]));
			}
		}

		this.insertToDOM();
		this.prepare();
		this.setPosition();
		this.removeFromDOM();
		
		return this;
	};
	
	$.prototype.PopOver = function(opt) {
		$.each(this, function() {
			var $this = $(this),
				id = this.getAttribute('id'),
				obj = id ? $('div#popover-'+id) : null;
            
			this.popover = new PopOver(obj, $this, opt);
		});
		return this;
	};
	
	PopOver.prototype = {}.extend(UserInterface.prototype, {
		defaults: {
			align: false,
			effect: 'scale',
			position: 'auto',
			parentPositioning: true,
			duration: 500,
			refreshPosition: false,
			arrow: true,
			roundCorner: true,
			inheritWidth: false,
			overlay: false,
			selectList: false,
			changeValueOnSelect: false,
			defaultsActions: true,
			movementX: 0,
			movementY: 0,
			margin: 0,
			translateY: 0,
			scale: 0.1
		},
		prepare: function() {
			var self = this;
			if(this.options.arrow) {
				if(this.object.children('.popover-arrow').length == 0) {
					this.arrow = $.create('.popover-arrow');
					this.object.append(this.arrow);
				} else {
					this.arrow = this.object.children('.popover-arrow');
				}
			}
			if(! this.options.roundCorner)
				this.object.css({ borderRadius: 0, paddingTop: 0, paddingBottom: 0 });
			if(this.options.inheritWidth) 
				this.object.css({ minWidth: this.handler.clientWidth() - (this.object.clientWidth() - this.object.width()) });
				
			if(this.options.defaultsActions) {
				this.handler.mousedown(function(e) {
                    e.stopPropagation();
					$.document.emit('mousedown');
					if(self.options.refreshPosition)
						self.setPosition();
					
					self.show();
                    
					self.object.mousedown(function(e) {
						e.stopPropagation();
					});
				
					$.document.on('mouseup', function() {
						$.document.on('mousedown', function() {
							self.close();
							$.document.off('mousedown mouseup');
						});
					});
				});
				
				self.object.find('button[type=cancel]').mouseup(function() {
					self.object.emit('cancel');
					self.close();
				});
				self.object.find('button[type=submit]').mouseup(function() {
					self.object.emit('submit');
					self.close();
				});
			
			}
			if(this.options.selectList) {
				var c = this.selectList.children('li');

				c.mouseup(function(e) {
					e.stopPropagation();
					var n = this.getAttribute('name');
					self.object.emit('selected', { index: c.indexOf(this), value: (n ? n : this.innerHTML) });
					self.close();
				}).mouseover(function(e) {
                    e.stopPropagation();
                    c.removeClass('active');
					this.addClass('active');
				}).mouseout(function() {
					this.removeClass('active');
				}).click(function(e) {
                    var a = $(this).children('a');
                    if (a.length === 1) {
                        a.item(0).click();
                    }
                });
			} else if (this.object.hasClass('tabbed')) {
                var aside = this.object.children('aside'),
                    sections = this.object.children('section'),
                    li = aside.find('li');
                
                
                sections.hide();
                
                li.mouseup(function() {
                    var $this = $(this),
                        id = $this.attr('id'),
                        max = 0;
                    
                    sections.css({opacity:0}).show().each(function(e,i) {
                        var height = $(this).clientHeight();
                        if (max < height) {
                            max = height;
                        }
                    }).hide().css({opacity:1});
                    aside.css({ height: max });
                    
                    li.removeClass('active');
                    $this.addClass('active');
                    sections.filter('#section-'+id).show();
                });
                
                li.get(0).mouseup();
            }
		},
		
		setPosition: function() {
			var handler = this.handler, 
				self = this, 
				posX = 0, posY = 0, arrowX = 0, arrowY = 0, arrowLength = 0, arrowRadius = 0,
				origin = this.handler.origin();
				
			if(this.options.arrow) {
				arrowLength = parseInt(this.arrow.height()/2)+1;
				arrowRadius = parseInt(Math.ceil((Math.sqrt(2) * this.arrow.clientHeight())/2))+1;
			} else {
				arrowLength = 1;
				arrowRadius = 1;
			}			
			
			if(self.options.position === 'auto') {
				var bh = $().scrollHeight(), bw = $().scrollWidth();
				if(origin.x < bh/2 || this.object.clientHeight() > origin.y) {
					this.options.position = 'below';
				} else {
					this.options.position = 'above';
				}		
				
			}
			
			if(self.options.position === 'below') {
				posX = origin.x + handler.clientWidth()/2 - this.object.clientWidth()/2;
				posY = origin.y + handler.clientHeight() + arrowRadius + self.options.margin;
				arrowX = this.object.clientWidth()/2 - arrowLength;
				arrowY = -arrowLength-1;
				this.radio = -1;
				if(self.options.parentPositioning) 
					posY += handler.parent().clientHeight() - handler.clientHeight() - handler.offset().top;					
			} else if(self.options.position === 'above') {
				posX = origin.x + handler.clientWidth()/2 - this.object.clientWidth()/2;
				posY = origin.y - this.object.clientHeight() - arrowRadius - self.options.margin;
				arrowX = this.object.clientWidth()/2 - arrowLength;
				arrowY = this.object.clientHeight() - arrowLength;
				this.radio = 1;
				if(self.options.parentPositioning)
					posY -= handler.offset().top;
			} else if(self.options.position === 'right') {
				posX = origin.x + handler.clientWidth() + arrowRadius + self.options.margin;
				posY = origin.y + handler.clientHeight()/2 - this.object.clientHeight()/2;
				arrowX = -arrowLength-1;
				arrowY = this.object.clientHeight()/2 - arrowLength;
				
			} else if(self.options.position === 'left') {
				posX = origin.x - this.object.clientWidth() - arrowRadius - self.options.margin;
				posY = origin.y + handler.clientHeight()/2 - this.object.clientHeight()/2;
				arrowX = this.object.clientWidth() - arrowLength;
				arrowY = this.object.clientHeight()/2 - arrowLength;
			}
			
			if(this.options.align === 'left') {
				posX = origin.x;
			} else if(this.options.align === 'right') {
				posX = origin.x + handler.clientWidth() - this.object.clientWidth();
			}
			
            if (posX < 0) {
                arrowX = arrowX + posX;
                posX = handler.offset().left;
            }
            var diff = posX + this.object.clientWidth() - window.outerWidth;
            if (diff > 0) {
                arrowX = arrowX + diff + handler.offset().right;
                posX = window.outerWidth - this.object.clientWidth() - handler.offset().right;
            }
            
			if(this.options.arrow) 
				this.arrow.css({ left: arrowX, top: arrowY });
			if(this.options.overlay) 
				posY = posY - handler.clientHeight();
            
			this.options.translateY = this.options.translateY * this.radio;
			this.object.removeClass('below above right left').addClass(self.options.position);
			if(this.object.css('position') !== 'absolute')
				this.object.css({ position:'absolute' });
			this.object.css({ left: posX+self.options.movementX, top: posY+self.options.movementY, opacity:0, translateY: self.options.translateY, scale: self.options.scale }).hide();
			
			if(['below','above'].indexOf(this.options.position) !== -1) {
				this.object.css({ origin: (arrowX+arrowLength+2)+'px '+arrowY+'px' });
			} else {
				this.object.css({ origin: arrowX+'px '+(arrowY+arrowRadius+2)+'px' });
			}
		},
		
		show: function() {
			var self = this;
			this.insertToDOM();		
			if(! this.object.hasClass('active')) {
				this.object.show().animate({ opacity:1, translateY:0, scale:1 }, this.options.duration, function() {
					self.object.find('input, textarea').focus();
                    self.object.addClass('active');
					self.object.emit('appear');
				});
			}
			return this;
		},
		
		close: function() {
			var self = this;
			if(this.object.hasClass('active')) {
				this.object.animate({ opacity:0, translateY:-self.options.translateY, scale: self.options.scale }, this.options.duration, function() {
					self.object.removeClass('active');
					self.object.emit('disappear');
					self.object.css({ opacity:0, translateY: self.options.translateY, scale: self.options.scale, display:'none' });
					setTimeout(function() { self.removeFromDOM(); }, 100);
				});
			}
			return this;
		},		
	});	
	window.PopOver = PopOver;
	
	
	var Hint = function(handler, opt) {
		if(!(this instanceof Hint)) {
			return new Hint(handler, opt);
		}
		var self = this, s, e;
		
		this.options = {}.extend(Hint.prototype.defaults, opt);
		
		var dir = handler.data('hintDirection');
		if (dir)
			this.options.direction = dir;
        
        var trigger = handler.data('hintTrigger');
        if (trigger)
            this.options.trigger = trigger;
		
		this.object = $.create('div.hint');
		this.handler = handler;
		this.object.html(handler.attr(this.options.catch));
				
		switch(this.options.trigger) {
			case 'click': {
				s = 'mousedown';
				e = 'mouseup';
				break;
			}
			case 'hover': {
				s = 'mouseover';
				e = 'mouseout';
				break;
			}
			case 'focus': {
				s = 'focus';
				e = 'blur';
				break;
			}
			default: {
				s = 'mouseover';
				e = 'mouseout';
			}
		}
		this.handler.on(s, $.invoke(this.show, this)).on(e, $.invoke(this.close, this));
		
		return this;
	};
	
	$.prototype.Hint = function(opt) {
		$.each(this, function() {
			this.hint = new Hint($(this), opt);
		});
		return this;
	};
	
	Hint.prototype = {}.extend(UserInterface.prototype, {
		defaults: {
			catch:'data-hint',
			direction:'right',
			trigger:'hover',
			delay: 200,
            delayOnClose: 1000,
			margin: 15,
			duration: 400
		},
		setPosition: function() {
			var posX = 0, posY = 0, 
                offset = this.handler.offset(),
                origin = this.handler.origin();
            
            origin.x = origin.x - offset.parent.parentNode.scrollLeft;
            origin.y = origin.y - offset.parent.parentNode.scrollTop;
            
            if(this.options.direction == 'left') {
				posX = parseInt(origin.x - this.object.clientWidth() - this.options.margin);
				posY = parseInt(origin.y + (this.handler.clientHeight() - this.object.clientHeight())/2);
			} else if(this.options.direction == 'bottom') {
				posX = parseInt(origin.x + (this.handler.clientWidth() - this.object.clientWidth())/2);
				posY = parseInt(origin.y + this.handler.clientHeight() + this.options.margin);
			} else if(this.options.direction == 'top') {
				posX = parseInt(origin.x + (this.handler.clientWidth() - this.object.clientWidth())/2);
				posY = parseInt(origin.y - this.object.clientHeight() - this.options.margin);
			} else if(this.options.direction == 'overlay') {
				posX = parseInt(origin.x + (this.handler.clientWidth() - this.object.clientWidth())/2);
				posY = parseInt(origin.y + (this.handler.clientHeight() - this.object.clientHeight())/2);
			} else {
				posX = parseInt(origin.x + this.handler.clientWidth() + this.options.margin);
				posY = parseInt(origin.y + (this.handler.clientHeight() - this.object.clientHeight())/2);	
			}
			
			var m = 5;
			
			if(posX < m) posX = m;
			if(posY < m) posY = m;
			if(posX + this.object.clientWidth() > document.width - m) posX = document.width - m;
			if(posY + this.object.clientHeight() > document.height - m) posY = document.height - m;
			
			this.object.css({ position:'absolute', top: posY+'px', left: posX+'px' });
		},
		
		show: function() {
			this.object.css('opacity', 0);
			this.insertToDOM();
			this.setPosition();
            
			clearTimeout(this.timeout);
			this.timeout = setTimeout($.invoke(this.object.fadeIn, this.object, [this.options.duration]), this.options.delay);
		},
		
		close: function() {
			var self = this;
            clearTimeout(self.timeout);
            self.timeout = setTimeout(function() {
                self.object.fadeOut(self.options.duration, $.invoke(self.removeFromDOM, self));
            }, self.options.delayOnClose);
		}
	});
	window.Hint = Hint;
	
	
	var Alert = function(header, content, opt) {
		if(!(this instanceof Alert)) {
			return new Alert(header, content, opt);
		}
		var self = this;
		
		this.options = {}.extend(Alert.prototype.defaults, opt);
		
		this.object = $.create('div.alert');
		this.fog = $.create('div.alert-fog').css('opacity', 0);
		this.border = $.create('div.alert-border').css('opacity', 0);
		this.body = $.create('div.alert-body');
		
		this.prepare(header, content);
		
		return this;
	};
	
	$.prototype.Alert = function() {
		$.each(this, function() {
			this.alert = new Alert('Alert header', 'Alert content<br/><br/><br/>');
			this.on('mouseup', $.invoke(this.alert.show, this.alert));
		});
		return this;
	};
	
	Alert.prototype = {}.extend(UserInterface.prototype, {
		defaults: {
			fogOpacity: 0.85,
			duration: 500,
			hideOnClick: true,
		},
		prepare: function(header, content, footer) {
			var self = this;
			if(!footer) footer = '<button type="submit">Submit</button><button type="cancel">Cancel</button>';
			this.body.append($.create('header').html(header)).append($.create('section').html(content)).append($.create('footer').html(footer));
			this.object.append(this.border.append(this.body)).append(this.fog);
			
			this.body.mousedown(function(e) {
				e.stopPropagation();
			});
			
			self.body.find('button[type=cancel]').mouseup(function() {
				self.object.emit('cancel');
				self.close();
			});
			self.body.find('button[type=submit]').mouseup(function() {
				self.object.emit('accept');
				self.close();
			});
		},
		
		show: function() {
			var self = this;

			this.insertToDOM();
			this.border.css({ scale:0.3 });
			this.fog.animate({ opacity: this.options.fogOpacity }, this.options.duration);
			
			setTimeout(function() {
				self.border.animate({ opacity:1, scale:1 }, self.options.duration/2, function() {
					self.border.removeAttr('style');
					self.object.emit('appear');
				});
			}, this.options.duration/2);
			
			if(this.options.hideOnClick) {
				this.fog.mousedown(function() {
					self.close();
					self.fog.off('mousedown');
				});
			}
		},
		
		close: function() {
			var self = this;
			
			this.fog.animate({ opacity:0 }, self.options.duration);
			self.border.animate({ opacity:0, translateY:-200 }, self.options.duration, function() {
				self.object.emit('disappear');
				self.removeFromDOM();
			});
		}
	});
	window.Alert = Alert;
	
	
	var Switch = function(object, opt) {
		if(!(this instanceof Switch)) {
			return new Switch(object, opt);
		}
		var self = this, data = object.data('switch');
		
		this.options = {}.extend(Switch.prototype.defaults, opt);
		
		if(data) {
			if(data.indexOf('localValue') !== -1) {
				this.options.localValue = true;
			}
		}
		
		if(!object)
			this.object = $.create('div.switch');
		else {
            this.slider = $.create('div.slider');
            this.container = $.create('div.switch-container');
            
            if (object.is('input')) {
                this.object = $.create('div.switch');
                this.input = object;
                this.object.attr({ id: 'switch-' + this.input.attr('name') });
                this.input.wrap(this.container.prepend(this.slider));
                this.container.wrap(this.object);
            } else {
                this.object = object;
                this.input = $.create('input.switch-input').attr('type', 'checkbox');
                this.input.attr({ name: this.object.attr('id').lbreak('switch-') });
                this.container.append(this.slider).append(this.input);
                this.object.append(this.container);
            }
            var hiddenInput = this.input.clone();
            hiddenInput.attr({ type: "hidden", class: "", value: "0" });
            this.input.attr({ value: "1" });
            this.input.before(hiddenInput);
        }
        this.prepare();
		
		return this;
	};
	
	$.prototype.Switch = function(opt) {
        $.each(this, function() {
			this.switch = new Switch($(this), opt);
		});
		return this;
	};
	
	Switch.prototype = {}.extend(UserInterface.prototype, {
		status: false,
		defaults: {
            duration: 400,
			localValue: false
		},
		
		prepare: function() {
			var self = this;
            
			if(this.options.localValue) {
				var l = localStorage.getObject('switch-'+this.object.attr('id'));
				if(l === 'on')
					this.setOn();
				else if(l === 'off')
					this.setOff();
			}
			
            if (this.object.is('.checked') || this.input.is(':checked')) {
                this.setOn();
            } else {
                this.setOff();
            }
            
			self.object.mouseup(function() {
				if(self.status)
					self.setOff();
				else
					self.setOn();
			});
			
			self.input.mouseup(function(e) {
				e.preventDefault();
				return false;
			});
		},
		
		setOn: function() {
            this.slider.animate({ marginLeft: 0 }, this.options.duration);
            
			this.status = true;
			this.object.addClass('checked');
			this.input.attr('checked', 'checked');
			this.object.emit('switchOn');
			if(this.options.localValue)
				localStorage.setObject('switch-'+this.object.attr('id'), 'on');
		},
		
		setOff: function() {
            var width = this.slider.width();
            this.slider.animate({ marginLeft: -(width-10) }, this.options.duration);
            
            this.status = false;
			this.object.removeClass('checked');
			this.input.removeAttr('checked');
			this.object.emit('switchOff');
			if(this.options.localValue)
				localStorage.setObject('switch-'+this.object.attr('id'), 'off');
		}
	});
	window.Switch = Switch;

	
	var Typeahead = function(input, list, opt) {
		if(!(this instanceof Typeahead)) {
			return new Typeahead(handler, list, opt);
		}

		var self = this, data = input.data('typeahead');
		
		this.options = {}.extend(Typeahead.prototype.defaults, opt);
		this.input = input;
		this.list = list;
		
		if(data) {
			var m = parseInt(data);
			if(m > 0) {
				this.options.visibleElements = m;
			}
			if(data.indexOf('inheritWidth') !== -1) {
				this.options.inheritWidth = true;
			}
			if(data.indexOf('onfocus') !== -1) {
				this.options.showOnFocus = true;
			}
			if(data.indexOf('asc') !== -1) {
				this.options.sort = 'asc';
			} else if(data.indexOf('desc') !== -1) {
				this.options.sort = 'desc';
			}
		}

		this.prepare();

		return this;
	};
	
	$.prototype.Typeahead = function(opt) {
		$.each(this, function() {
			var name = this.getAttribute('name'),
				obj = name ? $('ul#typeahead-'+name) : null;
				
			this.typeahead = new Typeahead($(this), obj, opt);
		});
		return this;
	};
	
	Typeahead.prototype = {}.extend(UserInterface.prototype, {
		defaults: {
			align: 'left',
			inheritWidth: false,
			showOnFocus: false,
			showAllValues: false,
			enterToComplete: false,
			visibleElements: 0,
			sort: false
		},
		prepare: function() {
			var self = this;
			this.input.popover = new PopOver(this.list, this.input, { position:'below', arrow: false, inheritWidth: this.options.inheritWidth, align: this.options.align, defaultsActions: false, translateY: 0 });
			
			if(! this.list)
				this.list = this.input.popover.selectList;
			
			if(this.options.sort == 'asc') {
				this.list.children('li').sort();
			} else if(this.options.sort == 'desc') {
				this.list.children('li').sort().reverse();
			}
			
			if(self.options.visibleElements > 0 && li.length > self.options.visibleElements) {
				self.input.popover.object.show();
				self.list.css({ maxHeight: li.clientHeight()*self.options.visibleElements });
				self.input.popover.object.hide();
			}
			
			this.input.mousedown(function() {
				self.show();
			});
			
			self.input.keyup(function(e) {
				var k = e.keyCode;
				if(k == 27)
					self.close();
				else if(k != 38 && k != 40)
					self.show();
				//e.preventDefault();
			}).keydown(function(e) {
				var k = e.keyCode;
				if(k == 13 || k == 38 || k == 40) {
					var act = self.input.popover.selectList.children('li.active!.hidden');
					
					if(act.length == 0) {
						if(k == 38)
							act = self.input.popover.selectList.children('li!.hidden').eq(-1).addClass('active');
						if(k == 40)
							act = self.input.popover.selectList.children('li!.hidden').eq(0).addClass('active');
					} else {
						if(k == 13) {
							act.emit('mouseup');
							return 0;
						} else if(k == 38) {
							var n = act.prev('!.hidden').addClass('active');
							self.list.scrollTop(n.offset().top - self.list.clientHeight()/2);
						} else if(k == 40) {
							var n = act.next('!.hidden').addClass('active');
							self.list.scrollTop(n.offset().top - self.list.clientHeight()/2);
						}
						act.removeClass('active');
					}
					e.preventDefault();
				}
			}).blur(function() {
				self.close();
			});
			
			self.input.popover.selectList.children('li').mouseup(function() {
				self.input.value(this.innerHTML).focus().change();
			});
		},
		
		refresh: function() {
			var self = this, li = this.list.children('li').removeClass('active');
			
			if(!this.options.showAllValues) {
				li.filter(function() { return (this.innerHTML.indexOf(self.input.value()) === 0 ? true : false); }).show().removeClass('hidden');
				li.filter(function() { return (this.innerHTML.indexOf(self.input.value()) !== 0 ? true : false); }).hide().addClass('hidden');
			}
			
			var nh = li.not('.hidden');
			
			if(nh.length > 0 && nh.filter('.active').length === 0) {
				this.input.popover.object.show();
				nh.first().addClass('active');
			} else {
				this.input.popover.object.hide();
				nh.removeClass('active');
			}
		},
		
		show: function() {
			var self = this;
			this.input.popover.show();
			this.refresh();
		},
		
		close: function() {
			var self = this;
			
			self.input.popover.close();
		}
	});
	window.Typeahead = Typeahead;
	
	
	var Scroller = function(obj, space, opt) {
		if(!(this instanceof Scroller)) {
			return new Scroller(obj, space, opt);
		}
		
		if(!obj) {
			obj = $.create('nav.scroller');
		}
		
		this.options = {}.extend(Scroller.prototype.defaults, opt);
		this.object = obj;
		this.scrollSpace = space || $();
		this.prepare();
		
		return this;
	};
	
	$.prototype.Scroller = function(opt) {
		$.each(this, function() {
			this.scroller = new Scroller($(this), opt);
		});
		return this;
	};
	
	Scroller.prototype = {}.extend(UserInterface.prototype, {
		defaults: {
			
		},
		
		prepare: function() {
			var self = this;
			
			this.object.find('a').click(function(e) {
				var href = this.href.substring(this.href.indexOf('#')),
					el = self.scrollSpace.find(href);
				
				if(el.length > 0) {
					self.jumpTo(el, href);
				}
				e.preventDefault();	
			});
		},
		
		jumpTo: function(el, href) {
			var s = { top: window.scrollTop, left: window.scrollLeft };
			window.location.hash = href;
			window.scrollTo(s.left, s.top);
			this.scrollSpace.scrollTo(0, el.origin().y - this.scrollSpace.offset().top);
		},
		scrollToTop: function() {
			
		}
	});
	window.Scroller = Scroller;
	
	
	$.prototype.dragNdrop = function() {
		var self = this;
		
		self.mousedown(function(e) {
			var $this = $(this), pos = { x: e.clientX, y: e.clientY };
			
			$this.css({ position:'absolute' });
			if($this.parent().css('position') == 'static') {
				$this.parent().css({ position: 'relative' });
			}
			pos.x = pos.x - $this.origin().x - $this.parent().origin().x;
			pos.y = pos.y - $this.origin().y - $this.parent().origin().y;				
			
			$.document.on('mousemove', function(e) {
				var x = e.clientX - pos.x, y = e.clientY - pos.y;
				
				$this.css({ left: x, top: y });
				
				if(x < 0) 
					$this.css({ left: 0 });
				else if(x+$this.clientWidth() >= $().clientWidth()) 
					$this.css({ left: $().clientWidth()-$this.clientWidth() });
				
				if(y < 0) 
					$this.css({ top: 0 });
				else if(y+$this.clientHeight() >= $().clientHeight())
					$this.css({ top: $().clientHeight()-$this.clientHeight()-1 });
				
			}).on('mouseup', function() {
				$.document.off('mousemove mouseup');
			});
		});
		return this;
	};
	
	$.loadLocalValues = function() {
		if($.localValues) {
			$('*[name][data-local-value]').each(function() {
				this.value = localStorage.getObject(this.tagName+'-'+this.name);
			}).on('change', function() {
				localStorage.setObject(this.tagName+'-'+this.name, this.value);
			});
		}
	};
})(window);
    
$(function() {
    $('.switch-input').Switch();
    $('.popover-handler').PopOver();
    $('[data-hint]').Hint();
    
    $('div#content div.row').mouseup(function(e) {
        e.stopPropagation();
        $(this).find('section p a').emit('click');
    });
});