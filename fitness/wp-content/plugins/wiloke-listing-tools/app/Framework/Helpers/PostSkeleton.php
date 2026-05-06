<?php

namespace WilokeListingTools\Framework\Helpers;

use WILCITY_SC\SCHelpers;
use Wiloke;
use WilokeHelpers;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Models\Coupon;
use WilokeListingTools\Models\EventModel;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\UserModel;
use WilokeThemeOptions;
use WP_Post;

final class PostSkeleton extends AbstractSkeleton
{
    /**
     * Post relationship
     */
    public function getPosts()
    {
        $aPosts = GetSettings::getPostMeta($this->post->ID, 'my_posts', '', '', $this->isFocus);

        if (empty($aPosts)) {
            return false;
        }

        $aPostData = [];

        $listing = $this->post;

        foreach ($aPosts as $postID) {
            if (get_post_status($postID) !== 'publish' || get_post_type($postID) !== 'post') {
                return false;
            }

            $post = get_post($postID);
            $this->setPost($post);

            $aPostData[$postID] = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/PostSkeleton/getPosts',
                [
                    'ID'            => absint($post->ID),
                    'title'         => $this->getTitle(),
                    'permalink'     => $this->getPermalink(),
                    'featuredImage' => $this->getFeaturedImage(),
                    'excerpt'       => $this->getExcerpt()
                ],
                $postID,
                $post
            );
        }

        $this->setPost($listing);

        return $aPostData;
    }

    public function getOReviews()
    {
        $aReviews = [];
        if (ReviewController::isEnableRating($this->post)) {
            $averageReview = GetSettings::getPostMeta($this->postID, 'average_reviews', '', '', $this->isFocus);
            if (empty($averageReview)) {
                $aReviews = false;
            } else {
                $aReviews = [];
                $aReviews['average'] = floatval($averageReview);
                $aReviews['mode'] = ReviewController::getMode($this->getPostType());
                $aReviews['quality'] = ReviewMetaModel::getReviewQualityString($averageReview, $this->getPostType());
            }
        }

        return $aReviews;
    }

    public function getMyEvents()
    {
        return $this->getEvents();
    }

    /**
     * Event Listing Relationship
     */
    public function getEvents()
    {
        $aArgs = [
            'post_type'      => 'event',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'post_parent'    => $this->postID
        ];

        $query = new \WP_Query(
            SearchFormController::buildQueryArgs($aArgs)
        );

        $aEventRelationship = [];
        if ($query->have_posts()) {
            $aEventPluck = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/PostSkeleton/getEvents',
                [
                    'ID',
                    'postTitle',
                    'isAds',
                    'postLink',
                    'tagLine',
                    'phone',
                    'price',
                    'priceRange',
                    'oCalendar',
                    'oFeaturedImg',
                    'isMyFavorite',
                    'totalFavorites',
                    'hostedBy',
                    'oAuthor', // fixedappold
                    'oAddress'
                ]
            );
            while ($query->have_posts()) {
                $query->the_post();
                $aEventRelationship[$query->post->ID]
                    = App::get('EventSkeleton')->getSkeleton($query->post, $aEventPluck);
            }
        } else {
            $aEventRelationship = false;
        }

        return $aEventRelationship;
    }

    public function getRestaurantMenu()
    {
        $wrapperClasses
            = isset($this->aAtts['restaurant_wrapper_classes']) ? $this->aAtts['restaurant_wrapper_classes'] : '';
        $numberOfMenus = GetSettings::getPostMeta($this->postID, 'number_restaurant_menus', '', '', $this->isFocus);

        $hasZero = GetSettings::getPostMeta($this->postID, 'restaurant_menu_group_0');
        if (empty($numberOfMenus) && !empty($hasZero)) {
            $numberOfMenus = 1;
        }

        if (empty($numberOfMenus)) {
            return false;
        }

        $aMenus = [];
        for ($i = 0; $i < $numberOfMenus; $i++) {
            $aMenu = GetSettings::getPostMeta($this->postID, 'restaurant_menu_group_' . $i, '', '', $this->isFocus);
            if (!empty($aMenu)) {
                foreach ($aMenu as $index => $aItem) {
                    if ((!isset($aItem['title']) || empty($aItem['title']))
                        && (!isset($aItem['description']) || empty($aItem['description']))) {
                        $aMenu = false;
                        continue;
                    }

                    if (isset($aItem['description'])) {
                        $aMenu[$index]['description'] = preg_replace("/\r\n|\r|\n/", '', $aItem['description']);
                    }

                    if (isset($aItem['price']) && !empty($aItem['price'])) {
                        if (is_numeric($aItem['price'])) {
                            $aMenu[$index]['price'] = GetWilokeSubmission::renderPrice($aItem['price']);
                        }
                    }

                    if (isset($aItem['gallery']) && is_array($aItem['gallery'])) {
                        foreach ($aItem['gallery'] as $id => $thumbnail) {
                            $large = wp_get_attachment_image_url($id, 'large');
                            if (!empty($large)) {
                                $aMenu[$index]['gallery'][$id] = $large;
                            }
                        }
                    }
                }

                if ($aMenu) {
                    $aMenus['restaurant_menu_group_' . $i]['items'] = $aMenu;
                    $aMenus['restaurant_menu_group_' . $i]['wrapper_class']
                        = 'wilcity_restaurant_menu_group_' . $i . ' ' . $wrapperClasses;
                    $aMenus['restaurant_menu_group_' . $i]['group_title']
                        = GetSettings::getPostMeta($this->postID, 'group_title_' . $i, '', '', $this->isFocus);
                    $aMenus['restaurant_menu_group_' . $i]['group_description']
                        = GetSettings::getPostMeta($this->postID, 'group_description_' . $i, '', '', $this->isFocus);
                    $aMenus['restaurant_menu_group_' . $i]['group_icon']
                        = GetSettings::getPostMeta($this->postID, 'group_icon_' . $i, '', '', $this->isFocus);
                }
            }
        }

        return $aMenus;
    }

    /**
     * @return array|bool
     */
    public function getMyPosts()
    {
        return $this->getPosts();
    }

    public function getExternalButton()
    {
        $buttonLink = GetSettings::getPostMeta($this->postID, 'button_link', '', '', $this->isFocus);

        if (!empty($buttonLink)) {
            $aResponse['name'] = GetSettings::getPostMeta($this->postID, 'button_name', '', '', $this->isFocus);
            $aResponse['link'] = $buttonLink;
            $aResponse['icon'] = GetSettings::getPostMeta($this->postID, 'button_icon', '', '', $this->isFocus);

            return $aResponse;
        }

        return false;
    }

    public function getOButton()
    {
        return $this->getExternalButton();
    }

    public function getReviewCategoriesStatistic(): array
    {
        if ($aReviewDetails = $this->getCache('review_details_statistic')) {
            return $aReviewDetails;
        }

        $aStatistic = ReviewMetaModel::getAverageReviewCategories($this->postID);

        $this->setCache('review_details_statistic', $aStatistic);

        return $aStatistic;
    }

    public function getAverageRating(): float
    {
        return floatval(GetSettings::getAverageRating($this->post->ID));
    }

    public function getQualityRating(): string
    {
        return ReviewMetaModel::getReviewQualityString($this->getAverageRating(), $this->post->post_type);
    }

    public function getReviewQuality(): string
    {
        return $this->getQualityRating();
    }

    public function getReviewMode(): int
    {
        return $this->getModeRating();
    }


    public function getAverageReview(): float
    {
        return $this->getAverageRating();
    }

    public function getIsEnableReview(): bool
    {
        return ReviewModel::isEnabledReview($this->postType);
    }

    public function getModeRating(): int
    {
        return absint(GetSettings::getOptions(General::getReviewKey('mode', $this->post->post_type), false, true));
    }

    public function getOTermFooter()
    {
        $aFooterSettings = GetSettings::getOptions(General::getSingleListingSettingKey('footer_card',
            $this->postType), false, true);
        $taxonomy = isset($aFooterSettings['taxonomy']) ? $aFooterSettings['taxonomy'] : 'listing_cat';

        $oTermFooter = WilokeHelpers::getTermByPostID($this->postID, $taxonomy);
        if (!$oTermFooter) {
            $aTermFooter = false;
        } else {
            $aTermFooter['name'] = $oTermFooter->name;
            $aTermFooter['link'] = get_term_link($oTermFooter->term_id);
            $aTermFooter['oIcon'] = WilokeHelpers::getTermOriginalIcon($oTermFooter);
        }

        return $aTermFooter;
    }

    public function getOBusinessHours()
    {
        if (BusinessHours::isEnableBusinessHour($this->postID)) {
            $aBusinessHours = BusinessHours::getCurrentBusinessHourStatus($this->postID);
            if ($aBusinessHours['status'] == 'day_off') {
                $aBusinessHours['class'] = ' color-quaternary';
            }
        } else {
            $aBusinessHours = false;
        }

        return $aBusinessHours;
    }

    public function getBusinessHours()
    {
        return BusinessHours::getAllBusinessHours($this->postID);
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

    public function getEventData()
    {
        $aEventCalendarSettings = GetSettings::getEventSettings($this->post->ID);
        $aListing['start'] = [
            'day'  => date_i18n(get_option('date_format'), $aEventCalendarSettings['startTimestamp']),
            'hour' => Time::renderTimeFormat($aEventCalendarSettings['startTimestamp'], $this->post->ID)
        ];

        $aListing['end'] = [
            'day'  => date_i18n(get_option('date_format'), ($aEventCalendarSettings['endTimestamp'])),
            'hour' => Time::renderTimeFormat($aEventCalendarSettings['endTimestamp'], $this->post->ID)
        ];

        $aListing['interested'] = SCHelpers::renderInterested($this->post, [], true);
        $aListing['hosted_by'] = SCHelpers::renderHostedBy($this->post, [], true);
        $aListing['hostedByName'] = GetSettings::getEventHostedByName($this->post);
        $aListing['hostedByURL'] = GetSettings::getEventHostedByUrl($this->post);
        $aListing['hostedByTarget'] = GetSettings::getEventHostedByTarget($aListing['hostedByURL']);
        $aListing['startAt'] = date_i18n('d', $aEventCalendarSettings['startTimestamp']);
        $aListing['startsOn'] = date_i18n('M', $aEventCalendarSettings['endTimestamp']);

        return $aListing;
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

        $aValues[] = [
            'type'         => 'favorite',
            'iconType'     => $this->postType === 'event' ? 'star' : 'love',
            'isMyFavorite' => $this->getIsMyFavorite()
        ];

        return apply_filters('wilcity/filter/wiloke-listing-tools/header-card', $aValues, $this->post);
    }

    public function getFooterCard()
    {
        $aFooter = GetSettings::getOptions(General::getSingleListingSettingKey('footer_card', $this->postType), false,
            true);
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

        if ($this->isPlanAllowed('toggle_business_hours')) {
            $aValues[] = [
                'type'     => 'business-hours',
                'icon'     => '',
                'value'    => $this->getBusinessHours(),
                'position' => 'left'
            ];
        }

        if ($this->isPlanAllowed('toggle_gallery')) {
            $aValues[] = [
                'type'     => 'gallery',
                'icon'     => 'la la-search-plus',
                'value'    => $this->getGallery(),
                'position' => 'right'
            ];
        }

        $aValues[] = [
            'type'         => 'favorite',
            'iconType'     => $this->postType === 'event' ? 'star' : 'love',
            'isMyFavorite' => $this->getIsMyFavorite(),
            'position'     => 'right'
        ];

        return apply_filters('wilcity/filter/wiloke-listing-tools/footer-card', $aValues, $this->post);
    }

    public function getCoupon()
    {
        $aCoupon = Coupon::getAllCouponInfo($this->postID);

        return is_array($aCoupon) ? $aCoupon : [];
    }

    public function getHeader()
    {
        $vrSrc = GetSettings::getPostMeta($this->post->ID, 'vr_src');
        return [
            'coverImg' => $this->getCoverImg(),
            'vrSrc'    => empty($vrSrc) ? '' : $vrSrc
        ];
    }

    public function getSkeleton($post, $aPluck, $aAtts = [], $isFocus = false)
    {
        if (empty($aPluck)) {
            $aPluck = [
                'title',
                'permalink',
                'postType',
                'logo',
                'excerpt',
                'oReviews',
                'featuredImage',
                'oAddress',
                'phone',
                'oTermFooter',
                'oBusinessHours',
                'gallery',
                'isMyFavorite',
                'price',
                'priceRange',
                'headerCard',
                'bodyCard',
                'footerCard',
                'isTopOfSearch'
            ];
        } else {
            $aPluck = is_array($aPluck) ? $aPluck : explode(',', $aPluck);
            $aPluck = array_map(function ($key) {
                return $key;
            }, $aPluck);
        }

        if (!in_array('group', $aPluck)) {
            $aPluck[] = 'group';
        }

        $isIgnoreMenuOrder = isset($aAtts['ignoreMenuOrder']) && $aAtts['ignoreMenuOrder'] === true;
        if (!$isIgnoreMenuOrder) {
            $aPluck[] = 'menuOrder';
        }

        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $this->aAtts = $aAtts;
        $this->setPost($post);
        $this->setFuncHasArgs('getTaxonomy');

        /**
         * @hooked WilcityRedis\Controllers@removeCachingPluckItems
         */
        $aPluck = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/PostSkeleton/aPluck',
            $aPluck,
            $post,
            $this->aAtts
        );

        $aListing = $this->pluck($aPluck, $isFocus);

        /**
         * @hooked WilcityRedis\Controllers@getPostSkeleton 5
         * @hooked WilcityRedis\Controllers@setPostSkeleton 10
         */
        $aListing = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/PostSkeleton/aListing',
            $aListing,
            $post,
            $this->aAtts
        );

        if ($isIgnoreMenuOrder) {
            $aListing['menuOrder'] = 0;
        }

        return $aListing;
    }
}
