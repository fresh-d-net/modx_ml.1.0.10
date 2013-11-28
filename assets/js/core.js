/**
 * User: fedo (studio fresh)
 * Date: 19.09.13
 * Time: 18:07
 * Event constructor
 */

$.fn.fancybox_binder = function(options){
	var self = this;

	var __constructor = function(options){
		//marked gallery
		var a_box_index = [];
		self.each(function(){
			var	el = $(this),
					p = el.parents('p'),
					index = p.index();
			el.attr('rel', 'gallery_' + index);
		})
		.fancybox(options);
		console.log(options);
	}

	return __constructor(options);
}

$(function(){
	//Конструктор события click
	$(document).on('click', '[data-click]', function(e){
		//define vars
		var	el = $(this),
				action = el.attr('data-click');

		//call action with event attribute
		var o_action = eval(action);
		if (typeof o_action !== 'undefined'){
			o_action.call(el, e);
		}else{
			e.preventDefault();
			console.error('Action ' + action + ' undefined');
		}
	});

	//Конструктор симулятивного события click
	$(document).on('click', '[data-click-simulate]', function(e){
		//define vars
		var	action = $(this).attr('data-click-simulate'),
				el = $(action);

		//call action with event attribute
		if (el.length >0){
			el.click();
		}else{
			e.preventDefault();
			console.error('Action ' + el + ' undefined');
		}

	});

	//Конструктор события change
	$(document).on('change', '[data-change]', function(e){
		//define vars
		var	el = $(this),
				action = el.attr('data-change');

		//call action with event attribute
		var o_action = eval(action);
		if (typeof o_action !== 'undefined'){
			o_action.call(el, e);
		}else{
			e.preventDefault();
			console.error('Action ' + action + ' undefined');
		}

	});

	//Конструктор отрправки формы. Отправка формы входа
	$(document).on('submit', '[data-submit]', function(e){
		//define vars
		var	el = $(this),
				action = el.attr('data-submit'),
				o_action = eval(action);
		if (typeof o_action !== 'undefined'){
			o_action.call(el, e);
		}else{
			e.preventDefault();
			console.error('Action ' + action + ' undefined');
		}
	});

	//Конструктор переключателей типа toggle
	$(document).on('click', '[data-toggle]', function(e){
		//define vars
		var	el = $(this),
				wrapper = el.attr('data-toggle');

		$('[data-toggle-wrapper='+wrapper+']').slideToggle(200);

		e.preventDefault();
	});

	//tabs plugin
	$('[data-plugin]').each(function(){

		var	el = $(this),
				s_json = el.attr('data-settings');
	
		if (s_json != undefined && s_json != '') {
			try {
				var o_data = $.parseJSON(s_json)
			} catch(err) {
				console.warn('isn\'t JSON');
				console.log(o_data);
				o_data = {};
			}
		}

		//run plugin
		el[el.attr('data-plugin')](o_data);
	});

	var mask = 'js-plugin-';
	$('[class*='+mask+']').each(function(){
		var	el = $(this),
				plugin = el.attr('class').replace(mask, ''),
				o_data = SETTINGS[plugin] || {};
		el[plugin](o_data);
	});

	var mask = 'js-plugin_group-';
	var	el = $('[class*='+mask+']');
	if(el.length > 0){
		var	reg = new RegExp(".+("+mask+")"),
				plugin = el.attr('class').replace(reg, ''),
				o_data = SETTINGS[plugin] || {};
		el[plugin](o_data);
	}



	//
	$(document).on('click', 'a[href=#], a[href=""]', function(e){
		e.preventDefault();
	});

	//

});