<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Models\UserModel;

class EventSkeleton extends AbstractSkeleton
{
    public $aDuplicateBodyFields
                           = [
            //        'listing_content',
            ////        'single_price',
            ////        'price_range',
            ////        'website',
            ////        'phone',
            ////        'map',
            ////        'taxonomy'
        ];
    public $aBodyFieldKeys = [];
    public $aCustomShortcodes
                           = [
            'textarea'                   => 'wilcity_render_textarea_field',
            'input'                      => 'wilcity_render_input_field',
            'text'                       => 'wilcity_render_text_field',
            'select'                     => 'wilcity_render_select_field',
            'listing_type_relationships' => 'wilcity_render_listing_type_relationships',
            'image'                      => 'wilcity_render_image_field',
            'checkbox2'                  => 'wilcity_render_checkbox2_field',
            'date_time'                  => 'wilcity_render_date_time_field',
            'multiple-checkbox'          => 'wilcity_render_multiple-checkbox_field',
        ];

    /**
     * @return string
     */
    public function getHostedByName(): string
    {
        return GetSettings::getEventHostedByName($this->post);
    }

    /**
     * @return string
     */
    public function getHostedByTarget(): string
    {
        return GetSettings::getEventHostedByTarget($this->getHostedByUrl());
    }

    /**
     * @return string
     */
    public function getHostedByUrl(): string
    {
        return GetSettings::getEventHostedByUrl($this->post);
    }

    /**
     * @return array
     */
    public function getHostedBy(): array
    {
        return [
            'name'   => $this->getHostedByName(),
            'target' => $this->getHostedByTarget(),
            'url'    => $this->getHostedByUrl()
        ];
    }

    public function getOAuthor()
    {
        $aData = $this->getHostedBy();
        $aData['displayName'] = $aData['name'];
        unset($aData['name']);

        return $aData;
    }

    public function getOFavorite()
    {
        return [
            'isMyFavorite'   => $this->getIsMyFavorite(),
            'totalFavorites' => $this->getTotalFavorites(),
            'text'           => 'peopleInterested'
        ];
    }

    public function getCalendar()
    {
        $aEventData = GetSettings::getEventSettings($this->postID);

        if (empty($aEventData) || !is_array($aEventData)) {
            return false;
        }

        $frequency = $aEventData['frequency'];
        $timeFormat = GetSettings::getPostMeta($this->postID, 'event_time_format');

        $newTimeFormat = 'D ' . Time::getTimeFormat($timeFormat);

        $aEventData['oStarts'] = [
            'date' => Time::toDateFormat($aEventData['startsOn']),
            'hour' => date_i18n($newTimeFormat, strtotime($aEventData['startsOn']))
        ];

        $aEventData['oEnds'] = [
            'date' => Time::toDateFormat($aEventData['endsOn']),
            'hour' => date_i18n($newTimeFormat, strtotime($aEventData['endsOn']))
        ];
        $aEventData['oOccur'] = [
            'frequency' => $frequency
        ];

        return $aEventData;
    }

    public function getOCalendar()
    {
        return $this->getCalendar();
    }

    public function getHeaderBlock()
    {
        $aMapInformation = GetSettings::getListingMapInfo($this->postID);
        $aMetaData = [];
        if (!empty($aMapInformation) && !empty($aMapInformation['address']) && !empty($aMapInformation['lat']) &&
            !empty($aMapInformation['lng'])) {
            $aMetaData[] = [
                'icon'  => 'la la-map-marker',
                'type'  => 'map',
                'value' => $aMapInformation
            ];
        }

        $aEventTaxonomies = $this->getTaxonomiesBelongsToPostType();
        if (!empty($aEventTaxonomies)) {
            foreach ($aEventTaxonomies as $taxonomy) {
                $aTerms = $this->getTaxonomy($taxonomy, false);
                if (!empty($aTerms)) {
                    $aMetaData[] = [
                        'icon'  => '',
                        'type'  => 'term',
                        'value' => $aTerms
                    ];
                }
            }
        }

        $email = GetSettings::getPostMeta($this->postID, 'email');
        if ($email) {
            $aMetaData[] = [
                'icon'  => 'la la-envelope',
                'type'  => 'email',
                'link'  => 'mailto:' . $email,
                'value' => $email
            ];
        }

        $phone = GetSettings::getPostMeta($this->postID, 'phone');
        if ($phone) {
            $aMetaData[] = [
                'icon'  => 'la la-phone',
                'type'  => 'phone',
                'link'  => 'tel:' . $phone,
                'value' => $phone
            ];
        }

        $website = GetSettings::getPostMeta($this->postID, 'website');
        if ($website) {
            $aMetaData[] = [
                'icon'  => 'la la-link',
                'type'  => 'website',
                'value' => $website,
                'link'  => $website
            ];
        }

        $aPriceRange = GetSettings::getPriceRange($this->postID, true);

        if ($aPriceRange) {
            $aMetaData[] = [
                'icon'  => 'la la-money',
                'type'  => 'price_range',
                'value' => $aPriceRange['minimumPrice'] . ' - ' . $aPriceRange['maximumPrice'],
                'link'  => get_permalink($this->postID)
            ];
        }

        $singlePrice = GetSettings::getPostMeta($this->postID, 'single_price');
        if (!empty($singlePrice)) {
            $aMetaData[] = [
                'icon'  => 'la la-money',
                'type'  => 'single_price',
                'value' => $singlePrice,
                'link'  => get_permalink($this->postID)
            ];
        }

        return apply_filters('wiloke-listing-tools/single-event/meta-data', $aMetaData, $this->post);
    }

    public function getBodyBlockFields($isRemoveDuplicateBlock = true)
    {
        if (!$isRemoveDuplicateBlock) {
            $isRemoveDuplicateBlock
                = isset($this->aAtts['isRemoveDuplicateBodyBlock']) ? $this->aAtts['isRemoveDuplicateBodyBlock'] :
                false;
        }

        $aRawBodyFields = GetSettings::getOptions(
            General::getEventContentFieldKey($this->post->post_type),
            false,
            true
        );

        if (!is_array($aRawBodyFields)) {
            return [];
        }

        $aBodyFields = [];
        if ($isRemoveDuplicateBlock) {
            foreach ($aRawBodyFields as $order => $aItem) {
                if (isset($aItem['key']) && !in_array($aItem['key'], $this->aDuplicateBodyFields)) {
                    $aParseKey = explode('|', $aItem['key']);

                    if (isset($aParseKey[1]) && $this->aCustomShortcodes[$aParseKey[1]]) {
                        $aItem['content'] = sprintf(
                            '[%s key="%s" /]',
                            $this->aCustomShortcodes[$aParseKey[1]],
                            $aParseKey[0]
                        );

                        $aItem['isCustomSection'] = 'yes';
                        $this->aCustomFieldStore[$aParseKey[0]] = $aParseKey[1];
                    }

                    $aItem['key'] = $aParseKey[0];
                    $aBodyFields[] = $aItem;
                }
            }
        }

        return $aBodyFields;
    }

    /**
     * @param bool $isRemoveDuplicateBlock
     *
     * @return array|mixed
     *  array (
     * 0 => 'video',
     * 1 => 'my_textarea_field1589296172517|textarea',
     * 2 => 'my_text_field1589296225418|input',
     * 3 => 'my_checkbox_field1589296195025|multiple-checkbox',
     * 4 => 'coupon',
     * )
     */
    public function getBodyBlockFieldKeys($isRemoveDuplicateBlock = true)
    {
        if (!empty($this->aBodyFieldKeys)) {
            return $this->aBodyFieldKeys;
        }

        $aBodyFields = $this->getBodyBlockFields($isRemoveDuplicateBlock);
        $this->aBodyFieldKeys = array_reduce($aBodyFields, function ($aCarry, $aField) {
            if (!isset($aField['key']) || empty($aField['key'])) {
                return $aCarry;
            }

            $aCarry[] = $aField['key'];

            return $aCarry;
        }, []);

        return $this->aBodyFieldKeys;
    }

    public function getNavigationSettings()
    {
        if (!empty($this->aNavigationSettings)) {
            return $this->aNavigationSettings;
        }

        $this->aNavigationSettings = $this->getBodyBlockFields();

        if (!empty($this->aNavigationSettings)) {
            $this->aNavigationSettings = ArrayCollectionFactory::set($this->aNavigationSettings)
                ->magicKeyGroup('key')
                ->output();
        }

        return $this->aNavigationSettings;
    }

    public function getBodyBlock($isRemoveDuplicateBlock = true)
    {
        $aPlucks = $this->getBodyBlockFieldKeys($isRemoveDuplicateBlock);
        if (empty($aPlucks)) {
            return false;
        }

        $aMetaData = [];
        $this->getAddListingFields();
        $this->getNavigationSettings();
        $aData = $this->getSkeleton($this->post, $aPlucks);
        unset($aData['menuOrder']);

        foreach ($this->aNavigationSettings as $aField) {
            if (!isset($aData[$aField['key']]) ||
                !isset($this->aAddListingFields[$aField['key']]) ||
                empty($aData[$aField['key']])
            ) {
                continue;
            }

            $aFieldData = [
                'icon'  => $aField['icon'],
                'type'  => isset($this->aCustomFieldStore[$aField['key']]) ?
                    $this->aCustomFieldStore[$aField['key']] : $aField['key'], // maybe custom field
                'key'   => $aField['key'],
                'value' => $aData[$aField['key']],
                'link'  => get_permalink($this->postID),
                'text'  => $aField['name']
            ];

            if (isset($this->aCustomFieldStore[$aField['key']])) {
                $aFieldData['isCustomSection'] = 'yes';
            }

            $aMetaData[] = $aFieldData;
        }

        return $aMetaData;
    }

    public function getToggleDiscussion(): string
    {
        $aGeneralSettings = GetSettings::getOptions(wilokeListingToolsRepository()
            ->get('event-settings:keys', true)
            ->sub('general'), true, true);

        if (!is_array($aGeneralSettings)) {
            return "no";
        }

        return $aGeneralSettings['toggle_comment_discussion'] == 'enable' ? 'yes' : 'no';
    }

    public function getIsEnableDiscussion()
    {
        return $this->getToggleDiscussion();
    }

    public function getVideo()
    {
        return $this->getVideos();
    }

    public function getSkeleton($post, $aPluck, $aAtts = [], $isFocus = false)
    {
        if (empty($aPluck)) {
            $aPluck = [
                'ID',
                'postTitle',
                'isAds',
                'postLink',
                'tagLine',
                'timezone',
                'oCalendar',
                'oFeaturedImg',
                'isMyFavorite',
                'totalFavorites',
                'hostedBy',
                'headerBlock',
                'bodyBlock'
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

        $aPluck[] = 'isEnableDiscussion';

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
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/EventSkeleton/aPluck',
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
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/EventSkeleton/aListing',
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
