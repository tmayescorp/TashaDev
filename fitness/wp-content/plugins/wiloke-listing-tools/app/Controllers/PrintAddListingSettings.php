<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Select;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Register\WilokeSubmissionConfiguration;
use WilokeListingTools\Models\BookingCom;
use WilokeSocialNetworks;
use WilokeThemeOptions;
use WilokeListingTools\Framework\Helpers\Submission;

trait PrintAddListingSettings
{
    protected        $aCaching = [];
    protected static $disableFieldType;
    protected        $aCustomFields
                               = [
            'image',
            'input',
            'textarea',
            'date_time',
            'select',
            'checkbox2',
            'group',
            'file',
            'multiple-checkbox'
        ];
    protected        $aDefaultValues
                               = [
            'video' => [
                'video_srcs' => [
                    [
                        'value' => ''
                    ]
                ]
            ]
        ];

    protected function getPlanSettingDetail($key, $format = 'string')
    {
        $val = false;
        if (isset($this->aPlanSettings[$key])) {
            $val = $this->aPlanSettings[$key];
            switch ($format) {
                case 'array':
                    $val = (array)$val;
                    break;
                case 'int':
                    $val = (int)$val;
                    break;
            }
        }

        return $val;
    }

    protected function addAdditionalToggle()
    {
        if (!isset($this->aPlanSettings['toggle_coupon']) || $this->aPlanSettings['toggle_coupon'] == 'enable') {
            return true;
        }

        $aCouponFields = [
            'coupon_title',
            'coupon_highlight',
            'coupon_popup_image',
            'coupon_description',
            'coupon_description',
            'coupon_code',
            'coupon_popup_description',
            'coupon_redirect_to'
        ];

        foreach ($aCouponFields as $key) {
            $this->aPlanSettings['toggle_' . $key] = 'disable';
        }
    }

    /**
     * Get terms of this post
     *
     * @param String $taxonomy
     * @param array $aSettings
     *
     * @return array
     */
    protected function getTerms(string $taxonomy = null, array $aSettings = [])
    {
        $maximum = isset($aSettings['maximum']) && !empty($aSettings['maximum']) ? absint($aSettings['maximum']) : 1;
        if ($maximum === 1) {
            $oTerm = GetSettings::getLastPostTerm($this->listingID, $taxonomy);

            if (empty($oTerm)) {
                return null;
            }

            $aResponse = [
                'id'    => $oTerm->term_id,
                'label' => $oTerm->name
            ];

            if ($oTerm->taxonomy === 'listing_tag') {
                $aResponse['belongsTo'] = TermSetting::getTagsBelongsTo($oTerm->term_id);
            }

            return $aResponse;
        }

        $aPostTerms = GetSettings::getPostTerms($this->listingID, $taxonomy);
        if (!is_array($aPostTerms)) {
            return [];
        }

        $aValues = [];
        foreach ($aPostTerms as $oTerm) {
            $aValue = [
                'id'    => $oTerm->term_id,
                'label' => $oTerm->name
            ];

            if ($oTerm->taxonomy === 'listing_tag') {
                $aValue['belongsTo'] = TermSetting::getTagsBelongsTo($oTerm->term_id);
            }

            $aValues[] = $aValue;
        }

        return $aValues;
    }

    public function getSectionClaimListingStatus($aSection)
    {
        if (empty($this->listingID)) {
            return [
                'listing_claim_status' => ''
            ];
        }

        return [
            'listing_claim_status' => GetSettings::getPostMeta($this->listingID, 'claim_status')
        ];
    }

    protected function getSectionBookingcombannercreator($aSection = [])
    {
        if (empty($this->listingID)) {
            return [];
        }
        $bookingID = BookingCom::getCreatorIDByParentID($this->listingID);
        if (empty($bookingID)) {
            return [];
        }

        $aValues = [];
        foreach ($aSection['fieldGroups'] as $key => $aField) {
            $fieldVal = BookingCom::getBookingComCreatorVal($bookingID,
                str_replace('bookingcombannercreator_', '', $key));
            if ($aField['type'] === 'wil-uploader') {
                $imgID = BookingCom::getBookingComCreatorVal($bookingID, 'bannerImg_id');
                $aValues[$key] = [
                    'fileName' => get_the_title($bookingID),
                    'src'      => $fieldVal,
                    'id'       => $imgID
                ];
            } else {
                $aValues[$key] = $fieldVal;
            }
        }

        return $aValues;
    }

    protected function buildPostsSelectTree($aPostIDs, $aSettings)
    {
        $maximum = isset($aSettings['maximum']) ? absint($aSettings['maximum']) : 1;

        return Select::buildPostsSelectTree($aPostIDs, $aSettings['selectValueFormat'], $maximum);
    }

    protected function getListingTypeRelationships(array $aSettings = [], array $aSection = [])
    {
        $isMultiple = isset($aSettings['maximum']) && $aSettings['maximum'] > 1;
        $aPostIDs = get_post_meta($this->listingID, 'wilcity_custom_' . $aSection['key'], !$isMultiple);

        return $this->buildPostsSelectTree($aPostIDs, $aSettings);
    }

    protected function getMyProducts(array $aSettings = [], array $aSection = [])
    {
        $aPostIDs = GetSettings::getPostMeta($this->listingID, $aSettings['key']);

        return $this->buildPostsSelectTree($aPostIDs, $aSettings);
    }

    protected function getMyRoom(array $aSettings = [], array $aSection = [])
    {
        $aPostIDs = GetSettings::getPostMeta($this->listingID, $aSettings['key']);

        return $this->buildPostsSelectTree($aPostIDs, $aSettings);
    }

    protected function getMyPosts(array $aSettings = [], array $aSection = [])
    {
        $aPostIDs = GetSettings::getPostMeta($this->listingID, $aSettings['key']);

        return $this->buildPostsSelectTree($aPostIDs, $aSettings);
    }

    protected function getListingCat(array $aSettings = [])
    {
        return $this->getTerms('listing_cat', $aSettings); // bz it gets from section class
    }

    protected function getListingTag(array $aSettings = [])
    {
        return $this->getTerms('listing_tag', $aSettings); // bz it gets from section class
    }

    protected function getListingLocation(array $aSettings = [])
    {
        return $this->getTerms('listing_location', $aSettings); // bz it gets from field class
    }

    /**
     * Undocumented function
     *
     * @param [string] $key Enter in plan key
     *
     * @return boolean
     */
    protected function isDisableOnPlan($key)
    {
        if ($key == 'video') {
            $key = 'videos';
        }

        return isset($this->aPlanSettings['toggle_' . $key]) && $this->aPlanSettings['toggle_' . $key] == 'disable';
    }

    protected function excludeSocialNetworks()
    {
        if (isset($_GET['listing_type']) && !empty($_GET['listing_type'])) {
            //			$aListingSettings = GetSettings::getOptions();
            $aListingSettings = GetSettings::getOptions(General::getUsedSectionKey($_GET['listing_type']), false, true);
        }
    }

    private function buildAddListingCache($aInfo, $postType)
    {
        wp_localize_script('wilcity-empty', 'WILCITY_ADDLISTING', $aInfo);
    }

    /**
     * We must parse Social network to fit wil-pickup-and-set field
     *
     * @return array
     */
    protected function parseSocialNetworkField($aFieldSettings)
    {
        if (!class_exists('\WilokeSocialNetworks')) {
            return [];
        }

        $aSocialNetworks = WilokeSocialNetworks::getPickupSocialOptions();
        if (isset($aFieldSettings['excludingSocialNetworks']) && is_array($aFieldSettings['excludingSocialNetworks'])) {
            $aSocialNetworks = array_filter($aSocialNetworks, function ($aSocial) use ($aFieldSettings) {
                return !in_array($aSocial['id'], $aFieldSettings['excludingSocialNetworks']);
            });
        }

        return array_values($aSocialNetworks);
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    protected function getPriceRangeOptions()
    {
        return wilokeListingToolsRepository()->get('addlisting:aPriceRange');
    }

    protected function getRestaurantSkeleton()
    {
        return wilokeListingToolsRepository()->get('addlisting:restaurantItem');
    }

    protected function getListingTitle($aSettings = [])
    {
        $title = get_the_title($this->listingID);
        //        if (isset($aSettings['fieldGroups'])) {
        //            return [
        //                'listing_title' => $title
        //            ];
        //        }

        return $title;
    }

    protected function getCustomButtonButtonName($aSettings = [])
    {
        return GetSettings::getPostMeta($this->listingID, 'button_name');
    }

    protected function getCustomButtonButtonLink($aSettings = [])
    {
        return GetSettings::getPostMeta($this->listingID, 'button_link');
    }

    protected function getCustomButtonButtonIcon($aSettings = [])
    {
        return GetSettings::getPostMeta($this->listingID, 'button_icon');
    }

    protected function getListingContent($aSettings = [])
    {
        $content = nl2br(get_post_field('post_content', $this->listingID));

        $content
            = preg_replace_callback('/(<!-- \/?wp:heading([^>]+)>)|(<!-- \/?wp:paragraph -->)|(<!-- \/?wp:list -->)|\n/',
            function () {
                return '';
            }, $content);

        return preg_replace_callback('#<br \/>(\s*<br \/>)+#', function () {
            return '<br />';
        }, $content);
    }

    /**
     * @param int $attachmentID
     * @param string $size
     *
     * @return array
     */
    protected function getImage(int $attachmentID, $size = 'thumbnail')
    {
        $src = wp_get_attachment_image_url($attachmentID, $size);
        if (empty($src)) {
            return [];
        }
        $aInfo = [];
        $aInfo['fileName'] = get_the_title($attachmentID);
        $aInfo['id'] = $attachmentID;
        $aInfo['src'] = $src;

        return $aInfo;
    }

    /**
     * @param array $aSettings
     *
     * @return array
     */
    protected function getFeaturedImage($aSettings = [])
    {
        $featuredImgID = get_post_thumbnail_id($this->listingID);

        if (empty($featuredImgID)) {
            return [];
        }
        return $this->getImage(absint($featuredImgID));
    }

    protected function getLogo($aSettings = [])
    {
        $logoID = GetSettings::getPostMeta($this->listingID, 'logo_id', '', 'int');
        if (empty($logoID)) {
            return [];
        }

        return $this->getImage(absint($logoID));
    }

    protected function getCoverImage($aSettings = [])
    {
        $coverImageID = GetSettings::getPostMeta($this->listingID, 'cover_image_id', '', 'int');
        if (empty($coverImageID)) {
            return [];
        }

        return $this->getImage(absint($coverImageID));
    }

    protected function getSocialNetworks($aSettings)
    {
        $aSocialNetworks = GetSettings::getPostMeta($this->listingID, 'social_networks');
        if (empty($aSocialNetworks)) {
            return [];
        }
        $aUsedSocials = $aSettings['pickupOptions'];
        $aValues = [];
        foreach ($aUsedSocials as $aSocial) {
            if (!isset($aSocialNetworks[$aSocial['id']]) || empty($aSocialNetworks[$aSocial['id']])) {
                continue;
            }

            $aValues[] = [
                'id'    => $aSocial['id'],
                'value' => $aSocialNetworks[$aSocial['id']]
            ];
        }

        return $aValues;
    }

    protected function getSectionCoupon(array $aSection = [])
    {
        $aCoupon = GetSettings::getPostMeta($this->listingID, 'coupon');
        if (empty($aCoupon)) {
            return [];
        }

        foreach ($aCoupon as $key => $val) {
            switch ($key) {
                case 'popup_image_id':
                    unset($aCoupon[$key]);
                    $aCoupon['coupon_popup_image'] = $this->getImage($val);
                    break;
                case 'popup_image':
                    break;
                case 'expiry_date':
                    unset($aCoupon[$key]);
                    $aCoupon['coupon_' . $key] = absint($val) *
                        1000; // js timestamp always bigger than 1k php timestamp
                    break;
                default:
                    unset($aCoupon[$key]);
                    $aCoupon['coupon_' . $key] = $val;
                    break;
            }
        }

        return $aCoupon;
    }

    /**
     * @param $hour
     *
     * @return false|string
     */
    protected function convertHourToJSFormat($hour)
    {
        return date('Hi', strtotime($hour));
    }

    protected function getSectionBusinessHours($aSettings = [])
    {
        $aValue['hourMode'] = GetSettings::getPostMeta($this->listingID, 'hourMode');
        $aValue['timeFormat'] = BusinessHours::getTimeFormat($this->listingID);
        $aBusinessHours = GetSettings::getBusinessHours($this->listingID);

        if (!empty($aBusinessHours)) {
            $aParsedBusinessHours = array_reduce(
                $aBusinessHours,
                function ($aAccomulator, $aItem) {
                    if (empty($aItem['firstOpenHour']) || empty($aItem['firstCloseHour'])) {
                        $aAccomulator = array_merge(
                            $aAccomulator,
                            [
                                $aItem['dayOfWeek'] => [
                                    [
                                        'isOpen' => false,
                                        'open'   => '',
                                        'close'  => '',
                                        'id'     => uniqid('business_hours_')
                                    ]
                                ]
                            ]
                        );
                    } else {
                        $fOpenHours = $this->convertHourToJSFormat($aItem['firstOpenHour']);
                        $fCloseHours = $this->convertHourToJSFormat($aItem['firstCloseHour']);
                        if ($fOpenHours === $fCloseHours) {
                            $fOpenHours = '24hrs';
                            $fCloseHours = '24hrs';
                        }
                        if (empty($aItem['secondOpenHour']) || empty($aItem['secondCloseHour'])) {
                            $aAccomulator = array_merge(
                                $aAccomulator,
                                [
                                    $aItem['dayOfWeek'] => [
                                        [
                                            'open'   => $fOpenHours,
                                            'close'  => $fCloseHours,
                                            'isOpen' => $aItem['isOpen'] === 'yes',
                                            'id'     => uniqid('business_hours_')
                                        ]
                                    ]
                                ]
                            );
                        } else {
                            $sOpenHours = $this->convertHourToJSFormat($aItem['secondOpenHour']);
                            $sCloseHours = $this->convertHourToJSFormat($aItem['secondCloseHour']);
                            if ($sOpenHours === $sCloseHours) {
                                $sOpenHours = '24hrs';
                                $sCloseHours = '24hrs';
                            }

                            $aAccomulator = array_merge(
                                $aAccomulator,
                                [
                                    $aItem['dayOfWeek'] => [
                                        [
                                            'open'   => $fOpenHours,
                                            'close'  => $fCloseHours,
                                            'isOpen' => $aItem['isOpen'] === 'yes',
                                            'id'     => uniqid('business_hours_')
                                        ],
                                        [
                                            'open'   => $sOpenHours,
                                            'close'  => $sCloseHours,
                                            'isOpen' => $aItem['isOpen'] === 'yes',
                                            'id'     => uniqid('business_hours_')
                                        ],
                                    ]
                                ]
                            );
                        }
                    }

                    return $aAccomulator;
                },
                []
            );
            $aValue['operating_times'] = $aParsedBusinessHours;
        } else {
            $aValue['operating_times'] = [];
        }

        return ['settings' => $aValue];
    }

    protected function getVideoSrcs($aSettings = [])
    {
        $aVideos = GetSettings::getPostMeta($this->listingID, $aSettings['key']);
        if (empty($aVideos)) {
            return [
                [
                    'value' => ''
                ]
            ];
        }

        return array_map(function ($item) {
            return [
                'value' => $item['src']
            ];
        }, $aVideos);
    }

    /**
     * Get Listing Meta
     *
     * @param [type] $key
     * @param [type] $valueFormat
     *
     * @return mixed
     */
    protected function getPostMeta($key, array $aSettings = [])
    {
        if (isset($aSettings['valueFormat'])) {
            // bz it's object format for js only. We can still use array format for PHP
            $valueFormat = $aSettings['valueFormat'] === 'object' ? 'array' : $aSettings['valueFormat'];
        } else {
            $valueFormat = '';
        }

        switch ($aSettings['type']) {
            case 'wil-uploader':
                if ($aSettings['maximum'] > 1) {
                    $aImages = GetSettings::getPostMeta($this->listingID, $key);
                    if (!empty($aImages)) {
                        foreach ($aImages as $imgID => $src) {
                            $val[] = $this->getImage(absint($imgID));
                        }
                    } else {
                        $val = [];
                    }
                } else {
                    $imgID = GetSettings::getPostMeta($this->listingID, $key . '_id');
                    if (empty($imgID)) {
                        $val = [];
                    } else {
                        $val = $this->getImage(absint($imgID));
                    }
                }
                break;
            case 'wil-datepicker':
                $val = GetSettings::getPostMeta($this->listingID, $key, '', $valueFormat);

                if (!empty($val)) {
                    $timezone = GetSettings::getPostMeta($this->listingID, $key . '_timezone', '', $valueFormat);

                    if (!empty($timezone)) {
                        $timestampOffset = Time::diffTimestamp($timezone);
                        $val = $val - $timestampOffset;
                    }
                }
                $val = empty($val) ? 0 : $val * 1000;
                break;
            default:
                $val = GetSettings::getPostMeta($this->listingID, $key, '', $valueFormat);
                break;
        }

        return $val;
    }

    protected function getCustomImage(array $aSection = []): array
    {
        $aValues = [];
        $prefix = 'wilcity_custom_' . $aSection['key'] . '_';
        foreach ($aSection['fieldGroups'] as $key => $fieldInfo) {
            $aValues[$key] = $this->getPostMeta($prefix . $key, $fieldInfo);
        }

        return $aValues;
    }

    protected function getCustomFile(array $aSection = []): array
    {
        $aValues = [];
        $prefix = 'wilcity_custom_' . $aSection['key'] . '_';
        foreach ($aSection['fieldGroups'] as $key => $fieldInfo) {
            $aRawFiles = $this->getPostMeta($prefix . $key, $fieldInfo);

            if (empty($aRawFiles)) {
                continue;
            }

            foreach ($aRawFiles as $id => $src) {
                $aValues[$key][] = [
                    'id'  => $id,
                    'src' => $src
                ];
            }
        }

        return $aValues;
    }

    protected function convertOptionsToPairKeyVal($options)
    {
        $values = [];
        foreach ($options as $option) {
            if (empty($option['id'])) {
                continue;
            }
            $values[$option['id']] = $option['label'];
        }

        return $values;
    }

    protected function buildSelectTreeValues($values, $fieldInfo)
    {
        $convertedOptions = $this->convertOptionsToPairKeyVal($fieldInfo['options']);
        if (isset($fieldInfo['maximum']) && $fieldInfo['maximum'] > 1) {
            if (isset($fieldInfo['selectValueFormat']) && $fieldInfo['selectValueFormat'] === 'object') {
                foreach ($values as $val) {
                    $aValues[] = [
                        'id'    => $val,
                        'label' => $convertedOptions[$val]
                    ];
                }
            } else {
                $aValues = $values;
            }
        } else {
            if (isset($fieldInfo['selectValueFormat']) && $fieldInfo['selectValueFormat'] === 'object') {
                $aValues = [
                    'id'    => $values,
                    'label' => $convertedOptions[$values]
                ];
            } else {
                $aValues = $values;
            }
        }

        return $aValues;
    }

    protected function getCustomSelect(array $aSection = [])
    {
        $aValues = [];
        $prefix = 'wilcity_custom_';
        foreach ($aSection['fieldGroups'] as $key => $fieldInfo) {
            $values = $this->getPostMeta($prefix . $aSection['key'], $fieldInfo);
            if (!empty($values)) {
                $aValues = $this->buildSelectTreeValues($values, $fieldInfo);
            }
        }

        if (!empty($aValues)) {
            return [
                $key => $aValues
            ];
        }

        return '';
    }

    protected function getCustomMultiplecheckbox(array $aSection = [])
    {
        $aValues = [];
        foreach ($aSection['fieldGroups'] as $key => $fieldInfo) {
            $aValues = $this->getCustomSelect($aSection);
        }

        return $aValues;
    }

    protected function getCustomField(array $aSection = [])
    {
        $aValues = [];
        $prefix = 'wilcity_custom_';

        foreach ($aSection['fieldGroups'] as $key => $fieldInfo) {
            $aValues[$key] = $this->getPostMeta($prefix . $aSection['key'], $fieldInfo);
        }

        return $aValues;
    }

    protected function parseGroupItemValues($aValue, $aFieldSkeletons)
    {
        $aFieldSkeletons = array_reduce(
            $aFieldSkeletons,
            function ($aAccomulator, $aItem) {
                return array_merge(
                    $aAccomulator,
                    [
                        $aItem['key'] => $aItem
                    ]
                );
            },
            []
        );

        foreach ($aValue as $order => $aItems) {
            foreach ($aItems as $itemKey => $itemVal) {
                if (isset($aFieldSkeletons[$itemKey]['type'])) {
                    $aField = $aFieldSkeletons[$itemKey];
                    switch ($aField['type']) {
                        case 'wil-uploader':
                            if (empty($itemVal)) {
                                $aValue[$order][$itemKey] = [];
                            } else {
                                if (isset($aField['maximum']) && $aField['maximum'] > 1) {
                                    $aGallery = [];
                                    foreach ($itemVal as $imgID => $val) {
                                        $aGallery[] = $this->getImage(absint($imgID));
                                    }
                                    $aValue[$order][$itemKey] = $aGallery;
                                } else {
                                    if ($itemKey == 'gallery') {
                                        if (is_array($itemVal)) {
                                            $id = array_keys($itemVal)[0];
                                            $aValue[$order][$itemKey] = $this->getImage($id);
                                        }
                                    } else {
                                        if (!isset($aItems[$itemKey . '_id'])) {
                                            $aValue[$order][$itemKey] = [];
                                        } else {
                                            $aValue[$order][$itemKey] = $this->getImage(absint($aItems[$itemKey .
                                            '_id']));
                                        }
                                    }
                                }
                            }
                            break;
                        case 'wil-select-tree':
                            $aValue[$order][$itemKey] = $this->buildSelectTreeValues($itemVal,
                                $aFieldSkeletons[$itemKey]);
                            break;
                    }
                } else {
                    unset($aValue[$order][$itemKey]);
                }
            }
        }

        return $aValue;
    }

    protected function getGroupValues(array $aSection)
    {
        $key = 'wilcity_group_' . $aSection['key'];
        $aValue = GetSettings::getPostMeta($this->listingID, $key, '', 'array');

        if (empty($aValue)) {
            return [];
        }
        $aFieldSkeletons = $aSection['fieldGroups']['settings']['fieldsSkeleton'];

        if (isset($aValue['items']) && is_array($aValue['items'])) {
            $aValue['items'] = $this->parseGroupItemValues($aValue['items'], $aFieldSkeletons);
        }

        return ['settings' => [$aValue]];
    }

    protected function getSectionRestaurantMenu($aSection): array
    {
        $numberOfFields = GetSettings::getPostMeta($this->listingID, 'number_restaurant_menus');
        $aRestaurantMenus = [];
        if (!empty($numberOfFields)) {
            for ($i = 0; $i < $numberOfFields; $i++) {
                $aItems = GetSettings::getPostMeta($this->listingID, 'restaurant_menu_group_' . $i, '', 'array');

                $aRestaurantMenus[$i] = [
                    'group_title'       => GetSettings::getPostMeta($this->listingID, 'group_title_' . $i),
                    'group_description' => GetSettings::getPostMeta($this->listingID, 'group_description_' . $i),
                    'group_icon'        => GetSettings::getPostMeta($this->listingID, 'group_icon_' . $i),
                    'items'             => !empty($aItems) ? $this->parseGroupItemValues($aItems,
                        $aSection['fieldGroups']['restaurant_menu']['fieldsSkeleton']) : []
                ];
            }
        }

        return ['restaurant_menu' => $aRestaurantMenus];
    }

    protected function getSectionEventCalendar($aSection)
    {
        $aEventData = GetSettings::getEventSettings($this->listingID);
        if (empty($aEventData)) {
            $aEventCalendar = [
                'specifyDays' => '',
                'frequency'   => '',
                'date'        => [
                    'starts' => '',
                    'ends'   => ''
                ]
            ];
        } else {
            $aEventCalendar = [
                'specifyDays' => $aEventData['specifyDays'],
                'frequency'   => $aEventData['frequency'],
                'date'        => [
                    'starts' => date($this->getEventDateFormat(), strtotime($aEventData['startsOn'])),
                    'ends'   => date($this->getEventDateFormat(), strtotime($aEventData['endsOn'])),
                ]
            ];
        }

        return [
            'event_calendar' => $aEventCalendar
        ];
    }

    protected function getSectionEventBelongsToListing($aSection)
    {
        $postParent = wp_get_post_parent_id($this->listingID);
        if (empty($postParent)) {
            return '';
        }

        return [
            'event_belongs_to_listing' => [
                'id'    => $postParent,
                'label' => get_the_title($postParent)
            ]
        ];
    }

    protected function getDefaultValue($sectionKey, $aSection = [])
    {
        if ($sectionKey === 'business_hours') {
            return [
                'settings' => [
                    'hourMode'   => '',
                    'timeFormat' => (int)WilokeThemeOptions::getOptionDetail('timeformat')
                ]
            ];
        }

        $cbFunc = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/AddListingController/getDefaultValue/callback/' .
            $aSection['type'],
            null,
            $sectionKey,
            $aSection
        );

        if (is_array($cbFunc) && method_exists($cbFunc[0], $cbFunc[1])) {
            return call_user_func_array($cbFunc, [null, $sectionKey, $aSection]);
        }

        return isset($this->aDefaultValues[$sectionKey]) ? $this->aDefaultValues[$sectionKey] : '';
    }

    /**
     * Get Listing Value
     *
     */
    private function getResults()
    {
        $aValues = [];
        if (empty($this->listingID)) {
            foreach ($this->aSections as $order => $aSection) {
                $value = $this->getDefaultValue($aSection['key'], $aSection);
                if (!empty($value)) {
                    $aValues[$aSection['key']] = $value;
                }

                $this->aSections[$order] = apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/Controllers/AddListingController/section/' .
                    $aSection['type'],
                    $aSection
                );
            }

            return $aValues;
        }

        foreach ($this->aSections as $order => $aSection) {
            $aSectionValues = [];
            if (isset($aSection['isGroup'])) {
                $aValues[$aSection['key']] = $this->getGroupValues($aSection);
            } elseif (isset($aSection['isCustomSection']) && in_array($aSection['type'], $this->aCustomFields)) {
                $cbFunc = 'getCustom' . ucfirst(str_replace('-', '', $aSection['type']));

                if (method_exists($this, $cbFunc)) {
                    $aValues[$aSection['key']] = $this->$cbFunc($aSection);
                } else {
                    $aValues[$aSection['key']] = $this->getCustomField($aSection);
                }
            } else {
                $cbFunc = apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/AddListingController/getResults/callback/' .
                    $aSection['type'],
                    [__CLASS__, General::generateCallbackFunction($aSection['key'], 'getSection')],
                    $aSection
                );
                if ((method_exists($cbFunc[0], $cbFunc[1]))) {
                    $aValues[$aSection['key']] = call_user_func_array($cbFunc, [$aSection, $this->listingID]);
                } else {
                    foreach ($aSection['fieldGroups'] as $groupKey => $aSettings) {
                        $cbFunc = General::generateCallbackFunction($groupKey);

                        if (method_exists($this, $cbFunc)) {
                            $aSectionValues[$groupKey] = $this->$cbFunc($aSettings, $aSection);
                        } else {
                            if (isset($aSettings['isTax'])) {
                                $aSectionValues[$groupKey] = $this->getTerms($aSettings['taxonomy'], $aSettings);
                            } else {
                                $aSectionValues[$groupKey] = $this->getPostMeta($groupKey, $aSettings);
                            }
                        }
                    }
                    $aValues[$aSection['key']] = $aSectionValues;
                }
            }

            $this->aSections[$order] = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/AddListingController/section/' . $aSection['type'],
                $aSection
            );
        }


        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/add-listing-settings/results',
            $aValues,
            $this->listingID,
            $this->planID,
            $this->aSections
        );
    }

    protected function getAddress(array $aSettings = [])
    {
        $aValue = ['address' => '', 'lat' => '', 'lng' => ''];
        $address = GetSettings::getAddress($this->listingID, false);
        $aLatLng = GetSettings::getLatLng($this->listingID);

        if (!empty($address)) {
            $aValue['address'] = $address;
        }

        if (!empty($aLatLng)) {
            $aValue['lat'] = $aLatLng['lat'];
            $aValue['lng'] = $aLatLng['lng'];
        } else {
            if (isset($aSettings['defaultLocation']) && !empty($aSettings['defaultLocation'])) {
                $aParseDefaultLatLng = explode(',', $aSettings['defaultLocation']);
                $aValue['lat'] = trim($aParseDefaultLatLng[0]);
                $aValue['lng'] = trim($aParseDefaultLatLng[1]);
            }
        }

        return $aValue;
    }

    protected function redefineFieldStatus($key)
    {
        return isset($this->aPlanSettings['toggle_' . $key]) ? $this->aPlanSettings['toggle_' . $key] : '';
    }

    protected function redefineMaximumItems($key)
    {
        if ($key === 'video_srcs' || $key === 'videos') {
            $key = 'maximumVideos';
        } else if ($key === 'gallery') {
            $key = 'maximumGalleryImages';
        } else {
            $key = 'maximum_' . $key;
        }

        return isset($this->aPlanSettings[$key]) && $this->aPlanSettings[$key] !== '' ? $this->aPlanSettings[$key] :
            false;
    }

    protected function reDefineValueFormat(array $aSettings = [])
    {
        if (!isset($aSettings['valueFormat'])) {
            return '';
        }

        if (isset($aSettings['maximum'])) {
            if ($aSettings['maximum'] == 1 && $aSettings['valueFormat'] === 'object') {
                return 'array';
            }
        }

        return $aSettings['valueFormat'];
    }

    protected function parseCustomFieldArgs($aFieldSkeletons)
    {
        $aParsed = [];
        switch ($aFieldSkeletons['type']) {
            case 'wil-select-tree':
                //            case 'wil-multiple-checkbox':
                $aParsed['options'] = General::parseSelectFieldOptions(
                    $aFieldSkeletons['options'],
                    'wil-select-tree'
                );
                break;
        }

        return $aParsed;
    }

    protected function parseSections($listingType = '')
    {
        if (WilokeThemeOptions::getOptionDetail('addlisting_unchecked_features_type') === 'hidden') {
            self::$disableFieldType = 'toggle';
        } else {
            self::$disableFieldType = 'fieldStatus';
        }

        $this->aSections = $this->getAvailableFields($listingType);

        foreach ($this->aSections as $sectionOrder => $aSection) {
            $isDirectlyParsed = true;

            switch ($aSection['key']) {
                case 'claim_listing_status':
                    $aSection['fieldGroups']['listing_claim_status']['isCustomField'] = 'yes';
                    $isDirectlyParsed = false;
                    break;
                case 'price_range':
                    $this->aSections[$sectionOrder]['fieldGroups']['price_range']['options']
                        = $this->getPriceRangeOptions();
                    break;
                case 'restaurant_menu':
                    $aMenuSkeleton = $this->getRestaurantSkeleton();
                    if ($val = $this->getPlanSettingDetail('maximum_restaurant_gallery_images', 'int')) {
                        $aMenuSkeleton['gallery']['maximum'] = $val;
                    }

                    if ($val = $this->getPlanSettingDetail('maximumItemsInMenu', 'int')) {
                        $this->aSections[$sectionOrder]['fieldGroups']['restaurant_menu']['maximumChildren'] = $val;
                    } else {
                        $this->aSections[$sectionOrder]['fieldGroups']['restaurant_menu']['maximumChildren'] = 10;
                    }

                    if ($val = $this->getPlanSettingDetail('maximumRestaurantMenus', 'int')) {
                        $this->aSections[$sectionOrder]['fieldGroups']['restaurant_menu']['maximum'] = $val;
                    } else {
                        $this->aSections[$sectionOrder]['fieldGroups']['restaurant_menu']['maximum'] = 10;
                    }

                    $this->aSections[$sectionOrder]['fieldGroups']['restaurant_menu']['fieldsSkeleton']
                        = array_values($aMenuSkeleton);
                    break;
                case 'coupon':
                    $timeFormat = Time::getTimeFormat();
                    $timeFormat = empty($timeFormat) ? 'h:m A' : str_replace('i', 'm', $timeFormat);
                    $this->aSections[$sectionOrder]['fieldGroups']['coupon_expiry_date']['format']
                        = $aSection['fieldGroups']['coupon_expiry_date']['dateFormat'] . ' ' . $timeFormat;
                    break;
                case 'listing_address':
                    $mapType = WilokeThemeOptions::getOptionDetail('map_type');
                    $this->aSections[$sectionOrder]['fieldGroups']['address']['mapType'] = $mapType;
                    if ($mapType === 'mapbox') {
                        $this->aSections[$sectionOrder]['fieldGroups']['address']['mapStyle']
                            = WilokeThemeOptions::getOptionDetail('mapbox_style');
                        $this->aSections[$sectionOrder]['fieldGroups']['address']['accessToken']
                            = WilokeThemeOptions::getOptionDetail('mapbox_api');
                    } else {
                        $this->aSections[$sectionOrder]['fieldGroups']['address']['accessToken']
                            = WilokeThemeOptions::getOptionDetail('general_google_api');
                        $this->aSections[$sectionOrder]['fieldGroups']['address']['language']
                            = WilokeThemeOptions::getOptionDetail('general_google_language');
                        $this->aSections[$sectionOrder]['fieldGroups']['address']['restrict']
                            = WilokeThemeOptions::getOptionDetail('general_search_restriction');
                    }
                    break;
                case 'business_hours':
                    if (empty($aSection['fieldGroups']['settings']['stdOpeningTime'])) {
                        $this->aSections[$sectionOrder]['fieldGroups']['settings']['stdOpeningTime']
                            = WilokeThemeOptions::getOptionDetail('listing_default_opening_hour');
                        $this->aSections[$sectionOrder]['fieldGroups']['settings']['stdClosedTime']
                            = WilokeThemeOptions::getOptionDetail('listing_default_closed_hour');
                    }
                    break;
                default:
                    $isDirectlyParsed = false;
                    break;
            }

            if (isset($aSection['fieldGroups']) && is_array($aSection['fieldGroups']) &&
                !empty($aSection['fieldGroups'])) {
                $addRequiredToSection = count($aSection['fieldGroups']) === 1;

                foreach ($aSection['fieldGroups'] as $groupKey => $aVal) {
                    if (!isset($aVal['toggle']) || $aVal['toggle'] != 'disable') {
                        $planKey = isset($aVal['taxonomy']) ? $aVal['taxonomy'] : $groupKey;

                        $planStatus = $this->redefineFieldStatus($planKey);

                        if ($planStatus) {
                            if (self::$disableFieldType === 'toggle' && $planStatus === 'disable') {
                                unset($this->aSections[$sectionOrder]['fieldGroups'][$groupKey]);
                                $isDirectlyParsed = true;
                            } else {
                                if ($groupKey === 'my_taxonomy') {
                                    $this->aSections[$sectionOrder]['fieldStatus'] = $planStatus;
                                } else {
                                    $this->aSections[$sectionOrder]['fieldGroups'][$groupKey][self::$disableFieldType]
                                        = $planStatus;
                                }
                                $isDirectlyParsed = false;
                            }
                        }
                    }
                    $maximum = $this->redefineMaximumItems($groupKey);
                    if ($maximum !== false) {
                        $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['maximum'] = absint($maximum);
                    }

                    if (!$isDirectlyParsed) {
                        switch ($aVal['type']) {
                            case 'wil-pickup-and-set':
                                if ($aVal['key'] === 'social_networks') {
                                    $aParsedSocial
                                        = $this->parseSocialNetworkField($aSection['fieldGroups']['social_networks']);
                                    $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['pickupOptions']
                                        = $aParsedSocial;
                                    $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['label']
                                        = $aVal['socialLinkLabel'];
                                    $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['addItemBtnName']
                                        = $aVal['btnName'];
                                    $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['pickupItemLabel']
                                        = $aVal['socialNameLabel'];
                                    unset($this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['socialNameLabel']);
                                    unset($this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['btnName']);
                                    unset($this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['socialLinkLabel']);
                                    unset($this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['social_networks']);
                                }
                                break;
                            case 'wil-group':
                                if (isset($aVal['fieldsSkeleton']) && is_array($aVal['fieldsSkeleton'])) {
                                    foreach ($aVal['fieldsSkeleton'] as $fieldOrder => $aFieldSkeleton) {
                                        if ($aFieldSkeleton['type'] === 'wil-select-tree') {
                                            $parseOptions
                                                = General::parseSelectFieldOptions($aFieldSkeleton['options'],
                                                'wil-select-tree');
                                            if (!empty($parseOptions)) {
                                                $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['fieldsSkeleton'][$fieldOrder]['options']
                                                    = $parseOptions;
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'wil-datepicker':
                                unset($aVal['showTimePanel']);
                                $dateFormat = get_option('date_format');
                                $dateFormat = str_replace('-', '/', $dateFormat);

                                if (isset($aVal['showTimePanel']) && $aVal['showTimePanel'] == 'no') {
                                    unset($aVal['showTimePanel']);
                                    $aVal['format'] = $dateFormat;
                                } else {
                                    $aVal['format'] = $dateFormat . ' ' . Time::getJSTimeFormat();
                                }

                                $aVal['dateValueType'] = 'format';

                                $this->aSections[$sectionOrder]['fieldGroups'][$groupKey] = $aVal;
                                break;
                            default:
                                if (isset($aVal['isCustomField'])) {
                                    if (in_array($aVal['type'], ['wil-select-tree', 'wil-multiple-checkbox']) &&
                                        isset($aVal['options'])) {

                                        $parseOptions = General::parseSelectFieldOptions($aVal['options'],
                                            'wil-select-tree');
                                        if (!empty($parseOptions)) {
                                            $this->aSections[$sectionOrder]['fieldGroups'][$groupKey]['options']
                                                = $parseOptions;
                                        }
                                    }
                                }
                                break;
                        }
                    }

                    if ($addRequiredToSection && isset($aVal['isRequired'])) {
                        $this->aSections[$sectionOrder]['isRequired'] = $aVal['isRequired'];
                    }
                }
                // If all fields of this section have been disabled, We will remove this section as well
                if (empty($this->aSections[$sectionOrder]['fieldGroups'])) {
                    unset($this->aSections[$sectionOrder]);
                }
            }
        }

        $this->aSections
            = apply_filters('wilcity/filter/wiloke-listing-tools/app/AddListingController/parseSections/getSections',
            $this->aSections);

        return $this->aSections;
    }

    private function getAddListingSettings($listingId, $planId = '', $listingType = '')
    {
        $this->listingID = $listingId;
        if (empty($this->listingID)) {
            $aPlans = Submission::getAddListingPostTypeKeys();
            if (count($aPlans) == 1 && GetWilokeSubmission::isFreeAddListing()) {
                $listingType = $aPlans[0];
            }
        } else {
            $listingType = get_post_type($this->listingID);
        }

        $aInfo = [
            'listingType' => $listingType
        ];

        if (!$planId) {
            $this->aPlanSettings = [];

            if (GetWilokeSubmission::isFreeAddListing()) {
                $aPlans = GetWilokeSubmission::getAddListingPlans($listingType . '_plans');
                if (is_array($aPlans)) {
                    $planID = end($aPlans);
                    $this->aPlanSettings = GetSettings::getPostMeta($planID, 'add_listing_plan');
                }
            }
        } else {
            $this->aPlanSettings = GetSettings::getPostMeta($planId, 'add_listing_plan');
        }

        if (!General::isElementorPreview()) {
            $this->parseSections($listingType);
        }

        /**
         * We need to put it before sections render
         */
        $aResults = $this->getResults();
        $aGroupInfo = General::getPostTypeSettings($listingType);

        return [
            'planID'                => $planId,
            'listingID'             => $this->listingID,
            'wilcityAddListingCsrf' => esc_js(wp_create_nonce('wilcity-submit-listing')),
            'listingType'           => $aInfo['listingType'],
            'sections'              => General::unSlashDeep(array_values($this->aSections)),
            // when unset an item from an array, it will convert this array to object type
            'results'               => General::unSlashDeep($aResults),
            'timeFormat'            => [
                [
                    'id'    => 12,
                    'label' => esc_html__('12-Hour Format', 'wiloke-listing-tools'),
                ],
                [
                    'id'    => 24,
                    'label' => esc_html__('24-Hour Format', 'wiloke-listing-tools'),
                ]
            ],
            'oSocialNetworks'       => class_exists('\WilokeSocialNetworks') ?
                WilokeSocialNetworks::$aSocialNetworks : [],
            'businessHours'         => wilokeListingToolsRepository()->get('addlisting:businessHours'),
            'groupInfo'             => [
                'post_type' => $aGroupInfo['postType'],
                'endpoint'  => $aGroupInfo['endpoint'],
                'name'      => $aGroupInfo['name']
            ]
        ];
    }

    public function printAddListingSettings()
    {
        global $post;
        if (
            empty($post) || GetWilokeSubmission::getField('addlisting') != $post->ID
        ) {
            return false;
        }

        $listingType = isset($_REQUEST['listing_type']) ? esc_js($_REQUEST['listing_type']) : 'listing';
        $planId = isset($_REQUEST['planID']) && !empty($_REQUEST['planID']);
        $listingId = isset($_REQUEST['postID']) ? $_REQUEST['postID'] : 0;

        wp_localize_script(
            'wilcity-empty',
            strtoupper(WILCITY_WHITE_LABEL) . '_ADDLISTING_INLINE',
            $this->getAddListingSettings($listingId, $planId, $listingType)
        );
    }

    public function printAddListingSettingPlaceholder()
    {
        wp_localize_script(
            'wilcity-empty',
            'WILCITY_ADDLISTING_INLINE',
            []
        );
    }
}
