<?php

namespace WilokeListingTools\Framework\Helpers;

use WILCITY_SC\SCHelpers;
use Wiloke;
use WilokeHelpers;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\Coupon;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReportModel;
use WilokeListingTools\Models\UserModel;
use WilokeThemeOptions;
use WP_Post;

class AbstractSkeleton
{
    protected $aAtts;
    /**
     * @var $post \WP_Post
     */
    protected $post;
    protected $postID;
    protected $postType;
    protected $aCache             = [];
    protected $galleryPreviewSize = 'medium';
    protected $aNavigationSettings;
    protected $aAddListingFields;
    protected $planID             = null;
    protected $isApp              = false;
    protected $aFuncHasArgs       = [];
    protected $isFocus            = false;
    protected $aExcludeCache      = [];
    /**
     * It's useful for Event Listing Type
     * The event key in Single Content is my_checkbox_field1589296195025|text
     * => We will store it to this property: $aCustomFieldStore['my_checkbox_field1589296195025'] = 'text';
     * @var array
     */
    protected $aCustomFieldStore = [];

    public function getTaxonomiesBelongsToPostType(): array
    {
        return get_object_taxonomies($this->postType);
    }


    public function getIsTopOfSearch(): bool
    {
        $promotionId = GetSettings::getPostMeta($this->post->ID, 'belongs_to_promotion');
        if (empty($promotionId)) {
            return false;
        }

        $oPost = get_post($promotionId);
        if (empty($oPost) && !$oPost instanceof WP_Post) {
            return false;
        }

        $aPromotionPlans = GetSettings::getPromotionPlans();
        $now = current_time("timestamp", 1);

        foreach ($aPromotionPlans as $key => $aPlanSetting) {
            $isTopOfSearch = strpos($aPlanSetting["position"], 'top_of_search') !== false;
            if ($isTopOfSearch) {
                $promotionExpiry = GetSettings::getPostMeta($promotionId, 'promote_' . $key);
                if (!empty($promotionExpiry)) {
                    $promotionExpiry = Time::convertUTCTimestampToLocalTimestamp(absint($promotionExpiry));
                    if ($now < $promotionExpiry) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function setAttr($name, $value): AbstractSkeleton
    {
        $this->aAtts[$name] = $value;
        return $this;
    }

    public function setExcludeCache($key)
    {
        if (is_array($key)) {
            $this->aExcludeCache = array_merge($this->aExcludeCache, $key);
        } else {
            $this->aExcludeCache[] = $key;
        }

        return $this;
    }

    public function hasExcludeCache($key)
    {
        return in_array($key, $this->aExcludeCache);
    }

    public function removeExcludeCache($key)
    {
        if (is_array($key)) {
            $this->aExcludeCache = array_diff($this->aExcludeCache, $key);
        } else {
            if ($this->hasExcludeCache($key)) {
                global $removeExcludeKey;
                $removeExcludeKey = $key;
                $this->aExcludeCache = array_filter($this->aExcludeCache, function ($item) {
                    global $removeExcludeKey;

                    return $removeExcludeKey != $item;
                });
            }
        }

        return $this;
    }

    public function getTaxonomies($isRemoveIfEmpty = true)
    {
        $aTaxonomies = $this->getTaxonomiesBelongsToPostType();
        if (empty($aTaxonomies) || !is_array($aTaxonomies)) {
            return false;
        }

        if (!$isRemoveIfEmpty) {
            if (isset($this->aAtts['isRemoveTaxonomyKeyIfEmpty'])) {
                $isRemoveIfEmpty = $this->aAtts['isRemoveTaxonomyKeyIfEmpty'];
            }
        }

        $aTaxonomiesData = [];
        foreach ($aTaxonomies as $taxonomy) {
            $aTerms = $this->getTaxonomy($taxonomy);
            if (!empty($aTerms) || (empty($aTerms) && !$isRemoveIfEmpty)) {
                $aTaxonomiesData[] = $aTerms;
            }
        }

        return $aTaxonomiesData;
    }

    public function getPostTerms($taxonomy, $isRemoveIfEmpty = true)
    {
        if (!$isRemoveIfEmpty) {
            if (isset($this->aAtts['isRemoveTaxonomyKeyIfEmpty'])) {
                $isRemoveIfEmpty = $this->aAtts['isRemoveTaxonomyKeyIfEmpty'];
            }
        }

        $aTerms = $this->getTaxonomy($taxonomy);
        if (!empty($aTerms) || (empty($aTerms) && !$isRemoveIfEmpty)) {
            $aTaxonomiesData[] = $aTerms;
        }

        return $aTaxonomiesData;
    }

    public function setFuncHasArgs($func)
    {
        $this->aFuncHasArgs[] = $func;

        return $this;
    }

    public function generateCBFunction($func)
    {
        $aParsed = explode('_', trim($func));

        $aParsed = array_reduce($aParsed, function ($aCarry, $character) {
            $aCarry[] = ucfirst($character);

            return $aCarry;
        });

        $func = implode('', $aParsed);

        return 'get' . $func;
    }

    public function isFuncHasArgs($func)
    {
        return in_array($func, $this->aFuncHasArgs);
    }

    public function setCache($key, $val): void
    {
        $this->aCache[$this->postID . $key] = $val;
    }

    public function getCache($key)
    {
        return isset($this->aCache[$this->postID . $key]) ? $this->aCache[$this->postID . $key] : null;
    }

    public function hasCache($key)
    {
        return array_key_exists($key, $this->aCache);
    }

    public function isPlanAllowed($key)
    {
        if ($this->planID !== null) {
            $this->planID = GetSettings::getListingBelongsToPlan($this->postID);
        }

        if (empty($this->planID)) {
            return true;
        }

        return Submission::isPlanSupported($this->planID, 'toggle_' . $key);
    }

    public function getOFeaturedImg(): ?array
    {
        $aImg['thumbnail'] = GetSettings::getFeaturedImg($this->postID, 'thumbnail');
        $aImg['medium'] = GetSettings::getFeaturedImg($this->postID, 'medium');
        $aImg['large'] = GetSettings::getFeaturedImg($this->postID, 'large');

        if (empty($aImg['thumbnail'])) {
            $aImg['thumbnail'] = '';
        }

        if (empty($aImg['medium'])) {
            $aImg['medium'] = '';
        }

        if (empty($aImg['large'])) {
            $aImg['large'] = '';
        }

        return $aImg;
    }

    public function getVideos(): array
    {
        $aItems = GetSettings::getPostMeta($this->postID, 'video_srcs', '', '', $this->isFocus);
        if (empty($aItems)) {
            return [];
        }

        $generalThumbnail = null;
        foreach ($aItems as $order => $aItem) {
            if (!isset($aItem['src']) || empty($aItem['src'])) {
                unset($aItems[$order]);
            }

            if (!isset($aItem['thumbnail']) || empty($aItem['thumbnail'])) {
                if ($generalThumbnail === null) {
                    $generalThumbnail = WilokeThemeOptions::getThumbnailUrl('listing_video_thumbnail');
                }

                if (!empty($generalThumbnail)) {
                    $aItems[$order]['thumbnail'] = $generalThumbnail;
                }
            }
        }

        return empty($aItems) ? [] : $aItems;
    }

    public function getOVideos(): array
    {
        return $this->getVideos();
    }

    /**
     * @return array|bool
     */
    public function getMyRoom()
    {
        $productID = GetSettings::getPostMeta($this->postID, 'wilcity_my_room', '', '', $this->isFocus);
        if (empty($productID)) {
            return false;
        }
        $oProductSkeleton = new ProductSkeleton();
        $aParsedProducts = [];

        if (get_post_status($productID) !== 'publish' || get_post_type($productID) !== 'product' ||
            !function_exists('wc_get_product')) {
            return false;
        }

        $aProductData = $oProductSkeleton->getSkeleton($productID);

        if (!isset($aProductData['productType']) || $aProductData['productType'] !== 'booking') {
            return false;
        }

        $aParsedProducts[$productID] = $aProductData;

        return $aParsedProducts;
    }

    /**
     * @return array|bool
     */
    public function getMyProducts()
    {
        $aAtts = $this->aAtts['myProductAtts'] ?? [];
        $authorID = get_post_field('post_author', $this->postID);
        return App::get('ListingProduct')->setPostID($this->postID)->setAuthor($authorID)->getProducts($aAtts);
    }

    public function getFeaturedImage()
    {
        $imgSize = $this->aAtts['img_size'] ?? 'large';

        return GetSettings::getFeaturedImg($this->getID(), $imgSize);
    }

    public function getCoupon()
    {
        $aCoupon = Coupon::getAllCouponInfo($this->postID);

        return is_array($aCoupon) ? $aCoupon : [];
    }

    public function getOAddress()
    {
        if (!class_exists('WilokeThemeOptions')) {
            return [];
        }

        $aListingAddress = GetSettings::getListingMapInfo($this->postID);

        if (!empty($aListingAddress) && !empty($aListingAddress['lat'])) {
            $mapPageUrl = add_query_arg(
                [
                    'title' => urlencode(get_the_title($this->postID)),
                    'lat'   => $aListingAddress['lat'],
                    'lng'   => $aListingAddress['lng']
                ],
                WilokeThemeOptions::getOptionDetail('map_page')
            );
            $aAddress['mapPageUrl'] = $mapPageUrl;
            $aAddress['address'] = stripslashes($aListingAddress['address']);
            $aAddress['addressOnGGMap'] = GetSettings::getAddress($this->postID, true);
            $aAddress['lat'] = $aListingAddress['lat'];
            $aAddress['lng'] = $aListingAddress['lng'];
            $aAddress['marker'] = SingleListing::getMapIcon($this->postID);
        } else {
            $aAddress = false;
        }

        return $aAddress;
    }

    public function getIsMyFavorite()
    {
        $userID = isset($this->aAtts['userID']) ? $this->aAtts['userID'] : '';

        return UserModel::isMyFavorite($this->postID, false, $userID) ? 'yes' : 'no';
    }

    public function getPrice()
    {
        $price = GetSettings::getPostMeta($this->postID, 'single_price', '', '', $this->isFocus);

        return empty($price) ? $price : Price::renderPrice($price);
    }

    public function getSinglePrice()
    {
        return $this->getPrice();
    }

    public function getPriceRange()
    {
        $aPriceRange = GetSettings::getPriceRange($this->postID, false);
        if ($aPriceRange) {
            return Price::renderPrice($aPriceRange['minimumPrice']) . ' - ' .
                Price::renderPrice($aPriceRange['maximumPrice']);
        }

        return '';
    }

    public static function getIsReport(): bool
    {
        return ReportModel::isEnable() === 'enable';
    }

    public function getIsChat(): bool
    {
        if (!Firebase::isFirebaseEnable()) {
            return false;
        }

        $claimed = GetSettings::getPostMeta($this->postID, 'claim_status');
        return $claimed == 'claimed';
    }

    public function getPhotos(): array
    {
        return $this->getGallery();
    }

    public function getCoverImg(): ?string
    {
        return GetSettings::getCoverImage($this->post->ID, $this->aAtts['cover_img_size'] ?? 'large');
    }

    public function getTimezone(): ?string
    {
        return BusinessHours::getTimezone($this->post->ID);
    }

    /**
     * @return mixed|string
     */
    public function getClaimStatus()
    {
        $claimStatus = GetSettings::getPostMeta($this->postID, 'wilcity_claim_status', '', '', $this->isFocus);

        return empty($claimStatus) ? 'not_claim' : $claimStatus;
    }

    public function getOPriceRange()
    {
        $mode = GetSettings::getPostMeta($this->postID, 'price_range', '', '', $this->isFocus);

        if (empty($mode) || $mode === 'cheap') {
            return [
                'mode' => $mode
            ];
        }

        $currencyCode = GetWilokeSubmission::getField('currency_code');
        $currencySymbol = GetWilokeSubmission::getSymbol($currencyCode);
        $currency = apply_filters('wilcity-shortcodes/currency-symbol', $currencySymbol);

        return [
            'mode'         => $mode,
            'description'  => GetSettings::getPostMeta($this->postID, 'price_range_desc', '', '', $this->isFocus),
            'minimumPrice' => GetSettings::getPostMeta($this->postID, 'minimum_price', '', '', $this->isFocus),
            'maximumPrice' => GetSettings::getPostMeta($this->postID, 'maximum_price', '', '', $this->isFocus),
            'currency'     => $currency
        ];
    }

    public function getTotalFavorites(): ?int
    {
        return FavoriteStatistic::countFavorites($this->postID);
    }

    public function getSocialNetworks()
    {
        return GetSettings::getSocialNetworks($this->postID);
    }

    public function getOSocialNetworks()
    {
        return $this->getSocialNetworks();
    }

    public function getTotalViews()
    {
        return GetSettings::getListingTotalViews($this->postID);
    }

    public function getEmail()
    {
        return GetSettings::getListingEmail($this->postID);
    }

    public function getWebsite()
    {
        if (!GetSettings::isPlanAvailableInListing($this->postID, 'toggle_website')) {
            return '';
        }

        return GetSettings::getPostMeta($this->postID, 'website', '', '', $this->isFocus);
    }

    public function getIsClaimed()
    {
        return SingleListing::isClaimedListing($this->postID, true) ? 'yes' : 'no';
    }

    public function getIsAds()
    {
        $adsType = '';
        if (isset($this->aAtts['adsType']) && !empty($this->aAtts['adsType'])) {
            $adsType = $this->aAtts['adsType'];
        } else {
            if (isset($this->aAtts['style']) && $this->aAtts['style'] == 'grid') {
                $adsType = 'GRID';
            }
        }

        return !empty($adsType) && SCHelpers::renderAds($this->postID, $adsType, true) ? 'yes' : 'no';
    }

    public function getGoogleAddress()
    {
        return $this->getOAddress();
    }

    public function getPhone()
    {
        if (!GetSettings::isPlanAvailableInListing($this->postID, 'toggle_phone') ||
            !SingleListing::isClaimedListing($this->postID)) {
            return '';
        }

        return GetSettings::getPostMeta($this->postID, 'phone', '', '', $this->isFocus);
    }

    public function getPostStatus()
    {
        return get_post_status($this->postID);
    }

    public function getTaxonomy($taxonomy, $isSingular = false)
    {
        if (!class_exists('WilokeHelpers')) {
            return $taxonomy;
        }

        $taxonomy = $taxonomy === 'taxonomy' ? $this->aAtts['taxonomy'] : $taxonomy;
        $aTerms = [];
        if ($isSingular) {
            $oTerm = WilokeHelpers::getTermByPostID($this->post->ID, $taxonomy);
            if ($oTerm) {
                $aTerms['ID'] = $oTerm->term_id;
                $aTerms['name'] = $oTerm->name;
                $aTerms['taxonomy'] = $oTerm->taxonomy;
                $aTerms['link'] = add_query_arg(['postType' => $this->postType], get_term_link($oTerm->term_id));
                $aTerms['oIcon'] = WilokeHelpers::getTermOriginalIcon($oTerm);

            }
        } else {
            $aRawTerms = GetSettings::getPostTerms($this->post->ID, $taxonomy);
            if ($aRawTerms) {
                foreach ($aRawTerms as $oTerm) {
                    $aTerm = [];
                    $aTerm['ID'] = $oTerm->term_id;
                    $aTerm['name'] = $oTerm->name;
                    $aTerm['link'] = add_query_arg(['postType' => $this->postType], get_term_link($oTerm->term_id));
                    $aTerm['oIcon'] = WilokeHelpers::getTermOriginalIcon($oTerm);
                    $aTerm['taxonomy'] = $oTerm->taxonomy;
                    $aTerms[] = $aTerm;

                }
            }
        }

        return $aTerms;
    }

    public function getListingLocation($isSingular = false)
    {
        return $this->getTaxonomy('listing_location');
    }

    public function getListingCat($isSingular = false)
    {
        return $this->getTaxonomy('listing_cat');
    }

    public function getListingTag($isSingular = false)
    {
        return $this->getTaxonomy('listing_tag');
    }

    public function getTags($isSingular = false)
    {
        return $this->getTaxonomy('listing_tag');
    }

    public function getLogo(): string
    {
        if (isset($this->aAtts['wilcity_logo_size']) && !empty($this->aAtts['wilcity_logo_size'])) {
            return GetSettings::getLogo($this->postID, $this->aAtts['wilcity_logo_size']);
        }

        if (isset($this->aAtts['logo_size']) && !empty($this->aAtts['logo_size'])) {
            return GetSettings::getLogo($this->postID, $this->aAtts['logo_size']);
        }

        return GetSettings::getLogo($this->postID);
    }

    public function getExcerpt()
    {
        $tagLine = GetSettings::getPostMeta($this->postID, 'tagline', '', '', $this->isFocus);

        if (!class_exists('Wiloke')) {
            return $tagLine;
        }

        if (empty($tagLine)) {
            $tagLine = Wiloke::contentLimit(
                WilokeThemeOptions::getOptionDetail('listing_excerpt_length'),
                $this->post,
                true,
                $this->post->post_content,
                true
            );

            $tagLine = strip_shortcodes($tagLine);
        }

        return $tagLine;
    }

    public function getTagLine()
    {
        return $this->getExcerpt();
    }

    public function getNavigationSettings()
    {
        if (empty($this->aNavigationSettings)) {
            $this->aNavigationSettings
                = GetSettings::getOptions(General::getSingleListingSettingKey('navigation', $this->postType), false,
                true
            );

            if (!empty($this->aNavigationSettings)) {
                $this->aNavigationSettings = ArrayCollectionFactory::set($this->aNavigationSettings)
                    ->magicKeyGroup('key')
                    ->output();
            }
        }

        return $this->aNavigationSettings;
    }

    public function getAddListingFields()
    {
        if (empty($this->aAddListingFields)) {
            $this->aAddListingFields = GetSettings::getOptions(
                General::getUsedSectionKey($this->postType), false, true
            );

            if (!empty($this->aAddListingFields)) {
                $this->aAddListingFields = ArrayCollectionFactory::set($this->aAddListingFields)
                    ->magicKeyGroup('key')
                    ->output();
            }
        }

        return $this->aAddListingFields;
    }

    public function setGalleryPreviewSize($size): void
    {
        $this->galleryPreviewSize = $size;
    }

    /**
     * @return array
     */
    public function getGallery(): array
    {
        $aItems = GetSettings::getPostMeta($this->postID, 'gallery', '', '', $this->isFocus);

        return GalleryHelper::gallerySkeleton($aItems, $this->galleryPreviewSize);
    }

    public function getOGallery(): array
    {
        return $this->getGallery();
    }

    public function getMenuOrder(): int
    {
        return absint($this->post->menu_order);
    }

    /**
     * @param $postID
     */
    public function setPostID($postID): void
    {
        $this->postID = absint($postID);
    }

    public function setPostType(): void
    {
        $this->postType = $this->post->post_type;
    }

    /**
     * @param $post
     *
     * @return $this
     */
    public function setPost($post)
    {
        $this->post = $post;
        $this->setPostType();
        $this->setPostID($post->ID);

        return $this;
    }

    public function getPostTitle(): string
    {
        return $this->post->post_title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getPostTitle();
    }

    public function getPostContent(): string
    {
        return do_shortcode($this->post->post_content);
    }

    public function getContent(): string
    {
        return $this->getPostContent();
    }

    public function getListingContent(): string
    {
        return $this->getPostContent();
    }

    /**
     * @return string
     */
    public function getPermalink(): ?string
    {
        return get_permalink($this->postID);
    }

    public function getParentID(): ?int
    {
        if ($postID = $this->getCache('parentID')) {
            return $postID;
        }
        $val = absint(wp_get_post_parent_id($this->post));
        $this->setCache('parentID', $val);

        return $val;
    }

    public function getID(): ?int
    {
        return absint($this->post->ID);
    }

    public function getPostID(): ?int
    {
        return absint($this->post->ID);
    }

    /**
     * @return int|null
     */
    public function getAuthorID(): ?int
    {
        return absint($this->post->post_author);
    }

    public function getPostType()
    {
        return $this->post->post_type;
    }

    public function getGroup()
    {
        return General::getPostTypeGroup($this->post->post_type);
    }

    public function getPostAuthor(): ?int
    {
        return absint($this->post->post_author);
    }

    public function getAuthorAvatar(): ?string
    {
        return User::getAvatar($this->post->post_author);
    }

    public function getAuthorName(): ?string
    {
        return User::getField('display_name', $this->post->post_author);
    }

    public function getCountShared(): int
    {
        return absint(GetSettings::getPostMeta($this->postID, 'count_shared', '', '', $this->isFocus));
    }

    public function getIsAuthor(): string
    {
        return $this->post->post_author == get_current_user_id() ? 'yes' : 'no';
    }

    public function getPostDate(): ?string
    {
        return date_i18n(get_option('date_format'), strtotime($this->post->post_date));
    }

    public function getDate(): ?string
    {
        return $this->getPostDate();
    }

    public function getPostLink(): ?string
    {
        return $this->getPermalink();
    }

    public function getLink()
    {
        return $this->getPermalink();
    }

    public function parseCustomShortcode($shortcode)
    {
        if (empty($shortcode)) {
            return '';
        }

        $shortcode = str_replace(['{{', '}}'], ['"', '"'], $shortcode);

        return trim(preg_replace_callback('/\s+/', function ($matched) {
            if (!empty($this->postID)) {
                return ' is_mobile="yes" post_id="' . $this->postID . '" ';
            }

            return ' is_mobile="yes" ';
        }, $shortcode, 1));
    }

    public function getSingleNavigationValue($fieldKey)
    {
        $this->getNavigationSettings();

        if (!isset($this->aNavigationSettings[$fieldKey])) {
            return false;
        } else {
            if (!isset($this->aNavigationSettings[$fieldKey]['content'])) {
                return '';
            }

            $rawContent = trim($this->aNavigationSettings[$fieldKey]['content']);
            if (empty($rawContent)) {
                return '';
            }

            $customShortcode = $this->parseCustomShortcode($rawContent);

            if (empty($customShortcode)) {
                return $this->aNavigationSettings[$fieldKey]['content'];
            }

            $rawParsedSC = do_shortcode($customShortcode);

            if (!is_array($rawParsedSC)) {
                if (Validation::isValidJson($rawParsedSC)) {
                    $content = Validation::getJsonDecoded();
                } else {
                    $content = $rawParsedSC;
                }
            } else {
                $content = $rawParsedSC;
            }

            if (isset($this->aAtts['is_mobile']) && $this->aAtts['is_mobile'] === 'yes') {
                if (function_exists('wilcityAppStripTags')) {
                    return wilcityAppStripTags($content);
                }
            }

            return $content;
        }
    }

    /**
     * @param $pluck
     * single_navigation is a sign of custom field in listing type
     * in_array($pluck, $this->aCustomFieldStore) is a sign of event
     *
     * @return bool
     */
    public function isCustomField($pluck)
    {
        return strpos($pluck, 'single_navigation') !== false || isset($this->aCustomFieldStore[$pluck]);
    }

    /**
     * @param  $aPluck
     * @param  $isFocus
     *
     * @return array
     */
    public function pluck($aPluck, $isFocus = false): array
    {
        $this->isFocus = true;

        $aItems = [];
        if (!empty($aPluck)) {
            foreach ($aPluck as $pluck) {
                $funcName = $this->generateCBFunction($pluck);

                $filterHook
                    = 'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/AbstractSkeleton/pluck/' . $pluck;

                if (has_filter($filterHook)) {
                    $aItems[$pluck] = apply_filters(
                        $filterHook,
                        '',
                        [
                            'pluck'   => $pluck,
                            'post'    => $this->post,
                            'postID'  => $this->postID,
                            'isFocus' => $isFocus,
                            'atts'    => $this->aAtts
                        ]
                    );
                } else {
                    if (is_callable([$this, $funcName])) {
                        if (!$isFocus && $this->hasCache($pluck) && !$this->hasExcludeCache($pluck)) {
                            $aItems[$pluck] = $this->getCache($pluck);
                        } else {
                            if ($this->isFuncHasArgs($funcName)) {
                                $aItems[$pluck] = $this->$funcName($pluck);
                            } else {
                                $aItems[$pluck] = $this->$funcName();
                            }
                        }

                        $this->setCache($pluck, $aItems[$pluck]);
                    } else {
                        // It's target of custom section
                        if ($this->isCustomField($pluck)) {
                            $aItems[$pluck] = $this->getSingleNavigationValue($pluck);
                        }

                        // Which means this is a custom section
                        if (isset($aItems[$pluck]) && $aItems[$pluck] !== false) {
                            $this->setCache($pluck, $aItems[$pluck]);
                        }
                    }
                }
            }
        }

        return $aItems;
    }
}
