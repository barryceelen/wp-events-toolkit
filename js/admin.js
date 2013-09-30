(function ($) {
	"use strict";
	$(function () {
			var eventsToolkit = {
				init: function() {
					this.setupDatepickers();
					this.setUpEventHandlers();
				},
				setupDatepickers: function() {

					var $startDateInput = $( "#event-start-string" );
					var $endDateInput   = $( "#event-end-string" );
					var regional        = ( typeof eventsToolkitVars !== null ) ? this.tryToDetermineRegional( eventsToolkitVars.regional ) : '';
					var dateFormat = ( typeof eventsToolkitVars !== null ) ? eventsToolkitVars.dateFormat : '';

					$.datepicker.setDefaults( $.datepicker.regional[regional] );
					$.datepicker.setDefaults( $.datepicker.formatDate[dateFormat] );
					$startDateInput.datepicker({
						altField: "#event-start",
						altFormat: $.datepicker.ISO_8601,
						dateFormat: dateFormat,
						autoSize: false,
						constrainInput: true,
						numberOfMonths: 3,
						onClose: function( selectedDate ) {
							$endDateInput.datepicker( "option", "minDate", selectedDate );
						}
					});

					$endDateInput.datepicker({
						altField: "#event-end",
						altFormat: $.datepicker.ISO_8601,
						dateFormat: dateFormat,
						autoSize: false,
						constrainInput: true,
						minDate: new Date($startDateInput.data("date")),
						numberOfMonths: 3,
					});

					$startDateInput.datepicker( "setDate", new Date($startDateInput.data("date")) );
					$endDateInput.datepicker( "setDate", new Date($endDateInput.data("date")) );
				},
				setUpEventHandlers: function() {
					var _this = this;
					var $body = $("body");

					$body.on("change.etoolkit","#event-all-day",function(e){
						$(".all-day-hidden").toggleClass('all-day-visible');
					});

					$body.on("change.etoolkit","#event-start-hh, #event-end-hh",function(e){
						_this.doTimeErrorHandling(this,24);
					});

					$body.on("change.etoolkit","#event-start-mm, #event-end-mm",function(e){
						_this.doTimeErrorHandling(this,60);
					});
				},
				doTimeErrorHandling: function(input,max){
					var $publish = $("#publish");
					var $input   = $(input);
					if ($input.val()=="")
						$input.val("00");
					else if ( ! $input.val().match(/^[0-9]{1,2}?$/) || $input.val() >= max ) {
						$input.addClass("error");
						$publish.attr("disabled","disabled");
					} else {
						$input.removeClass("error");
						$publish.removeAttr("disabled");
					}
				},
				tryToDetermineRegional: function( locale ) {
				// Try to find the locale in jQuery.datepicker.regional, lots of assumptions going on here...
				// Empty or english, return empty string
				if ( !jQuery.datepicker.regional || !locale || ( "" || "en" || "en_US" ) == locale )
					return '';
				var regional = jQuery.datepicker.regional;
				// If a regional exists, return it
				if ( regional.hasOwnProperty( locale ) )
					return locale;
				// One more try, take the first part of the locale for handling things like en-GB
				locale = locale.split( "-" );
				if ( regional.hasOwnProperty( locale[0] ) )
					return locale[0];
				// locale does not occur in regional, assume english
				return "";
			}
		}
		eventsToolkit.init();
	});
}(jQuery));
