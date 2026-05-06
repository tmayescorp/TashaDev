<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\TransformAddListingData\TransformAddListingToBackEnd;
use WilokeListingTools\Controllers\TransformAddListingData\TransformAddListingToBackEndFactory;
use WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SearchFormSkeleton;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeListingTools\Frontend\User;

trait Validation
{
    protected $aListingData
                                        = [
            'post_title'  => 'No Title 1',
            'post_status' => 'unpaid'
        ];
    protected $aCoupon;
    protected $aRawFeaturedImage;
    protected $aRawCoverImg;
    protected $parentListingID;
    protected $aRawLogo;
    protected $myRoom;
    protected $myPosts;
    protected $aTags                    = [];
    protected $category                 = [];
    protected $location                 = [];
    protected $email;
    protected $website;
    protected $phone;
    protected $aGoogleAddress           = [];
    protected $aPriceRange              = [];
    protected $aSocialNetworks          = [];
    protected $aBusinessHours           = [];
    protected $aGallery                 = [];
    protected $aRawGallery              = [];
    protected $aGeneralData             = [];
    protected $aEventCalendar           = [];
    protected $aHostedBy                = [];
    protected $aCustomButton            = [];
    protected $aVideos                  = [];
    protected $aCustomGroupCollection   = [];
    protected $aCustomSections;
    protected $aRestaurantMenu          = [];
    protected $singlePrice;
    protected $aBookingComBannerCreator = [];
    protected $aMyProducts              = [];
    protected $aListingRelationships    = [];
    protected $aAddListingSettings;
    /**
     * @var \WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton $oAddListingSkeleton
     */
    protected $oAddListingSkeleton;
    protected $aCustomTaxonomies = [];
    /**
     * @var RetrieveController
     */
    protected $oRetrieveValidation;
    protected $aRestData = [];
    protected $aResolvedToggleKeys
                         = [
            'video' => 'videos'
        ];

    protected function isAllowSaving($key)
    {
        return !isset($this->aPlanSettings[$key]) || $this->aPlanSettings[$key] !== 'disable';
    }

    /**
     * @param string $key
     *
     * @return array|string
     */
    public function getRestData($key = '')
    {
        if (!empty($key)) {
            return $this->aRestData[$key] ?? '';
        }

        return $this->aRestData;
    }

    protected function getToggleKey($sectionKey)
    {
        $key = $this->aResolvedToggleKeys[$sectionKey] ?? $sectionKey;

        return 'toggle_' . $key;
    }

    protected function sureItemsDoNotExceededPlan($planKey, $aItems)
    {
        if (!isset($this->aPlanSettings[$planKey]) || empty($this->aPlanSettings[$planKey])) {
            return $aItems;
        }

        return array_splice($aItems, 0, $this->aPlanSettings[$planKey]);
    }

    protected function cleanListingCat($aSection)
    {
        if (!isset($aSection['listing_cat']) || empty($aSection['listing_cat'])) {
            $this->category = [];

            return true;
        }

        if (isset($aSection['listing_cat']['id'])) {
            $this->category = absint($aSection['listing_cat']['id']);

            return true;
        }

        if (is_array($aSection['listing_cat'])) {
            array_map(
                function ($aItem) {
                    $this->category[] = absint($aItem['id']);
                },
                $aSection['listing_cat']
            );
        } else {
            $this->category = [$aSection['listing_cat']];
        }

        return true;
    }

    protected function cleanListingTag($aSection)
    {
        if (!isset($aSection['listing_tag']) || empty($aSection['listing_tag'])) {
            $this->aTags = [];
        }

        if (isset($aSection['listing_tag']['id'])) {
            $this->aTags = [absint($aSection['listing_tag']['id'])];
        }

        array_map(
            function ($aItem) {
                $this->aTags[] = absint($aItem['id']);
            },
            $aSection['listing_tag']
        );

        return true;
    }

    protected function cleanRestaurantMenu($aSection)
    {
        if (!$this->isAllowSaving('toggle_restaurant_menu')) {
            return false;
        }
        if (!isset($aSection['restaurant_menu']) || empty($aSection['restaurant_menu'])) {
            return false;
        }

        $aMenus = $this->sureItemsDoNotExceededPlan('maximumRestaurantMenus', $aSection['restaurant_menu']);
        foreach ($aMenus as $menuOrder => $aMenu) {
            if (isset($aMenu['items']) && !empty($aMenu['items'])) {
                foreach ($aMenu['items'] as $itemOrder => $aItem) {
                    if (isset($aItem['gallery']) && !empty($aItem['gallery'])) {
                        $aGallery
                            = $this->sureItemsDoNotExceededPlan('maximumGalleryImages', $aItem['gallery']);
                        $aMenu['items'][$itemOrder]['gallery'] = Submission::convertGalleryToBackendFormat($aGallery);
                    }
                    $aMenus[$menuOrder]['items']
                        = $this->sureItemsDoNotExceededPlan('maximumItemsInMenu', $aMenu['items']);
                }
            }
        }

        $this->aRestaurantMenu = ValidationHelper::deepValidation($aMenus);

        return false;
    }

    protected function cleanSocialNetworks($aValue)
    {
        if (empty($aValue)) {
            return [];
        }

        foreach ($aValue as $aSocial) {
            $key = (string)ValidationHelper::deepValidation($aSocial['id']);
            $this->aSocialNetworks[$key] = ValidationHelper::deepValidation($aSocial['value']);
        }
    }

    protected function cleanCoupon($aSection)
    {
        if (Submission::isPlanSupported($this->planID, 'toggle_coupon')) {
            foreach ($aSection as $key => $val) {
                $key = str_replace('coupon_', '', $key);
                if ($key === 'expiry_date') {
                    $this->aCoupon[$key] = ValidationHelper::deepValidation($val, 'absint');
                    if (!empty($this->aCoupon[$key])) {
                        $this->aCoupon[$key]
                            = $this->aCoupon[$key] / 1000; // JS timestamp always bigger 1k PHP timestamp
                    }
                } else {
                    if ($key == 'popup_image') {
                        $aPopupImg = ValidationHelper::deepValidation($val);
                        if (isset($aPopupImg['id'])) {
                            $this->aCoupon['popup_image_id'] = $aPopupImg['id'];
                            $this->aCoupon['popup_image'] = $aPopupImg['src'];
                        }
                    } else {
                        $this->aCoupon[$key] = ValidationHelper::deepValidation($val);
                    }
                }
            }
        }
    }

    protected function cleanListingTitle(array $aSection)
    {
        if (isset($aSection['listing_title']) && !empty($aSection['listing_title'])) {
            if (isset($aSection['listing_title']['listing_title'])) {
                $listingTitle = $aSection['listing_title']['listing_title'];
                $this->aListingData['post_title'] = ValidationHelper::deepValidation($listingTitle);
            } else {
                $this->aListingData['post_title'] = ValidationHelper::deepValidation($aSection['listing_title']);
            }
        }
    }

    protected function cleanListingContent(array $aSection)
    {
        if (isset($aSection['listing_content']) && !empty($aSection['listing_content'])) {
            $this->aListingData['post_content'] = $aSection['listing_content'];

            return true;
        }

        return false;
    }

    private function sureAllChildrenIsNotEmpty($aValue, $sectionKey, $fieldKey)
    {
        $status = true;
        switch ($fieldKey) {
            case 'address':
                if (!is_array($aValue)) {
                    $status = false;
                } else {
                    foreach ($aValue as $val) {
                        if (empty($val)) {
                            $status = false;
                            break;
                        }
                    }
                }
                break;
            default:
                $status = !empty($aValue);
                break;
        }

        return $status;
    }


    protected function validateAddress($aValue, $aFieldInfo)
    {
        if (!isset($aValue['address']) || empty($aValue['address'])) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('The address is required', 'wiloke-listing-tools')
            ];
        }

        if (!isset($aValue['address']['address']) || empty($aValue['address']['address'])) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('The address is required', 'wiloke-listing-tools')
            ];
        }

        if (!isset($aValue['address']['lat']) || empty($aValue['address']['lat'])) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('The latitude is required', 'wiloke-listing-tools')
            ];
        }

        if (!isset($aValue['address']['lng']) || empty($aValue['address']['lng'])) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('The longitude is required', 'wiloke-listing-tools')
            ];
        }

        return ['status' => 'success'];
    }

    protected function surePassConditional($sectionKey, $taxonomy = '')
    {
        if (!empty($taxonomy)) {
            $toggleKey = $this->getToggleKey($taxonomy);
        } else {
            $toggleKey = $this->getToggleKey($sectionKey);
        }

        if (!Submission::isPlanSupported($this->planID, $toggleKey)) {
            return true;
        }

        $aFieldGroups = ArrayCollectionFactory::set($this->aAddListingSettings)
            ->deepPluck($sectionKey . '->fieldGroups')
            ->output();
        if (empty($aFieldGroups)) {
            return true;
        }

        foreach ($aFieldGroups as $fieldKey => $aFieldInfo) {
            if (!Submission::isPlanSupported($this->planID, $this->getToggleKey($fieldKey))) {
                if (isset($this->aData[$fieldKey]) && !empty($this->aData[$fieldKey])) {
                    $this->oRetrieveValidation->error(
                        [
                            sprintf(esc_html__('%s is not supported by this plan', 'wiloke-listing-tools'), isset
                            ($aFieldInfo['label']) ? $aFieldInfo['label'] : $aFieldInfo['key'])
                        ]
                    );
                }
                continue;
            }

            $toggle = ArrayCollectionFactory::set($aFieldInfo)->pluck('toggle')->format('string')->output('enable');
            $required = ArrayCollectionFactory::set($aFieldInfo)->pluck('isRequired')->format('bool')->output();

            if (($toggle != 'disable') && $required) {

                $cbFunc = General::generateCallbackFunction($fieldKey, 'validate');

                if (method_exists($this, $cbFunc)) {
                    $aValidation = $this->$cbFunc($this->aData[$sectionKey], $aFieldInfo);
                    if ($aValidation['status'] == 'error') {
                        $this->oRetrieveValidation->error(
                            [
                                $aValidation['msg']
                            ]
                        );
                    }
                } else {
                    if (
                        !isset($this->aData[$sectionKey]) || !isset($this->aData[$sectionKey][$fieldKey]) ||
                        !$this->sureAllChildrenIsNotEmpty($this->aData[$sectionKey][$fieldKey], $sectionKey, $fieldKey)
                    ) {
                        $isValid = apply_filters(
                            'wiloke-listing-tools/filter/app/Controllers/Validation/surePassConditional/' . $fieldKey,
                            false,
                            $sectionKey,
                            $aFieldInfo,
                            $this->aData
                        );

                        if (!$isValid) {
                            $this->oRetrieveValidation->error(
                                [
                                    sprintf(esc_html__('%s is required', 'wiloke-listing-tools'),
                                        isset($aFieldInfo['label']) ?
                                            $aFieldInfo['label'] : $aFieldInfo['key'])
                                ]
                            );
                        }
                    }
                }
            }
        }
        do_action('wiloke/wiloke-listing-tools/app/Controllers/Validation/after/surePassConditional', $aFieldGroups,
            $this->aData);

        return true;
    }

    protected function cleanGeneralData(array $aSection, $sectionKey = "")
    {
        foreach ($aSection as $key => $val) {
            $type = ArrayCollectionFactory::set($this->aAddListingSettings)->deepPluck($sectionKey . '->type')
                ->output();

            switch ($type) {
                case 'custom_taxonomy':
                    $taxonomy = $this->oAddListingSkeleton->getFieldParam(
                        $sectionKey,
                        'fieldGroups->my_taxonomy->taxonomy'
                    );

                    if (empty($val)) {
                        $this->aCustomTaxonomies[$taxonomy] = [];
                    } else {
                        $maximum = $this->oAddListingSkeleton->getFieldParam(
                            $sectionKey, 'fieldGroups->my_taxonomy->maximum'
                        );

                        if ($maximum > 1) {
                            $aTermIDs = array_slice($val, 0, $maximum);
                            foreach ($aTermIDs as $aTerm) {
                                $this->aCustomTaxonomies[$taxonomy][] = absint($aTerm['id']);
                            }
                        } else {
                            $this->aCustomTaxonomies[$taxonomy] = [absint($val['id'])];
                        }
                    }
                    break;
                case 'header':
                case 'listing_title':
                    if ($key === 'listing_title') {
                        $this->cleanListingTitle(['listing_title' => $val]);
                    } else {
                        $this->aGeneralData[$key] = ValidationHelper::deepValidation($val);
                    }
                    break;
                case 'contact_info':
                    if ($key === 'social_networks') {
                        $this->cleanSocialNetworks($val);
                    } else {
                        $this->aGeneralData[$key] = ValidationHelper::deepValidation($val);
                    }
                    break;
                case 'listing_type_relationships':
                    if (empty($val)) {
                        $this->aListingRelationships[$sectionKey] = [];
                    }

                    if (isset($val['id'])) {
                        $this->aListingRelationships[$sectionKey][] = absint($val['id']);
                    } else {
                        foreach ($val as $aItem) {
                            $this->aListingRelationships[$sectionKey][] = absint($aItem['id']);
                        }
                    }
                    break;
                case 'group':
                    // supporting 1 deep currently
                    if (ArrayCollectionFactory::set($val)->pluck('0->items->0')) {
                        $aRawItems = ArrayCollectionFactory::set($val)
                            ->deepPluck('0->items')
                            ->output();
                        $aGeneralData
                            = ArrayCollectionFactory::set($val)->pluck('0')->except('items')->output();
                        $this->aCustomGroupCollection[$sectionKey]
                            = ValidationHelper::deepValidation($aGeneralData);
                        $aFieldSettings
                            = ArrayCollectionFactory::set($this->aAddListingSettings)
                            ->deepPluck($sectionKey .
                                '->fieldGroups->settings->fieldsSkeleton')
                            ->magicKeyGroup('key')
                            ->output();
                        $aItemVals = [];

                        foreach ($aRawItems as $rawItemOrder => $aRawItemVals) {
                            foreach ($aRawItemVals as $rawItemKey => $rawItemVal) {
                                $builtFieldVal = TransformAddListingToBackEndFactory::set(
                                    ArrayCollectionFactory::set($aFieldSettings)
                                        ->deepPluck($rawItemKey . '->type')
                                        ->output(),
                                    ArrayCollectionFactory::set($aFieldSettings)
                                        ->deepPluck($rawItemKey . '->maximum')
                                        ->output(100)
                                )->input($rawItemVal)->withKey($rawItemKey);

                                $aItemVals = array_merge($aItemVals, $builtFieldVal);
                            }
                            $this->aCustomGroupCollection[$sectionKey]['items'][$rawItemOrder] = $aItemVals;
                        }
                    } else {
                        $this->aCustomGroupCollection[$sectionKey] = '';
                    }
                    break;
                case 'file':
                    $sectionFileKey = $sectionKey . '_' . $key;
                    if (empty($val)) {
                        $this->aCustomSections[$sectionFileKey] = [];
                    } else {
                        $aFiles = [];
                        $maximum = $this->oAddListingSkeleton->getFieldParam(
                            $sectionKey,
                            'fieldGroups->files->maximum',
                            1
                        );

                        if ($maximum > count($val)) {
                            $aValues = array_slice($val, 0, $maximum);
                        } else {
                            $aValues = $val;
                        }

                        foreach ($aValues as $aRawFile) {
                            if (isset($aRawFile['id'])) {
                                if (current_user_can('administrator') || (get_post_field('post_author',
                                            $aRawFile['id']) == get_current_user_id())
                                ) {
                                    $aFiles[intval($aRawFile['id'])] = esc_url($aRawFile['src']);
                                }
                            }
                        }

                        if ($maximum == 1) {
                            if (!empty($aFiles)) {
                                $aFilesToArrValues = array_values($aFiles);
                                $this->aCustomSections[$sectionFileKey] = $aFilesToArrValues[0];
                            } else {
                                $this->aCustomSections[$sectionFileKey] = "";
                            }
                        } else {
                            $this->aCustomSections[$sectionFileKey] = $aFiles;
                        }
                    }
                    break;
                case 'image':
                    if ($key === 'image') {
                        $imgIDKey = $sectionKey . '_' . $key . '_id';
                        $imgKey = $sectionKey . '_' . $key;
                        if (isset($val['id'])) {
                            $this->aCustomSections[$imgIDKey]
                                = ValidationHelper::deepValidation($val['id']);
                            $this->aCustomSections[$imgKey]
                                = ValidationHelper::deepValidation($val['src']);
                        } else {
                            $this->aCustomSections[$imgIDKey] = "";
                            $this->aCustomSections[$imgKey] = "";
                        }
                    } else {
                        $key = $sectionKey . '_link_to';
                        if (!empty($val)) {
                            $this->aCustomSections[$key] = ValidationHelper::deepValidation($val);
                        } else {
                            $this->aCustomSections[$key] = "";
                        }
                    }
                    break;
                case 'date_time':
                    if (!empty($val)) {
                        if (empty($val)) {
                            $this->aCustomSections[$sectionKey] = '';
                        } else if (!is_numeric($val)) {
                            if (strpos($val, '/')) {
                                $val = str_replace('/', '-', $val);
                                $this->aCustomSections[$sectionKey] = strtotime(trim($val));
                                break;
                            }

                            if (preg_match("/(AM|PM|am|pm)$/", $val)) {
                                $this->aCustomSections[$sectionKey] = strtotime(trim($val));
                                break;
                            }

                            $clue = strpos($val, '+') !== false ? '+' : '-';
                            $aParseDate = explode($clue, $val);

                            if (isset($aParseDate[1])) {
                                $timezone = 'UTC' . trim($clue) . $aParseDate[1];
                                $this->aCustomSections[$sectionKey . '_timezone'] = $timezone;
                            }
                            $this->aCustomSections[$sectionKey] = strtotime(trim($aParseDate[0]));
                        }
                    }
                    break;
                default:
                    if ($this->oAddListingSkeleton->getFieldParam($sectionKey, 'isCustomSection')) {
                        $type = $this->oAddListingSkeleton->getFieldParam($sectionKey, 'type');
                        if (in_array($type, ['select'])) {
                            if (empty($val)) {
                                $this->aCustomSections[$sectionKey] = '';
                            } else {
                                $maximum = $this->oAddListingSkeleton->getFieldParam(
                                    $sectionKey,
                                    'fieldGroups->settings->maximum',
                                    1
                                );
                                $val = ValidationHelper::deepValidation($val);

                                if ($maximum > 1) {
                                    $aValues = array_slice($val, 0, $maximum);
                                    foreach ($aValues as $aValue) {
                                        if (isset($aValue['id'])) {
                                            $cleanedVal = $aValue['id'];
                                        } else {
                                            $cleanedVal = $aValue;
                                        }
                                        $this->aCustomSections[$sectionKey][] = $cleanedVal;
                                    }
                                } else {
                                    if (isset($val['id'])) {
                                        $cleanedVal = $this->aCustomSections[$sectionKey] = $val['id'];
                                    } else {
                                        $cleanedVal = $this->aCustomSections[$sectionKey] = $val;
                                    }
                                    $this->aCustomSections[$sectionKey] = $cleanedVal;
                                }
                            }
                        } elseif ($type === 'multiple-checkbox') {
                            if (empty($val)) {
                                $this->aCustomSections[$sectionKey] = '';
                            } else {
                                $this->aCustomSections[$sectionKey]
                                    = ValidationHelper::deepValidation($val);
                            }
                        } else {
                            $this->aCustomSections[$sectionKey] = ValidationHelper::deepValidation($val);
                        }
                    } else {
                        $this->aRestData[$sectionKey][$key] = ValidationHelper::deepValidation($val);
                    }
                    break;
            }
        }
    }

    protected function cleanFeaturedImage(array $aSection, $sectionKey)
    {
        if (!isset($aSection['featured_image']) || !is_array($aSection['featured_image'])) {
            $this->aRawFeaturedImage = [];
        }

        $this->aRawFeaturedImage = ValidationHelper::deepValidation($aSection['featured_image']);
    }

    protected function cleanListingAddress($aSection, $sectionKey)
    {
        if (
            isset($aSection['address']) &&
            !empty($aSection['address']) && !empty($aSection['address']['lat']) &&
            !empty($aSection['address']['lng'])
        ) {
            $this->aGoogleAddress['address'] = ValidationHelper::deepValidation(
                $aSection['address']['address']
            );

            $this->aGoogleAddress['latLng']
                = ValidationHelper::deepValidation($aSection['address']['lat']) . ',' .
                ValidationHelper::deepValidation($aSection['address']['lng']);
        }
        if (!empty($aSection['listing_location'])) {
            if (isset($aSection['listing_location']['id'])) {
                $this->location = [absint($aSection['listing_location']['id'])];
            } else {
                if (is_array($aSection['listing_location'])) {
                    $this->location = array_map(
                        function ($aItem) {
                            return absint($aItem['id']);
                        },
                        $aSection['listing_location']
                    );
                } else {
                    $this->location = [absint($aSection['listing_location'])];
                }
            }
        }

        return true;
    }

    protected function parseBusinessHour($input)
    {
        if ($input == 'Midnight') {
            return '00:00:00';
        }

        $aItem = str_split($input, 2);

        return $aItem[0] . ':' . $aItem[1] . ':00';
    }

    protected function cleanSinglePrice($aSection)
    {
        if (!empty($aSection['single_price'])) {
            $this->singlePrice = floatval($aSection['single_price']);
        }
    }

    protected function cleanPriceRange($aSection)
    {
        if (
            !isset($aSection['minimum_price']) || !isset($aSection['maximum_price']) ||
            empty($aSection['minimum_price']) || empty($aSection['maximum_price'])
        ) {
            return false;
        }

        $aSection['minimum_price'] = floatval($aSection['minimum_price']);
        $aSection['maximum_price'] = floatval($aSection['maximum_price']);

        if ($aSection['minimum_price'] >= $aSection['maximum_price']) {
            return false;
        }

        $this->aPriceRange['minimum_price'] = $aSection['minimum_price'];
        $this->aPriceRange['maximum_price'] = $aSection['maximum_price'];
        $this->aPriceRange['price_range'] = ValidationHelper::deepValidation($aSection['price_range']);
        $this->aPriceRange['price_range_desc']
            = ValidationHelper::deepValidation($aSection['price_range_desc']);

        return true;
    }

    protected function cleanBusinessHours(array $aSection)
    {
        //        var_export($aSection);die;
        if (!isset($aSection['settings']) || empty($aSection['settings'])) {
            return false;
        }

        $this->aBusinessHours['hourMode']
            = ValidationHelper::deepValidation($aSection['settings']['hourMode']);
        $this->aBusinessHours['timeFormat']
            = ValidationHelper::deepValidation($aSection['settings']['timeFormat']);

        $totalDayOff = 0;
        if ($this->aBusinessHours['hourMode'] === 'open_for_selected_hours') {
            foreach ($aSection['settings']['operating_times'] as $dayOfWeek => $aItems) {
                if (!$aItems[0]['isOpen'] || empty($aItems[0]['open']) || empty($aItems[0]['close'])) {
                    $this->aBusinessHours['businessHours'][$dayOfWeek]['isOpen'] = 'no';
                    $totalDayOff = $totalDayOff + 1;
                } else {
                    $aBHourItem = [];
                    if ($aItems[0]['open'] === '24hrs') {
                        $aBHourItem['firstOpenHour'] = '24:00:00';
                        $aBHourItem['firstCloseHour'] = '24:00:00';
                    } else {
                        $aBHourItem['firstOpenHour'] = $this->parseBusinessHour($aItems[0]['open']);
                        $aBHourItem['firstCloseHour'] = $this->parseBusinessHour($aItems[0]['close']);

                        if (!empty($aItems[1]['open']) && !empty($aItems[1]['close'])) {
                            $aBHourItem['secondOpenHour'] = $this->parseBusinessHour($aItems[1]['open']);
                            $aBHourItem['secondCloseHour'] = $this->parseBusinessHour($aItems[1]['close']);
                        }
                    }
                    $this->aBusinessHours['businessHours'][$dayOfWeek]['operating_times'] = $aBHourItem;
                    $this->aBusinessHours['businessHours'][$dayOfWeek]['isOpen'] = 'yes';
                }
            }
        }
        if ($totalDayOff == 7) {
            $this->aBusinessHours['hourMode'] = 'no_hours_available';
            unset($this->aBusinessHours['secondCloseHour']);
        }

        return true;
    }

    protected function isValidPost($postID, $postType)
    {
        if (get_post_type($postID) !== $postType) {
            return false;
        }

        if (!in_array(get_post_status($postID), ['pending', 'publish'])) {
            return false;
        }

        if (current_user_can('administrator')) {
            return true;
        }

        return apply_filters('wilcity/filter/wiloke-listing-tools/app/Validation/isValidPost',
            get_post_field('post_author', $postID) == get_current_user_id()
        );
    }

    private function getSelectTreeItem($aItem, $postType = '')
    {
        if (isset($aItem['id'])) {
            $val = $aItem['id'];

        } else {
            $val = $aItem;
        }

        if (!empty($postType)) {
            if (!$this->isValidPost($val, $postType)) {
                return false;
            }
        }

        return $val;
    }

    private function getSelectTreeVal($rawVal, $postType = '')
    {
        if (empty($rawVal)) {
            return false;
        }

        if (isset($rawVal['id']) || !is_array($rawVal)) {
            $val = $this->getSelectTreeItem($rawVal, $postType);
        } else {
            $val = [];
            foreach ($rawVal as $item) {
                $val[] = $this->getSelectTreeItem($item, $postType);
            }
        }

        return $val;
    }

    protected function cleanMyPosts($aSection)
    {
        if (empty($aSection['my_posts'])) {
            return false;
        }

        $this->myPosts = $this->getSelectTreeVal($aSection['my_posts'], 'post');

        return true;
    }

    protected function cleanMyRoom($aSection)
    {
        if (!isset($aSection['my_room']) || empty($aSection['my_room'])) {
            return false;
        }
        $this->myRoom = $this->getSelectTreeVal($aSection['my_room'], 'product');

        return true;
    }

    protected function cleanHostedBy($aSection)
    {
        $this->aHostedBy = ValidationHelper::deepValidation($aSection);

        return true;
    }

    protected function cleanMyProducts($aSection)
    {
        if (!isset($aSection['my_product_mode']) || empty($aSection['my_product_mode'])) {
            return false;
        }

        if ($aSection['my_product_mode'] === 'specify_products') {
            $this->aMyProducts['my_products'] = $this->getSelectTreeVal($aSection['my_products'], 'product');
        } elseif ($aSection['my_product_mode'] === 'specify_product_cats') {
            $this->aMyProducts['my_product_cats'] = $this->getSelectTreeVal($aSection['my_product_cats']);
        }

        $this->aMyProducts['my_product_mode'] = $aSection['my_product_mode'];

        return true;
    }

    protected function cleanBookingcombannercreator($aSection)
    {
        if (!Submission::isPlanSupported($this->planID, 'toggle_bookingcombannercreator')) {
            return false;
        }

        foreach ($aSection as $key => $val) {
            $key = sanitize_text_field(str_replace('bookingcombannercreator_', '', $key));
            if ($key === 'bannerImg') {
                $this->aBookingComBannerCreator[$key] = isset($val['src']) ? $val['src'] : '';
                $this->aBookingComBannerCreator[$key . '_id'] = isset($val['id']) ? $val['id'] : '';
            } else {
                $this->aBookingComBannerCreator[$key] = ValidationHelper::deepValidation($val);
            }
        }
    }

    protected function cleanVideo($aSection)
    {
        if (!Submission::isPlanSupported($this->planID, 'toggle_videos')) {
            return false;
        }

        if (!isset($aSection['video_srcs']) || empty($aSection['video_srcs'])) {
            return false;
        }

        $aVideos = $this->sureItemsDoNotExceededPlan('maximumVideos', $aSection['video_srcs']);
        foreach ($aVideos as $order => $aValue) {
            if (empty($aValue) || !isset($aValue['value']) || empty($aValue['value'])) {
                continue;
            }

            if (strpos($aValue['value'], 'youtube') !== -1) {
                $aValue['value'] = preg_replace_callback(
                    '/&.*/',
                    function () {
                        return '';
                    },
                    $aValue['value']
                );
            }

            $this->aVideos[$order]['src'] = ValidationHelper::deepValidation($aValue['value']);
            $this->aVideos[$order]['thumbnail'] = '';
        };

        return true;
    }

    protected function cleanEventCalendar($aSection)
    {
        if (!isset($aSection['event_calendar']) || empty($aSection['event_calendar'])) {
            return false;
        }

        $aEventCalendar = $aSection['event_calendar'];
        if (empty($aEventCalendar['date'])) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('The event calendar is required', 'wiloke-listing-tools')
            ]);
        }

        if (empty($aEventCalendar['date']['starts'])) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('The Start date is required', 'wiloke-listing-tools')
            ]);
        }

        if (empty($aEventCalendar['date']['ends'])) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('The End date is required', 'wiloke-listing-tools')
            ]);
        }


        $oStart = \DateTime::createFromFormat(
            $this->getEventDateFormat(),
            $aEventCalendar['date']['starts']
        );

        if (!$oStart) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('Invalid start date format', 'wiloke-listing-tools')
            ]);
        }

        $aEventCalendar['date']['starts'] = $oStart->getTimestamp();

        $oEnd = \DateTime::createFromFormat(
            $this->getEventDateFormat(),
            $aEventCalendar['date']['ends']
        );

        if (!$oEnd) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('Invalid end date format', 'wiloke-listing-tools')
            ]);
        }

        $aEventCalendar['date']['ends'] = $oEnd->getTimestamp();

        if ($aEventCalendar['date']['starts'] >= $aEventCalendar['date']['ends']) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('The End date must be greater than Start date', 'wiloke-listing-tools')
            ]);
        }

        if ($aEventCalendar['frequency'] === 'weekly') {
            if (empty($aEventCalendar['specifyDays'])) {
                $this->oRetrieveValidation->error([
                    'msg' => esc_html__('You must specify Event Occur day', 'wiloke-listing-tools')
                ]);
            }
        }

        $dateFormat = Time::convertBackendEventDateFormat();

        $this->aEventCalendar['starts'] = $oStart->format($dateFormat);
        $this->aEventCalendar['frequency'] = $aEventCalendar['frequency'];
        $this->aEventCalendar['openingAt'] = $oStart->format(Time::getTimeFormat());
        $this->aEventCalendar['endsOn'] = $oEnd->format($dateFormat);
        $this->aEventCalendar['closedAt'] = $oEnd->format(Time::getTimeFormat());
        $this->aEventCalendar['specifyDays'] = $aEventCalendar['specifyDays'];

        return true;
    }

    protected function cleanEventBelongsToListing($aSection)
    {
        if (isset($aSection['event_belongs_to_listing']) && !empty($aSection['event_belongs_to_listing'])) {
            $aResponse = $this->middleware(['isUserLoggedIn', 'isPostAuthor', 'isPublishedPost'], [
                'postID'        => $aSection['event_belongs_to_listing']['id'],
                'postAuthor'    => get_current_user_id(),
                'passedIfAdmin' => true,
            ]);

            if ($aResponse['status'] === 'error') {
                $this->oRetrieveValidation->error($aResponse);
            }

            $this->parentListingID = $aSection['event_belongs_to_listing']['id'];
        }
    }

    protected function cleanGallery($aSection)
    {
        if (!Submission::isPlanSupported($this->planID, 'toggle_gallery')) {
            return false;
        }

        if (!isset($aSection['gallery']) || empty($aSection['gallery'])) {
            return false;
        }
        $aRawGallery = $this->sureItemsDoNotExceededPlan('maximumGalleryImages', $aSection['gallery']);

        $this->aGallery = array_reduce(
            $aRawGallery,
            function ($aAcummulator, $aImg) {
                return $aAcummulator + [$aImg['id'] => $aImg['src']];
            },
            []
        );

        return true;
    }

    protected function cleanCustomButton($aSection)
    {
        $this->aCustomButton = ValidationHelper::deepValidation($aSection);

        if (isset($this->aCustomButton['custom_button_button_icon'])) {
            $this->aCustomButton['button_icon'] = $this->aCustomButton['custom_button_button_icon'];
        }

        if (isset($this->aCustomButton['custom_button_button_name'])) {
            $this->aCustomButton['button_name'] = $this->aCustomButton['custom_button_button_name'];
        }

        if (isset($this->aCustomButton['custom_button_button_link'])) {
            $this->aCustomButton['button_link'] = $this->aCustomButton['custom_button_button_link'];
        }
    }

    protected function processHandleData()
    {
        $this->oRetrieveValidation = new RetrieveController(new AjaxRetrieve());

        if (empty($this->aData)) {
            $this->oRetrieveValidation->error([
                'msg' => esc_html__('Please fill up all requirement fields.', 'wiloke-listing-tools')
            ]);
        }

        $this->oAddListingSkeleton = new AddListingFieldSkeleton($this->listingType);
        $this->aAddListingSettings = $this->oAddListingSkeleton->getFields();

        foreach ($this->aAddListingSettings as $sectionKey => $aSections) {
            foreach ($aSections['fieldGroups'] as $fieldKey => $aField) {
                $taxonomy = '';
                if (isset($aField['taxonomy'])) {
                    $taxonomy = $aField['taxonomy'];
                }
                $this->surePassConditional($sectionKey, $taxonomy);
            }
        }

        foreach ($this->aData as $sectionKey => $aSection) {
            $cbFunc = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Validation/cleandata',
                [
                    App::get('AddListingController'),
                    General::generateCallbackFunction($sectionKey, 'clean')
                ],
                $sectionKey,
                $aSection
            );

            if (method_exists($cbFunc[0], $cbFunc[1])) {
                call_user_func_array($cbFunc, [$aSection, $sectionKey]);
            } else {
                if (is_array($aSection)) {
                    $this->cleanGeneralData($aSection, $sectionKey);
                }
            }
        }

    }
}
