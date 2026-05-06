<?php

namespace WilokeListingTools\Framework\Helpers;

use WeDevs\Dokan\Vendor\Vendor;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\UserLatLng;

class ProductSkeleton extends AbstractSkeleton
{
    protected $aAtts;
    private   $productId;
    protected $post;
    public    $postType    = 'product';
    private   $aExtraPluck = [];
    /**
     * @var \WC_Product
     */
    private $oProduct;
    private $thumbnail = 'thumbnail';

    /**
     * @param $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        $this->postID = $productId;
        return $this;
    }

    public function getPost()
    {
        $this->post = get_post($this->productId);

        return $this;
    }

    /**
     * @param $thumbnail
     *
     * @return $this
     */
    private function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getLink()
    {
        return get_permalink($this->productId);
    }

    public function getVendorInfo()
    {
        if (function_exists('dokan')) {
            return dokan()->vendor->get($this->getUserId());
        }

        return false;
    }

    /**
     * @return string
     */
    public function getShopUrl()
    {
        $oVendor = $this->getVendorInfo();
        if (!$oVendor) {
            return '';
        }

        return $oVendor->get_shop_url();
    }

    public function getStoreInfo()
    {
        return get_user_meta($this->getUserId(), 'dokan_profile_settings', true);
    }

    public function getPhone()
    {
        $aUserInfo = $this->getStoreInfo();
        $phone = isset($aUserInfo['phone']) ? $aUserInfo['phone'] : '';
        if (empty($phone)) {
            $phone = GetSettings::getUserMeta($this->getUserId(), 'phone');
        }
        return $phone;
    }

    public function getEmail()
    {
        $oUser = get_user_by('ID', $this->getUserId());
        return $oUser->user_email;
    }

    private function getProduct()
    {
        if (function_exists('wc_get_product')) {
            $this->oProduct = wc_get_product($this->productId);
        }
        return $this;
    }

    public function getTotalSales()
    {
        return $this->oProduct->get_total_sales();
    }

    public function getLogo(): string
    {
        App::get('VendorSkeleton')->setVendorId($this->getUserId())->getVendorInfo();
        return App::get('VendorSkeleton')->getLogo();
    }

    public function getMapInfo()
    {
        App::get('VendorSkeleton')->setVendorId($this->getUserId())->getVendorInfo();
        return App::get('VendorSkeleton')->getMapInfo();
    }

    public function getHeaderCard()
    {
        $aHeader = GetSettings::getOptions(
            General::getSingleListingSettingKey('header_card', $this->postType), false, true
        );

        $headerType = isset($aHeader['btnAction']) ? $aHeader['btnAction'] : 'total_views';
        if (!$this->isPlanAllowed($headerType)) {
            return [];
        }

        $aValues = [];
        switch ($headerType) {
            case 'call_us':
                $val = $this->getPhone();
                if (!empty($val)) {
                    $aValues[] = [
                        'type'  => 'phone',
                        'icon'  => 'la la-phone',
                        'value' => 'tel:' . $val,
                        'name'  => 'Call us',
                        'i18'   => 'callUs'
                    ];
                }
                break;
            case 'email_us':
                $val = $this->getEmail();
                if (!empty($val)) {
                    $aValues[] = [
                        'type'  => 'email',
                        'icon'  => 'la la-envelope',
                        'value' => 'mailto:' . $val,
                        'name'  => 'Email',
                        'i18'   => 'emailUs'
                    ];
                }
                break;
            default:
                $val = $this->getTotalViews();
                if (!empty($val)) {
                    $aValues[] = [
                        'type'  => 'totalViews',
                        'icon'  => 'la la-eye',
                        'name'  => sprintf(_n('%s view', '%s views', $val, 'wiloke-listing-tools'),
                            number_format_i18n($val)),
                        'value' => get_permalink()
                    ];
                }
                break;
        }

        return $aValues;
    }


    public function getOAddress()
    {
        App::get('VendorSkeleton')->setVendorId($this->getUserId())->getVendorInfo();
        $aValue = App::get('VendorSkeleton')->getAddress();

        if (empty($aValue) || !isset($aValue['lat']) || !isset($aValue['lng']) || !isset($aValue['address'])) {
            return [];
        }

        if (empty($aValue['lat']) || empty($aValue['lng']) || empty($aValue['address'])) {
            return [];
        }

        return $aValue;
    }

    public function getAddress()
    {
        return $this->getOAddress();
    }

    public function getGallery(): array
    {
        $aIds = $this->oProduct->get_gallery_image_ids();
        if (empty($aIds)) {
            return [];
        }
        $aIds = array_combine($aIds, $aIds);
        return GalleryHelper::gallerySkeleton($aIds, $this->galleryPreviewSize);
    }

    public function getFooterCard()
    {
        $aFooter = GetSettings::getOptions(General::getSingleListingSettingKey('footer_card', $this->postType), false, true);
        if (empty($aFooter)) {
            return [];
        }

        $aValues = [];
        foreach ($aFooter as $taxonomy) {
            $val = $this->getTaxonomy($taxonomy, true);
            if (!empty($val)) {
                $aValues[] = [
                    'value'    => $val,
                    'position' => 'left',
                    'type'     => 'taxonomy'
                ];
            }
        }

//        if ($this->isPlanAllowed('toggle_gallery')) {
        $aValues[] = [
            'type'     => 'gallery',
            'icon'     => 'la la-search-plus',
            'value'    => $this->getGallery(),
            'position' => 'right'
        ];
//        }

        return apply_filters('wilcity/filter/wiloke-listing-tools/footer-card', $aValues, $this->oProduct);
    }

    public function getBodyCard()
    {
        $aBody = GetSettings::getOptions(
            General::getSingleListingSettingKey('card', $this->postType), false, true
        );

        $aValues = [];
        if (!empty($aBody)) {
            foreach ($aBody as $aItem) {
                if (!$this->isPlanAllowed($aItem['key'])) {
                    continue;
                }

                $val = null;
                switch ($aItem['type']) {
                    case 'custom_taxonomy':
                        $val = $this->getTaxonomy($aItem['key'], false);
                        break;
                    case 'custom_field':
                        if (!empty($aItem['content'])) {
                            $this->getAddListingFields();
                            $aItem['customFieldType'] = $this->aAddListingFields[$aItem['key']]['type'];
                            $content
                                = str_replace(']', ' post_id="' . $this->postID . '" return_format="json"]',
                                $aItem['content']);
                            $content = str_replace(['{{', '}}'], ['"', '"'], $content);
                            if (!empty($content)) {
                                $val = do_shortcode($content);
                                if (!empty($val)) {
                                    $val = isJson($val) ? json_decode($val, true) : $val;
                                }
                            }
                        }
                        break;
                    case 'website':
                        $val = $this->getShopUrl();
                        break;
                    default:
                        $funcName = 'get' . ucfirst(str_replace('_', '', $aItem['type']));
                        if (method_exists($this, $funcName)) {
                            $val = $this->$funcName();
                        }
                        break;
                }

                if (!empty($val)) {
                    $aItem['value'] = $val;
                    unset($aItem['content']);
                    $aValues[] = $aItem;
                }
            }
        }

        return $aValues;
    }

    public function getID(): ?int
    {
        return $this->oProduct->get_id();
    }

    public function getTitle()
    {
        return $this->oProduct->get_title();
    }

    public function getProductName()
    {
        return $this->getTitle();
    }

    public function getProductTaxonomy($taxonomy, $isSingular = false)
    {
        if ($taxonomy === 'product_attributes') {
            $attributes = $this->oProduct->get_attributes();
        } else {
            return $this->getTaxonomy($taxonomy, $isSingular);
        }
    }

    public function getProductCats()
    {
        return $this->getTaxonomy('product_cat');
    }

    public function getProductTags()
    {
        return $this->getTaxonomy('product_tag');
    }

    public function getProductAttributes()
    {
        return $this->getProductTaxonomy('product_attributes');
    }

    private function getThumbnail()
    {
        return get_the_post_thumbnail_url($this->getID(), $this->thumbnail);
    }

    public function getOFeaturedImg(): ?array
    {
        $aImg['thumbnail'] = GetSettings::getFeaturedImg($this->getID(), 'thumbnail');
        $aImg['medium'] = GetSettings::getFeaturedImg($this->getID(), 'thumbnail');
        $aImg['large'] = GetSettings::getFeaturedImg($this->getID(), 'thumbnail');

        return $aImg;
    }

    private function getSalePriceHTML()
    {
        $salePrice = $this->getSalePrice();
        return empty($salePrice) ? '' : wc_price($salePrice);
    }

    private function getSalePrice()
    {
        if ($this->oProduct->is_type('variable')) {
            $regularPriceMin = $this->oProduct->get_variation_regular_price();
            $regularPriceMax = $this->oProduct->get_variation_regular_price('max');
            $salePriceMin = $this->oProduct->get_variation_sale_price('min', false);
            $salePriceMax = $this->oProduct->get_variation_sale_price('max', false);

            if ($salePriceMin == $regularPriceMin && $salePriceMax == $regularPriceMax) {
                $salePrice = '';
            } else {
                $salePrice = floatval($salePriceMin) . ' - ' . floatval($salePriceMax);
            }
        } elseif ($this->oProduct->is_type('booking')) {
            $salePrice = '';
        } else {
            $salePrice = $this->oProduct->get_sale_price() === '' ? '' : floatval($this->oProduct->get_sale_price());
        }

        return $salePrice;
    }

    private function getRegularPriceHTML()
    {
        $regularPrice = $this->getRegularPrice();

        return empty($regularPrice) ? '' : wc_price($regularPrice);
    }

    private function getRegularPrice()
    {
        if ($this->oProduct->is_type('variable')) {
            $regularPriceMin = $this->oProduct->get_variation_regular_price();
            $regularPriceMax = $this->oProduct->get_variation_regular_price('max');

            if (empty($regularPriceMin) && empty($regularPriceMax)) {
                $regularPrice = $this->getPrice();
            } else {
                $regularPrice = floatval($regularPriceMin) . ' - ' . floatval($regularPriceMax);
            }

        } elseif ($this->oProduct->is_type('booking')) {
            $regularPrice
                = floatval(wc_booking_calculated_base_cost(new \WC_Product_Booking($this->oProduct->get_id())));
        } else {
            $regularPrice = floatval($this->oProduct->get_regular_price());
        }

        return $regularPrice;
    }

    public function getPrice()
    {
        if ($this->oProduct->is_type('variable')) {
            $minPrice = $this->oProduct->get_variation_price();
            $maxPrice = $this->oProduct->get_variation_price('max');

            $price = floatval($minPrice) . ' - ' . floatval($maxPrice);
        } elseif ($this->oProduct->is_type('booking')) {
            $price = floatval(wc_booking_calculated_base_cost(new \WC_Product_Booking($this->oProduct->get_id())));
        } else {
            $price = floatval($this->oProduct->get_price());
        }

        return $price;
    }

    public function getSinglePrice()
    {
        return $this->oProduct->get_price_html();
    }

    private function getPriceHTML()
    {
        $price = $this->getPrice();

        return empty($price) ? '' : wc_price($price);
    }

    private function getAverageRating()
    {
        return floatval(number_format($this->oProduct->get_average_rating(), 2));
    }

    private function getRatingCounts()
    {
        return intval($this->oProduct->get_rating_counts());
    }

    public function getRatingStar()
    {
        return [
            'total'   => $this->getRatingCounts(),
            'average' => $this->getAverageRating(),
            'isShow'  => get_option('woocommerce_enable_review_rating')
        ];
    }

    private function getQuantity()
    {
        $oCart = WC()->cart;
        if (empty($oCart)) {
            return 0;
        }

        $aCartItem = $oCart->get_cart_item_quantities();

        return isset($aCartItem[$this->productId]) ? absint($aCartItem[$this->productId]) : 0;
    }

    private function getCartKey()
    {
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/ProductSkeleton/cartKey',
            WooCommerce::getCartKey($this->productId),
            $this->productId,
            $this->oProduct
        );
    }

    private function getDataAverageRating()
    {
        return $this->getAverageRating();
    }

    private function getStockStatus()
    {
        return $this->oProduct->get_stock_status();
    }

    private function getProductType()
    {
        return $this->oProduct->get_type();
    }

    private function getType()
    {
        return $this->oProduct->get_type();
    }

    public function isProductType($type)
    {
        return $this->oProduct->is_type($type);
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    public function getUserId()
    {
        return get_post_field('post_author', $this->getID());
    }

    public function getOAuthor(): array
    {
        return [
            'ID'          => get_post_field('post_author', $this->getID()),
            'displayName' => User::getField('display_name', get_post_field('post_author', $this->getID())),
            'avatar'      => User::getAvatar($this->getID())
        ];
    }

    public function getCategories(): array
    {
        $aCategoryIDs = $this->oProduct->get_category_ids();
        $aCategories = [];
        if (!empty($aCategoryIDs)) {
            foreach ($aCategoryIDs as $catID) {
                $oCat = get_term($catID, 'product_cat');
                $aCategories[] = $oCat->name;
            }
        } else {
            $aCategories = [];
        }

        return $aCategories;
    }

    public function getOCategories(): array
    {
        return $this->getCategories();
    }

    /**
     * @param $productID
     *
     * @return bool
     */
    public function isProductInWishlist($productID)
    {
        if (!function_exists('YITH_WCWL')) {
            $isAddedToWishlist = false;
        } else {
            $isAddedToWishlist = YITH_WCWL()->is_product_in_wishlist($productID);
        }

        return $isAddedToWishlist;
    }

    public function setExtraPluck($aPluck)
    {
        $this->aExtraPluck = $aPluck;

        return $this;
    }

    public function getSaleOff()
    {
        if ($this->oProduct->is_type('variable')) {
            $aPrices = $this->oProduct->get_variation_prices();

            $saleOff = '';

            foreach ($aPrices['sale_price'] as $attributeOrder => $salePrice) {
                $regularPrice = $aPrices['regular_price'][$attributeOrder];
                if ($salePrice == $regularPrice) {
                    continue;
                }

                $currentSaleOff = ceil((absint($salePrice - $regularPrice) / $regularPrice) * 100);

                if ($currentSaleOff > $saleOff) {
                    $saleOff = $currentSaleOff;
                }
            }

            return $saleOff;
        } else {
            $regularPrice = $this->oProduct->get_regular_price();
            $salePrice = $this->oProduct->get_sale_price();

            if (empty($regularPrice)) {
                return '';
            }

            if (empty($salePrice)) {
                return '';
            } else {
                return ceil((absint($salePrice - $regularPrice) / $regularPrice) * 100);
            }
        }
    }

    public function getOReviews()
    {
        $aReviews = [
            'isRatingStar' => 'yes'
        ];
        if (get_option('woocommerce_enable_review_rating') !== 'no') {
            $aReviews['average'] = $this->oProduct->get_average_rating();
            $aReviews['total'] = $this->oProduct->get_review_count();
        }

        return $aReviews;
    }

    /**
     * @param array $aPluck
     * @param array $aAtts
     * @param bool $isFocus
     *
     * @return array|\WP_Error
     */
    public function get(array $aPluck = [], array $aAtts = [], $isFocus = false)
    {
        if (empty($aPluck)) {
            $aPluck = [
                'ID',
                'title',
                'name',
                'logo',
                'oFeaturedImg',
                'oCategories',
                'oAuthor',
                'thumbnail',
                'productType',
                'salePriceHTML',
                'salePrice',
                'regularPriceHTML',
                'regularPrice',
                'priceHTML',
                'price',
                'averageRating',
                'ratingCounts',
                'cartKey',
                'quantity',
                'link',
                'isAddedToCart',
                'isAddedToWishlist',
                'stockStatus',
                'getOReviews',
                'saleOff'
            ];
        }

        $aPluck = array_merge($aPluck, $this->aExtraPluck);

        $this->getProduct();

        if (empty($this->oProduct) || is_wp_error($this->oProduct)) {
            return new \WP_Error('broke', esc_html__('The product is no longer available', 'wiloke-listing-tools'));
        }

        $this->aAtts = $aAtts;
        $aResponse = [];
        foreach ($aPluck as $pluck) {
            $method = 'get' . ucfirst($pluck);
            $filterHook = 'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/ProductSkeleton/pluck/' . $pluck;

            if (has_filter($filterHook)) {
                $aResponse[$pluck] = apply_filters(
                    $filterHook,
                    '',
                    [
                        'pluck'   => $pluck,
                        'post'    => $this->oProduct,
                        'postID'  => $this->getID(),
                        'isFocus' => $isFocus,
                        'atts'    => $this->aAtts
                    ]
                );
            } else {
                if (method_exists($this, $method)) {
                    $aResponse[$pluck] = $this->{$method}();
                } else {
                    $aResponse[$pluck] = '';
                }
            }
        }

        return $aResponse;
    }

    public function getSkeleton($product, array $aPluck = [], array $aAtts = [], $isFocus = false)
    {
        if (is_numeric($product)) {
            $this->setProductId($product);
            $this->getProduct();
            $this->getPost();
        } else {
            if ($product instanceof \WP_Post) {
                $this->setProductId($product->ID);
                $this->getProduct();
                $this->post = $product;
            } else {
                $this->setProductId($product->get_id());
                $this->oProduct = $product;
                $this->getPost();
            }
        }

        return $this->get($aPluck, $aAtts, $isFocus);
    }
}
