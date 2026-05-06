<?php

namespace WilokeListingTools\Controllers;

use Elementor\Core\Base\Document;
use Elementor\Plugin;
use WilokeHelpers;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\Event;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\MapFactory;
use WilokeListingTools\Framework\Helpers\PostSkeleton;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\SearchFieldSkeleton;
use WilokeListingTools\Framework\Helpers\SearchFormSkeleton;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\SQLEscape;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeThemeOptions;
use WP_Query;
use WP_REST_Request;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Helpers\StringHelper;

class SearchFormController extends Controller
{
    public static    $aSearchFormSettings;
    private          $searchFormVersionKey = 'hero_search_form_version';
    protected static $aTermsPrinted        = [];
    protected        $aGotTags             = [];
    protected        $aTagsBelongToCat     = [];


    public function __construct()
    {
        add_action('wp_ajax_wilcity_fetch_individual_cat_tags', [$this, 'fetchIndividualCatTags']);
        add_action('wp_ajax_nopriv_wilcity_fetch_individual_cat_tags', [$this, 'fetchIndividualCatTags']);

        add_action('wp_ajax_wilcity_fetch_terms_suggestions', [$this, 'fetchTermsSuggestions']);
        add_action('wp_ajax_nopriv_wilcity_fetch_terms_suggestions', [$this, 'fetchTermsSuggestions']);

        //        add_action('wp_ajax_wilcity_fetch_hero_fields', [$this, 'fetchHeroSearchFields']);
        //        add_action('wp_ajax_nopriv_wilcity_fetch_hero_fields', [$this, 'fetchHeroSearchFields']);
        //
        add_action('wp_ajax_wilcity_fetch_terms_options', [$this, 'fetchTermOptions']);
        add_action('wp_ajax_nopriv_wilcity_fetch_terms_options', [$this, 'fetchTermOptions']);
        //        add_action('wilcity/footer/vue-popup-wrapper', [$this, 'printQuickSearchForm']);
        add_action('wilcity/saved-hero-search-form', [$this, 'saveSearchFormVersion'], 10, 2);

        //        add_action('wp_ajax_wilcity_fetch_hero_fields', [$this, 'getHeroSearchFields']);
        //        add_action('wp_ajax_nopriv_wilcity_fetch_hero_fields', [$this, 'getHeroSearchFields']);

        add_action('wp_ajax_wilcity_quick_search_form_suggestion', [$this, 'fetchHeroSearchFormSuggestion']);
        add_action('wp_ajax_nopriv_wilcity_quick_search_form_suggestion', [
            $this,
            'fetchHeroSearchFormSuggestion'
        ]);

        add_action('wp_ajax_wil_quick_search_listings', [$this, 'fetchQuickSearchListings']);
        add_action('wp_ajax_nopriv_wil_quick_search_listings', [
            $this,
            'fetchQuickSearchListings'
        ]);

        // Show up Query
        add_action('rest_api_init', [$this, 'registerRestRouter']);
        add_action('wp_head', [$this, 'printSearchFormQuery']);
        add_shortcode('wilcity_quick_search_form_shortcode', [$this, 'renderQuickSearchFormShortcode']);

        add_action('init', [$this, 'setBuildWithElementor'], 1);
        add_action('elementor/ajax/register_actions', [$this, 'setBuildWithElementorBeforeSaving'], 1);
        add_filter(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/fetchListings/imageSize',
            [$this, 'addSearchImageSize'],
            10,
            2
        );
        // Modify Term Query
        //        add_filter('terms_clauses', [$this, 'addGetTermByParentTaxonomyConditional'], 10, 3);
    }

    public function addSearchImageSize($size, $aFilter)
    {
        if (isset($aFilter['pageNow']) && $aFilter['pageNow'] == 'search') {

            $searchPageID = WilokeThemeOptions::getOptionDetail('search_page');

            if (!empty($searchPageID)) {
                $imgSize = GetSettings::getPostMeta($searchPageID, 'search_img_size');
                if (!empty($imgSize)) {
                    $size = $imgSize;
                }
            }
        }
        return $size;
    }

    public function setBuildWithElementorBeforeSaving($that)
    {
        $postID = $_REQUEST['editor_post_id'];
        if (wilcityIsSearchPage($postID)) {
            update_post_meta($postID, Document::BUILT_WITH_ELEMENTOR_META_KEY, 'builder');
        }
    }

    public function setBuildWithElementor()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'elementor') {
            $postID = $_GET['post'];
            if (wilcityIsSearchPage($postID)) {
                update_post_meta($postID, Document::BUILT_WITH_ELEMENTOR_META_KEY, 'builder');
            }
        }
    }

    public function renderQuickSearchFormShortcode()
    {
        ob_start();
        $aQuickSearchForm = GetSettings::getOptions('quick_search_form_settings', false, true);
        if (!isset($aQuickSearchForm['quick_search_form_settings']) ||
            ($aQuickSearchForm['quick_search_form_settings'] == 'yes')) : ?>
            <div class="wil-tb__cell">
                <?php get_template_part('templates/quick-search'); ?>
            </div>
        <?php endif;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function registerRestRouter()
    {
        register_rest_route('wiloke/v2', 'listings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'fetchListings'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'terms', [
            'methods'             => 'POST',
            'callback'            => [$this, 'getListingsInTerm'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'search', [
            'methods'             => 'GET',
            'callback'            => [$this, 'searchFormJson'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'autocomplete', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handleAutoComplete'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'search/fields', [
            'methods'             => 'GET',
            'callback'            => [$this, 'fetchSearchFields'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'hero-search/fields', [
            'methods'             => 'GET',
            'callback'            => [$this, 'fetchHeroSearchFields'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'taxonomy/(?P<taxonomy>[^\/]+)/sub-terms', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getSubLocations'],
            'args'                => [
                'context'  => [
                    'type'     => 'String',
                    'required' => false
                ],
                'postType' => [
                    'type'     => 'String',
                    'required' => false
                ],
                'parent'   => [
                    'type'     => ['String', 'Number'],
                    'required' => false
                ]
            ],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'taxonomy/(?P<taxonomy>[^\/]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'fetchTerms'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function handleAutoComplete(WP_REST_Request $oRequest)
    {
        $oRetrieve = new RetrieveController(new RestRetrieve());
        $aRequest = $oRequest->get_params();
        $aSearchTarget = $aRequest['searchTarget'];

        $aResponse = [];

        if (!isset($aRequest['s']) || empty($aRequest['s'])) {
            return $oRetrieve->error([]);
        }

        if (!is_array($aSearchTarget)) {
            $aSearchTarget = explode(',', $aSearchTarget);
        }

        $aSearchTarget = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/SearchFormController/search-target',
            $aSearchTarget,
            $aRequest
        );

        foreach ($aSearchTarget as $target) {
            switch ($target) {
                case 'geocoder':
                    $oMap = MapFactory::get();
                    $aSuggestions = $oMap->getGeocoder(trim($aRequest['s']), [
                        'limit' => apply_filters(
                            'wilcity/filter/wiloke-listing-tools/app/SearchFormController/search-target-limit/geocoder',
                            2,
                            $aRequest,
                            $aSearchTarget
                        )
                    ]);
                    if (!empty($aSuggestions)) {
                        if (isset($aResponse['items'])) {
                            $aResponse['items'] = array_merge($aResponse['items'], $aSuggestions);
                        } else {
                            $aResponse['items'] = $aSuggestions;
                        }
                    }
                    break;
                case 'listing':
                    $aArgs = QueryHelper::buildQueryArgs($aRequest);
                    $aArgs['posts_per_page'] = apply_filters(
                        'wilcity/filter/wiloke-listing-tools/app/SearchFormController/search-target-limit/listing',
                        6,
                        $aRequest,
                        $aSearchTarget
                    );

                    $query = $this->createWPQuery($aArgs);
                    if ($query instanceof WP_Query === false && !empty($query)) {
                        return $oRetrieve->success($query);
                    }

                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            $aResponse['items'][]
                                = apply_filters('wilcity/filter/wiloke-listing-tools/autocomplete/listing', [
                                'type'          => 'post',
                                'ID'            => $query->post->ID,
                                'name'          => get_the_title($query->post->ID),
                                'featuredImage' => get_the_post_thumbnail_url($query->post->ID, 'thumbnail'),
                                'link'          => get_permalink($query->post->ID)
                            ], $query->post);
                        }
                    }
                    wp_reset_postdata();
                    break;
                default:
                    if (taxonomy_exists($target)) {
                        $aTerms = get_terms([
                            'taxonomy'   => $target,
                            'number'     => apply_filters(
                                'wilcity/filter/wiloke-listing-tools/app/SearchFormController/search-target-limit/taxonomy',
                                2,
                                $aRequest,
                                $aSearchTarget
                            ),
                            'name__like' => trim($aRequest['s']),
                            'postTypes'  => SQLEscape::realEscape($aRequest['postType'])
                        ]);

                        if (!empty($aTerms) && !is_wp_error($aTerms)) {
                            foreach ($aTerms as $oTerm) {
                                $aTerm = get_object_vars($oTerm);
                                $icon = TermSetting::getTermIconIcon($oTerm);
                                if (isset($aTerm['name'])) {
                                    $aTerm['name'] = StringHelper::replaceEntityString($aTerm['name']);
                                }
                                $aResponse['items'][] = wp_parse_args(
                                    [
                                        'type'      => 'term',
                                        'ID'        => $oTerm->term_id,
                                        'id'        => $oTerm->term_id,
                                        'label'     => $oTerm->name,
                                        'icon'      => $icon,
                                        'iconColor' => TermSetting::getTermIconColor($oTerm),
                                        'link'      => get_term_link($oTerm),
                                        'img'       => TermSetting::hasRealTermIconIcon($oTerm) &&
                                        !TermSetting::hasRealTermIconImage($oTerm) ? '' :
                                            TermSetting::getTermImageIcon($oTerm)
                                    ],
                                    $aTerm
                                );
                            }
                        }
                    }
                    break;
            }
        }

        /**
         * hooked WilcityRedis\Controllers\SearchController@cacheSearchValue
         */
        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/SearchFormController/handleAutoComplete',
            $aResponse,
            $aRequest
        );

        if (!empty($aResponse)) {
            return $oRetrieve->success($aResponse);
        } else {
            return $oRetrieve->error([]);
        }
    }

    public static function buildQueryArgs($aAtts)
    {
        return QueryHelper::buildQueryArgs($aAtts);
    }

    private function parseRequestFromUrl(): array
    {
        $aRequest = [];

        if (isset($_GET['type'])) {
            $aRequest['postType'] = SQLEscape::realEscape(stripslashes($_GET['type']));
        } elseif (!isset($_GET['postType'])) {
            global $post;
            if (!isset($post->ID)) {
                return [];
            }

            if (function_exists('wilcityIsEventsTemplate') && wilcityIsEventsTemplate()) {
                $aRequest['postType'] = Event::getEventPostType($post->ID);
            } elseif (is_tax()) {
                $aRequest['postType'] = TermSetting::getDefaultPostType(
                    get_queried_object()->term_id,
                    get_queried_object()->taxonomy
                );
            } else {
                $postType = GetSettings::getPostMeta($post->ID, 'default_post_type');
                $aRequest['postType'] = empty($postType) || $postType === 'default' ? General::getDefaultPostTypeKey
                () : $postType;
            }
        }

        if (isset($_GET) && is_array($_GET)) {
            foreach ($_GET as $key => $val) {
                if (empty($val)) {
                    continue;
                }

                if (in_array($key, ['order--by'])) {
                    $key = str_replace('--', '', $key);
                } else {
                    $key = str_replace('--', '_', $key);
                }

                if (ValidationHelper::isValidJson($val)) {
                    $val = ValidationHelper::getJsonDecoded();
                }

                if (taxonomy_exists($key)) {
                    $aRequest[sanitize_text_field($key)] = is_array($val) ? $val : [$val];
                } else {
                    $aRequest[sanitize_text_field($key)] = ValidationHelper::deepValidation($val);
                }
            }
        }

        if (!isset($aRequest['orderby'])) {
            if ($aRequest['postType'] !== 'event') {
                $defaultOrderBy = WilokeThemeOptions::getOptionDetail('listing_search_page_order_by');
                if ($defaultOrderBy !== 'menu_order') {
                    $aRequest['orderby'] = $defaultOrderBy;
                }
            }
        }

        if (is_tax()) {
            $oQueriedObject = get_queried_object();
            $aRequest[$oQueriedObject->taxonomy] = [$oQueriedObject->term_id];

            if (!isset($aRequest['postType'])) {
                $aRequest['postType'] = TermSetting::getDefaultPostType(
                    $oQueriedObject->term_id,
                    $oQueriedObject->taxonomy
                );
            }
        }

        return $aRequest;
    }

    public function printSearchFormQuery()
    {
        $isAllow
            = apply_filters('wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/printSearchFormQuery/isAllow',
            (!is_404() && function_exists('wilcityIsSearchV2') &&
                (
                    wilcityIsSearchPage() ||
                    wilcityIsDefaultTermPage() ||
                    wilcityIsEventsTemplate() ||
                    is_front_page()
                ) && (!class_exists('WooCommerce') || (!is_shop() && !is_checkout()))));

        if (is_404()) {
            return false;
        }

        if ($isAllow) {
            if (is_tax()) {
                $queryObjected = get_queried_object();
                if (wilcityIsDefaultTermPage() &&
                    TermSetting::hasTermChildren($queryObjected->term_id, $queryObjected->taxonomy)) {
                    return false;
                }
                $searchPageID = absint(WilokeThemeOptions::getOptionDetail('search_page'));
            } else {
                global $post;
                $searchPageID = $post->ID;
            }

            $aArgs = $this->parseRequestFromUrl();
            $aTaxonomies = TermSetting::getListingTaxonomyKeys();
            $aArgs['postsPerPage'] = GetSettings::getPostsPerPage($searchPageID);
            $aTerms = [];


            /**
             * @var SearchFormSkeleton $SearchFormSkeleton
             */

            if (isset($aArgs['postType']) && !empty($aArgs['postType'])) {
                $oSearchFieldSkeleton = SearchFormSkeleton::load($aArgs['postType']);
            } else {
                $aArgs['postType'] = GetSettings::getDefaultPostType(true);
                $oSearchFieldSkeleton = SearchFormSkeleton::load($aArgs['postType']);
            }

            foreach ($aTaxonomies as $taxonomy) {
                if (isset($aArgs[$taxonomy])) {
                    $isMultiple = $oSearchFieldSkeleton->getFieldParam($taxonomy, 'isMultiple');
                    if ($isMultiple === 'yes') {
                        foreach ($aArgs[$taxonomy] as $termId) {
                            $aRawTerm = get_term($termId, $taxonomy);
                            if (!is_wp_error($aRawTerm) && !empty($aRawTerm)) {
                                $aTerms[] = $aRawTerm;
                            }
                        }
                    } else {
                        $aArgs[$taxonomy] = is_array($aArgs[$taxonomy]) ? $aArgs[$taxonomy][0] : $aArgs[$taxonomy];
                        $aRawTerm = get_term($aArgs[$taxonomy], $taxonomy);
                        if (!is_wp_error($aRawTerm) && !empty($aRawTerm)) {
                            $aTerms[] = $aRawTerm;
                        }
                    }
                }
            }

            if (is_tax()) {
                $oQueriedObject = get_queried_object();
                $aArgs[$oQueriedObject->taxonomy] = [$oQueriedObject->term_id];
            }

            $aExcludePostTypesFromMap = GetSettings::getPostMeta((int)$searchPageID, 'exclude_post_types_from_map');
            $aExcludePostTypesFromMap = empty($aExcludePostTypesFromMap) ? [] : $aExcludePostTypesFromMap;

            $aScripts['query'] = $aArgs;

            $aScripts['excludePostTypesFromMap'] = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/printSearchFormQuery/excludePostTypesFromMap',
                $aExcludePostTypesFromMap
            );
            $aScripts['terms'] = $aTerms;
            $style = GetSettings::getPostMeta($searchPageID, 'style');

            if (!empty($style)) {
                $aScripts['gridLayout'] = $style;
            }
            $aScripts = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/printSearchFormQuery',
                $aScripts
            );

            if (WPML::isActive()) {
                $aScripts['lang'] = WPML::getCurrentLanguage();
            }
            ?>
            <script lang="javascript">
                window.WILCITY_SEARCH = "<?php echo base64_encode(json_encode($aScripts)) ?>";
            </script>
            <?php
        }
    }

    private function buildTermsArgs($aData): array
    {
        return wp_parse_args(
            $aData,
            [
                'order'   => 'DESC',
                'orderby' => 'count',
                'number'  => 10
            ]
        );
    }

    private function buildTermsArgsOnRestQuery($aRequest)
    {
        global $wpdb;
        $aRequest = wp_parse_args(
            $aRequest,
            [
                'taxonomy'    => '',
                'order'       => 'DESC',
                'orderBy'     => 'count',
                'number'      => 40,
                'isHideEmpty' => false
            ]
        );

        if (!is_bool($aRequest['isHideEmpty'])) {
            $aRequest['isHideEmpty'] = $aRequest['isHideEmpty'] == 'yes' || $aRequest['isHideEmpty'] == 1;
        }

        $aArgs = $this->buildTermsArgs(
            [
                'taxonomy'   => $wpdb->_real_escape($aRequest['taxonomy']),
                'order'      => $wpdb->_real_escape($aRequest['order']),
                'orderby'    => $wpdb->_real_escape($aRequest['orderBy']),
                'number'     => $wpdb->_real_escape($aRequest['count']),
                'hide_empty' => $wpdb->_real_escape($aRequest['isHideEmpty'])
            ]
        );

        // Getting terms by geocoder
        if (isset($aRequest['context']) && !empty($aRequest['context'])) {
            $aArgs['meta_query'][] = [
                [
                    'key'     => 'wilcity_location_code',
                    'value'   => $aRequest['context'],
                    'compare' => '='
                ]
            ];
        }

        if (isset($aRequest['parent']) && TermSetting::hasTermChildren($aRequest['parent'], $aRequest['taxonomy'])) {
            $aArgs['parent'] = absint($aRequest['parent']);
        } elseif (isset($aRequest['isShowParentOnly'])) {
            if ($aRequest['isShowParentOnly'] === 'yes') {
                $aArgs['parent'] = 0;
            }
        }

        if (isset($aRequest['postType']) && !empty($aRequest['postType'])) {
            $aArgs['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key'     => 'wilcity_belongs_to',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key'     => 'wilcity_belongs_to',
                    'compare' => 'LIKE',
                    'value'   => $wpdb->_real_escape($aRequest['postType'])
                ]
            ];
        }

        if ($aRequest['taxonomy'] === 'listing_tag') {
            if (isset($aRequest['listing_cat']) && !empty($aRequest['listing_cat'])) {
                $aArgs['listing_cat_revelation'] = $aRequest['listing_cat'];
            }
        }

        if (isset($aRequest['search']) && !empty($aRequest['search'])) {
            $aArgs['name__like'] = sanitize_text_field($aRequest['search']);
        }

        if (isset($aRequest['postalCode']) && !empty($aRequest['postalCode'])) {
            if ($aRequest['taxonomy'] === 'listing_location') {
                $aArgs['meta_query'][] = [
                    [
                        'key'   => 'wilcity_postal_code',
                        'value' => $wpdb->_real_escape($aRequest['postalCode'])
                    ]
                ];
            }
        }

        return apply_filters('wilcity/filter/set-term-args', $aArgs, $aRequest);
    }

    public function getSubLocations(WP_REST_Request $oRequest)
    {
        $oRetrieve = new RetrieveController(new RestRetrieve());
        $aArgs = $this->buildTermsArgs($oRequest->get_params());
        $aTerms = TermSetting::getTerms($aArgs);
        if (empty($aTerms) || is_wp_error($aTerms)) {
            return $oRetrieve->error([]);
        }

        return $oRetrieve->success(['terms' => array_values($aTerms)]);
    }

    public function fetchTerms(WP_REST_Request $oRequest)
    {
        WPML::cookieCurrentLanguage();
        $aParams = $oRequest->get_params();
        if (isset($aParams['maximum']) && !empty($aParams['maximum'])) {
            $aParams['number'] = $aParams['maximum'];
        }

        $aTerms = get_terms(
            $this->buildTermsArgsOnRestQuery($aParams)
        );

        $oRetrieve = new RetrieveController(new RestRetrieve());
        if (empty($aTerms) || is_wp_error($aTerms)) {
            return $oRetrieve->error(['msg' => esc_html__('Oops! We found no term', 'wiloke-listing-tools')]);
        }

        if ($oRequest->get_param('mode') == 'select') {
            $aTerms = array_map(function ($oTerm) use ($aParams) {
                $children = TermSetting::hasTermChildren($oTerm->term_id, $oTerm->taxonomy);

                $aTerm = get_object_vars($oTerm);
                $aIcon = isset($aParams['component']) && in_array($aParams['component'], [
                    'herosearch',
                    'mainsearch',
                    'addlisting'
                ]) ?
                    WilokeHelpers::getTermOriginalIcon($oTerm, false) :
                    WilokeHelpers::getTermOriginalIcon($oTerm, true);

                $aResult = array_merge([
                    'type'        => 'term',
                    'label'       => $oTerm->name,
                    'ID'          => $oTerm->term_id,
                    'id'          => absint($oTerm->term_id),
                    'img'         => $aIcon['url'] ?? '',
                    'featuredImg' => $aIcon['url'] ?? '',
                    'icon'        => $aIcon['icon'] ?? '',
                    'iconColor'   => $aIcon['iconColor'] ?? ''
                ], $aTerm, $aIcon);

                if ($children) {
                    $aResult['children'] = null;
                }

                if ($oTerm->taxonomy === 'listing_tag') {
                    $aResult['belongsTo'] = TermSetting::getTagsBelongsTo($oTerm->term_id);
                }

                return $aResult;
            }, $aTerms);

            return $oRetrieve->success(['results' => array_values($aTerms)]);
        }

        if ($oRequest->get_param('taxonomy') === 'listing_tag') {
            $aTerms = array_map(function ($oTerm) {
                $aTerm = get_object_vars($oTerm);
                if ($oTerm->taxonomy === 'listing_tag') {
                    $aTerm['belongsTo'] = TermSetting::getTagsBelongsTo($oTerm->term_id);
                }

                return (object)$aTerm;
            }, $aTerms);
        }

        return $oRetrieve->success(['terms' => array_values($aTerms)]);
    }

    public function getListingsInTerm()
    {
        $args = file_get_contents("php://input");
        if (empty($args)) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('There is no listing yet', 'wiloke-listing-tools')
            ];
        }

        $aRequest = json_decode($args, true);
        $aArgs = json_decode(base64_decode($aRequest['args']), true);

        $aSCSettings = json_decode(base64_decode($aRequest['scSettings']), true);

        $aArgs['post_type'] = $aRequest['postType'];

        if ($aArgs['orderby'] == 'nearbyme') {
            $aArgs['geocode'] = $aRequest['oAddress'];
        }

        $query = new WP_Query($aArgs);

        if (!$query->have_posts()) {
            wp_reset_postdata();

            return [
                'status' => 'error',
                'msg'    => esc_html__('There is no listing yet', 'wiloke-listing-tools')
            ];
        }

        $aListings = [];
        $aAtts = [
            'maximum_posts_on_lg_screen' => 'col-lg-3',
            'maximum_posts_on_md_screen' => 'col-md-4',
            'maximum_posts_on_sm_screen' => 'col-sm-6',
            'img_size'                   => 'wilcity_360x200'
        ];

        $aAtts = wp_parse_args($aSCSettings, $aAtts);
        while ($query->have_posts()) {
            $query->the_post();
            $aListings[] = self::jsonSkeleton($query->post, $aAtts);
        }
        wp_reset_postdata();

        return [
            'status'   => 'success',
            'listings' => $aListings
        ];
    }

    public static function flushSearchCache()
    {
        $aPostTypeKeys = General::getPostTypeKeys(false, false);

        if (empty($aPostTypeKeys)) {
            return false;
        }

        foreach ($aPostTypeKeys as $postType) {
            SetSettings::deleteOption(General::mainSearchFormSavedAtKey($postType), true);
        }
    }

    public function saveSearchFormVersion($postType, $aFields)
    {
        SetSettings::setOptions($this->searchFormVersionKey, current_time('timestamp'));
    }

    public function fetchHeroSearchFormSuggestion()
    {
        WPML::cookieCurrentLanguage();
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aQuickSearchForm = GetSettings::getOptions('quick_search_form_settings', false, true);
        if (
            !isset($aQuickSearchForm['toggle_quick_search_form']) ||
            $aQuickSearchForm['toggle_quick_search_form'] == 'no'
        ) {
            return $oRetrieve->error([]);
        }

        if ($aQuickSearchForm['suggestion_order_by'] == 'rand') {
            $aListOrderBy = [
                'count' => 'Count',
                'id'    => 'ID',
                'slug'  => 'Slug',
                'name'  => 'Name',
                'none'  => 'None'
            ];
            $orderby = array_rand($aListOrderBy);
        } else {
            $orderby = $aQuickSearchForm['suggestion_order_by'];
        }

        $args = [
            'taxonomy' => $aQuickSearchForm['taxonomy_suggestion'],
            'number'   => !empty($aQuickSearchForm['number_of_term_suggestions']) ?
                $aQuickSearchForm['number_of_term_suggestions'] : 6,
            'orderby'  => $orderby,
            'order'    => $aQuickSearchForm['suggestion_order']
        ];

        if (isset($aQuickSearchForm['isShowParentOnly']) && $aQuickSearchForm['isShowParentOnly'] == 'yes') {
            $args['parent'] = 0;
        }

        $aTerms = GetSettings::getTerms($args);

        if (empty($aTerms) || is_wp_error($aTerms)) {
            $oRetrieve->error([]);
        }

        foreach ($aTerms as $order => $oTerm) {
            $aTerms[$order]->link = get_term_link($oTerm);
            $aGradientSettings = GetSettings::getTermGradients($oTerm);
            $aTerms[$order]->oGradient = $aGradientSettings;
            $aTerms[$order]->featuredImg = WilokeHelpers::getTermFeaturedImage($oTerm, [700, 350]);
        }

        $oRetrieve->success(['terms' => $aTerms, 'title' => $aQuickSearchForm['taxonomy_suggestion_title']]);
    }

    public static function isValidTerm($postType, $oTerm)
    {
        $aTermBelongsTo = GetSettings::getTermMeta($oTerm->term_id, 'belongs_to');
        if (in_array($oTerm->term_id, self::$aTermsPrinted)) {
            return false;
        }

        if (empty($aTermBelongsTo)) {
            return true;
        }

        return in_array($postType, $aTermBelongsTo);
    }

    public function buildTermItemInfo($oTerm)
    {
        $aTerm['value'] = $oTerm->slug;
        $aTerm['name'] = $oTerm->name;
        $aTerm['parent'] = $oTerm->parent;

        $aIcon = WilokeHelpers::getTermOriginalIcon($oTerm);
        if ($aIcon) {
            $aTerm['oIcon'] = $aIcon;
        } else {
            $featuredImgID = GetSettings::getTermMeta($oTerm->term_id, 'featured_image_id');
            $featuredImg = wp_get_attachment_image_url($featuredImgID, [32, 32]);
            $aTerm['oIcon'] = [
                'type' => 'image',
                'url'  => $featuredImg
            ];
        }

        return $aTerm;
    }

    public function fetchTermOptions()
    {
        $at = absint($_POST['at']);
        $savedAt = GetSettings::getOptions('get_taxonomy_saved_at', false, true);

        if (empty($savedAt)) {
            $savedAt = current_time('timestamp', 1);
            SetSettings::setOptions('get_taxonomy_saved_at', $savedAt);
        }

        if ($at == $savedAt) {
            wp_send_json_success([
                'action' => 'used_cache'
            ]);
        }

        if (isset($_POST['orderBy']) && !empty($_POST['orderBy'])) {
            $orderBy = $_POST['orderBy'];
        } else {
            $orderBy = 'count';
        }

        if (isset($_POST['order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
        } else {
            $order = 'DESC';
        }

        $isShowParentOnly = isset($_POST['isShowParentOnly']) && $_POST['isShowParentOnly'] == 'yes';
        $aRawTerms = GetSettings::getTaxonomyHierarchy([
            'taxonomy'   => $_POST['taxonomy'],
            'orderby'    => $orderBy,
            'order'      => $order,
            'hide_empty' => isset($_POST['isHideEmpty']) ? $_POST['isHideEmpty'] : 0,
            'parent'     => 0
        ], $_POST['postType'], $isShowParentOnly, true);

        if (!$aRawTerms) {
            $aTerms = [
                [
                    -1 => esc_html__('There are no terms', 'wiloke-listing-tools')
                ]
            ];
        } else {
            $aTerms = [];
            foreach ($aRawTerms as $oTerm) {
                if (isset($_POST['postType']) && !self::isValidTerm($_POST['postType'], $oTerm)) {
                    continue;
                }

                $aTerms[] = $this->buildTermItemInfo($oTerm);
            }
        }
        wp_send_json_success([
            'terms'  => $aTerms,
            'action' => 'update_new_terms',
            'at'     => $savedAt
        ]);
    }

    public function fetchHeroSearchFields(WP_REST_Request $oRequest)
    {
        WPML::cookieCurrentLanguage();
        $oController = new RetrieveController(new RestRetrieve());

        $aRequest = $oRequest->get_params();
        $aCache = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/fetchHeroSearchFields',
            '',
            $aRequest
        );

        if ($aCache) {
            return $oController->success($aCache);
        }

        $postType = $oRequest->get_param('postType');

        $at = $oRequest->get_param('cacheAt') ? absint($oRequest->get_param('cacheAt')) : 0;

        if (empty($postType)) {
            return $oController->error([
                'msg' => esc_html__('The Listing Type is required.', 'wiloke-listing-tools')
            ]);
        }

        $at = absint($at);
        $savedAt = GetSettings::getOptions(General::heroSearchFormSavedAt($postType), false, true);

        if (empty($savedAt)) {
            $savedAt = current_time('timestamp', 1);
            SetSettings::setOptions(General::heroSearchFormSavedAt($postType), $savedAt, true);
        } else {
            $savedAt = absint($savedAt);
        }

        if ($at > $savedAt) {
            return $oController->success([
                'action' => 'use_cache'
            ]);
        }

        $aFields = GetSettings::getOptions(General::getHeroSearchFieldsKey($postType), false, true);
        $oSearchFieldSkeleton = App::get('SearchFieldSkeleton');
        $oSearchFieldSkeleton->setFields($aFields)->setPostType($postType);

        $aFields = array_map(function ($aField) use ($oSearchFieldSkeleton) {
            if (isset($aField['group']) && $aField['group'] == 'term') {
                if ($aField['isAjax'] === 'no') {
                    $aField['loadOptionMode'] = 'ajaxloadroot';
                } else {
                    $aField['loadOptionMode'] = 'ajax';
                }


                $aField['searchUrl'] = rest_url(WILOKE_PREFIX . '/v2/taxonomy/' . $aField['key']);
            }

            $aRawField = $oSearchFieldSkeleton->getField($aField);

            return !empty($aRawField) ? $aRawField : $aField;
        }, $aFields);

        $aFields = apply_filters('wilcity/filter/wiloke-listing-tools/hero-search-form/fields', $aFields);

        if (empty($aFields)) {
            return $oController->error([
                'msg' => esc_html__(
                    'Please go to Wiloke Tools -> Your Listing Type settings -> Add some fields to Hero Search Form',
                    'wiloke-listing-tools'
                )
            ]);
        }

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/SearchFormController/fetchHeroSearchFields/results',
            [
                'fields'    => $aFields,
                'timestamp' => $savedAt,
                'action'    => 'update_search_fields'
            ],
            $aRequest
        );

        return $oController->success([
            'fields'    => $aFields,
            'timestamp' => $savedAt,
            'action'    => 'update_search_fields'
        ]);
    }

    protected function getTag($oTerm, $aQuery = [])
    {
        $aTagSlugs = GetSettings::getTermMeta($oTerm->term_id, 'tags_belong_to');
        if (empty($aTagSlugs)) {
            return false;
        }

        $aTagIDs = [];
        foreach ($aTagSlugs as $tag) {
            $oTag = get_term_by('slug', $tag, 'listing_tag');
            if ($oTag) {
                $aTagIDs[] = $oTag->term_id;
            }
        }

        $aArgs = [
            'taxonomy' => 'listing_tag',
            'include'  => $aTagIDs
        ];

        if (isset($aQuery['order']) && !empty($aQuery['order'])) {
            $aArgs['order'] = $aQuery['order'];
        }

        if (isset($aQuery['orderBy']) && !empty($aQuery['orderBy'])) {
            $aArgs['orderby'] = $aQuery['orderBy'];
        }

        if (isset($aQuery['hide_empty'])) {
            $aArgs['hide_empty'] = filter_var($aQuery['hide_empty'], FILTER_VALIDATE_BOOLEAN);
        } else {
            $aArgs['hide_empty'] = false;
        }

        $aTerms = get_terms($aArgs);
        if (empty($aTerms) || is_wp_error($aTerms)) {
            return false;
        }

        foreach ($aTerms as $oTag) {
            if (in_array($oTag->slug, $this->aGotTags)) {
                continue;
            }

            $this->aTagsBelongToCat[] = [
                'value' => $oTag->slug,
                'name'  => $oTag->name,
                'label' => $oTag->name
            ];
            $this->aGotTags[] = $oTag->slug;
        }
    }

    public function fetchIndividualCatTags()
    {
        if (!isset($_POST['termSlug']) || empty($_POST['termSlug'])) {
            wp_send_json_error();
        } else {
            if (is_array($_POST['termSlug'])) {
                foreach ($_POST['termSlug'] as $termID) {
                    $oTerm = get_term_by('slug', $termID, 'listing_cat');
                    $this->getTag($oTerm, $_POST);
                }
            } else {
                $oTerm = get_term_by('slug', $_POST['termSlug'], 'listing_cat');
                $this->getTag($oTerm, $_POST);
            }

            if (empty($this->aTagsBelongToCat)) {
                wp_send_json_error();
            }

            wp_send_json_success($this->aTagsBelongToCat);
        }
    }

    public static function getSearchFormSettings()
    {
        if (empty(self::$aSearchFormSettings)) {
            self::$aSearchFormSettings = GetSettings::getOptions('quick_search_form_settings', false, true);
        }

        return self::$aSearchFormSettings;
    }

    public function fetchTermsSuggestions()
    {
        self::getSearchFormSettings();
        $isShowParentOnly = isset(self::$aSearchFormSettings['isShowParentOnly']) &&
        self::$aSearchFormSettings['isShowParentOnly'] == 'yes' ? 1 : '';
        $aRawCategories = GetSettings::getTerms([
            'taxonomy'   => self::$aSearchFormSettings['taxonomy_suggestion'],
            'number'     => self::$aSearchFormSettings['number_of_term_suggestions'],
            'orderby'    => self::$aSearchFormSettings['suggestion_order_by'],
            'hide_empty' => false,
            'parent'     => $isShowParentOnly
        ]);

        if (!empty($aRawCategories) && !is_wp_error($aRawCategories)) {
            $aCategories = [];
            foreach ($aRawCategories as $oRawCategory) {
                $aCategories[] = [
                    'name'  => $oRawCategory->name,
                    'slug'  => $oRawCategory->slug,
                    'id'    => $oRawCategory->term_id,
                    'link'  => get_term_link($oRawCategory),
                    'oIcon' => WilokeHelpers::getTermOriginalIcon($oRawCategory)
                ];
            }

            wp_send_json_success([
                'aResults' => $aCategories
            ]);
        }

        wp_send_json_error();
    }

    private function isPassedDateRange($aArgs): bool
    {
        if (isset($aArgs['date_range'])) {
            if (empty($aArgs['date_range']['from']) || empty($aArgs['date_range']['to'])) {
                return false;
            }

            $dateFormat = apply_filters('wilcity_date_picker_format', 'mm/dd/yy');
            $dateFormat = Time::convertJSDateFormatToPHPDateFormat($dateFormat);
            $from = Time::toTimestamp($dateFormat, $aArgs['date_range']['from']);
            $to = Time::toTimestamp($dateFormat, $aArgs['date_range']['to']);
            if ($from > $to) {
                return false;
            }
        }

        return true;
    }

    public static function jsonSkeleton($post, $aAtts, $aPluck = [])
    {
        $oPostSkeleton = new PostSkeleton();
        $aListing = $oPostSkeleton->getSkeleton($post, $aPluck, $aAtts);
        $aListing['postID'] = $post->ID;

        $aListing = apply_filters('wilcity/filter-listing-slider/meta-data', $aListing, $post);
        $aListings[] = (object)$aListing;

        return $aListing;
    }

    public function fetchQuickSearchListings()
    {
        WPML::cookieCurrentLanguage();
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (!ValidationHelper::isValidJson($_GET['data'])) {
            $oRetrieve->error([]);
        }

        $aArgs = QueryHelper::buildQueryArgs(ValidationHelper::getJsonDecoded());

        $query = $this->createWPQuery(WPML::addFilterLanguagePostArgs($aArgs));
        if ($query instanceof WP_Query === false && !empty($query)) {
            return $oRetrieve->success($query);
        }

        if (!$query->have_posts()) {
            wp_reset_postdata();

            return $oRetrieve->error([]);
        }

        $oPostSkeleton = new PostSkeleton();
        while ($query->have_posts()) {
            $query->the_post();
            $aPostsNotIn[] = $query->post->ID;
            $aListings[] = $oPostSkeleton->getSkeleton($query->post,
                [
                    'ID',
                    'tagLine',
                    'title',
                    'permalink',
                    'featuredImage',
                ],
                [
                    'img_size' => 'large'
                ]
            );
        }
        wp_reset_postdata();

        return $oRetrieve->success($aListings);
    }

    public function searchFormJson(WP_REST_Request $oRequest)
    {
        $aRequest = $oRequest->get_params();

        $aCache = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/searchFormJson',
            '',
            $aRequest
        );

        $oRetrieve = new RetrieveController(new RestRetrieve());
        if ($aCache) {
            return $oRetrieve->success($aCache);
        }

        $aArgs = QueryHelper::buildQueryArgs($aRequest);
        unset($aArgs['userID']);

        $aArgs['posts_per_page'] = 12;
        $hasPostsNotIn = false;
        if ($aArgs['orderby'] == 'rand') {
            if (isset($aArgs['post__not_in']) && !empty($aArgs['post__not_in'])) {
                $hasPostsNotIn = true;
                unset($aArgs['paged']);
            }
        }

        $aError = [
            'msg' => esc_html__('Sorry, We found no posts matched what are you looking for ...', 'wiloke-listing-tools')
        ];

        if (!$this->isPassedDateRange($aArgs)) {
            return $oRetrieve->error($aError);
        }

        $query = $this->createWPQuery($aArgs);
        if ($query instanceof WP_Query === false && !empty($query)) {
            return $oRetrieve->success($query);
        }

        if (!$query->have_posts()) {
            wp_reset_postdata();

            return $oRetrieve->error($aError);
        }

        $aListings = [];

        $aAtts = [
            'maximum_posts_on_lg_screen' => 'col-lg-3',
            'maximum_posts_on_md_screen' => 'col-md-4',
            'maximum_posts_on_sm_screen' => 'col-sm-6',
            'img_size'                   => isset($aRequest['img_size']) && !empty($aRequest['img_size']) ?
                $aRequest['img_size'] : apply_filters(
                    'wilcity/filter/search-without-map/default-img-size',
                    'wilcity_360x200'
                ),
            'userID'                     => $aRequest['userID']
        ];

        $aPostsNotIn = [];
        while ($query->have_posts()) {
            $query->the_post();
            $aPostsNotIn[] = $query->post->ID;
            $aListings[] = self::jsonSkeleton($query->post, $aAtts);
        }
        wp_reset_postdata();

        $aResponse = [
            'listings' => $aListings,
            'maxPosts' => absint($query->found_posts),
            'maxPages' => absint($query->max_num_pages)
        ];

        if ($hasPostsNotIn) {
            $aResponse['maxPosts'] = absint($aResponse['maxPosts']) + count($aArgs['post__not_in']);
            $aResponse['maxPages'] = absint($aResponse['maxPages']) + 1;
            $aResponse['postsNotIn'] = $aPostsNotIn;
        }

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/SearchFormController/search-results',
            $aResponse,
            $aRequest
        );


        return $oRetrieve->success($aResponse);
    }

    /**
     * @WP_REST_Request("/listings}", methods={"GET"})
     * @param WP_REST_Request $oRequest
     * @return mixed
     */
    public function fetchListings(WP_REST_Request $oRequest)
    {
        $lang = $oRequest->get_param("lang");
        if (!empty($lang)) {
            WPML::setCurrentLanguage($lang);
        } else {
            WPML::cookieCurrentLanguage();
        }

        $aRequest = $oRequest->get_params();
        $oRetrieve = new RetrieveController(new RestRetrieve());
        $aCache = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/fetchListings',
            '',
            $aRequest
        );

        if ($aCache) {
            return $oRetrieve->success($aCache);
        }

        $aArgs = QueryHelper::buildQueryArgs($aRequest);
        $aArgs['isWilcitySearch'] = 'yes';

        $filter = 'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/fetchListings';
        if (is_string($aArgs['post_type'])) {
            $postTypeFilter = $filter . '/' . $aArgs['post_type'] . '/';
        }

        $aError = [
            'msg' => esc_html__('Sorry, We found no posts matched what are you looking for ...', 'wiloke-listing-tools')
        ];

        if (!$this->isPassedDateRange($aArgs)) {
            return $oRetrieve->error($aError);
        }

        if (isset($postTypeFilter) && has_filter($postTypeFilter . 'args')) {
            $aArgs = apply_filters($postTypeFilter . 'args', $aArgs, $aRequest);
        }

        if (WPML::isActive()) {
            $aArgs = WPML::addFilterLanguagePostArgs($aArgs);
        }

        if (isset($postTypeFilter) && has_filter($postTypeFilter . 'createQuery')) {
            $query = apply_filters($postTypeFilter . 'createQuery', null, $aArgs, $aRequest);
            if (empty($query) || is_wp_error($query)) {
                return $oRetrieve->error($aError);
            }
        } else {
            $query = $this->createWPQuery($aArgs);
            if ($query instanceof WP_Query === false && !empty($query)) {
                return $oRetrieve->success($query);
            }

            if (!$query->have_posts()) {
                wp_reset_postdata();

                return $oRetrieve->error($aError);
            }
        }

        $aListings = [];

        $aAtts = [
            'img_size' => apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/SearchFormController/fetchListings/imageSize',
                'large',
                $aRequest
            ),
            'userID'   => $oRequest->get_param('userID')
        ];

        if ($aArgs['post_type'] === 'event') {
            if (isset($aArgs['orderby']) && strpos($aArgs['orderby'], 'menu_order') === false) {
                $aAtts['ignoreMenuOrder'] = true;
            }
        }

        $aPostsNotIn = [];
        if (isset($postTypeFilter)) {
            $aArgs = apply_filters($postTypeFilter . 'args', $aArgs, $aRequest);
        }

        $oPostSkeleton = new PostSkeleton();
        if (isset($postTypeFilter) && has_filter($postTypeFilter . 'PostSkeleton')) {
            $oPostSkeleton = apply_filters($postTypeFilter . 'PostSkeleton', $oPostSkeleton, $aArgs, $aRequest);
        }

        if (isset($aRequest['pluck']) && !empty($aRequest['pluck'])) {
            $aPluck = is_string($aRequest['pluck']) ? explode(',', trim($aRequest['pluck'])) : $aRequest['pluck'];
        } else {
            $aPluck = [
                'headerCard',
                'bodyCard',
                'footerCard',
                'title',
                'featuredImage',
                'oReviews',
                'ID',
                'postID',
                'logo',
                'excerpt',
                'permalink',
                'isAds',
                'isClaimed',
                'oAddress',
                'coupon',
                'postType',
                'isTopOfSearch'
            ];
        }

        if (General::isPostTypeInGroup($aArgs['post_type'], 'event')) {
            $aPluck = array_merge($aPluck, ['eventData', 'isMyFavorite']);
        }

        if (isset($postTypeFilter) && has_filter($postTypeFilter . 'pluck')) {
            $aPluck = apply_filters($postTypeFilter . 'pluck', $aPluck, $aArgs, $aRequest);
        }

        if (isset($aRequest['pageNow'])) {
            switch ($aRequest['pageNow']) {
                case 'search':
                    $aAtts['adsType'] = 'TOP_SEARCH';
                    break;
                case 'homepage':
                    $aAtts['adsType'] = 'GRID';
                    break;
            }
        }

        if (isset($postTypeFilter) && has_filter($postTypeFilter . 'query')) {
            $aListings = apply_filters(
                $postTypeFilter . 'query',
                [],
                $query,
                $oPostSkeleton,
                $aArgs,
                $aPluck,
                $aAtts
            );
        } else {
            while ($query->have_posts()) {
                $query->the_post();
                $aPostsNotIn[] = $query->post->ID;
                $aListings[] = $oPostSkeleton->getSkeleton(
                    $query->post,
                    $aPluck,
                    $aAtts
                );
            }
        }
        wp_reset_postdata();

        $aResponse = [
            'listings' => $aListings,
            'maxPosts' => absint($query->found_posts),
            'maxPages' => absint($query->max_num_pages)
        ];


        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/SearchFormController/fetchListings/results',
            $aResponse,
            $aRequest
        );


        return $oRetrieve->success(apply_filters('wilcity/filter/set-query-values', $aResponse, $aArgs));
    }

    /**
     * @param WP_REST_Request $oRequest
     *
     * @return mixed
     */
    public function fetchSearchFields(WP_REST_Request $oRequest)
    {
        WPML::cookieCurrentLanguage();
        $oRetrieve = new RetrieveController(new RestRetrieve());

        $cacheAt = absint($oRequest->get_param('cacheAt'));
        $postType = $oRequest->get_param('postType') ? $oRequest->get_param('postType') : 'listing';

        $savedAt = GetSettings::getOptions(General::mainSearchFormSavedAtKey($postType), false, true);
        if (empty($savedAt)) {
            $savedAt = current_time('timestamp', 1);
            SetSettings::setOptions(General::mainSearchFormSavedAtKey($postType), $savedAt, true);
        }

        if ($cacheAt >= $savedAt) {
            return $oRetrieve->success([
                'action' => 'use_cache'
            ]);
        }

        /**
         * @hooked WilcityRedis\Controllers\SearchController:getSearchFields
         */
        $aSearchFields = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controller/SearchFormController/before/getSearchFields',
            [],
            General::getSearchFieldsKey($postType)

        );

        if (!empty($aSearchFields)) {
            return $aSearchFields;
        }

        $aSearchFields = GetSettings::getOptions(General::getSearchFieldsKey($postType), false, true);

        if (empty($aSearchFields)) {
            return $oRetrieve->error([
                'msg' => sprintf(esc_html__(
                    'Oops! You have not not configured the search fields yet. To configure the search field, go to Wiloke Tools -> %s Settings -> Main Search Form',
                    'wiloke-listing-tools'
                ), ucfirst(str_replace('_', ' ', $postType))),
            ]);
        } else {
            $aFields = [];
            $oSearchFieldSkeleton = new SearchFieldSkeleton();
            $oSearchFieldSkeleton->setFields($aSearchFields)->setPostType($postType);

            foreach ($aSearchFields as $key => $aSearchField) {
                $aField = $aSearchField;
                if (isset($aSearchField['originalKey'])) {
                    $originalKey = $aSearchField['originalKey'];
                } else {
                    $originalKey = $aSearchField['key'];
                }
                switch ($originalKey) {
                    case 'custom_dropdown':
                    case 'price_range':
                    case 'post_type':
                    case 'event_filter':
                    case 'orderby':
                    case 'order':
                        $aField = $oSearchFieldSkeleton->getField($aSearchField);
                        break;
                    case 'google_place':
                        $aField['address'] = isset($_REQUEST['address']) ? stripslashes($_REQUEST['address']) : '';
                        break;
                }

                if (isset($aSearchField['childType'])) {
                    switch ($aSearchField['childType']) {
                        case 'wil-checkbox':
                            if (isset($aSearchField['isMultiple']) && $aSearchField['isMultiple'] === 'no') {
                                $aField['childType'] = 'wil-radio';
                            }
                            break;
                        case 'wil-radio':
                            if (isset($aSearchField['isMultiple']) && $aSearchField['isMultiple'] === 'yes') {
                                $aField['childType'] = 'wil-checkbox';
                            }
                            break;
                    }
                }

                if (!empty($aField)) {
                    $aFields[] = $aField;
                }
            }
        }

        /**
         * @hooked WilcityRedis\Controllers\SearchController:cacheSearchFields
         */
        return $oRetrieve->success(
            apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controller/SearchFormController/after/getSearchFields',
                [
                    'fields'    => $aFields,
                    'timestamp' => $savedAt,
                    'action'    => 'update_search_fields',
                    'postType'  => $postType
                ],
                General::getSearchFieldsKey($postType)
            )
        );
    }
}
