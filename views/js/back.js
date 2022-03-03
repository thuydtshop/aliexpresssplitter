/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)t
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
var aliexpresssplitter = {
	page: 1,
	id_product: 0,
	id_group: 0,
	combinations: {},
	all: function() {
		let is_checked_all = $('#aep-chk-all').prop('checked');
		$('.aepchk').prop('checked', is_checked_all);
	},
	more: function() {
		aliexpresssplitter.page++;

		let id = 0;
		$('.aepchk').each(function() { id = $(this).val(); });

		$.ajax({
			type: 'get',
			url: site_url + '/modules/aliexpresssplitter/product.php?page=' + aliexpresssplitter.page + '&id=' + id,
			beforeSend: function() {
				$('#aep-btn-more').text('Loading...');
				$('#aep-btn-more').prop('disabled', true);
			},
			success: function(response) {
				$('#aliexpresssplitter-list-products').append(response);

				$('#aep-btn-more').text('Load More Products');
				$('#aep-btn-more').prop('disabled', false);
			},
			error: function() {
				$('#aep-btn-more').text('Load More Products');
				$('#aep-btn-more').prop('disabled', false);
			}
		});
	},
	get: function(id) {
		aliexpresssplitter.id_product = id;

		$('#aliexpresssplitter-modal-attributes-body').html('<p>Loading...</p>');
		$('#aliexpresssplitter-modal-attributes').modal('show');

		$.ajax({
			type: 'get',
			url: site_url + '/modules/aliexpresssplitter/attribute.php?id=' + id,
			dataType: 'json',
			beforeSend: function() {
				$('#product-title').text('');
			},
			success: function(response) {
				$('#product-title').text(response.title);

				$('#aliexpresssplitter-modal-attributes-body').html('');
				$('#aliexpresssplitter-modal-attributes-body').html(response.attributes_list);

				if (typeof response.combinations !== 'undefined' && response.combinations != '') {
					aliexpresssplitter.combinations = response.combinations;
				}
			},
			error: function() {
				$('#aliexpresssplitter-modal-attributes-body').html('<p>Cannot get attributes.</p>');
			}
		});
	},
	do: function(e) {
		let valided = 0;
		$('input[name="aliexpresssplitterattributes[]"]:checked').each(function() {
			valided++;
		});
		if (valided == 0) {
			alert('Please select at least one color for split this product.');
			return;
		}

		let attrs = [];
		$('input[name="aliexpresssplitterattributes[]"]').each(function() {
			if ($(this).prop('checked') == true) {
				attrs.push($(this).val());
			}
		});

		$.ajax({
			type: 'post',
			url: site_url + '/modules/aliexpresssplitter/ajax.php',
			data: {
				id_product: aliexpresssplitter.id_product,
				attributes: attrs,
				r: $('#wreplace').val(),
				d: $('#wdelete').val()
			},
			dataType: 'json',
			beforeSend: function() {
				$(e).text('Processing...');
				$(e).prop('disabled', true);
			},
			success: function(response) {
				$(e).text('process now');
				$(e).prop('disabled', false);

				$('#aliexpresssplitter-modal-attributes').modal('hide');
			},
			error: function() {
				$(e).text('Process now');
				$(e).prop('disabled', false);

				$('#aliexpresssplitter-modal-attributes').modal('hide');
			}
		});
	},
	changeChkAll: function(e) {
		let v = $(e).prop('checked');
		$('#aliexpresssplitter-modal-attributes-body input[name="aliexpresssplitterattributes[]"]').prop('checked', v);
	}
};
