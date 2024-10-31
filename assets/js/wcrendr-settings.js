(function($) {

	const button = $('#wcrendr-test-creds');

	var wcrendrDateShown = false;

	const validateCreds = function(e) {
		e.preventDefault();

		const credential_data = {
			brand_id: $('#woocommerce_wcrendr_brand_id').val(),
			client_id: $('#woocommerce_wcrendr_client_id').val(),
			client_secret: $('#woocommerce_wcrendr_client_secret').val()
		}

		let hasError = false;
		button.siblings('.notice').remove();

		Object.entries(credential_data).forEach(([type, typeValue]) => {
			$('#woocommerce_wcrendr_'+type).siblings('.has-error').remove();
			if(!typeValue.length) {
				$('#woocommerce_wcrendr_'+type).after('<div style="color: red" class="has-error">This field is required.</div>');
				hasError = true;
			}
		});

		if(hasError) {
			return;
		}
		button.after('<span class="spinner" style="visibility: visible; float: none;"></span>');
		button.prop('disabled', true);
		
		var creds = {
			brand_id: $('#woocommerce_wcrendr_brand_id').val(),
			//store_id: $('#woocommerce_wcrendr_store_id').val(),
			client_id: $('#woocommerce_wcrendr_client_id').val(),
			client_secret: $('#woocommerce_wcrendr_client_secret').val(),
		};
		
		if($('#woocommerce_wcrendr_store_id').val() != '') {
			creds.store_id == $('#woocommerce_wcrendr_store_id').val();
		}

		$.ajax({
			url: wcrendr_settings.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'test_rendr_creds',
				creds: creds,
				nonce: wcrendr_settings.verify_creds
			},
			success: function(r) {
				if(r.data.message) {
					button.after(`<div class="notice notice-${r.success ? 'success' : 'error'}"><p>${r.data.message}</p></div>`);
				}
			},
			error(xhr, status, error) {
				button.after(`<div class="notice notice-error"><p>${status}</p></div>`);
			},
			complete: function() {
				button.siblings('.spinner').remove();
				button.prop('disabled', false);
			}
		})
	}

	$(document.body).on('click', '#wcrendr-test-creds', validateCreds).on('change', 'input[type="checkbox"][name^="woocommerce_wcrendr_opening_hours"]', function() {
		if($(this).is(':checked')) {
			$(this).parent().parent().find('input[name$="_from"]').prop('disabled', false);
			$(this).parent().parent().find('input[name$="_from"]').attr('placeholder', '09:00');
			$(this).parent().parent().find('input[name$="_to"]').prop('disabled', false);
			$(this).parent().parent().find('input[name$="_to"]').attr('placeholder', '17:00');
		} else {
			$(this).parent().parent().find('input[type="text"]').each(function() {
				$(this).attr('placeholder', 'Closed --');
				$(this).val('');
				$(this).prop('disabled', true);
			})
		}
	}).on('click', '.wcrendr-box-remove span', function(e) {
		e.preventDefault();
		$(this).parent().parent().remove();
		$(document.body).trigger('recount_boxes')
	}).on('click', '.rendr-presets table tfoot button', function(e) {
		var container = $('.rendr-presets table tr.clone').clone();
		container.find('input').each(function() {
			$(this).attr('name', $(this).attr('name').replace('_clone', ''));
		});
		container.removeClass('clone');
		$('.rendr-presets table tbody').append(container);
		$(document.body).trigger('recount_boxes');
	}).on('recount_boxes', function() {
		var i = 0;
		$('.rendr-presets table tbody tr:not(.clone)').each(function() {
			$(this).find('input').each(function() {
				var nameAttr = $(this).attr('name');
				$(this).attr('name', nameAttr.replace(/\[[0-9]*\]/, '['+i+']'));
			})
			i++;
		});
	}).on('change', '#woocommerce_wcrendr_packing_preference', function() {
		if($('#woocommerce_wcrendr_packing_preference').val() == 'preset') {
			$('.rendr-presets').show();
		}else {
			$('.rendr-presets').hide();
		}
	}).on('click', '#woocommerce_wcrendr_blocked_dates_button', function() {
		if(wcrendrDateShown) {
			$('#woocommerce_wcrendr_blocked_dates').datepicker('hide');
			wcrendrDateShown = false
		} else {
			$('#woocommerce_wcrendr_blocked_dates').datepicker('show');
			wcrendrDateShown = true
		}
	});

	$(function() {

		const inputmask = new Inputmask({ regex: "[0-2][0-9]:[0-5][0-9]" })

		$('input[name^="woocommerce_wcrendr_opening_hours"][name$="_from"], input[name^="woocommerce_wcrendr_opening_hours"][name$="_to"]').each(function() {
			inputmask.mask(this);
		})

		$('input[type="checkbox"][name^="woocommerce_wcrendr_opening_hours"]:not(:checked)').each(function() {
			$(this).parent().parent().find('input[type="text"]').each(function() {
				$(this).attr('placeholder', 'Closed --');
				$(this).val('');
				$(this).prop('disabled', true);
			})
		})

		$(document.body).trigger('recount_boxes');

		if($('#woocommerce_wcrendr_packing_preference').val() == 'preset') {
			$('.rendr-presets').show();
		}else {
			$('.rendr-presets').hide();
		}

		if($('#woocommerce_wcrendr_blocked_dates').length > 0) {
			$('#woocommerce_wcrendr_blocked_dates').hide();
			$('#woocommerce_wcrendr_blocked_dates').after('<button type="button" id="woocommerce_wcrendr_blocked_dates_button" class="button">Select dates</button>');
			var wcrendrDates = $('#woocommerce_wcrendr_blocked_dates').val().split(',').map(i => parseInt(i));
			console.log(wcrendrDates);
			$('#woocommerce_wcrendr_blocked_dates').datepicker({
				dateFormat: "@",
				onSelect: function(dateText, inst) {
					if(dateText.length <= 0) {
						return;
					}
					if(wcrendrDates.indexOf((parseInt(dateText)/1000)) >= 0) {
						wcrendrDates.splice(wcrendrDates.indexOf((parseInt(dateText)/1000)), 1);
					} else {
						wcrendrDates.push((parseInt(dateText)/1000));
					}
					$('#woocommerce_wcrendr_blocked_dates').val(wcrendrDates.join(','))
				},
				beforeShowDay: function(date) {
					if(wcrendrDates.indexOf((new Date(date).valueOf())/1000) >= 0) {
						return [true, "ui-state-highlight"]
					} else {
						return [true, ""]
					}
				},
				onClose: function() {
					wcrendrDateShown = false;
				}
			})
		}
	});
	
	console.log(jQuery('select[multiple][name^="woocommerce_wcrendr"]'));
    jQuery('select[multiple][name^="woocommerce_wcrendr"]').each(function() {
    	jQuery(this).selectWoo();
	})
	
	var rendrBrandSettingsVisibility = function() {
		jQuery('#woocommerce_wcrendr_disable_brand').each(function() {
			if(jQuery(this).is(':checked')) {
				jQuery('#woocommerce_wcrendr_banner_theme, #woocommerce_wcrendr_disable_banner, #woocommerce_wcrendr_cart_brand_theme, #woocommerce_wcrendr_disable_cart_brand').each(function() {
					jQuery(this).parents('tr').hide();
				});
			} else {
				jQuery('#woocommerce_wcrendr_disable_banner, #woocommerce_wcrendr_disable_cart_brand').show();
				if(jQuery('#woocommerce_wcrendr_disable_banner').is(':checked')) {
					jQuery('#woocommerce_wcrendr_banner_theme').parents('tr').hide();
				} else {
					jQuery('#woocommerce_wcrendr_banner_theme').parents('tr').show();
				}
				if(jQuery('#woocommerce_wcrendr_disable_cart_brand').is(':checked')) {
					jQuery('#woocommerce_wcrendr_cart_brand_theme').parents('tr').hide();
				} else {
					jQuery('#woocommerce_wcrendr_cart_brand_theme').parents('tr').show();
				}
			}
		})
	}
	
	jQuery(document).on('change', '#woocommerce_wcrendr_disable_banner, #woocommerce_wcrendr_disable_brand, #woocommerce_wcrendr_disable_cart_brand', rendrBrandSettingsVisibility);
	
})(jQuery);