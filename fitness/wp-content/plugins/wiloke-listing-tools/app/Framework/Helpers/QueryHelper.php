<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeThemeOptions;

class QueryHelper
{
    protected static $aTaxonomies;
    private static   $oSearchFormSkeleton;
    private static   $aCustomDropdownKeys;
    protected static $aEventShortcodeFilterBy
        = [
            'upcoming_event',
            'happening_event',
            'ongoing_event',
            'starts_from_ongoing_event'
        ];
    protected static $aEventSearchV2SpecifyFilterBy
        = [
            'upcoming_event',
            'this_week_event',
            'ongoing_event',
            'today_event',
            'tomorrow_event',
            'this_week_event',
            'next_week_event',
            'this_month_event'
        ];
    protected static $aOrderByBasedOnQuery
        = [
            'best_sales',
            'best_viewed',
            'best_rated',
            'recommended',
            'newest',
            'discount'
        ];

    public static function hasOrderByBasedOnQuery($aRequest)
    {
        foreach (self::$aOrderByBasedOnQuery as $key) {
            if (isset($aRequest[$key]) && $aRequest[$key] === 'yes') {
                return $key;
            }
        }

        return false;
    }

    public static function getDefaultEventOrderBy($isCustomQuery = false, $aRequest = [])
    {
        if (isset($aRequest['event_filter'])) {
            if (in_array($aRequest['event_filter'], self::$aEventSearchV2SpecifyFilterBy)) {
                return 'wilcity_event_starts_on';
            }

            if ($aRequest['event_filter'] === 'recommended') {
                return 'menu_order wilcity_event_starts_on';
            }
        }

        $orderBy = WilokeThemeOptions::getOptionDetail('event_search_page_order_by');
        if (empty($orderBy)) {
            return 'wilcity_event_starts_on';
        }

        if ($orderBy !== 'menu_order') {
            return $orderBy;
        }

        $orderByFallback = WilokeThemeOptions::getOptionDetail('event_search_page_order_by_fallback');

        if (empty($orderByFallback)) {
            return $orderBy;
        }

        return $isCustomQuery ? $orderBy . ',' . $orderByFallback : $orderBy . ' ' . $orderByFallback;
    }

    public static function convertTermQueryToSearchOption($taxonomy, $val)
    {
        if (empty($val)) {
            return '';
        }

        $aTerms = is_array($val) ? $val : explode(',', $val);
        $aResults = [];
        foreach ($aTerms as $term) {
            $name = TermSetting::getTermField($term, $taxonomy, 'name');
            if (!empty($name)) {
                $aResults[] = [
                    'label' => $name,
                    'id'    => absint(TermSetting::getTermField($term, $taxonomy, 'term_id'))
                ];
            }
        }

        return $aResults;
    }

    public static function buildUrl($url, $aArgs)
    {
        $aQuery = [];
        foreach ($aArgs as $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }

            $aQuery[$key] = $val;
        }

        return add_query_arg($aQuery, $url);
    }

    public static function buildSearchPageURL($aArgs)
    {
        $searchPageID = WilokeThemeOptions::getOptionDetail('search_page');
        if (empty($searchPageID)) {
            return '';
        }

        $searchURL = get_permalink($searchPageID);
        return self::buildUrl($searchURL, $aArgs);
    }

    protected static function getTaxQuery($aRequest, $taxonomy, $logic = 'AND')
    {
        if (!isset($aRequest[$taxonomy]) || empty($aRequest[$taxonomy]) || $aRequest[$taxonomy] == -1) {
            return [];
        }

        if (is_string($aRequest[$taxonomy]) && Validation::isValidJson($aRequest[$taxonomy])) {
            $aParseTax = Validation::getJsonDecoded();
            if (empty($aParseTax)) {
                return [];
            }
        } else {
            $aParseTax = $aRequest[$taxonomy];
        }

        $aParseTax = is_array($aParseTax) ? $aParseTax : explode(',', $aParseTax);

        if (is_array($aParseTax)) {
            $fieldType = is_numeric($aParseTax[0]) ? 'term_id' : 'slug';
        } else {
            $fieldType = is_numeric($aParseTax) ? 'term_id' : 'slug';
        }

        $aArgs = [
            'taxonomy' => $taxonomy,
            'field'    => $fieldType,
            'terms'    => $aParseTax
        ];

        return $aArgs;
    }

    protected static function buildTaxQuery($aRequest)
    {
        if (isset($aRequest['tax_query']) && !empty($aRequest['tax_query'])) {
            if (is_string($aRequest['tax_query']) && Validation::isValidJson($aRequest['tax_query'])) {
                return Validation::getJsonDecoded();
            }

            $aRequest['tax_query'] = array_map(function ($item) {
                if (is_string($item) && Validation::isValidJson($item)) {
                    return Validation::getJsonDecoded();
                }
                return $item;
            }, $aRequest['tax_query']);

            return $aRequest['tax_query'];
        }

        if (empty(self::$aTaxonomies)) {
            $postType = isset($aRequest['postType']) ? $aRequest['postType'] : '';
            $aCustomTerms = TermSetting::getCustomListingTaxonomies($postType);
            if (is_array($aCustomTerms)) {
                self::$aTaxonomies = array_keys($aCustomTerms);
                self::$aTaxonomies = array_merge(
                    self::$aTaxonomies,
                    ['listing_location', 'listing_cat', 'listing_tag']
                );
            }
        }

        self::$aTaxonomies = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/QueryHelper/buildTaxQuery/taxonomies',
            self::$aTaxonomies,
            $aRequest
        );

        $aArgs = [];
        foreach (self::$aTaxonomies as $taxonomy) {
            $termLogic = apply_filters(
                'wilcity/wiloke-listing-tools/filter/multi-tax-logic',
                'AND',
                $taxonomy,
                self::$aTaxonomies,
                $aRequest
            );

            $aTaxQuery = self::getTaxQuery($aRequest, $taxonomy, $termLogic);
            if (!empty($aTaxQuery)) {
                $aArgs[] = $aTaxQuery;
            }
        }

        if (!empty($aArgs) && count($aArgs) > 1) {
            $aArgs['relation'] = apply_filters('wilcity/wiloke-listing-tools/filter/tax-query-logic', 'AND');
        }
        return $aArgs;
    }

    private static function getCustomDropdownKeys($postType)
    {
        if (empty(self::$oSearchFormSkeleton)) {
            self::$oSearchFormSkeleton = new SearchFormSkeleton($postType);
        }

        return self::$oSearchFormSkeleton->getCustomDropdownKeys();
    }

    private static function getCustomTaxonomiesKeys($postType)
    {
        if (empty(self::$oSearchFormSkeleton)) {
            self::$oSearchFormSkeleton = new SearchFormSkeleton($postType);
        }

        return self::$oSearchFormSkeleton->getCustomTaxonomiesKeys();
    }

    private static function generateMetaKey($metaKey)
    {
        return strpos($metaKey, 'wilcity_custom_') === false ? 'wilcity_custom_' . $metaKey : $metaKey;
    }

    public static function buildQueryArgs($aRequest)
    {
        if (is_array($aRequest)) {
            foreach ($aRequest as $key => $val) {
                unset($aRequest[$key]);
                $aRequest[str_replace('--', '_', $key)] = $val;
            }
        }

        if (!isset($aRequest['postType'])) {
            if (isset($aRequest['post_type'])) {
                $aRequest['postType'] = $aRequest['post_type'];
            } elseif (isset($aRequest['type'])) {
                $aRequest['postType'] = $aRequest['type'];
            } else {
                $aRequest['postType'] = General::getFirstPostTypeKey(false, false);
            }
        }

        $isMap = !isset($aRequest['is_map']) ? 'no' : $aRequest['is_map'];

        $aArgs = [
            'post_type'   => $aRequest['postType'],
            'post_status' => 'publish',
            'is_map'      => $isMap
        ];

        if (isset($aRequest['timezoneOffset'])) {
            $aArgs['timezoneOffset'] = $aRequest['timezoneOffset'];
        }

        $search = '';
        if (isset($aRequest['s'])) {
            $search = $aRequest['s'];
        } else if (isset($aRequest['keyword'])) {
            $search = $aRequest['keyword'];
        } else if (isset($aRequest['wp_search'])) {
            $search = $aRequest['wp_search'];
        }

        $aArgs['s'] = $search;
        $aTaxQuery = self::buildTaxQuery($aRequest);

        if (General::getPostTypeGroup($aRequest['postType']) == 'event') {
            if (isset($aRequest['event_filter'])) {
                if ($aRequest['event_filter'] !== 'pick_a_date_event') {
                    unset($aRequest['date_range']);
                    $aArgs['event_filter'] = $aRequest['event_filter'];
                }
            }
        }

        if ($orderBy = self::hasOrderByBasedOnQuery($aRequest)) {
            $aRequest['orderby'] = $orderBy;
        };

        // resolved old event setting
        if (isset($aRequest['orderby']) &&
            in_array($aRequest['orderby'], self::$aEventShortcodeFilterBy)) {
            if (isset($aRequest['pagenow']) && $aRequest['pagenow'] === 'search') {
                $aRequest['orderby'] = 'wilcity_event_starts_on';
            }

            $aArgs['event_filter'] = $aRequest['orderby'];
        }

        if (empty($aRequest['orderby'])) {
            $aPostTypes = is_array($aRequest['postType']) ? $aRequest['postType'] : [$aRequest['postType']];

            if (General::isPostTypeInGroup($aPostTypes, 'event')) {
                $orderBy = self::getDefaultEventOrderBy(false, $aRequest);
                if (isset($aRequest['order']) && !empty($aRequest['order'])) {
                    $order = trim($aRequest['order']);
                } else {
                    $order = WilokeThemeOptions::getOptionDetail('event_search_page_order');
                }
            } else {
                $aDirectoryTypes = General::getPostTypeKeys(false, true);

                $isListingPostType = is_string($aRequest['postType']) && in_array($aRequest['postType'],
                        $aDirectoryTypes) || is_array($aRequest['postType']) && empty(array_diff($aRequest['postType'],
                        $aDirectoryTypes));
                if ($isListingPostType) {
                    $orderBy = WilokeThemeOptions::getOptionDetail('listing_search_page_order_by');
                    if (empty($orderBy)) {
                        $orderBy = 'menu_order post_date';
                        $order = 'DESC';
                    } else {
                        $orderBy = $orderBy == 'menu_order' ?
                            $orderBy . ' ' .
                            WilokeThemeOptions::getOptionDetail('listing_search_page_order_by_fallback') :
                            $orderBy;
                        $order = WilokeThemeOptions::getOptionDetail('listing_search_page_order');
                    }
                } else {
                    $order = 'DESC';
                    $orderBy = 'post_date';
                }
            }

            $aArgs['order'] = $order;
            $aArgs['orderby'] = $orderBy;
        } else {
            $aArgs['order'] = $aRequest['order'] ?? 'DESC';
            if ($aRequest['orderby'] === 'newest') {
                $aArgs['orderby'] = 'post_date';
                $aArgs['order'] = 'DESC';
            } else {
                $aArgs['orderby'] = $aRequest['orderby'];
            }
        }

        if ($aArgs['orderby'] == 'rand' && isset($aRequest['postsNotIn']) && is_array($aRequest['postsNotIn'])) {
            $aArgs['post__not_in'] = $aRequest['postsNotIn'];
        }

        if (isset($aRequest['aBounds']) && !empty($aRequest['aBounds'])) {
            unset($aRequest['oAddress']);
            $aArgs['map_bounds'] = $aRequest['aBounds'];
        } else if (isset($aRequest['map_bounds']) && !empty($aRequest['map_bounds'])) {
            unset($aRequest['oAddress']);
            $aArgs['map_bounds'] = $aRequest['map_bounds'];
        }

        if (isset($aArgs['map_bounds'])) {
            if (is_array($aRequest['map_bounds']) && isset($aRequest['map_bounds'][0])) {
                $aParsed = explode(',', $aRequest['map_bounds'][0]);
                $aBounds['aFLatLng'] = [
                    'lat' => $aParsed[0],
                    'lng' => $aParsed[1]
                ];

                $aParsed = explode(',', $aRequest['map_bounds'][1]);
                $aBounds['aSLatLng'] = [
                    'lat' => $aParsed[0],
                    'lng' => $aParsed[1]
                ];
                $aArgs['map_bounds'] = $aBounds;
            }
        }
        if (isset($aRequest['oAddress']) && !empty($aRequest['oAddress'])) {
            // var_dump(is_string($aRequest['oAddress']));
            if (is_string($aRequest['oAddress'])) {
                if (!ValidationHelper::isValidJson($aRequest['oAddress'])) {
                    $aParsedRawGeocoder = explode(',', $aRequest['oAddress']);
                    $aParsedGeocoder = [];
                    foreach ($aParsedRawGeocoder as $item) {
                        $aParsedGeocodeItem = explode(':', $item);
                        $aParsedGeocoder[$aParsedGeocodeItem[0]] = round($aParsedGeocodeItem[1], 5);
                    }

                    $aArgs['oAddress'] = $aParsedGeocoder;
                } else {
                    $aArgs['oAddress'] = ValidationHelper::getJsonDecoded();
                }
            }
            $aArgs['order'] = 'ASC';
            if (isset($aRequest['oAddress']['radius'])) {
                $radius = absint($aRequest['oAddress']['radius']);
            } else {
                $radius = WilokeThemeOptions::getOptionDetail('default_radius');
            }

            if (isset($aRequest['oAddress']['unit'])) {
                $unit = $aRequest['oAddress']['unit'];
            } elseif (GetSettings::getSearchFormField($aRequest['postType'], 'unit')) {
                $unit = GetSettings::getSearchFormField($aRequest['postType'], 'unit');
            } else {
                $unit = WilokeThemeOptions::getOptionDetail('unit_of_distance');
            }

            if (isset($aRequest['oAddress']['isMobileApp']) && $aRequest['oAddress']['isMobileApp']) {
                $aArgs['geocode'] = [
                    'latLng' => $aRequest['oAddress']['lat'] . ',' . $aRequest['oAddress']['lng'],
                    'radius' => $radius,
                    'unit'   => empty($unit) ? 'km' : $unit
                ];
            } else {
                $aArgs['geocode'] = [
                    'latLng' => $aArgs['oAddress']['lat'] . ',' . $aArgs['oAddress']['lng'],
                    'radius' => $radius,
                    'unit'   => empty($unit) ? 'km' : $unit
                ];
            }
            unset($aArgs['oAddress']);
        }

        if (isset($aRequest['open_now']) && !empty($aRequest['open_now']) && $aRequest['open_now'] !== 'no') {
            $aArgs['open_now'] = $aRequest['open_now'];
        }

        if (isset($aRequest['date_range'])) {
            // temporary fix
            if (is_string($aRequest['date_range']) && strpos($aRequest['date_range'], '[') !== false) {
                $aRequest['date_range'] = str_replace(['[', ']'], ['', ''], $aRequest['date_range']);
                $aRequest['date_range'] = explode(',', $aRequest['date_range']);
            }


            if (isset($aRequest['date_range']['from'])) {
                $aArgs['date_range'] = [
                    'from' => $aRequest['date_range']['from'],
                    'to'   => $aRequest['date_range']['to']
                ];
            } else {
                $aArgs['date_range'] = [
                    'from' => $aRequest['date_range'][0],
                    'to'   => $aRequest['date_range'][1]
                ];
            }

            if (isset($aArgs['date_range']) && !empty($aArgs['date_range'])) {
                unset($aArgs['orderby']);
                unset($aArgs['pick_a_date_event']);
            }
        }

        if (
            isset($aRequest['price_range']) && !empty($aRequest['price_range']) &&
            $aRequest['price_range'] !== 'nottosay'
        ) {
            if (Validation::isValidJson($aRequest['price_range'])) {
                $aPriceRange = Validation::getJsonDecoded();
                if (isset($aPriceRange['min'])) {
                    $aPriceRange = Validation::deepValidation($aPriceRange);
                    $aArgs['price_range'] = $aPriceRange;
                }
            } else {
                $aArgs['meta_query'][] = [
                    [
                        'key'     => 'wilcity_price_range',
                        'value'   => $aRequest['price_range'],
                        'compare' => '='
                    ]
                ];
            }
        }

        if (isset($aRequest['claimed']) && $aRequest['claimed'] === 'yes') {
            $aArgs['meta_query'][] = [
                [
                    'key'     => 'wilcity_claim_status',
                    'value'   => 'claimed',
                    'compare' => '='
                ]
            ];
        }

        if (isset($aRequest['orderby']) && $aRequest['orderby'] === 'discount') {
            if ($aArgs['post_type'] != 'product') {
                $aArgs['meta_query'][] = [
                    [
                        'key'     => 'wilcity_coupon_expiry',
                        'value'   => time(),
                        'compare' => '>='
                    ]
                ];

                $aRequest['orderby'] = 'menu_order post_date';
            } else {
                $aArgs['meta_query'][] = [
                    'relation' => 'OR',
                    [ // Simple products type
                      'key'     => '_sale_price',
                      'value'   => 0,
                      'compare' => '>',
                      'type'    => 'numeric'
                    ],
                    [ // Variable products type
                      'key'     => '_min_variation_sale_price',
                      'value'   => 0,
                      'compare' => '>',
                      'type'    => 'numeric'
                    ]
                ];
            }
        }

        if (empty($aRequest['posts_per_page'])) {
            $aArgs['posts_per_page'] = isset($aRequest['postsPerPage']) && !empty($aRequest['postsPerPage']) ?
                absint($aRequest['postsPerPage']) :
                get_option('posts_per_page');
        } else {
            $aArgs['posts_per_page'] = $aRequest['posts_per_page'];
        }

        $aArgs['posts_per_page'] = $aArgs['posts_per_page'] > 200 ? 200 : $aArgs['posts_per_page'];

        if (isset($aRequest['page']) && !empty($aRequest['page'])) {
            $aArgs['paged'] = absint($aRequest['page']);
        } elseif (isset($aRequest['offset']) && !empty($aRequest['offset'])) {
            $aArgs['paged'] = absint($aRequest['offset']);
        }

        if (isset($aRequest['postStatus']) && !empty($aRequest['postStatus'])) {
            $aArgs['post_status'] = sanitize_text_field($aRequest['postStatus']);
        }

        if (isset($aRequest['post_parent']) && !empty($aRequest['post_parent'])) {
            $aArgs['post_parent'] = absint($aRequest['post_parent']);
        } else if (isset($aRequest['parentID']) && !empty($aRequest['parentID'])) {
            $aArgs['post_parent'] = absint($aRequest['parentID']);
        }

        if (isset($aRequest['post__in']) && !empty($aRequest['post__in'])) {
            if (is_string($aRequest['post__in'])) {
                $aParsedPostsIn = explode(',', $aRequest['post__in']);
                foreach ($aParsedPostsIn as $postId) {
                    $postId = absint(trim($postId));
                    if (!empty($postId)) {
                        $aArgs['post__in'][] = $postId;
                    }
                }
            }
        }

        if (isset($aRequest['author__in']) && !empty($aRequest['author__in'])) {
            if (is_string($aRequest['author__in']) && Validation::isValidJson($aRequest['author__in'])) {
                $aAuthors = Validation::getJsonDecoded();
            } else if (is_array($aRequest['author__in'])) {
                $aAuthors = $aRequest['author__in'];
            }

            if (isset($aAuthors)) {
                $aArgs['author__in'] = $aAuthors;
            }
        } else if (isset($aRequest['author']) && !empty($aRequest['author'])) {
            $aArgs['author__in'] = [$aRequest['author']];
        }

        switch ($aArgs['orderby']) {
            case 'best_sales':
                $aArgs['meta_key'] = 'total_sales';
                $aArgs['orderby'] = 'meta_value_num';
                break;
            case 'recommended':
            case 'premium_listings':
                if ($aArgs['post_type'] !== 'product') {
                    $aArgs['orderby'] = 'menu_order';
                    $aArgs['order'] = 'DESC';
                    if (isset($aRequest['TYPE'])) {
                        if ($aRequest['TYPE'] == 'LISTINGS_SLIDER') {
                            $aMetaKey = GetSettings::getPromotionKeyByPosition('listing_slider_sc', true);
                        } else {
                            $aMetaKey = GetSettings::getPromotionKeyByPosition('listing_grid_sc', true);
                        }

                        if (!empty($aMetaKey)) {
                            $aArgs['meta_key'] = $aMetaKey[0];
                            $aArgs['orderby'] = 'rand menu_order';
                        }
                    }
                } else {
                    if (!empty($aTaxQuery)) {
                        $aTaxQuery['relation'] = apply_filters(
                            'wilcity/wiloke-listing-tools/filter/tax-query-logic',
                            'AND'
                        );
                    }

                    $aTaxQuery[] = [
                        'taxonomy' => 'product_visibility',
                        'field'    => 'name',
                        'terms'    => 'featured',
                        'operator' => 'IN', // or 'NOT IN' to exclude feature products
                    ];
                }
                break;
            case 'best_viewed':
                $aArgs['orderby'] = 'meta_value_num';
                $aArgs['meta_key'] = 'wilcity_count_viewed';
                break;
            case 'best_shared':
                $aArgs['orderby'] = 'meta_value_num';
                $aArgs['meta_key'] = 'wilcity_count_shared';
                break;
            case 'rating':
            case 'lowest_rating':
            case 'best_rated':
            case 'highest_rating':
                if ($aRequest['orderby'] === 'highest_rating' || $aRequest['orderby'] === 'best_rated') {
                    $aArgs['order'] = 'DESC';
                } else if ($aRequest['orderby'] === 'lowest_rating') {
                    $aArgs['order'] = 'ASC';
                }
                $aArgs['orderby'] = 'meta_value_num';
                if ($aArgs['post_type'] == 'product') {
                    $aArgs['meta_key'] = '_wc_average_rating';
                } else {
                    $aArgs['meta_query'][] = [
                        'relation' => 'OR',
                        [ //check to see if date has been filled out
                          'key'     => 'wilcity_average_reviews',
                          'compare' => '>=',
                          'value'   => 0
                        ],
                        [ //if no date has been added show these posts too
                          'key'     => 'wilcity_average_reviews',
                          'compare' => 'NOT EXISTS'
                        ]
                    ];
                }
                break;
            case 'claimed':
                $aArgs['meta_query'] = [
                    'claim_clause' => [
                        'key' => 'wilcity_claim_status',
                    ],
                ];

                $aArgs['orderby'] = apply_filters('wilcity/wiloke-listing-tools/filter/order-by-claimed', [
                    'claim_clause' => 'ASC',
                ]);
                break;
        }

        if (!is_array($aArgs['post_type'])) {
            $aDropdownKeys = self::getCustomDropdownKeys($aArgs['post_type']);
            $aRequestKeys = array_keys($aRequest);

            $aCustomFields = array_intersect($aDropdownKeys, $aRequestKeys);
            if (!empty($aCustomFields)) {
                foreach ($aCustomFields as $metaKey) {
                    if (empty($aRequest[$metaKey])) {
                        continue;
                    }

                    if (Validation::isValidJson($aRequest[$metaKey])) {
                        if (is_array($aRequest[$metaKey])) {
                            $aMetaValues = $aRequest[$metaKey];
                        } else {
                            $aMetaValues = Validation::getJsonDecoded();
                        }

                        if (is_array($aMetaValues)) {
                            $aMetaQuery = [];
                            foreach ($aMetaValues as $val) {
                                $aMetaQuery[] = [
                                    'key'     => self::generateMetaKey($metaKey),
                                    'value'   => $val,
                                    'compare' => 'LIKE'
                                ];
                            }

                            if (!empty($aMetaQuery)) {
                                if (count($aMetaQuery) > 1) {
                                    $aMetaQuery['relation'] = apply_filters(
                                        'wilcity/wiloke-listing-tools/filter/multi-field-option-logic',
                                        'AND'
                                    );
                                }
                                $aArgs['meta_query'][] = $aMetaQuery;
                            }
                        }
                    } else {
                        if (is_array($aRequest[$metaKey])) {
                            foreach ($aRequest[$metaKey] as $value) {
                                $aArgs['meta_query'][] = [
                                    'key'     => self::generateMetaKey($metaKey),
                                    'compare' => 'LIKE',
                                    'value'   => $value,
                                ];
                            }
                            $aArgs['meta_query']['relation'] = 'OR';
                        } else {
                            $aArgs['meta_query'][] = [
                                'key'     => self::generateMetaKey($metaKey),
                                'compare' => 'LIKE',
                                'value'   => $aRequest[$metaKey]
                            ];
                        }
                    }
                }
            }
        }

        if (isset($aArgs['meta_query']) && count($aArgs['meta_query']) > 1 && !isset
            ($aArgs['meta_query']['relation'])) {
            $aArgs['meta_query']['relation'] = apply_filters(
                'wilcity/wiloke-listing-tools/filter/multi-metadata-logic',
                'AND'
            );
        }

        if (empty($aArgs['s'])) {
            unset($aArgs['s']);
        }

        if (!empty($aTaxQuery) && is_array($aTaxQuery)) {
            $aArgs['tax_query'] = $aTaxQuery;
        }

        return apply_filters('wiloke-listing-tools/search-form-controller/query-args', $aArgs, $aRequest);
    }
}
