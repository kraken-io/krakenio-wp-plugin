jQuery(document).ready(function ($) {


    var errors = [
        {
            code: 401,
            msg: 'Unnknown API Key. Please check your API key and try again'
        },
        {
            code: 403,
            msg: 'Your account has been temporarily suspended'
        },
        {
            code: 413,
            msg: 'File size too large. The maximum file size for your plan is 1048576 bytes'
        },
        {
            code: 415,
            msg: 'File type not supported'
        },
        {
            code: 415,
            msg: 'WebP compression is non available for SVG images'
        },
        {
            code: 422,
            msg: 'You need to specify either callback_url or wait flag'
        },
        {
            code: 422,
            msg: 'This image can not be optimized any further'
        },
        {
            code: 500,
            msg: 'Kraken has encountered an unexpected error and cannot fulfill your request'
        },
        {
            code: 502,
            msg: 'Couldn\'t get this file'
        }
    ];


	$('a.krakenError').tipsy({
   		fade: true, 
   		gravity: 'e' 
   	});
 
	var data = {
		action: 'kraken_request'
	}

	,

	errorTpl = '<div class="krakenErrorWrap"><a class="krakenError">Failed! Hover here</a></div>'
	
	,

	requestSuccess = function (data, textStatus, jqXHR) {

		var $button = $(this)
		,	$parent = $(this).parent();

		if (data.success && typeof data.error === 'undefined') {
			
			$button.text("Image optimized");

			var	type = data.type 
			,	originalSize = data.original_size
			,	krakedSize = data.kraked_size
			,	originalSize = data.original_size
			,	savingsPercent = data.savings_percent
			,	$originalSizeColumn = $(this).parent().prev("td.original_size");

			$parent.fadeOut("fast", function () {
				$(this).replaceWith('<strong>' + krakedSize + '</strong><br /><small>Type:&nbsp;' + type + '</small><br /><small>Savings: ' + savingsPercent + '</small>');
				$originalSizeColumn.html(originalSize);
				$parent.remove();
			});

		} else if (data.error) {
			console.log("ERROR", data.error);

			var $error = $(errorTpl).attr("title", data.error);

			$parent
				.closest("td")
				.find(".krakenErrorWrap")
				.remove();


			$parent.after($error);
			$error.tipsy({
		   		fade: true, 
		   		gravity: 'e' 
		   	});

			$button
				.text("Retry request")
				.removeAttr("disabled")
				.css({
					opacity: 1
				});
		}
	},

	requestFail = function (jqXHR, textStatus, errorThrown) {
		$(this).removeAttr("disabled");
	},

	requestComplete = function (jqXHR, textStatus, errorThrown) {
		$(this).removeAttr("disabled");
		$(this)
			.parent()
			.find(".krakenSpinner")
			.css("display", "none");
	};


	$(".kraken_req").click(function (e) {
		e.preventDefault();
		var $button = $(this)
		,	$parent = $(this).parent();

		data.id = $(this).data("id");

		$button
			.text("Optimizing image...")
			.attr("disabled", true)
			.css({
				opacity: "0.5"
			});

		
		$parent
			.find(".krakenSpinner")
			.css("display", "inline");


		var jqxhr = $.ajax({
			url: ajax_object.ajax_url,
			data: data,
			type: "post",
			dataType: "json",
			context: $button
		})

		.done(requestSuccess)

		.fail(requestFail)

		.always(requestComplete);
	
	});
});