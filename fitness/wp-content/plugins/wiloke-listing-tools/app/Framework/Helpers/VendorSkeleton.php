<?php


namespace WilokeListingTools\Framework\Helpers;


use WeDevs\Dokan\Vendor\Vendor;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;

class VendorSkeleton extends AbstractSkeleton
{
    protected $aAtts;
    private   $vendorId;
    protected $post;
    public    $postType       = 'product';
    private   $aExtraPluck    = [];
    private   $aHeaderSection = [];

    /**
     * @var \WC_Product
     */
    private $oProduct;

    /**
     * @var \Dokan_Vendor
     */
    private $oVendor;
    private $thumbnail = 'thumbnail';

    /**
     *
     * @return $this
     */
    public function setVendorId($vendorId)
    {
        $this->vendorId = $vendorId;
        return $this;
    }

    public function setUserId($vendorId)
    {
        return $this->setVendorId($vendorId);
    }

    public function getLink()
    {
        $this->getVendorInfo();
        if ($this->oVendor) {
            return $this->oVendor->get_shop_url();
        }

        return '';
    }

    public function getShopLink()
    {
        return $this->getLink();
    }

    public function getShopName()
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getShopUrl()
    {
        return $this->getLink();
    }

    public function getVendorInfo()
    {
        if (function_exists('dokan')) {
            if (method_exists(dokan()->vendor, 'get')) {
                $this->oVendor = dokan()->vendor->get($this->getUserId());
                return $this->oVendor;
            }
        }

        return false;
    }

    public function getFullAddress()
    {
        if (!$this->oVendor) {
            return '';
        }

        $aRawAddress = $this->oVendor->get_address();
        if (empty($aRawAddress['street_1'])) {
            return '';
        }

        return stripslashes($aRawAddress['street_1']) . ' ' . $aRawAddress['city'];
    }

    public function getMapInfo()
    {
        if ($this->oVendor) {
            return [
                'permalink' => $this->getShopLink(),
                'popup'     => [
                    'title'   => $this->getShopName(),
                    'excerpt' => $this->getFullAddress()
                ]
            ];
        }

        return [
            'permalink' => get_permalink(wc_get_page_id('shop')),
            'popup'     => [
                'title'   => get_option('blogname'),
                'excerpt' => get_option('blogdescription')
            ]
        ];
    }

    public function getLogo(): string
    {
        if ($this->oVendor) {
            $logoId = $this->oVendor->get_avatar_id();
            if (!empty($logoId)) {
                $logoUrl = wp_get_attachment_thumb_url($logoId);
            }
        }

        if (!isset($logoUrl) || empty($logoUrl)) {
            $logoUrl = User::getAvatar($this->getUserId());
        }
        return $logoUrl;
    }

    public function getStoreInfo()
    {
        return get_user_meta($this->getUserId(), 'dokan_profile_settings', true);
    }

    public function getPhone()
    {
        if ($this->oVendor) {
            return $this->oVendor->get_phone();
        }

        $phone = GetSettings::getUserMeta($this->getUserId(), 'phone');
        return empty($phone) ? '' : $phone;
    }

    public function getPermalink(): string
    {
        return $this->getShopLink();
    }

    public function getEmail()
    {
        if ($this->oVendor) {
            if ($this->oVendor->show_email()) {
                return $this->oVendor->get_email();
            }

            return '';
        }

        return '';
    }

    public function getLocation()
    {
        if ($this->oVendor) {
            $location = $this->oVendor->get_location();

            if ($location) {
                $aParse = explode(',', $location);
                return [
                    'lat' => $aParse[0],
                    'lng' => $aParse[1]
                ];
            }
        }

        return [
            'lat' => '',
            'lng' => ''
        ];
    }

    public function getAddress()
    {
        if ($this->oVendor) {
            $aRawAddress = $this->oVendor->get_address();
            $aLocation = $this->getLocation();
            if (empty($aLocation)) {
                return false;
            }
            $mapPageUrl = add_query_arg(
                [
                    'title' => $this->getName(),
                    'lat'   => $aLocation['lat'],
                    'lng'   => $aLocation['lng']
                ],
                get_permalink(\WilokeThemeOptions::getOptionDetail('map_page'))
            );

            $aAddress['mapPageUrl'] = $mapPageUrl;
            $aAddress['address'] = stripslashes($aRawAddress['street_1']) . ' ' . $aRawAddress['city'];
            if (empty(trim($aAddress['address']))) {
                $aAddress['address'] = $this->oVendor->get_info_part('find_address');
            }
            $aAddress['addressOnGGMap'] = apply_filters(
                'wilcity/filter/wiloke-listing-tools/google-address',
                esc_url('https://www.google.com/maps/search/' . urlencode($aAddress['address'])),
                [
                    'address' => $aAddress['address'],
                    'lat'     => $aLocation['lat'],
                    'lng'     => $aLocation['lng'],
                ]
            );

            $aAddress['lat'] = $aLocation['lat'];
            $aAddress['lng'] = $aLocation['lng'];
            $aAddress['marker'] = $this->getLogo();

            return array_merge($aAddress, $aLocation);
        }

        return false;
    }

    public function getTotalSales()
    {
        if ($this->oVendor) {
            return $this->oVendor->get_total_sales();
        }

        return 0;
    }

    public function getHeaderCard()
    {
        $aValues = [];
        $val = $this->getPhone();
        if (!empty($val)) {
            $this->aHeaderSection[] = 'phone';
            $aValues[] = [
                'type'  => 'phone',
                'icon'  => 'la la-phone',
                'value' => 'tel:' . $val,
                'name'  => 'Call us',
                'i18'   => 'callUs'
            ];
        } else {
            $email = $this->getEmail();
            if (!empty($email)) {
                $this->aHeaderSection[] = 'email';
                $aValues[] = [
                    'type'  => 'email',
                    'icon'  => 'la la-envelope',
                    'value' => 'mailto:' . $email,
                    'name'  => 'Email',
                    'i18'   => 'emailUs'
                ];
            }
        }
        return $aValues;
    }

    public function getFooterCard()
    {
        $aValues[] = [
            'value'      => $this->getShopLink(),
            'name'       => $this->getName(),
            'position'   => 'left',
            'type'       => 'website',
            'btnClasses' => 'no-style',
            'iconUrl'    => $this->getLogo()
        ];

        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/VendorSkeleton/getFooterCard',
            $aValues,
            $this->oVendor
        );
    }

    public function getID(): ?int
    {
        return $this->vendorId;
    }

    public function getName(): string
    {
        return !$this->oVendor ? '' : $this->oVendor->get_shop_name();
    }

    public function getTitle()
    {
        return $this->getName();
    }

    private function getThumbnail()
    {
        return get_the_post_thumbnail_url($this->getID(), $this->thumbnail);
    }

    public function getOFeaturedImg(): ?array
    {
        /**
         * @param Vendor $oVendor
         */
        $oVendor = $this->getVendorInfo();
        $aImg = [];

        if ($oVendor) {
            $bannerId = $oVendor->get_banner_id();
            if (!empty($bannerId)) {
                $aImg['thumbnail'] = GetSettings::getFeaturedImg($bannerId, 'thumbnail');
                $aImg['medium'] = GetSettings::getFeaturedImg($bannerId, 'thumbnail');
                $aImg['large'] = GetSettings::getFeaturedImg($bannerId, 'thumbnail');
            }
        }

        return $aImg;
    }

    public function getUserId()
    {
        return $this->vendorId;
    }

    public function getOAuthor(): array
    {
        return [
            'ID'          => get_post_field('post_author', $this->getID()),
            'displayName' => User::getField('display_name', get_post_field('post_author', $this->getID())),
            'avatar'      => User::getAvatar($this->getID())
        ];
    }

    public function getBodyCard()
    {
        $aBodyItems = [
            'email'   => [
                'name'      => esc_html__('Email us', 'wiloke-listing-tools'),
                'type'      => 'link',
                'variation' => 'email',
                'icon'      => 'la la-envelope'
            ],
            'phone'   => [
                'name'      => esc_html__('Call us', 'wiloke-listing-tools'),
                'type'      => 'link',
                'variation' => 'phone',
                'icon'      => 'la la-phone'
            ],
            'address' => [
                'name'      => esc_html__('Address', 'wiloke-listing-tools'),
                'type'      => 'link',
                'target'    => '_blank',
                'variation' => 'google_address'
            ]
        ];

        $aValues = [];
        foreach ($aBodyItems as $item => $aInfo) {
            if (in_array($item, $this->aHeaderSection)) {
                continue;
            }

            $method = 'get' . ucfirst($item);
            $val = $this->$method();
            if (!empty($val)) {
                $aValues[] = array_merge(
                    [
                        'value' => $val
                    ],
                    $aInfo
                );
            }
        }

        return $aValues;
    }

    /**
     * @param array $product WC_Product|WP_Post|vendor id
     * @param array $aPluck
     * @param array $aAtts
     * @param bool $isFocus
     *
     * @return array|\WP_Error
     */
    public function getSkeleton($product, array $aPluck = [], array $aAtts = [], $isFocus = false)
    {
        if ($product instanceof \WC_Product) {
            $this->setVendorId(get_post_field('post_author', $product->get_id()));
        } else if ($product instanceof \WP_Post) {
            $this->setVendorId($product->post_author);
        } else {
            $this->setVendorId($product);
        }
        $this->getVendorInfo();

        if (empty($aPluck)) {
            $aPluck = [
                'ID',
                'title',
                'name',
                'logo',
                'oFeaturedImg',
                'oCategories',
                'oAuthor',
                'quantity',
                'link',
                'getOReviews',
                'saleOff'
            ];
        }

        $aPluck = array_merge($aPluck, $this->aExtraPluck);

        if (empty($this->oVendor) || is_wp_error($this->oVendor)) {
            return new \WP_Error('broke', esc_html__('The vendor does not exists', 'wiloke-listing-tools'));
        }

        $this->aAtts = $aAtts;
        $aResponse = [];

        foreach ($aPluck as $pluck) {
            $method = 'get' . ucfirst($pluck);
            $filterHook = 'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/VendorSkeleton/pluck/' . $pluck;

            if (has_filter($filterHook)) {
                $aResponse[$pluck] = apply_filters(
                    $filterHook,
                    '',
                    [
                        'pluck'   => $pluck,
                        'post'    => $this->oVendor,
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

//        $aResponse['group'] = 'vendor';
        return $aResponse;
    }
}
