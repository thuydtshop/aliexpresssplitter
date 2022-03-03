<?php
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
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Aliexpresssplitter extends Module
{
    const LIMITED = 100;

    private $productIds = [];

    public function __construct()
    {
        $this->name = 'aliexpresssplitter';
        $this->tab = 'administration';
        $this->version = '2.6.0';
        $this->author = 'Protovo';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Aliexpress Splitter');
        $this->description = $this->l('split imported products from Aliexpress');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitAliexpresssplitterModule')) == true) {
            $this->postProcess();
        }

        $products = $this->getProducts(self::LIMITED, 1);

        $this->context->smarty->assign([
            'products' => $products,
            'site_url' => Tools::getHttpHost(true).__PS_BASE_URI__
        ]);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    protected function getProducts($conditions, $type)
    {
        $id_lang = Configuration::get('PS_LANG_DEFAULT');
        $sql = 'select p.id_product, p.ean13, p.id_category_default, pl.name, pl.link_rewrite
            from '._DB_PREFIX_.'product p 
            inner join '._DB_PREFIX_.'g_aliexpress ga on ga.id_product = p.id_product 
            left join '._DB_PREFIX_.'product_lang pl on (p.id_product = pl.id_product ' . 
                Shop::addSqlRestrictionOnLang('pl') . ')
            where pl.id_lang = '.(int)$id_lang.'
                and p.id_product ';
        if ($type == 2) {
            $sql .= 'in ('.implode(',', $conditions).')';
        }
        $sql .= ' order by p.id_product desc';
        ///$sql .= ' limit '.self::LIMITED;

        $newProducts = [];

        $products = Db::getInstance()->executeS($sql);
        if ($products) {
            foreach ($products as $idx => &$product) {
                $this->productIds[] = $product['id_product'];

                $obj = new Product($product['id_product']);
                $attributeGroups = $obj->getAttributesGroups($id_lang);
                if ($attributeGroups) {
                    $groups = [];
                    foreach ($attributeGroups as $attributeGroup) {
                        if (!in_array($attributeGroup['id_attribute_group'], $groups)) {
                            $groups[] = $attributeGroup['id_attribute_group'];
                        }
                    }
                }

                $product['category'] = Category::getLinkRewrite(
                    (int) $product['id_category_default'], 
                    (int)$id_lang
                );

                $product['link'] = $this->context->link->getProductLink(
                    (int) $product['id_product'], 
                    $product['link_rewrite'], 
                    $product['category'], 
                    $product['ean13']
                );

                ///$obj_product = new Product($product['id_product']);
                $cover = Product::getCover($product['id_product']);
                $id_image = isset($cover['id_image']) ? $cover['id_image'] : 0;
                $product['image'] = $this->context->link->getImageLink(
                    $product['link_rewrite'], 
                    $id_image, 
                    ImageType::getFormattedName('small')
                );

                if (count($groups) > 1) {
                    $newProducts[] = $product;
                }
            }
        }

        return $newProducts;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }
}
