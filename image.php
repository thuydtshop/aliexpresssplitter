<?php 
class AliexpressSpliterImage 
{
	public function duplicateProductImages($idProductOld, $idProductNew, $combinationImages)
    {
        $imagesTypes = ImageType::getImagesTypes('products');
        $result = Db::getInstance()->executeS('
			SELECT `id_image`
			FROM `' . _DB_PREFIX_ . 'image`
			WHERE `id_product` = ' . (int) $idProductOld
		);

        foreach ($result as $row) {
            $imageOld = new Image($row['id_image']);///echo '<pre>';print_r($imageOld);exit;
            $imageNew = clone $imageOld;
            unset($imageNew->id);
            ///unset($imageNew->id_image);
            $imageNew->id_product = (int) $idProductNew;

            // A new id is generated for the cloned image when calling add()
            if (@$imageNew->add()) {

                $newPath = $imageNew->getPathForCreation();
                foreach ($imagesTypes as $imageType) {
                    if (file_exists(_PS_PROD_IMG_DIR_ . $imageOld->getExistingImgPath() . '-' . $imageType['name'] . '.jpg')) {
                        if (!Configuration::get('PS_LEGACY_IMAGES')) {
                            $imageNew->createImgFolder();
                        }
                        copy(
                            _PS_PROD_IMG_DIR_ . $imageOld->getExistingImgPath() . '-' . $imageType['name'] . '.jpg',
                        $newPath . '-' . $imageType['name'] . '.jpg'
                        );
                        if (Configuration::get('WATERMARK_HASH')) {
                            $oldImagePath = _PS_PROD_IMG_DIR_ . $imageOld->getExistingImgPath() . '-' . $imageType['name'] . '-' . Configuration::get('WATERMARK_HASH') . '.jpg';
                            if (file_exists($oldImagePath)) {
                                copy($oldImagePath, $newPath . '-' . $imageType['name'] . '-' . Configuration::get('WATERMARK_HASH') . '.jpg');
                            }
                        }
                    }
                }

                if (file_exists(_PS_PROD_IMG_DIR_ . $imageOld->getExistingImgPath() . '.jpg')) {
                    copy(_PS_PROD_IMG_DIR_ . $imageOld->getExistingImgPath() . '.jpg', $newPath . '.jpg');
                }

                $this->replaceAttributeImageAssociationId($combinationImages, (int) $imageOld->id, (int) $imageNew->id);

                // Duplicate shop associations for images
                ///$imageNew->duplicateShops($idProductOld);
                ///if (Shop::isTableAssociated($this->def['table'])) {
		            $sql = 'SELECT id_shop
						FROM ' . _DB_PREFIX_ . 'image_shop
						WHERE id_product = ' . (int) $idProductOld;
			        if ($results = Db::getInstance()->executeS($sql)) {
			            $ids = [];
			            foreach ($results as $row) {
			                $ids[] = $row['id_shop'];
			            }

			            $this->associateTo($imageNew, $ids);
			        }
		        ///}
            } else {
                return false;
            }
        }

        return Image::duplicateAttributeImageAssociations($combinationImages);
    }

    public function associateTo($imageNew, $id_shops)
    {
        if (!$imageNew) {
            return;
        }

        if (!is_array($id_shops)) {
            $id_shops = [$id_shops];
        }

        $data = [];
        foreach ($id_shops as $id_shop) {
            if (!$imageNew->isAssociatedToShop($id_shop)) {
            	$r = Db::getInstance()->getValue('select count(*) from '._DB_PREFIX_.'image_shop where id_product = '.$imageNew->id_product.' and id_shop='.$id_shop);
            	if (!$r) {
	                $data[] = [
	                	'id_product' => $imageNew->id_product,
	                    'id_image' => (int) $imageNew->id,
	                    'id_shop' => (int) $id_shop,
	                ];
	            }
            }
        }

        if ($data) {
            Db::getInstance()->insert('image_shop', $data);
        }

        return true;
    }

    protected function replaceAttributeImageAssociationId(&$combinationImages, $savedId, $idImage)
    {
        if (!isset($combinationImages['new']) || !is_array($combinationImages['new'])) {
            return;
        }
        foreach ($combinationImages['new'] as $id_product_attribute => $image_ids) {
            foreach ($image_ids as $key => $imageId) {
                if ((int) $imageId == (int) $savedId) {
                    $combinationImages['new'][$id_product_attribute][$key] = (int) $idImage;
                }
            }
        }
    }
}
