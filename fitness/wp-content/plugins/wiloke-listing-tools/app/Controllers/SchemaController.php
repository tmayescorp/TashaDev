<?php

namespace WilokeListingTools\Controllers;

use Wiloke;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Price;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\MetaBoxes\Review;
use WilokeListingTools\Models\EventModel;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WP_Post;

class SchemaController extends Controller
{
    protected $schemaKey           = 'schema_markup';
    protected $schemaMarkupSavedAt = 'schema_markup_saved_at';

    public function __construct()
    {
        add_action('wp_head', [$this, 'addSchemaMarkupToHeader']);
        add_action('post_updated', [$this, 'savePost'], 10, 3);
        add_action('wilcity/wiloke-listing-tools/app/Controllers/SchemaController/focus-rebuild-schema',
            [$this, 'focusRebuildSchema']);
    }

    protected function generalSchemaMarkup($post)
    {
        global $post;
        if (empty($post)) {
            return false;
        }

        $schemaSettings = GetSettings::getOptions(General::getSchemaMarkupKey($post->post_type));

        if (empty($schemaSettings)) {
            return '';
        }

        if (strpos($schemaSettings, '{{listing_location}}') !== false) {
            $aLocations = wp_get_post_terms($post->ID, 'listing_location');
            $location = null;
            if (!empty($aLocations) && !is_wp_error($aLocations)) {
                $location = $aLocations[0]->name;
            }
            $schemaSettings = str_replace('{{listing_location}}', $location, $schemaSettings);
        }

        if (strpos($schemaSettings, 'priceRange') !== false) {
            $aPriceRange = GetSettings::getPriceRange($post->ID);
            if (!$aPriceRange) {
                $priceRange = null;
            } else {
                if (class_exists('\NumberFormatter')) {
                    $aPriceRange['maximumPrice'] = Price::renderPrice($aPriceRange['maximumPrice']);
                    $aPriceRange['minimumPrice'] = Price::renderPrice($aPriceRange['minimumPrice']);
                    $priceRange = $aPriceRange['minimumPrice'] . ' - ' . $aPriceRange['maximumPrice'];
                    $schemaSettings = str_replace('{{priceRange}}', $priceRange, $schemaSettings);
                } else {
                    $aPriceRange = GetSettings::getPriceRange($post->ID, true);
                    $priceRange = $aPriceRange['minimumPrice'] . ' - ' . $aPriceRange['maximumPrice'];
                    $priceRange = html_entity_decode($priceRange);
                }
            }
            $schemaSettings = str_replace('{{priceRange}}', $priceRange, $schemaSettings);
        }

        if (strpos($schemaSettings, 'singlePrice') !== false) {
            $singlePrice = GetSettings::getPostMeta($post->ID, 'single_price');
            $schemaSettings = str_replace('{{singlePrice}}', $singlePrice, $schemaSettings);
        }

        $aLatLng = ['lat' => null, 'lng' => null];

        if (strpos($schemaSettings, '{{latitude}}') !== false) {
            $aRawLatLng = GetSettings::getLatLng($post->ID);
            if ($aRawLatLng) {
                $aLatLng = $aRawLatLng;
            }

            $schemaSettings = str_replace(
                [
                    '{{latitude}}',
                    '{{longitude}}'
                ],
                [
                    $aLatLng['lat'],
                    $aLatLng['lng']
                ],
                $schemaSettings
            );
        }

        $featuredImg = null;

        if (strpos($schemaSettings, '{{featuredImg}}') !== false) {
            $featuredImg = GetSettings::getFeaturedImg($post->ID, 'full');
            $schemaSettings = str_replace('{{featuredImg}}', $featuredImg, $schemaSettings);
        }
        if (strpos($schemaSettings, '{{eventStartDate}}') !== false) {
            $aEventSettings = GetSettings::getEventSettings($post->ID);
            $eventStartsOn = null;
            $eventEndsOn = null;

            if ($aEventSettings) {
                $startsOnTimestamp = strtotime($aEventSettings['startsOn']);
                $endsOnTimestamp = strtotime($aEventSettings['endsOn']);
                $eventStartsOn = date('c', $startsOnTimestamp);
                $eventEndsOn = date('c', $endsOnTimestamp);
            }

            $schemaSettings = str_replace(
                [
                    '{{eventStartDate}}',
                    '{{eventEndDate}}'
                ],
                [
                    $eventStartsOn,
                    $eventEndsOn
                ],
                $schemaSettings
            );
        }

        if (strpos($schemaSettings, '{{googleAddress}}') !== false) {
            $schemaSettings
                = str_replace('{{googleAddress}}', GetSettings::getAddress($post->ID, false), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{streetAddress}}') !== false) {
            $schemaSettings
                = str_replace('{{streetAddress}}', GetSettings::getAddress($post->ID, false), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{telephone}}') !== false) {
            $schemaSettings
                = str_replace('{{telephone}}', GetSettings::getPostMeta($post->ID, 'phone'), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{website}}') !== false) {
            $schemaSettings
                = str_replace('{{website}}', GetSettings::getPostMeta($post->ID, 'website'), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{listingURL}}') !== false) {
            $schemaSettings = str_replace('{{listingURL}}', get_permalink($post->ID), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{email}}') !== false) {
            $schemaSettings = str_replace('{{email}}', GetSettings::getPostMeta($post->ID, 'email'), $schemaSettings);
        }

        if (strpos($schemaSettings, '{{author}}') !== false) {
            if ($post->post_type == 'event') {
                $author = GetSettings::getEventHostedByName($post);
                if (empty($author)) {
                    $author = User::getField('display_name', $post->ID);
                }
            } else {
                $author = User::getField('display_name', $post->post_author);
            }

            $schemaSettings = str_replace('{{author}}', $author, $schemaSettings);
        }
        $totalReviews = ReviewModel::countTotalReviews($post->ID);
        $totalReviews = empty($totalReviews) ? 0 : $totalReviews;

        if (empty($totalReviews)) {
            $schemaSettings = preg_replace_callback('/"aggregateRating(.+)reviewDetails}}"?,/', function () {
                return '';
            }, $schemaSettings);
        } else {

            if (strpos($schemaSettings, '{{averageRating}}') !== false) {
                $averageRating = GetSettings::getAverageRating($post->ID);
                $averageRating = empty($averageRating) ? 0 : $averageRating;
                $schemaSettings = str_replace('{{averageRating}}', $averageRating, $schemaSettings);
            }

            $bestRating = GetSettings::getBestRating($post->post_type);
            if (strpos($schemaSettings, '{{bestRating}}') !== false) {
                $schemaSettings = str_replace('{{bestRating}}', $bestRating, $schemaSettings);
            }

            if (strpos($schemaSettings, '{{totalRatings}}') !== false) {
                $totalReviews = ReviewModel::countTotalReviews($post->ID);
                $totalReviews = empty($totalReviews) ? 0 : $totalReviews;
                $schemaSettings = str_replace('{{totalRatings}}', absint($totalReviews), $schemaSettings);
            }

            if (strpos($schemaSettings, '{{reviewCount}}') !== false) {
                if (empty(!$totalReviews)) {
                    $schemaSettings = str_replace('{{reviewCount}}', absint($totalReviews), $schemaSettings);
                } else {
                    $schemaSettings = str_replace('{{reviewCount}}', -1, $schemaSettings);
                }
            }

            if (strpos($schemaSettings, '{{reviewDetails}}') !== false) {
                $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
                $aContentsOrder = SingleListing::getNavOrder();
                $maxReviewsPerPage = 0;
                if (isset($aContentsOrder['reviews'])) {
                    $maxReviewsPerPage = isset($aContentsOrder['reviews']['maximumItemsOnHome']) ?
                        absint($aContentsOrder['reviews']['maximumItemsOnHome']) : 0;
                }
                $aReviewDetails
                    = ReviewModel::getReviews($post->ID, ['page' => $page, 'postsPerPage' => $maxReviewsPerPage]);

                if (empty($aReviewDetails)) {
                    $schemaSettings = str_replace('{{reviewDetails}}', '', $schemaSettings);
                } else {
                    $aReviewsSchema = [];
                    while ($aReviewDetails->have_posts()) {
                        $aReviewDetails->the_post();
                        $aReviewsSchema[] = [
                            '@type'         => 'Review',
                            'author'        => [
                                '@type' => 'Person',
                                'name'  => User::getField('display_name', $aReviewDetails->post->post_author)
                            ],
                            'datePublished' => date('Y-m-d', strtotime($aReviewDetails->post->post_date)),
                            'description'   => str_replace(
                                ['"'],
                                ["'"],
                                get_the_excerpt($aReviewDetails->post->ID)
                            ),
                            'name'          => $aReviewDetails->post->post_title,
                            'reviewRating'  => [
                                '@type'       => 'Rating',
                                'bestRating'  => $bestRating,
                                'worstRating' => 0,
                                'ratingValue' => ReviewMetaModel::getAverageReviewsItem($aReviewDetails->post->ID)
                            ]
                        ];
                    }
                    wp_reset_postdata();

                    $schemaSettings = str_replace(
                        '"{{reviewDetails}}"',
                        json_encode($aReviewsSchema, JSON_UNESCAPED_SLASHES),
                        $schemaSettings
                    );
                }
            }
        }

        if (strpos($schemaSettings, '{{openingHours}}') !== false) {
            $hourMode = GetSettings::getPostMeta($post->ID, 'hourMode');
            switch ($hourMode) {
                case 'always_open':
                    $schemaAlwaysOpen = apply_filters('wilcity/schema-markup/always_open',
                        esc_html__('Mo-Su Monday through Sunday, all day', 'wiloke-listing-tools'));
                    $schemaSettings = str_replace('"{{openingHours}}"', '"' . $schemaAlwaysOpen . '"', $schemaSettings);
                    break;
                case 'open_for_selected_hours':
                    $aBusinessHours = GetSettings::getBusinessHours($post->ID);

                    $aBusinessHoursSchema = [];
                    $aDayOfWeek = wilokeListingToolsRepository()->get('general:aDayOfWeek');
                    $timeFormat = GetSettings::getTimeFormat($post->ID);
                    if (!empty($aBusinessHours)) {
                        foreach ($aBusinessHours as $aBusinessHour) {
                            if ($aBusinessHour['isOpen'] != 'yes' ||
                                $aBusinessHour['firstOpenHour'] == $aBusinessHour['firstCloseHour'] ||
                                empty($aBusinessHour['firstOpenHour']) || empty($aBusinessHour['firstCloseHour'])) {
                                continue;
                            }

                            $concatBusinessHour
                                = Time::renderTimeFormat(strtotime($aBusinessHour['firstOpenHour']), $timeFormat) .
                                '-' .
                                Time::renderTimeFormat(strtotime($aBusinessHour['firstCloseHour']), $timeFormat);
                            $aBusinessHoursSchema[] = $aDayOfWeek[$aBusinessHour['dayOfWeek']] . ' ' .
                                $concatBusinessHour;
                            if (($aBusinessHour['secondCloseHour'] == $aBusinessHour['secondOpenHour']) ||
                                empty($aBusinessHour['secondCloseHour']) || empty($aBusinessHour['secondOpenHour'])) {
                                $concatBusinessHour
                                    = Time::renderTimeFormat(strtotime($aBusinessHour['secondOpenHour']), $timeFormat) .
                                    '-' .
                                    Time::renderTimeFormat(strtotime($aBusinessHour['secondCloseHour']), $timeFormat);
                                $aBusinessHoursSchema[]
                                    = $aDayOfWeek[$aBusinessHour['dayOfWeek']] . ' ' . $concatBusinessHour;
                            }
                        }
                    }

                    $schemaSettings
                        = str_replace('"{{openingHours}}"', json_encode($aBusinessHoursSchema, JSON_UNESCAPED_SLASHES),
                        $schemaSettings);
                    break;
                default:
                    $schemaSettings = str_replace('{{openingHours}}', '', $schemaSettings);
                    break;
            }
        }

        if (strpos($schemaSettings, '{{photos}}') !== false) {
            $aGallery = GetSettings::getPostMeta($post->ID, 'gallery');
            if (!$aGallery) {
                $schemaSettings = str_replace('{{photos}}', '', $schemaSettings);
            } else {
                $aImgSrcs = [];
                foreach ($aGallery as $imgID => $src) {
                    $imgSrc = wp_get_attachment_image_url($imgID, 'large');
                    if (!$imgSrc) {
                        $aImgSrcs[] = $src;
                    } else {
                        $aImgSrcs[] = $imgSrc;
                    }
                }

                $schemaSettings = str_replace('"{{photos}}"', json_encode($aImgSrcs, JSON_UNESCAPED_SLASHES),
                    $schemaSettings);
            }
        }

        if (strpos($schemaSettings, '{{coverImg}}') !== false) {
            $coverImg = GetSettings::getPostMeta($post->ID, 'cover_image');
            if (!$coverImg) {
                $schemaSettings = str_replace('{{coverImg}}', null, $schemaSettings);
            } else {
                $schemaSettings = str_replace('{{coverImg}}', $coverImg, $schemaSettings);
            }
        }

        if (strpos($schemaSettings, '{{logo}}') !== false) {
            $logo = GetSettings::getPostMeta($post->ID, 'logo');
            if (!$logo) {
                $schemaSettings = str_replace('{{logo}}', null, $schemaSettings);
            } else {
                $schemaSettings = str_replace('{{logo}}', $logo, $schemaSettings);
            }
        }

        if (strpos($schemaSettings, '{{socialNetworks}}') !== false) {
            $aSocialNetworks = GetSettings::getSocialNetworks($post->ID);
            if (empty($aSocialNetworks)) {
                $schemaSettings = str_replace('{{socialNetworks}}', null, $schemaSettings);
            } else {
                $aSocialUrls = [];
                foreach ($aSocialNetworks as $socialUrl) {
                    if (!empty($socialUrl)) {
                        $aSocialUrls[] = $socialUrl;
                    }
                }
                if (!empty($aSocialUrls)) {
                    $schemaSettings = str_replace('"{{socialNetworks}}"',
                        json_encode($aSocialUrls, JSON_UNESCAPED_SLASHES), $schemaSettings);
                }
            }
        }

        if (strpos($schemaSettings, '{{eventOffer}}') !== false) {
            $aEventDate = EventModel::getEventData($post->ID);
            if (!empty($aEventDate["startsOn"])) {
                $currencyCode = GetWilokeSubmission::getField('currency_code');
                $schemaSettings = str_replace(
                    '"{{eventOffer}}"',
                    json_encode(
                        [
                            "@type"         => "Offer",
                            "url"           => get_permalink($post->ID),
                            "price"         => GetSettings::getPostMeta($post->ID, "single_price"),
                            "priceCurrency" => $currencyCode,
                            "availability"  => "http://schema.org/InStock",
                            "validFrom"     => date(
                                DATE_ATOM,
                                strtotime($aEventDate["startsOn"])
                            )
                        ],
                        JSON_UNESCAPED_SLASHES
                    ),
                    $schemaSettings
                );
            } else {
                $schemaSettings = str_replace('"{{eventOffer}}"', null, $schemaSettings);
            }
        }

        $schemaSettings = str_replace(
            [
                '{{postTitle}}',
                '{{featuredImg}}',
                '{{postExcerpt}}',
                '{{worstRating}}'
            ],
            [
                $post->post_title,
                $featuredImg,
                str_replace(
                    ['"'],
                    ["'"],
                    Wiloke::contentLimit(
                        apply_filters('wilcity/schema_markup/post_excerpt', 100),
                        $post,
                        true,
                        $post->post_content,
                        true
                    )
                ),
                0
            ],
            $schemaSettings
        );

        $schemaSettings
            = apply_filters('wilcity/filter-wiloke-listing-tools/app/Controllers/SchemaController/generate-schema',
            $schemaSettings, $post);

        SetSettings::setPostMeta($post->ID, $this->schemaKey, $schemaSettings);
        SetSettings::setPostMeta($post->ID, $this->schemaMarkupSavedAt, current_time('timestamp', 1));

        return $schemaSettings;
    }

    public function focusRebuildSchema($post)
    {
        if ($post->post_status !== 'publish') {
            return false;
        }
        $this->generalSchemaMarkup($post);
    }

    public function savePost($postID, $postBefore, $postAfter)
    {
        if ($postAfter->post_status !== 'publish') {
            return false;
        }
        $this->generalSchemaMarkup($postAfter);
    }

    public function addSchemaMarkupToHeader($thepost = null)
    {
        $aPostTypes = General::getPostTypeKeys(false);
        if (!is_singular($aPostTypes)) {
            return false;
        }

        if ($thepost instanceof WP_Post) {
            $post = $thepost;
        } else {
            global $post;
        }

        if (!GetSettings::isPlanAvailableInListing($post->ID, 'toggle_schema_markup')) {
            return false;
        }

        $savedSchemaMarkupAt = GetSettings::getOptions(General::getSchemaMarkupSavedAtKey($post->post_type));
        if (!$savedSchemaMarkupAt) {
            return false;
        }

        if (isset($_GET['paged']) && !empty($_GET['paged'])) {
            $this->schemaMarkupSavedAt = $this->schemaMarkupSavedAt . '_' . absint($_GET['paged']);
        }

        $generatedSchemaAt = GetSettings::getPostMeta($post->ID, $this->schemaMarkupSavedAt, true);

        if (!defined("WP_DEBUG") || WP_DEBUG) {
            if (!$generatedSchemaAt || $generatedSchemaAt < $savedSchemaMarkupAt) {
                $schemaSettings = $this->generalSchemaMarkup($post);
            } else {
                $schemaSettings = GetSettings::getPostMeta($post->ID, $this->schemaKey);
            }
        } else {
            $schemaSettings = $this->generalSchemaMarkup($post);
        }
        ?>
        <script type="application/ld+json"><?php echo $schemaSettings; ?></script>
        <?php
        do_action('wilcity/wiloke-listing-tools/app/Controllers/SchemaController/after-schema', $schemaSettings, $post);
    }
}
