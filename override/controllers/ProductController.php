<?php

class ProductController extends ProductControllerCore
{
    public function initContent()
    {
        FrontController::initContent();

        if (!$this->errors) {
            if (Pack::isPack((int)$this->product->id) && !Pack::isInStock((int)$this->product->id)) {
                $this->product->quantity = 0;
            }

            $this->product->description = $this->transformDescriptionWithImg($this->product->description);

            // Assign to the template the id of the virtual product. "0" if the product is not downloadable.
            $this->context->smarty->assign('virtual', ProductDownload::getIdFromIdProduct((int)$this->product->id));

            $this->context->smarty->assign('customizationFormTarget', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

            if (Tools::isSubmit('submitCustomizedDatas')) {
                // If cart has not been saved, we need to do it so that customization fields can have an id_cart
                // We check that the cookie exists first to avoid ghost carts
                if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                    $this->context->cart->add();
                    $this->context->cookie->id_cart = (int)$this->context->cart->id;
                }
                $this->pictureUpload();
                $this->textRecord();
                $this->formTargetFormat();
            } elseif (Tools::getIsset('deletePicture') && !$this->context->cart->deleteCustomizationToProduct($this->product->id, Tools::getValue('deletePicture'))) {
                $this->errors[] = Tools::displayError('An error occurred while deleting the selected picture.');
            }

            $pictures = array();
            $text_fields = array();
            if ($this->product->customizable) {
                $files = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_FILE, true);
                foreach ($files as $file) {
                    $pictures['pictures_'.$this->product->id.'_'.$file['index']] = $file['value'];
                }

                $texts = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_TEXTFIELD, true);

                foreach ($texts as $text_field) {
                    $text_fields['textFields_'.$this->product->id.'_'.$text_field['index']] = str_replace('<br />', "\n", $text_field['value']);
                }
            }

            $this->context->smarty->assign(array(
                'pictures' => $pictures,
                'textFields' => $text_fields));

            $this->product->customization_required = false;
            $customization_fields = $this->product->customizable ? $this->product->getCustomizationFields($this->context->language->id) : false;
            if (is_array($customization_fields)) {
                foreach ($customization_fields as $customization_field) {
                    if ($this->product->customization_required = $customization_field['required']) {
                        break;
                    }
                }
            }

            // Assign template vars related to the category + execute hooks related to the category
            $this->assignCategory();
            // Assign template vars related to the price and tax
            $this->assignPriceAndTax();

            // Assign template vars related to the images
            $this->assignImages();
            // Assign attribute groups to the template
            $this->assignAttributesGroups();

            // Assign attributes combinations to the template
            $this->assignAttributesCombinations();

            // Pack management
            $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : array();
            $this->context->smarty->assign('packItems', $pack_items);
            $this->context->smarty->assign('packs', Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1));

            if (isset($this->category->id) && $this->category->id) {
                $return_link = Tools::safeOutput($this->context->link->getCategoryLink($this->category));
            } else {
                $return_link = 'javascript: history.back();';
            }

            $accessories = $this->product->getAccessories($this->context->language->id);
            if ($this->product->cache_is_pack || $accessories && count($accessories)) {
                $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
            }
            if ($this->product->customizable) {
                $customization_datas = $this->context->cart->getProductCustomization($this->product->id, null, true);
            }

            $this->context->smarty->assign(array(
                'stock_management' => Configuration::get('PS_STOCK_MANAGEMENT'),
                'customizationFields' => $customization_fields,
                'id_customization' => empty($customization_datas) ? null : $customization_datas[0]['id_customization'],
                'accessories' => $accessories,
                'return_link' => $return_link,
                'product' => $this->product,
                'product_manufacturer' => new Manufacturer((int)$this->product->id_manufacturer, $this->context->language->id),
                'token' => Tools::getToken(false),
                'features' => $this->product->getFrontFeatures($this->context->language->id),
                'attachments' => (($this->product->cache_has_attachments) ? $this->product->getAttachments($this->context->language->id) : array()),
                'allow_oosp' => $this->product->isAvailableWhenOutOfStock((int)$this->product->out_of_stock),
                'last_qties' =>  (int)Configuration::get('PS_LAST_QTIES'),
                'HOOK_EXTRA_LEFT' => Hook::exec('displayLeftColumnProduct'),
                'HOOK_EXTRA_RIGHT' => Hook::exec('displayRightColumnProduct'),
                'HOOK_PRODUCT_OOS' => Hook::exec('actionProductOutOfStock', array('product' => $this->product)),
                'HOOK_PRODUCT_ACTIONS' => Hook::exec('displayProductButtons', array('product' => $this->product)),
                'HOOK_PRODUCT_TAB' =>  Hook::exec('displayProductTab', array('product' => $this->product)),
                'HOOK_PRODUCT_TAB_CONTENT' =>  Hook::exec('displayProductTabContent', array('product' => $this->product)),
                'HOOK_PRODUCT_CONTENT' =>  Hook::exec('displayProductContent', array('product' => $this->product)),
                'display_qties' => (int)Configuration::get('PS_DISPLAY_QTIES'),
                'display_ht' => !Tax::excludeTaxeOption(),
                'jqZoomEnabled' => Configuration::get('PS_DISPLAY_JQZOOM'),
                'ENT_NOQUOTES' => ENT_NOQUOTES,
                'outOfStockAllowed' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                'errors' => $this->errors,
                'body_classes' => array(
                    $this->php_self.'-'.$this->product->id,
                    $this->php_self.'-'.$this->product->link_rewrite,
                    'category-'.(isset($this->category) ? $this->category->id : ''),
                    'category-'.(isset($this->category) ? $this->category->getFieldByLang('link_rewrite') : '')
                ),
                'display_discount_price' => Configuration::get('PS_DISPLAY_DISCOUNT_PRICE'),
            ));
        }
        $this->setTemplate(_PS_THEME_DIR_.'product.tpl');
    }
}