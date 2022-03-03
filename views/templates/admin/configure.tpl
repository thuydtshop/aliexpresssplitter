{*
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
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<script type="text/javascript">
	const site_url = "{$site_url}";
</script>
<div class="panel">
	<h3>{l s='Aliexpress Splitter' mod='aliexpresssplitter'}</h3>
	
	<div class="row aliexpresssplitter-rows">
		<div class="col-12 col-md-2 aep-text-center">Image</div>
		<div class="col-12 col-md-10">Product name</div>
	</div>
	<div id="aliexpresssplitter-list-products">
		{foreach $products $product}
		<div class="row aliexpresssplitter-row">
			<div class="col-12 col-sm-2 aep-text-center">
				<img src="{$product['image']}" width="50" />
			</div>
			<div class="col-12 col-sm-8">
				<a href="{$product['link']}" target="_blank">{$product['name']}</a>
			</div>
			<div class="col-12 col-sm-2">
				<button type="button" class="btn btn-primary" onclick="aliexpresssplitter.get({$product['id_product']})">Split this</button>
			</div>
		</div>
		{/foreach}
	</div>

	<button id="aep-btn-more" type="button" class="btn btn-default aliexpresssplitter-right aliexpresssplitter-btn-run" onclick="aliexpresssplitter.more()">Load More Products</button>
</div>

<div id="aliexpresssplitter-modal-attributes" class="modal fade">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:#eee;">
				<h4 id="product-title"></h4>
			</div>
			<div class="modal-body" style="background-color:#eee; border-bottom:1px solid #ccc;">
				<h4>Rules for update product name</h4>
				<div class="row">
					<div class="col-12 col-sm-6">
						<div class="form-group">
							<label>Replace this word</label>
							<input type="text" name="wreplace" id="wreplace" class="form-control">
						</div>
					</div>
					<div class="col-12 col-sm-6">
						<div class="form-group">
							<label>Delete this word</label>
							<input type="text" name="wdelete" id="wdelete" class="form-control">
						</div>
					</div>
				</div>
			</div>
			<div class="modal-body">
				<h4>Split product base on:</h4>
				<div id="aliexpresssplitter-modal-attributes-body"></div>
				<div class="form-group" style="background-color:#eee;padding:8px;">
					<div class="checkbox">
						<label for="aliexpresssplitterckhall">
							<input type="checkbox" value="0" id="aliexpresssplitterckhall" onchange="aliexpresssplitter.changeChkAll(this)"> Select All
						</label>
					</div>
				</div>
			</div>

			<div class="modal-footer" style="background-color:#eee">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onclick="aliexpresssplitter.do(this)">Process Now</button>
			</div>
		</div>
	</div>
</div>
