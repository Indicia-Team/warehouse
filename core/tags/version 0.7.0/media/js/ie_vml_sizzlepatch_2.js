(function($) {

	var isJQuery13 = /1\.3/.test($.fn.jquery);

	if (isJQuery13 && $.browser.msie)

		(function($) {

			// IE VML elements throw an error error instead of returning "undefined"
			// if non-existing attributes are accessed.
			// So we need to avoid direct attribute access by using "attributes" property.

			// IE document.getElementsByTagName(*) has the annoying habit to return comment elements
			// that lack the existence of "attributes" attribute.
			// So we need to assure that it really exists.

			var undefined,
				tmp,
				attr = function(elem, att_name) {
					return elem.attributes ? ((tmp = elem.attributes[att_name]) ? tmp.value : undefined) : elem[att_name];
				},
				filters = $.find.selectors.filters,
				old_filters = $.extend({}, filters);

			$.extend(filters, {

				enabled: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? (false === attr(elem, "disabled") && "hidden" !== attr(elem, "type")) : old_filters.enabled(elem);
				},
				disabled: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? (true === attr(elem, "disabled")) : old_filters.disabled(elem);
				},
				checked: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? (true === attr(elem, "checked")) : old_filters.checked(elem);
				},
				selected: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? (function(elem) {
						// Accessing this property makes selected-by-default
						// options in Safari work properly
						elem.parentNode.selectedIndex;
						return true === attr(elem, "selected");
					})(elem) : old_filters.selected(elem);
				},
				text: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("text" === attr(elem, "type")) : old_filters.text(elem);
				},
				radio: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("radio" === attr(elem, "type")) : old_filters.radio(elem);
				},
				checkbox: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("checkbox" === attr(elem, "type")) : old_filters.checkbox(elem);
				},
				file: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("file" === attr(elem, "type")) : old_filters.file(elem);
				},
				password: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("password" === attr(elem, "type")) : old_filters.password(elem);
				},
				submit: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("submit" === attr(elem, "type")) : old_filters.submit(elem);
				},
				image: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("image" === attr(elem, "type")) : old_filters.image(elem);
				},
				reset: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("reset" === attr(elem, "type")) : old_filters.reset(elem);
				},
				button: function(elem){
					return elem.tagUrn === "urn:schemas-microsoft-com:vml" ? ("button" === attr(elem, "type") || elem.nodeName.toUpperCase() === "BUTTON") : old_filters.button(elem);
				}

			});

		})($);

})(jQuery);
