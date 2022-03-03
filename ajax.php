<?php 
include_once('../../config/config.inc.php');
include_once('../../init.php');

include_once(dirname(__FILE__).'/image.php');
$imgClass = new AliexpressSpliterImage();

$id_product = Tools::getValue('id_product', 0);
$attributes_publishing = Tools::getValue('attributes', []);

sort($attributes_publishing);

$wreplace = Tools::getValue('r', '');
$wdelete = Tools::getValue('d', '');

$json = [ 'error' => 1, 'message' => '' ];
if ($id_product < 1 || count($attributes_publishing) < 1) {
	$json['message'] = 'You have to select at least one color for split this product.';
	die(Tools::jsonEncode($json));
}

$attribute_groups = [];
$attribute_names = [];

$product = new Product((int)$id_product);
if (Validate::isLoadedObject($product)) {
	$attributesOlds = $product->getAttributesGroups(Context::getContext()->language->id);

	if ($attributesOlds) {
		foreach ($attributesOlds as $attribute_old) {
			if ( 
				$attribute_old['group_type'] == 'color' &&
				in_array($attribute_old['id_attribute'], $attributes_publishing) && 
				!in_array($attribute_old['attribute_name'], $attribute_groups) 
			) {
				$attribute_groups[] = $attribute_old['attribute_name'];
			}

			$attribute_names[ $attribute_old['id_attribute'] ] = $attribute_old['attribute_name'];
		}
	}

	if (count($attribute_groups) < 1) {
		$json = [ 'error' => 1, 'message' => 'Product have not attributes. ' ];
		die(Tools::jsonEncode($json));
	}

	$id_product_old = $product->id;
    if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
        $shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());
        foreach ($shops as $shop) {
            if ($product->isAssociatedToShop($shop['id_shop'])) {
                $product_price = new Product($id_product_old, false, null, $shop['id_shop']);
                $product->price = $product_price->price;
            }
        }
    }
    unset( $product->id, $product->id_product );
    $product->indexed = 0;
    $product->active = 0;

    include_once dirname(__FILE__).'/class.Splitter.php';
    $SpitterTools = new SpitterTools();

    $oldProductNames = $product->name;

    $index = 0;
	foreach ($attributes_publishing as $row_attribute_old) {
		// update product name
		foreach ($product->name as $langKey => $product_name) {
			
			// replace and delete strings
			$new_product_name = $oldProductNames[$langKey];
			if (!empty(trim($wdelete))) {
				$new_product_name = str_replace(
					Tools::strtolower($wdelete), 
					'', 
					Tools::strtolower($new_product_name)
				);
			}
			if (!empty(trim($wreplace))) {
				$new_product_name = str_replace( 
					Tools::strtolower($wreplace),
					Tools::strtolower($attribute_names[$row_attribute_old]), 
					Tools::strtolower($new_product_name)
				);
			}

	        $new_product_name = Tools::substr($new_product_name, 0, 120);
	        
            $product->name[$langKey] = pSQL($new_product_name);

			$product->link_rewrite[ $langKey ] = Tools::link_rewrite($product->name[ $langKey ]);

			if (!empty($product->meta_description[ $langKey ])) {
				$product->meta_description[ $langKey ] = $SpitterTools->concatString(
					$product->meta_description[ $langKey ], 
					$attribute_groups[$index], 
					496
				);
			}

			if (!empty($product->meta_keywords[ $langKey ])) {
				$product->meta_keywords[ $langKey ] = $SpitterTools->concatString(
					$product->meta_keywords[ $langKey ], 
					$oldAttribute->name, 
					248
				);
			}

			if (!empty($product->meta_title[ $langKey ])) {
				$product->meta_title[ $langKey ] =  strip_tags($SpitterTools->concatString(
					$product->meta_title[ $langKey ], 
					$attribute_groups[$index], 
					120
				));
			}
		}

		if ($product->add()
	        && Category::duplicateProductCategories($id_product_old, $product->id)
	        && Product::duplicateSuppliers($id_product_old, $product->id)
	        && ($combination_images = Product::duplicateAttributes($id_product_old, $product->id)) !== false
	        && GroupReduction::duplicateReduction($id_product_old, $product->id)
	        && Product::duplicateAccessories($id_product_old, $product->id)
	        && Product::duplicateFeatures($id_product_old, $product->id)
	        && Product::duplicateSpecificPrices($id_product_old, $product->id)
	        && Pack::duplicate($id_product_old, $product->id)
	        && Product::duplicateCustomizationFields($id_product_old, $product->id)
	        && Product::duplicateTags($id_product_old, $product->id)
	        && Product::duplicateDownload($id_product_old, $product->id)
    	) {
            if ($product->hasAttributes()) {
                Product::updateDefaultAttribute($product->id);
            }

            $id_product_new = $product->id;
            
            if (
            	!Tools::getValue('noimage') && 
            	!$imgClass->duplicateProductImages($id_product_old, $id_product_new, $combination_images)
            ) {
                $json['message'] = 'An error occurred while copying the image. ';
            } else {
                Hook::exec('actionProductAdd', [
                	'id_product_old' => $id_product_old, 
                	'id_product' => (int) $product->id, 
                	'product' => $product
                ]);
                if (
                	in_array($product->visibility, ['both', 'search']) && 
                	Configuration::get('PS_SEARCH_INDEXATION')
                ) {
                    Search::indexation(false, $product->id);
                }
            }

            // update attributes for new
            $newAttributes = $product->getAttributesGroups(Context::getContext()->language->id);
            $id_product_attribute_news = [];
			if ($newAttributes) {
				foreach ($newAttributes as $oldIdx => $newAttribute) {
					if ( 
						in_array($newAttribute['id_attribute'], $attributes_publishing) && 
						!in_array($newAttribute['id_product_attribute'], $id_product_attribute_news)
					) {
						$id_product_attribute_news[] = $newAttribute['id_product_attribute'];
						///$id_product_attribute_news[$newAttribute['id_product_attribute']] = $newAttribute;
	                }

	                if (isset($attributesOlds[$oldIdx]) && isset($attributesOlds[$oldIdx]['quantity'])) {
		                $old_qty = $attributesOlds[$oldIdx]['quantity'];
	        			Db::getInstance()->update('stock_available', [
	        				'quantity' => $old_qty
	        			], 'id_product = '.(int)$id_product_new.' AND id_product_attribute = '.(int)$newAttribute['id_product_attribute']);
	        		}
	            }
	        }

	        // run to remove combination with quantity < 1
			if ($newAttributes) {
	        	foreach ($newAttributes as $newAttribute) {
	        		if (
	        			!in_array($newAttribute['id_product_attribute'], $id_product_attribute_news)
	        		) {
	        			$product->deleteAttributeCombination((int) $newAttribute['id_product_attribute']);
	                    $product->checkDefaultAttributes();
	                    Tools::clearColorListCache((int) $id_product_new);
	                    if (!$product->hasAttributes()) {
	                        $product->cache_default_attribute = 0;
	                        $product->update();
	                    } else {
	                        Product::updateDefaultAttribute($newAttribute['id_product_attribute']);
	                    }
	        		}
	        	}
	        }

	        $newAttributes = $product->getAttributesGroups(Context::getContext()->language->id);
	        if ($newAttributes) {
	        	$new_groups = [];
	        	foreach ($newAttributes as $new_atrribute) {
	        		if (
	        			$new_atrribute['attribute_name'] == $attribute_groups[$index] &&
	        			!in_array($new_atrribute['id_product_attribute'], $new_groups)
	        		) {
	        			$new_groups[] = $new_atrribute['id_product_attribute'];
	        		}
	        	}
	        	foreach ($newAttributes as $new_atrribute) {
	        		if (!in_array($new_atrribute['id_product_attribute'], $new_groups) || $new_atrribute['quantity'] < 1) {
	        			$product->deleteAttributeCombination((int) $new_atrribute['id_product_attribute']);
	                    $product->checkDefaultAttributes();
	                    Tools::clearColorListCache((int) $id_product_new);
	                    if (!$product->hasAttributes()) {
	                        $product->cache_default_attribute = 0;
	                        $product->update();
	                    } else {
	                        Product::updateDefaultAttribute($new_atrribute['id_product_attribute']);
	                    }
	        		}
	        	}
	        }

	        $newAttributes = $product->getAttributesGroups(Context::getContext()->language->id);
	        if ($newAttributes) {
	        	foreach ($newAttributes as $new_atrribute) {
	        		if ($new_atrribute['group_type'] == 'color') {
	        			Db::getInstance()->delete('product_attribute_combination', 'id_attribute = '.(int)$new_atrribute['id_attribute'].' AND id_product_attribute = '.(int)$new_atrribute['id_product_attribute']);
	        		}
	        	}
	        }

	        // update product images again
	        $newproduct = new Product($id_product_new);
	        $combinationImages = $newproduct->getCombinationImages(Context::getContext()->language->id);
	        if ($combinationImages) {
	        	$combinationImagesIds = [];
	        	foreach ($combinationImages as $comImgs) {
	        		if ($comImgs) {
	        			foreach ($comImgs as $comImg) {
	        				if (!in_array($comImg['id_image'], $combinationImagesIds)) {
	        					$combinationImagesIds[] = $comImg['id_image'];
	        				}
	        			}
	        		}
	        	}
	        	
	        	$productImages = $newproduct->getImages(Context::getContext()->language->id);
	        	if ($combinationImagesIds && $productImages) {
	        		$count = 0;
	        		foreach ($productImages as $proImg) {
	        			$newProductImg = new Image($proImg['id_image']);
	        			if ($count == 0 && in_array($proImg['id_image'], $combinationImagesIds)) {
	        				$newProductImg->cover = 1;
	        				$newProductImg->id_product = $id_product_new;
	        				$newProductImg->update();
	        				$count++;
	        			} else {
	        				$newProductImg->delete();
	        			}
	        		}
	        	}
	        }

            // update link product aliexpress
            $row_aliexpress = Db::getInstance()->getRow('select * from '._DB_PREFIX_.'g_aliexpress where id_product = '.(int)$id_product_old);
            Db::getInstance()->insert('g_aliexpress', [
            	'id_product' => $id_product_new,
            	'aliexpress_id_product' => pSQL($row_aliexpress['aliexpress_id_product']),
            	'aliexpress_link' => pSQL($row_aliexpress['aliexpress_link']),
            	'shipping' => pSQL($row_aliexpress['shipping']),
            	'id_g_alisupplier' => $row_aliexpress['id_g_alisupplier'],
            	'status' => $row_aliexpress['status'],
            	'auto_update' => $row_aliexpress['auto_update'],
            	'video_url' => pSQL($row_aliexpress['video_url']),
            	'show_video' => $row_aliexpress['show_video'],
            	'json_data' => is_null($row_aliexpress['json_data']) ? '' : pSQL($row_aliexpress['json_data']),
            	'shipping_data' => is_null($row_aliexpress['shipping_data']) ? '' : pSQL($row_aliexpress['shipping_data']),
            	'waiting_update' => $row_aliexpress['waiting_update']
            ]);
            $refreshAttributes = $product->getAttributesGroups(Context::getContext()->language->id);
            if ($refreshAttributes) {
            	$id_product_attributes = [];
            	foreach ($refreshAttributes as $refreshAttribute) {
            		if (!in_array($refreshAttribute['id_product_attribute'], $id_product_attributes)) 
            			$id_product_attributes[] = $refreshAttribute['id_product_attribute'];
            	}
            	foreach ($id_product_attributes as $id_product_attribute) {
            		$refresh_row_sku = Db::getInstance()->getRow('select * from '._DB_PREFIX_.'g_aliexpress_sku where id_product = '.(int)$id_product_old.' and id_product_attribute = '.(int)$id_product_attribute);
            		Db::getInstance()->insert('g_aliexpress_sku', [
            			'id_product' => $id_product_new,
            			'id_product_attribute' => $id_product_attribute,
            			'skuattr' => pSQL(isset($refresh_row_sku['skuattr']) ? $refresh_row_sku['skuattr'] : ''),
            			'price' => isset($refresh_row_sku['price']) ? $refresh_row_sku['price'] : 0
            		]);
            	}
            }
        } else {
            $json['message'] = 'An error occurred while creating an object. ';
        }

        $index++;
	}

	// remove old product
	///$old_product = new Product($id_product_old);
	///$old_product->delete();
}

$json['error'] = 0;
if (empty($json['message'])) {
	$json['message'] = 'Exported successfully.';
}
die(Tools::jsonEncode($json));
