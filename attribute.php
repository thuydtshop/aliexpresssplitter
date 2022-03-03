<?php ini_set('display_errors', 1);ini_set('display_startup_errors', 1);error_reporting(E_ALL);
include_once('../../config/config.inc.php');
include_once('../../init.php');

$id = Tools::getValue('id', 1);
$product = new Product($id);
$id_lang = Context::getContext()->language->id;
$attributes = $product->getAttributesGroups($id_lang);

$tempAttributes = [];

$groups = [];
if ($attributes) {
	foreach ($attributes as $attribute) {
		if (isset($attribute['group_type']) && $attribute['group_type'] == 'color' && !isset($groups[ $attribute['id_attribute']]) ) {
			$groups[ $attribute['id_attribute'] ] = $attribute['attribute_name'];
		}
	}
}

$html = '';
if ($groups) {
	asort($groups);

	$idx = 0;
	$html .= '<div class="row">';
	foreach ($groups as $key => $value) {
		$html .= '<div class="col-md-6">
					<div class="checkbox">
                        <label>
                          	<input type="checkbox" name="aliexpresssplitterattributes[]" id="aliexpresssplitter-chk-'.$key.'" value="'.$key.'" '. ($idx == 0 ? 'checked' : '') .'>'.$value.'
                        </label>
                    </div>
                </div>';
		$idx++;
	}
	$html .= '</div>';
}

$json = [
	'title' => $product->name[$id_lang],
	'attributes_list' => $html
];

echo json_encode($json);
exit;