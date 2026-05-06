<?php

namespace WILCITY_APP\Controllers\WooCommerce\Cart;

class VariationValidation
{
    private $variationId;
    private $quantity;
    private $aAttributes;
    /**
     * @var $oProduct \WC_Product
     */
    private $oProduct;
    
    public function setVariationId($variationId)
    {
        $this->variationId = $variationId;
        
        return $this;
    }
    
    public function setQuantity($quantity)
    {
        $this->quantity = wc_stock_amount(abs($quantity));
        
        return $this;
    }
    
    public function setProduct(\WC_Product $oProduct)
    {
        $this->oProduct = $oProduct;
        
        return $this;
    }
    
    public function setAttributes($aAttributes)
    {
        if (is_string($aAttributes)) {
            $aParse = explode('|', $this->aAttributes);
            foreach ($aParse as $attribute) {
                $aAttribute                  = explode(':', $attribute);
                $aAttributes[$aAttribute[0]] = $aAttribute[1];
            }
            $this->aAttributes = $aAttributes;
        } else {
            $this->aAttributes = $aAttributes;
        }
        
        return $this;
    }
    
    /**
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        if (empty($this->variationId)) {
            return [
              'status' => 'error',
              'msg'    => wilcityAppGetLanguageFiles('variationIDRequired')
            ];
        }
        
        $aMissingAttributes = [];
        $aVariations        = [];
        $aPostedAttributes  = [];
        foreach ($this->oProduct->get_attributes() as $aAttribute) {
            if (!$aAttribute['is_variation']) {
                continue;
            }
            $attributeKey = sanitize_title($aAttribute['name']);
            
            if (isset($this->aAttributes[$attributeKey])) {
                if ($aAttribute['is_taxonomy']) {
                    // Don't use wc_clean as it destroys sanitized characters.
                    $value = sanitize_title(wp_unslash($this->aAttributes[$attributeKey]));
                } else {
                    return [
                      'status' => 'error',
                      'msg'    => wilcityAppGetLanguageFiles('productAttributeMustATerm')
                    ];
                }
                $aPostedAttributes[$attributeKey] = $value;
            }
        }
        
        // If no variation ID is set, attempt to get a variation ID from posted attributes.
        if (empty($this->variationId)) {
            /**
             * @var $oDataStore \WC_Product_Data_Store_CPT
             */
            $oDataStore        = \WC_Data_Store::load('product');
            $this->variationId = $oDataStore->find_matching_product_variation($this->oProduct, $aPostedAttributes);
        }
        
        // Do we have a variation ID?
        if (empty($this->variationId)) {
            return [
              'status' => 'error',
              'msg'    => wilcityAppGetLanguageFiles('mustChooseProductOptions')
            ];
        }
        
        // Check the data we have is valid.
        $aVariationData = wc_get_product_variation_attributes($this->variationId);
        
        foreach ($this->oProduct->get_attributes() as $aAttribute) {
            if (!$aAttribute['is_variation']) {
                continue;
            }
            
            // Get valid value from variation data.
            $attributeKey = sanitize_title($aAttribute['name']);
            $valid_value  = isset($aVariationData[$attributeKey]) ? $aVariationData[$attributeKey] : '';
            
            /**
             * If the attribute value was posted, check if it's valid.
             *
             * If no attribute was posted, only error if the variation has an 'any' attribute which requires a value.
             */
            if (isset($aPostedAttributes[$attributeKey])) {
                $value = $aPostedAttributes[$attributeKey];
                
                // Allow if valid or show error.
                if ($valid_value === $value) {
                    $aVariations[$attributeKey] = $value;
                } elseif ('' === $valid_value && in_array($value, $aAttribute->get_slugs(), true)) {
                    // If valid values are empty, this is an 'any' variation so get all possible values.
                    $aVariations[$attributeKey] = $value;
                } else {
                    /* translators: %s: Attribute name. */
                    return [
                      'status' => 'error',
                      'msg'    => sprintf(
                        wilcityAppGetLanguageFiles('invalidProductAttribute'), wc_attribute_label
                        ($aAttribute['name'])
                      )
                    ];
                }
            } elseif ('' === $valid_value) {
                $aMissingAttributes[] = wc_attribute_label($aAttribute['name']);
            }
        }
        
        if (!empty($aMissingAttributes)) {
            /* translators: %s: Attribute name. */
            return [
              'status' => 'error',
              'msg'    => sprintf(_n(wilcityAppGetLanguageFiles('requiredField'),
                wilcityAppGetLanguageFiles('requiredFields'),
                count($aMissingAttributes), 'woocommerce'), wc_format_list_of_items($aMissingAttributes))
            ];
        }
        
        $passedValidation = apply_filters(
          'woocommerce_add_to_cart_validation',
          true,
          $this->oProduct->get_id(),
          $this->quantity,
          $this->variationId,
          $aVariations
        );
        
        if ($passedValidation) {
            return [
              'status'     => 'success',
              'variations' => $aVariations
            ];
        }
        
        return [
          'status' => 'error',
          'msg'    => wilcityAppGetLanguageFiles('invalidVariation')
        ];
    }
}
