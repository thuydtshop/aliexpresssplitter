<?php 

include_once('../../config/config.inc.php');
include_once('../../init.php');

$page = Tools::getValue('page', 1);
$id = Tools::getValue('id', 1);
if ($page < 1) $page = 1;

$limited = 100;
$offset = ($page - 1) * 100;

$id_lang = Configuration::get('PS_LANG_DEFAULT');
$sql = 'select p.id_product, p.ean13, p.id_category_default, pl.name, pl.link_rewrite
    from '._DB_PREFIX_.'product p 
    inner join '._DB_PREFIX_.'g_aliexpress ga on ga.id_product = p.id_product 
    left join '._DB_PREFIX_.'product_lang pl on (p.id_product = pl.id_product ' . Shop::addSqlRestrictionOnLang('pl') . ')
    where pl.id_lang = '.(int)$id_lang.' and p.id_product ';
$sql .= ' < '.(int)$id;
$sql .= ' order by p.id_product desc';
$sql .= ' limit '.$offset.','. $limited;

$products = Db::getInstance()->executeS($sql);
if ($products) {
    foreach ($products as &$product) {
        $product['category'] = Category::getLinkRewrite((int) $product['id_category_default'], (int)$id_lang);
        $product['link'] = Context::getContext()->link->getProductLink((int) $product['id_product'], $product['link_rewrite'], $product['category'], $product['ean13']);
    }
}

$html = '';
if ($products) {
	foreach ($products as $product) {
		$html .= '<div class="row aliexpresssplitter-row">
			<div class="col-12 col-sm-1 aep-text-center">
				<input type="checkbox" class="aepchk" value="'.$product['id_product'].'" id="aep-chk-'.$product['id_product'].'">
			</div>
			<div class="col-12 col-sm-9">
				<a href="'.$product['link'].'" target="_blank">'.$product['name'].'</a>
			</div>
			<div class="col-12 col-sm-2">
				<button type="button" class="btn btn-primary" onclick="aliexpresssplitter.get('.$product['id_product'].'')">Split this</button>
			</div>
		</div>';
	}
}

echo $html;
return;