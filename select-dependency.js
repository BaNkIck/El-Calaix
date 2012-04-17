/**
 * SelectDependency
 * @author Aaron Navarro Heras
 * @version 1.0
 * @requires MooTools 1.1
 */
var SelectDependency = new Class({

	source: null,
	target: null,

	selector_regexp: /[#.\[\]*]/,

	options: {
		onChange:            Class.empty,
		method:              "get",
		url:                 null,
		requestValueKey:     "value", // Name of the value parameter
		requestParams:       {}, // Extra parameters for the URL
		responseLabelKey:    "label",
		responseValueKey:    "value",
		responseSelectedKey: "selected",
		loading_text:        "Loading..."
	},

	initialize: function(target, source, options){

		this.target = this._getElement(target);
		this.source = this._getElement(source);
		this.setOptions(options);

		this.attachEvents();

	},

	attachEvents: function(){
		this.source.addEvent("change", this.load.bindWithEvent(this));
	},

	load: function(){
		var _this = this;
		var result = [];

		// if (this.options.method == "get"){
			this.options.requestParams[this.options.requestValueKey] = this.source.getValue();
			var query = this.toQueryString(this.options.requestParams);
		// }
		
		var url        = this.options.url +"?"+ query;
		var requesting = this._bind(this.requesting);
		var loaded     = this._bind(this.loaded);

		var request = new Json.Remote(url, {
			method: this.options.method,
			onRequest: requesting,
			onComplete: loaded
		}).send();

	},

	requesting: function(){
		this.target.empty();
		this.addOption(0, this.options.loading_text);
		this.target.fireEvent("change");
		this.fireEvent("onChange");
	},

	loaded: function(items){
		this.target.empty();
		var _this = this;

		items.each(function(item){

			_this.addOption(
				item[_this.options.responseValueKey],
				item[_this.options.responseLabelKey],
				item[_this.options.responseSelectedKey]
			);

		});

		this.target.fireEvent("change");
		this.fireEvent("onChange");

	},

	addOption: function(value, label, selected){

		if (!label) label = value;

		var option = new Element("option", {
				value: value
		})
		.setText(label)
		.inject(this.target)
		;

		if (selected){
			option.selected = true;
		}

		return option;

	},

	toQueryString: function(object){
		var queryString = [];
		$each(object, function(value, name){
			if (value === false || !name) return;
			var qs = function(val){
				queryString.push(name + '=' + encodeURIComponent(val));
			};
			if ($type(value) == 'array') value.each(qs);
			else qs(value);
		});
		return queryString.join('&');
	},

	_getElement: function(input){
		if (typeof input == "string" && this.selector_regexp.test(input)){
			input = $$(input)[0];
		} else {
			input = $(input);
		}
		return input;
	},

	_bind: function(method){
		var _this = this;
		return(function(){
			return( method.apply(_this, arguments) );
		});
	}

});

SelectDependency.implement(new Events, new Options);
