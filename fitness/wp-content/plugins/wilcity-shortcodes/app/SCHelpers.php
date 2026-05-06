<?php

namespace WILCITY_SC;

use NumberFormatter;
use WC_Product;
use Wiloke;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\MetaBoxes\Review;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\UserModel;

class SCHelpers
{
	public static  $isApp     = false;
	private static $listingID = '';
	public static  $post;

	public static function getCustomSCClass($sc)
	{
		if (strpos($sc, 'wilcity_render') !== false) {
			preg_match('/(wilcity_render_)([^\s]*)/', $sc, $aMatches);
			if (isset($aMatches[2])) {
				return $aMatches[2];
			}
		}

		return '';
	}

	public static function getColumnClasses($aAtts)
	{
		if (!isset($aAtts['maximum_posts_on_md_screen'])) {
			return '';
		}

		return $aAtts['maximum_posts_on_md_screen'] . ' ' . $aAtts['maximum_posts_on_lg_screen'] . ' ' .
			$aAtts['maximum_posts_on_sm_screen'] . ' col-xs-6';
	}

	public static function parseHeading($aAtts, $toJSON = true)
	{
		$aParams = shortcode_atts(
			[
				'mark'                   => '',
				'mark_color'             => '',
				'heading'                => '',
				'heading_color'          => '',
				'desc'                   => '',
				'desc_color'             => '',
				'header_desc_text_align' => '',
				'wrapper_classes'        => 'heading_module__156eJ'
			],
			$aAtts
		);

		if (is_tax()) {
			$termName = get_queried_object()->name;
			$aParams['heading'] = str_replace('%termName%', $termName, $aParams['heading']);
			$aParams['desc'] = str_replace('%termName%', $termName, $aParams['desc']);
		}

		if (isset($aAtts['header_desc_text_align'])) {
			$aParams['wrapper_classes'] .= ' ' . $aAtts['header_desc_text_align'];
		}

		if (isset($aAtts['extra_class'])) {
			$aParams['wrapper_classes'] .= ' ' . $aAtts['extra_class'];
		}

		if (isset($aAtts['blue_mark'])) {
			$aParams['mark'] = $aAtts['blue_mark'];
		}

		if (isset($aAtts['blue_mark_color'])) {
			$aParams['mark_color'] = $aAtts['blue_mark_color'];
		}

		return $toJSON ? json_encode($aParams, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : $aParams;
	}

	public static function getLinkRel($url)
	{
		$homeURL = home_url('/');

		return strpos($url, $homeURL) !== false ? 'follow' : 'nofollow';
	}

	public static function renderLazyLoad($imgUrl, $aAtts = [], $isFocusRender = false)
	{
		$aAtts = wp_parse_args(
			$aAtts,
			[
				'divClass'       => '',
				'divInfo'        => [],
				'imgClass'       => '',
				'isNotRenderImg' => false,
				'alt'            => ''
			]
		);

		if (!$isFocusRender && \WilokeThemeOptions::isEnable('general_toggle_lazyload')) :
			?>
            <div class="<?php echo esc_attr($aAtts['divClass'] .
				apply_filters('wilcity/filter/class-prefix', ' wilcity-lazyload')); ?>"
                 data-src="<?php echo esc_url($imgUrl); ?>">
				<?php if (!$aAtts['isNotRenderImg']) : ?>
                    <img class="<?php echo esc_attr($aAtts['imgClass'] . apply_filters('wilcity/filter/class-prefix',
							' wilcity-lazyload')); ?>" data-src="<?php echo esc_url($imgUrl); ?>"
                         alt="<?php echo esc_attr($aAtts['alt']); ?>">
				<?php endif; ?>
            </div>
		<?php
		else:
			?>
            <div class="<?php echo esc_attr($aAtts['divClass']); ?>"
                 data-info='<?php echo json_encode($aAtts['divInfo']); ?>'
                 style="background-image: url(<?php echo esc_url($imgUrl); ?>);">
				<?php if (!$aAtts['isNotRenderImg']) : ?>
                    <img class="<?php echo esc_attr($aAtts['imgClass']); ?>" src="<?php echo esc_url($imgUrl); ?>"
                         alt="<?php echo esc_attr($aAtts['alt']); ?>">
				<?php endif; ?>
            </div>
		<?php
		endif;
	}

	public static function removeUnnecessaryParamOnApp($aData)
	{
		unset($aData['isApp']);
		unset($aData['extra_class']);
		unset($aData['bg_color']);
		unset($aData['alignment']);
		unset($aData['blur_mark']);
		unset($aData['blur_mark_color']);

		return $aData;
	}

	/*
	 * This function helps to resolve Video Popup doesn't works if customer put &feature as query string
	 */
	public static function cleanYoutubeUrl($url)
	{

		if (!strpos($url, 'facebook.com')) {
			$aParseVideo = explode('?v=', $url);
			$aSplitQueryString = explode('&', $aParseVideo[1]);

			return $aParseVideo[0] . '?v=' . $aSplitQueryString[0];
		} else {
			return $url;
		}
	}

	public static function renderSidebarRating($post)
	{
		$averageRating = GetSettings::getAverageRating($post->ID, '', true, true);
		?>
        <div class="starRating_module__w77sS d-inline-block">
            <div class="starRating_point__12mp0"><?php echo GetSettings::getAverageRating($post->ID, true,
					true); ?></div>
            <div class="starRating_data__xAaEP" data-rating="<?php echo $averageRating; ?>"><i class="fa fa-star"></i><i
                        class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i
                        class="fa fa-star"></i>
            </div>
            <div class="starRating_text__3_CO9"></div>
        </div>
		<?php
	}

	public static function renderSidebarAddress($post)
	{
		$address = GetSettings::getAddress($post->ID, false);
		if (!empty($address)) :
			?>
            <div class="widget-listing2_excerpt__3wHpJ"><?php echo esc_html($address); ?></div>
		<?php
		endif;
	}

	public static function renderSidebarMetaData($post, $aAtts)
	{
		SCHelpers::renderAds($post, 'LISTING_SIDEBAR');
		do_action('wilcity/wilcity-shortcodes/wilcity-sidebar-' . $aAtts['style'] . '/start-meta-data', $post, $aAtts);
		if (!empty($aAtts['aMetaData'])) {
			foreach ($aAtts['aMetaData'] as $meta) {
				switch ($meta) {
					case 'rating':
						SCHelpers::renderSidebarRating($post);
						break;
					case 'address':
						SCHelpers::renderSidebarAddress($post);
						break;
					default:
						do_action(
							'wilcity/wilcity-shortcodes/wilcity-sidebar-' . $aAtts['style'] . '/new-meta-data',
							$post,
							$meta,
							$aAtts
						);
						break;
				}
			};
		}
		do_action('wilcity/wilcity-shortcodes/wilcity-sidebar-' . $aAtts['style'] . '/end-meta-data', $post, $aAtts);
	}

	public static function getViewAllUrl($atts)
	{
		global $wiloke;
		$aQuery = ['type' => $atts['post_type']];
		if (isset($atts['order']) && !empty($atts['order'])) {
			$aQuery['order'] = $atts['order'];
		}

		if (isset($atts['orderby']) && !empty($atts['orderby'])) {
			$aQuery['order_by'] = $atts['orderby'];
		}

		if (is_tax()) {
			$oTax = get_queried_object();
			$aQuery[$oTax->taxonomy] = $oTax->slug;
			if ($atts['post_type'] == 'depends_on_belongs_to') {
				$aDirectoryTypes = GetSettings::getTermMeta($oTax->term_id, 'belongs_to');
				if (empty($aDirectoryTypes)) {
					$aQuery['type'] = GetSettings::getDefaultPostType(true);
				} else {
					$aQuery['type'] = $aDirectoryTypes[0];
				}
			}
		} else {
			if (isset($atts['listing_cats']) && !empty($atts['listing_cats'])) {
				$aListingCatIDs = self::getAutoCompleteVal($atts['listing_cats']);
				$aListingCatSlugs = array_map(function ($catID) {
					$oTerm = get_term($catID, 'listing_cat');
					if (!empty($oTerm) && !empty($oTerm)) {
						return $oTerm->slug;
					}
				}, $aListingCatIDs);
				if (!empty($aListingCatSlugs)) {
					$aQuery['listing_cat'] = implode(',', $aListingCatSlugs);
				}
			}

			if (isset($atts['listing_tags']) && !empty($atts['listing_tags'])) {
				$aListingTagIDs = self::getAutoCompleteVal($atts['listing_tags']);
				$aListingTagIDs = array_map(function ($catID) {
					$oTerm = get_term($catID, 'listing_tag');
					if (!empty($oTerm) && !empty($oTerm)) {
						return $oTerm->term_id;
					}
				}, $aListingTagIDs);

				if (!empty($aListingTagIDs)) {
					$aQuery['listing_tag'] = implode(',', $aListingTagIDs);
				}
			}

			if (isset($atts['listing_locations']) && !empty($atts['listing_locations'])) {
				$aListingLocations = self::getAutoCompleteVal($atts['listing_locations']);
				$aListingLocations = array_map(function ($catID) {
					$oTerm = get_term($catID, 'listing_location');
					if (!empty($oTerm) && !empty($oTerm)) {
						return $oTerm->slug;
					}
				}, $aListingLocations);

				if (!empty($aListingLocations)) {
					$aQuery['listing_location'] = implode(',', $aListingLocations);
				}
			}
		}

		$url = esc_url(add_query_arg(
			$aQuery,
			get_permalink($wiloke->aThemeOptions['search_page'])
		));

		return apply_filters('wilcity-shortcode/button-view-more/url', $url,
			get_permalink($wiloke->aThemeOptions['search_page']), $aQuery, $atts);
	}

	public static function renderTermsOnCard($post, $aSettings)
	{
		$taxonomy = $aSettings['key'];
		$aTerms = wp_get_post_terms($post->ID, $taxonomy);
		if (empty($aTerms) || is_wp_error($aTerms)) {
			return '';
		}
		$icon = 'color-primary ' . $aSettings['icon'];
		?>
        <div class="d-inline-block mr-15 w-auto vertical-top grid-body-item-<?php echo esc_attr($taxonomy); ?>">
            <i class="<?php echo esc_attr($icon); ?>"></i>
            <div style="display: inline;">
				<?php foreach ($aTerms as $oTerm): ?>
                    <a href="<?php echo esc_url(get_term_link($oTerm)); ?>">
                        <span class="icon-box-1_text__3R39g"><?php echo esc_html($oTerm->name); ?></span>
                    </a>
				<?php endforeach; ?>
            </div>
        </div>
		<?php
	}

	public static function getPostMetaData($post, $metaKey)
	{
		switch ($metaKey) {
			case 'date':
				return date_i18n(get_option('date_format'), strtotime($post->post_date));
				break;
			case 'category':
				$aCategories = get_the_category($post->ID);
				if (empty($aCategories)) {
					return false;
				}
				$aCatNames = array_map(function ($oTerm) {
					return $oTerm->name;
				}, $aCategories);

				return implode(', ', $aCatNames);
				break;
			case 'comment':
				$comments = get_comments_number($post->ID);
				if (empty($comments)) {
					return esc_html__('No Comment', 'wilcity-shortcodes');
				} else if ($comments == 1) {
					return esc_html__('1 Comment', 'wilcity-shortcodes');
				} else if ($comments == 2) {
					return esc_html__('2 Comments', 'wilcity-shortcodes');
				} else {
					return sprintf(esc_html__('%d Comments', 'wilcity-shortcodes'), $comments);
				}
				break;
			default:
				return apply_filters('wilcity/post/meta-data/item/' . $metaKey, '', $post);
				break;
		}
	}

	public static function decodeAtts($atts)
	{
		if ($atts) {
			if ($aResponse = json_decode(base64_decode($atts), true)) {
				return $aResponse;
			}

			return json_decode(utf8_decode($atts), true);
		}

		return [];
	}

	public static function encodeAtts(array $atts)
	{
		if (!empty($atts)) {
			return base64_encode(json_encode($atts));
		}

		return $atts;
	}

	public static function parseImgSize($size)
	{
		if (empty($size)) {
			return '';
		}

		if (strpos($size, 'wilcity_') !== false) {
			return $size;
		} else if (strpos($size, ',') !== false) {
			return explode(',', $size);
		} else if (strpos($size, 'x') !== false) {
			return explode('x', $size);
		}

		return $size;
	}

	public static function prepareCustomSC($customSC, $postID = '', $isApp = false)
	{
		$customSC = str_replace(['{{', '}}'], ['"', '"'], $customSC);
		if (strpos($customSC, 'post_id') === false) {
			if (!empty($postID)) {
				self::$listingID = $postID;
				$customSC = preg_replace_callback('/\[[^\s]*\s/', function ($aMatched) {
					return $aMatched[0] . ' post_id="' . self::$listingID . '" is_grid="yes" ';
				}, $customSC, 1);
			}
		}

		if ($isApp) {
			$customSC = preg_replace_callback('/\[[^\s]*\s/', function ($aMatched) {
				return $aMatched[0] . ' is_mobile="yes" ';
			}, $customSC, 1);
		}

		return $customSC;
	}

	public static function getFeaturedImage($postID, $size = '')
	{
		$thumbnailURL = get_the_post_thumbnail_url($postID, $size);

		if (empty($thumbnailURL)) {
			global $wiloke;
			if (isset($wiloke->aThemeOptions['listing_featured_image']['id'])) {
				$thumbnailURL
					= wp_get_attachment_image_url($wiloke->aThemeOptions['listing_featured_image']['id'], $size);
			}

			if (empty($thumbnailURL)) {
				if (isset($wiloke->aThemeOptions['listing_featured_image']['url'])) {
					$thumbnailURL = $wiloke->aThemeOptions['listing_featured_image']['url'];
				}
			}
		}

		return $thumbnailURL;
	}

	public static function getPost()
	{
		if (wp_doing_ajax()) {
			$post = get_post($_POST['postID']);
		} else {
			global $post;
		}

		return $post;
	}

	public static function parseTermQuery($atts)
	{
		$aArgs = [
			'taxonomy'   => $atts['taxonomy'],
			'number'     => isset($atts['number']) ? $atts['number'] : 6,
			'hide_empty' => $atts['is_hide_empty'] == 'yes'
		];

		if (isset($atts[$atts['taxonomy'] . 's']) && !empty($atts[$atts['taxonomy'] . 's'])) {
			$aRawTermIDs = $atts[$atts['taxonomy'] . 's'];
			if (!empty($aRawTermIDs)) {
				$aRawTermIDs = is_array($aRawTermIDs) ? $aRawTermIDs : explode(',', $aRawTermIDs);
				$aTerms = [];

				foreach ($aRawTermIDs as $rawTerm) {
					$aParse = explode(':', $rawTerm);
					$aTerms[] = $aParse[0];
				}

				$aArgs['include'] = $aTerms;
				$aArgs['orderby'] = 'include';
				$aArgs['number'] = count($aTerms);
			} else {
				$aArgs['orderby'] = $atts['orderby'];
				$aArgs['order'] = $atts['order'];
			}
		} else {
			if (isset($atts['parent']) && !empty($atts['parent'])) {
				$aArgs['orderby'] = $atts['orderby'];
				$aArgs['order'] = $atts['order'];
				$aArgs['parent'] = $atts['parent'];
			} else if ($atts['is_show_parent_only'] == 'yes') {
				$aArgs['parent'] = 0;
			}
		}

		return $aArgs;
	}

	public static function mergeIsAppRenderingAttr($aAtts)
	{
		if (isset($_POST['post_ID'])) {
			$pageTemplate = get_page_template_slug($_POST['post_ID']);
			if ($pageTemplate == 'templates/mobile-app-homepage.php') {
				self::$isApp = true;
			}
		}
		$aAtts['isApp'] = self::$isApp;

		//        if (isset($aAtts['taxonomy']) && $aAtts['taxonomy'] == '_self') {
		//            $aAtts['taxonomy']            = get_query_var('taxonomy');
		//            $aAtts['is_show_parent_only'] = 'no';
		//            $aAtts['parent']              = get_queried_object_id();
		//        }

		return $aAtts;
	}

	public static function isApp($aAtts)
	{
		if (isset($aAtts['isApp']) && $aAtts['isApp']) {
			return true;
		}

		return false;
	}

	public static function renderPlanPrice($price, $aPriceSettings = [], $productID = null, $isComparedAt = false)
	{
		if (!empty($productID) && class_exists('\WC_Product')) {
			$oProduct = new WC_Product($productID);

			return '<span class="pricing_price__2vtrC color-primary">' . $oProduct->get_price_html() . '</span>';
		}

		$currencyPosition = GetWilokeSubmission::getField('currency_position');
		$currencyCode = GetWilokeSubmission::getField('currency_code');
		$currencySymbol = GetWilokeSubmission::getSymbol($currencyCode);

		$price = apply_filters('wilcity/filter/pricing-price', $price, $aPriceSettings);
		$classes = "pricing_price__2vtrC color-primary";
		if ($isComparedAt) {
			$price = "<del>" . $price . "</del>";
			$classes .= " wil-compared-price";
		}

		ob_start();
		?>
        <span class="<?php echo esc_attr($classes); ?>">
        <?php
        switch ($currencyPosition) {
	        case 'left':
		        ?>
                <sup class="pricing_currency__2bkpj"><?php echo esc_html($currencySymbol); ?></sup><span
                    class="pricing_amount__34e-B"><?php echo Wiloke::ksesHTML($price); ?></span>
		        <?php
		        break;
	        case 'right':
		        ?>
                <span
                        class="pricing_amount__34e-B"><?php echo Wiloke::ksesHTML($price); ?></span><sup
                    class="pricing_currency__2bkpj"><?php echo esc_html($currencySymbol); ?></sup>
		        <?php
		        break;
	        case 'left_space':
		        ?>
                <sup
                        class="pricing_currency__2bkpj"><?php echo esc_html($currencySymbol); ?></sup> <span
                    class="pricing_amount__34e-B"><?php echo Wiloke::ksesHTML($price); ?></span>
		        <?php
		        break;
	        case 'right_space':
		        ?>
                <span
                        class="pricing_amount__34e-B"><?php echo Wiloke::ksesHTML($price); ?></span> <sup
                    class="pricing_currency__2bkpj"><?php echo esc_html($currencySymbol); ?></sup>
		        <?php
		        break;
        }
        ?>
        </span>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return apply_filters('wilcity/wilcity-shortcodes/filter/listing-plan-price', $content, $price, $aPriceSettings);
	}

	public static function renderIconAndLink($link, $icon, $content, $aArgs = [])
	{
		$wrapperClass
			= isset($aArgs['wrapperClass']) ? 'icon-box-1_module__uyg5F one-text-ellipsis ' . $aArgs['wrapperClass'] :
			'icon-box-1_module__uyg5F one-text-ellipsis';
		$style = isset($aArgs['style']) ? $aArgs['style'] : 'icon-box-1_block1__bJ25J';
		$iconWrapperClass = isset($aArgs['iconWrapperClass']) ? 'icon-box-1_icon__3V5c0 ' . $aArgs['iconWrapperClass'] :
			'icon-box-1_icon__3V5c0';
		?>
        <div class="<?php echo esc_attr($wrapperClass); ?>">
            <div class="<?php echo esc_attr($style); ?>">
				<?php if (is_email($link)): ?>
                <a href="mailto:<?php echo esc_attr($link); ?>">
					<?php elseif (isset($aArgs['isPhone'])): ?>
                    <a href="tel:<?php echo esc_attr($link); ?>">
						<?php elseif (isset($aArgs['isGoogle'])):
						$link = str_replace('/', '%2F', $link);
						?>
                        <a target="_blank"
                           href="<?php echo esc_url('https://www.google.com/maps/search/' . esc_attr($link)); ?>">
							<?php else: ?>
                            <a target="_blank" href="<?php echo esc_url($link); ?>" rel="nofollow">
								<?php endif;
								?>
                                <div class="<?php echo esc_attr($iconWrapperClass); ?>">
                                    <i class="<?php echo esc_attr($icon); ?>"></i>
                                </div>
                                <div class="icon-box-1_text__3R39g"><?php echo esc_html(stripslashes($content)); ?></div>
                            </a>
            </div>
        </div>
		<?php
	}

	public static function parseAutoComplete($val)
	{
		if (empty($val)) {
			return false;
		}
		if (is_array($val)) {
			$aTerms = array_filter($val, function ($val) {
				return !empty($val);
			});

			return $aTerms;
		}

		return explode(',', $val);
	}

	public static function getEventTypeKeys()
	{
		if (!class_exists('WilokeListingTools\Framework\Helpers\General') ||
			!method_exists('WilokeListingTools\Framework\Helpers\General', 'getPostTypeKeysGroup')) {
			return ['event' => 'event'];
		}
		$aRawPostTypes = General::getPostTypeKeysGroup('event');

		$aPostTypes = [];
		foreach ($aRawPostTypes as $postType) {
			$aPostTypes[$postType] = $postType;
		}

		return $aPostTypes;
	}

	public static function getEventPostTypeOptions()
	{
		$aPostTypes = self::getEventTypeKeys();

		return array_merge(['depends_on_belongs_to' => 'Using Belongs To Setting'], $aPostTypes);
	}

	public static function getPostTypeKeys($isExcludeEvents = true, $isIncludeDefaultPostTypes = true)
	{
		return self::getListingPostTypeKeys($isExcludeEvents, $isIncludeDefaultPostTypes);
	}

	public static function getListingPostTypeKeys($isExcludeEvents = true, $isIncludeDefaultPostTypes = true)
	{
		if (!class_exists('WilokeListingTools\Framework\Helpers\General') ||
			!method_exists('WilokeListingTools\Framework\Helpers\General', 'getPostTypeKeysGroup')) {
			return ['event' => 'event'];
		}

		$aRawPostTypes = General::getPostTypeKeys($isIncludeDefaultPostTypes, $isExcludeEvents);

		$aPostTypes = [];
		foreach ($aRawPostTypes as $postType) {
			$aPostTypes[$postType] = $postType;
		}

		return $aPostTypes;
	}

	public static function getListingPostTypeOptions()
	{
		$aPostTypes = self::getListingPostTypeKeys();

		return array_merge(['depends_on_belongs_to' => 'Using Belongs To Setting'], $aPostTypes);
	}

	public static function getPostTypeOptions()
	{
		return self::getListingPostTypeOptions();
	}

	public static function getAutoCompleteVal($val, $returnType = 'key', $type = 'taxonomy')
	{
		if (empty($val)) {
			return false;
		}
		$aParse = self::parseAutoComplete($val);
		if (!$aParse) {
			return false;
		}

		$aValues = [];
		foreach ($aParse as $val) {
			$termName = "";
			$aInfo = explode(':', $val);
			if (isset($aInfo[1])) {
				$termName = $aInfo[1];
			} else {
				switch ($type) {
					case 'taxonomy':
						$oTerm = get_term($val);

						if (!empty($oTerm) && !is_wp_error($oTerm)) {
							$termName = $oTerm->name;
						}
						break;
					case 'listing':
						$termName = get_the_title($val);
						break;
				}

				if (empty($termName)) {
					continue;
				}
			}

			if ($returnType === 'both') {
				$aValues[$aInfo[0]] = $termName;
			} elseif ($returnType == 'value') {
				$aValues[] = $termName;
			} else {
				$aValues[] = $aInfo[0];
			}
		}

		return $aValues;
	}

	public static function parseArgs($atts)
	{
		$aArgs = [
			'post_type'   => $atts['post_type'],
			'post_status' => 'publish'
		];

		$aTaxQuery = [];

		if (is_tax()) {
			$termID = get_queried_object_id();
			$taxonomy = get_query_var('taxonomy');

			$aTaxQuery[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => [$termID]
			];

			unset($atts[$taxonomy . 's']);

			if ($aArgs['post_type'] == 'depends_on_belongs_to' || empty($aArgs['post_type'])) {
				$postType = TermSetting::getDefaultPostType($termID, $taxonomy);
				if (empty($postType)) {
					$aArgs['post_type'] = GetSettings::getDefaultPostType(true);
				} else {
					$aArgs['post_type'] = $postType;
				}
			}
		}

		if (isset($atts['listing_locations']) && !empty($atts['listing_locations'])) {
			$aTaxQuery[] = [
				'taxonomy' => 'listing_location',
				'field'    => 'term_id',
				'terms'    => self::getAutoCompleteVal($atts['listing_locations'])
			];
		}

		if (isset($atts['listing_cats']) && !empty($atts['listing_cats'])) {
			$aTaxQuery[] = [
				'taxonomy' => 'listing_cat',
				'field'    => 'term_id',
				'terms'    => self::getAutoCompleteVal($atts['listing_cats'])
			];
		}

		if (isset($atts['listing_tags']) && !empty($atts['listing_tags'])) {
			$aTaxQuery[] = [
				'taxonomy' => 'listing_tag',
				'field'    => 'term_id',
				'terms'    => self::getAutoCompleteVal($atts['listing_tags'])
			];
		}

		if (isset($atts['custom_taxonomy_key']) && !empty($atts['custom_taxonomy_key'])) {
			$customTaxIds = $atts['custom_taxonomies_id'];
			if (!empty($customTaxIds)) {
				$aParseCustomTaxIDs = explode(',', $customTaxIds);
				foreach ($aParseCustomTaxIDs as $key => $val) {
					$aParseCustomTaxIDs[$key] = trim($val);
				}

				$aTaxQuery[] = [
					'taxonomy' => $atts['custom_taxonomy_key'],
					'field'    => 'term_id',
					'terms'    => $aParseCustomTaxIDs
				];
			}
		}

		if (isset($atts['listing_ids']) && !empty($atts['listing_ids'])) {
			$aArgs['post__in'] = self::getAutoCompleteVal($atts['listing_ids'], 'key', 'listing');
			if (!is_array($aArgs['post__in'])) {
				unset($aArgs['post__in']);
			} else {
				$aArgs['posts_per_page'] = count($aArgs['post__in']);
			}
		} else {
			if (isset($atts['posts_per_page'])) {
				$aArgs['posts_per_page'] = $atts['posts_per_page'];
			} else if (isset($atts['maximum_posts'])) {
				$aArgs['posts_per_page'] = $atts['maximum_posts'];
			}
		}

		if (!empty($aTaxQuery)) {
			$aArgs['tax_query'] = [$aTaxQuery];
		}

		if (count($aTaxQuery) > 1) {
			$aArgs['tax_query']['relation']
				= apply_filters('wilcity/wilcity-shortcodes/query/taxonomy-relationship', 'AND');
		}

		switch ($atts['orderby']) {
			case 'best_rated':
				$aArgs['orderby'] = 'meta_value_num post_date';
				$aArgs['meta_key'] = 'wilcity_average_reviews';
				$aArgs['order'] = 'DESC';
				break;
			case 'best_viewed':
				$aArgs['orderby'] = 'meta_value_num post_date';
				$aArgs['meta_key'] = 'wilcity_count_viewed';
				$aArgs['order'] = 'DESC';
				break;
			case 'best_shared':
				$aArgs['orderby'] = 'meta_value_num post_date';
				$aArgs['meta_key'] = 'wilcity_count_shared';
				$aArgs['order'] = 'DESC';
				break;
			case 'premium_listings':
				$aArgs['order'] = 'DESC';
				$aArgs['orderby'] = 'rand post_date';
				if ($atts['TYPE'] == 'LISTINGS_SLIDER') {
					$aMetaKey = GetSettings::getPromotionKeyByPosition('listing_slider_sc', true);
				} else {
					$aMetaKey = GetSettings::getPromotionKeyByPosition('listing_grid_sc', true);
				}

				if (!empty($aMetaKey)) {
					$aArgs['meta_key'] = $aMetaKey[0];
				}

				break;
			case 'open_now':
				$aArgs['open_now'] = 'yes';
				break;
			default:
				$aArgs['orderby'] = $atts['orderby'];
				break;
		}

		if (isset($atts['orderby']) && isset($atts['order'])) {
			$aArgs['order'] = $atts['order'];
		} else if (isset($atts['orderby']) && in_array($atts['orderby'], ['upcoming_event', 'happening_event'])) {
			$aArgs['event_filter'] = $atts['orderby'];
			$aArgs['order'] = 'ASC';
		}

		return apply_filters('wilcity/filter/wilcity-shortodes/schelpers/parse-args', $aArgs);
	}

	public static function renderClaimedBadge($postID, $nothing = '')
	{
		if (SingleListing::isClaimedListing($postID, true)) {
			?>
            <span class="wil-verified-badge wil-verified wil-verified-wrap wil-verified"><?php esc_html_e('Verified',
					'wilcity-shortcodes'); ?></span>
			<?php
		}
	}

	public static function renderAds($postID, $type = '', $isReturn = false)
	{
		$postID = is_numeric($postID) ? $postID : $postID->ID;

		$isAds = false;
		switch ($type) {
			case 'LISTINGS_SLIDER':
				$promo_key = GetSettings::getPromotionKeyByPosition('listing_slider_sc', false);
				if (empty($promo_key) || !is_array($promo_key)) {
					break;
				}
				$val = GetSettings::getPostMeta($postID, $promo_key[0]);
				if (!empty($val)) {
					$isAds = true;
				}
				break;
			case 'GRID':
				$promo_key = GetSettings::getPromotionKeyByPosition('listing_grid_sc', false);
				if (empty($promo_key) || !is_array($promo_key)) {
					break;
				}
				$val = GetSettings::getPostMeta($postID, $promo_key[0]);
				if (!empty($val)) {
					$isAds = true;
				}
				break;
			case 'TOP_SEARCH':
				$promo_key = GetSettings::getPromotionKeyByPosition('top_of_search', false);
				if (empty($promo_key) || !is_array($promo_key)) {
					break;
				}
				$val = GetSettings::getPostMeta($postID, $promo_key[0]);
				if (!empty($val)) {
					$isAds = true;
				}
				break;
			case 'LISTING_SIDEBAR':
				$promo_key = GetSettings::getPromotionKeyByPosition('listing_sidebar', false);
				if (empty($promo_key) || !is_array($promo_key)) {
					break;
				}
				$val = GetSettings::getPostMeta($postID, $promo_key[0]);
				if (!empty($val)) {
					$isAds = true;
				}
				break;
		}

		if ($isReturn) {
			return $isAds;
		}

		if ($isAds) {
			?>
            <span class="wil-ads"><?php esc_html_e('Ads', 'wilcity-shortcodes'); ?></span>
			<?php
		}
	}

	public static function renderInterested($post, $aAtts = [], $isReturn = false)
	{
		$total = FavoriteStatistic::countFavorites($post->ID);
		if (empty($total)) {
			return '';
		}

		$total = abs($total);
		if ($isReturn) {
			return [
				'type'  => 'interested',
				'value' => sprintf(_n('%d person interested', '%d people interested', $total, 'wilcity-shortcodes'),
					$total),
				'icon'  => 'la la-star'
			];
		}
		?>
        <li class="event_metaList__1bEBH text-ellipsis">
            <span><?php echo sprintf(_n('%d person interested', '%d people interested', $total, 'wilcity-shortcodes'),
		            $total); ?></span>
        </li>
		<?php
	}

	public static function renderEventStartsOn($post, $aAtts = [], $isReturn = false)
	{
		$aEventCalendarSettings = GetSettings::getEventSettings($post->ID);
		if ($isReturn) {
			return [
				'type'  => 'event_starts_on',
				'value' => [
					'day'  => date_i18n(get_option('date_format'), strtotime($aEventCalendarSettings['startsOn'])),
					'hour' => Time::renderTimeFormat(strtotime($aEventCalendarSettings['startsOn']), $post->ID)
				],
				'icon'  => 'la la-clock-o'
			];
		}
		?>
        <span class="event_month__S8D_o color-primary"><?php echo date_i18n('M',
				strtotime($aEventCalendarSettings['startsOn'])); ?></span>
        <span class="event_date__2Z7TH"><?php echo date_i18n('d',
				strtotime($aEventCalendarSettings['startsOn'])); ?></span>
		<?php
	}

	public static function renderFavorite($post, $aAtts = [], $isReturn = false)
	{
		if (self::isApp($aAtts)) {
			return UserModel::isMyFavorite($post->ID) ? 'yes' : 'no';
		}

		if (!\WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
			return false;
		}

		if ($post->post_type == 'event') {
			$favoriteIconClass = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
		} else {
			$favoriteIconClass = UserModel::isMyFavorite($post->ID) ? 'la la-heart color-primary' : 'la la-heart-o';
		}

		?>
        <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix', 'wilcity-js-favorite')); ?>"
           data-post-id="<?php echo esc_attr($post->ID); ?>" href="#"
           data-tooltip="<?php esc_html_e('Save to my favorites', 'wilcity-shortcodes'); ?>"
           data-tooltip-placement="top"><i class="<?php echo esc_attr($favoriteIconClass); ?>"></i></a>
		<?php
	}

	public static function renderCardHeaderButtonAction($post)
	{
		$aHeader = GetSettings::getOptions(General::getSingleListingSettingKey('header_card', $post->post_type));
		$type = isset($aHeader['btnAction']) ? $aHeader['btnAction'] : 'total_views';
		switch ($type):
			case 'call_us':
				$phone = GetSettings::getListingPhone($post->ID);
				if (empty($phone)) {
					return '';
				}
				?>
                <a class="utility-meta_module__mfOnV utility-meta_light__2EzdO utility-meta_border__3O9g6  mb-10 mr-5 wilcity-listing-card-header-phone"
                   href="tel:<?php echo esc_attr($phone); ?>"><i class="la la-phone"></i><?php esc_html_e('Call us',
						'wilcity-shortcodes'); ?></a>
				<?php
				break;
			case 'email_us':
				$email = GetSettings::getListingEmail($post->ID);
				if (empty($email)) {
					return '';
				}
				?>
                <a class="utility-meta_module__mfOnV utility-meta_light__2EzdO utility-meta_border__3O9g6  mb-10 mr-5"
                   href="mailto:<?php echo esc_attr($email); ?>"><i
                            class="la la-envelope"></i><?php esc_html_e('Mail us', 'wilcity-shortcodes'); ?></a>
				<?php
				break;
			default:
				$totalViews = GetSettings::getListingTotalViews($post->ID);

				if (empty($totalViews)) {
					return '';
				}
				?>
                <a class="utility-meta_module__mfOnV utility-meta_light__2EzdO utility-meta_border__3O9g6  mb-10 mr-5"
                   href="<?php echo get_permalink($post->ID); ?>"><i
                            class="la la-eye"></i><?php echo sprintf(_n('%d View', '%d Views', $totalViews,
						'wilcity-shortcodes'), $totalViews); ?></a>
				<?php
				break;
		endswitch;
	}

	public static function renderFavoriteStyle2($post, $aAtts = [], $isReturn = false)
	{
		if (self::isApp($aAtts)) {
			return UserModel::isMyFavorite($post->ID) ? 'yes' : 'no';
		}

		if (!\WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
			return false;
		}

		if ($post->post_type == 'event') {
			$favoriteIconClass = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
		} else {
			$favoriteIconClass = UserModel::isMyFavorite($post->ID) ? 'la la-heart color-primary' : 'la la-heart-o';
		}

		?>
        <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
			'wilcity-js-favorite')); ?> utility-meta_module__mfOnV utility-meta_primary__2xTvX utility-meta_border__3O9g6  mb-10 mr-5"
           data-post-id="<?php echo esc_attr($post->ID); ?>" href="#"
           data-tooltip="<?php esc_html_e('Save to my favorites', 'wilcity-shortcodes'); ?>"
           data-tooltip-placement="top"><i class="<?php echo esc_attr($favoriteIconClass); ?>"></i></a>
		<?php
	}

	public static function renderGallery($post, $aAttts = [])
	{
		$aImagesID = GetSettings::getPostMeta($post->ID, 'gallery');
		if (!empty($aImagesID)) :
			$aImagesSrc = [];
			$gallery_size = apply_filters('wiloke-listing-tools/listing-card/gallery-size', 'large');
			foreach ($aImagesID as $id => $imgSrc) {
				$largeImg = wp_get_attachment_image_url($id, $gallery_size);
				if (!$largeImg) {
					$aImagesSrc[] = $imgSrc;
				} else {
					$aImagesSrc[] = $largeImg;
				}
			}

			if (self::isApp($aAttts)) {
				return $aImagesSrc;
			}
			?>
            <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix', 'wilcity-preview-gallery')); ?>"
               href="#" data-tooltip="<?php esc_html_e('Gallery', 'wilcity-shortcodes'); ?>"
               data-tooltip-placement="top" data-gallery="<?php echo esc_attr(implode(',', $aImagesSrc)); ?>">
                <i class="la la-search-plus"></i>
            </a>
		<?php endif;

		return '';
	}

	public static function renderFooterTaxonomy($post, $aAtts = [])
	{
		$aFooterSettings
			= GetSettings::getOptions(General::getSingleListingSettingKey('footer_card', $post->post_type));
		$taxonomy = isset($aFooterSettings['taxonomy']) ? $aFooterSettings['taxonomy'] : 'listing_cat';

		if (is_tax($taxonomy)) {
			$aTerm = get_term_by('slug', get_query_var('term'), $taxonomy);
		} else {

			$aQueryArgs = isset($aAtts['oArgs']) ? $aAtts['oArgs'] : [];
			$aTerm = \WilokeHelpers::getTermByPostID($post->ID, $taxonomy, true, $aQueryArgs);
		}

		if ($aTerm) {
			if (self::isApp($aAtts)) {
				return [
					'oTerm' => $aTerm,
					'oIcon' => \WilokeHelpers::getTermOriginalIcon($aTerm)
				];
			}
			echo '<div class="icon-box-1_block1__bJ25J">' .
				\WilokeHelpers::getTermIcon($aTerm, 'icon-box-1_icon__3V5c0 rounded-circle', true,
					['postType' => $post->post_type]) . '</div>';
		}

		return '';
	}

	public static function renderListingCat($post, $aAtts = [])
	{
		return self::renderFooterTaxonomy($post, 'listing_card');
	}

	public static function renderBusinessStatus($post, $aAtts = [], $isGridItem = false)
	{
		if (BusinessHours::isEnableBusinessHour($post)) :
			$aBusinessHours = BusinessHours::getCurrentBusinessHourStatus($post);

			if (self::isApp($aAtts)) {
				return $aBusinessHours['text'];
			}

			if ($isGridItem) {
				if ($aBusinessHours['status'] == 'day_off') {
					$aBusinessHours['class'] = ' color-quaternary';
				}
			}
			?>
            <div class="icon-box-1_block2__1y3h0 <?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
				'wilcity-listing-hours')); ?>"><span
                        class="<?php echo esc_attr($aBusinessHours['class']); ?>"><?php echo esc_html($aBusinessHours['text']); ?></span>
            </div>
		<?php endif;

		return '';
	}

	public static function renderTitle($post, $heading = 'h2')
	{
		?>
        <<?php echo $heading; ?> class="listing_title__2920A text-ellipsis">
        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"><?php echo get_the_title($post->ID); ?></a>
        </<?php echo $heading; ?>>
		<?php
	}

	public static function renderText($aAtts)
	{
		if (!isset($aAtts['heading'])) {
			return '';
		}
		$aAtts = wp_parse_args(
			$aAtts,
			[
				'divClass'     => '',
				'headingClass' => '',
				'heading'      => '',
				'desc'         => '',
				'descClass'    => ''
			]
		);
		?>
        <div class="<?php echo esc_attr($aAtts['divClass']); ?>">
            <h5 class="<?php echo esc_attr($aAtts['headingClass']); ?>"><?php echo esc_html($aAtts['heading']); ?></h5>
			<?php if (!empty($aAtts['desc'])) ?>
            <div class="<?php echo esc_attr($aAtts['descClass']); ?>"><?php \Wiloke::ksesHTML($aAtts['desc']);
				?></div>
        </div>
		<?php
	}

	public static function renderPhone($post, $aAtts = [], $isReturn = false)
	{
		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-phone';
		}

		$phone = GetSettings::getPostMeta($post->ID, 'phone');
		if (self::isApp($aAtts) || $isReturn) {
			return [
				'value' => $phone,
				'icon'  => $aAtts['icon'],
				'type'  => 'phone'
			];
		}

		if (!empty($phone)) :
			?>
            <a class="text-ellipsis phone-number" href="tel:<?php echo esc_attr($phone); ?>">
                <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo esc_html($phone); ?>
            </a>
		<?php endif;
	}

	public static function renderSingleSocialNetworks($post, $aAtts = [], $isReturn = false)
	{
		$aSocialNetworks = \WilokeSocialNetworks::getUsedSocialNetworks();
	}

	public static function renderSinglePrice($post, $aAtts = [], $isReturn = false)
	{
		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-money';
		}

		$price = GetSettings::getPostMeta($post->ID, 'single_price');
		if (self::isApp($aAtts) || $isReturn) {
			return [
				'value' => $price,
				'icon'  => $aAtts['icon'],
				'type'  => 'single_price'
			];
		}

		if (!empty($price)) :
			?>
            <a class="text-ellipsis single-price" href="<?php echo get_permalink($post->ID); ?>">
                <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo GetWilokeSubmission::renderPrice($price); ?>
            </a>
		<?php endif;
	}

	public static function renderWebsite($post, $aAtts = [], $isWebsite = false)
	{
		$website = GetSettings::getPostMeta($post->ID, 'website');
		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-link';
		}

		if (self::isApp($aAtts) || $isWebsite) {
			return [
				'value' => $website,
				'icon'  => $aAtts['icon'],
				'type'  => 'website'
			];
		}

		if (!empty($website)) :
			?>
            <a class="text-ellipsis website" href="<?php echo esc_url($website); ?>" target="_blank">
                <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo esc_html($website); ?>
            </a>
		<?php endif;
	}

	public static function renderEmail($post, $aAtts = [], $isReturn = false)
	{
		$email = GetSettings::getPostMeta($post->ID, 'email');
		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-envelope';
		}

		if (self::isApp($aAtts) || $isReturn) {
			return [
				'value' => $email,
				'icon'  => $aAtts['icon'],
				'type'  => 'email'
			];
		}

		if (!empty($email) && is_email($email)) :
			?>
            <a class="text-ellipsis mail-address" href="mailto:<?php echo esc_attr($email); ?>">
                <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo esc_html($email); ?>
            </a>
		<?php endif;
	}

	public static function scNotHasIcon($aContent)
	{
		foreach ($aContent as $aSc) {
			if (!isset($aSc['oIcon']) || !empty($aSc['oIcon']['icon'])) {
				return true;
			}
		}

		return false;
	}

	public static function renderCustomField($post, $aAtts = [], $isReturn = false)
	{
		if (empty($aAtts['content'])) {
			return '';
		}
		$sc = self::prepareCustomSC($aAtts['content'], $post->ID, $isReturn);
		if ($isReturn) {
			return do_shortcode($sc);
		}

		$parsedSc = do_shortcode($sc);

		if (empty($parsedSc)) {
			return '';
		}

		if (isJson($parsedSc)) {
			if (strpos($aAtts['content'], 'select') !== false || strpos($aAtts['content'], 'checkbox') !== false) {
				$aParsedSc = json_decode($parsedSc, true);
				$aScHasIcon = [];
				$aScName = [];

				foreach ($aParsedSc as $aSc) {
					if (isset($aSc['unChecked']) && $aSc['unChecked'] === 'yes') {
						continue;
					}
					if (!empty($aSc['oIcon']['icon'])) {
						$sc
							= do_shortcode("[wilcity_render_box_icon1 icon='" . $aSc['oIcon']['icon'] . "' name='" .
							$aSc['name'] .
							"' color='" . $aSc['oIcon']['color'] . "']");
						if (!empty($sc)) {
							$aScHasIcon[] = $sc;
						}

						$aScName[] = $aSc['name'];
					} else {
						$aScName[] = $aSc['name'];
					}
				}

				if (empty($aScHasIcon)) {
					if (!empty($aScName)) {
						$aScName
							= apply_filters('wilcity/wilcity-shortcodes/filter/listing-grid/aScName', $aScName, $aAtts,
							$post);
						?>
                        <a class="text-ellipsis custom-content"
                           href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                            <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i> <?php echo implode(', ',
								$aScName); ?>
                        </a>
						<?php
					}
				} else {
					$aScHasIcon = apply_filters(
						'wilcity/wilcity-shortcodes/filter/listing-grid/aScHasIcon',
						$aScHasIcon,
						$aAtts,
						$post,
						$aScName,
						$parsedSc
					);

					$content = is_array($aScHasIcon) ? implode("\r\n", $aScHasIcon) : $aScHasIcon;
					echo '<div class="custom-content wil-meta-wrapper">' . $content . '</div>';
				}
			}
		} else {
			if (empty($parsedSc)) {
				return '';
			}

			$class = preg_replace_callback('/\s+/', function () {
				return '';
			}, $parsedSc);

			$class = strip_tags(strtolower(trim($class)));

			$checkLink = strpos(strval($parsedSc), "href");

			if ($checkLink): ?>
                <div class="text-ellipsis custom-content <?php echo esc_attr($class); ?>">
                    <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i> <?php echo $parsedSc; ?>
                </div>
			<?php else: ?>
                <a class="text-ellipsis custom-content <?php echo esc_attr($class); ?>"
                   href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                    <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i> <?php echo $parsedSc; ?>
                </a>
			<?php endif;
		}
	}

	public static function renderPriceRange($post, $aAtts = [], $isReturn = false)
	{
		$aPriceRange = GetSettings::getPriceRange($post->ID);
		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-money';
		}

		$symbol = !empty($aPriceRange) ? GetWilokeSubmission::getSymbol($aPriceRange['currency']) : '';
		$symbol = apply_filters('wilcity/price-range/currencySymbol', $symbol);

		if (self::isApp($aAtts) || $isReturn) {
			if (empty($aPriceRange) || ($aPriceRange['mode'] == 'nottosay') ||
				($aPriceRange['minimumPrice'] == $aPriceRange['maximumPrice'])) {
				return [
					'value'  => $aPriceRange,
					'icon'   => $aAtts['icon'],
					'type'   => 'price_range',
					'symbol' => $symbol
				];
			} else {
				$aPriceRange['minimumPrice']
					= GetWilokeSubmission::renderPrice($aPriceRange['minimumPrice'], '', false, $symbol);
				$aPriceRange['maximumPrice']
					= GetWilokeSubmission::renderPrice($aPriceRange['maximumPrice'], '', false, $symbol);

				return [
					'value'  => $aPriceRange,
					'icon'   => $aAtts['icon'],
					'type'   => 'price_range',
					'symbol' => $symbol
				];
			}
		}

		if (empty($aPriceRange) || ($aPriceRange['mode'] == 'nottosay') ||
			($aPriceRange['minimumPrice'] == $aPriceRange['maximumPrice'])) {
			return '';
		}

		?>
        <a class="text-ellipsis price-range" href="<?php echo esc_url(get_permalink($post->ID)); ?>">
            <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo GetWilokeSubmission::renderPrice($aPriceRange['minimumPrice'],
					'', false, $symbol) . ' - ' .
				GetWilokeSubmission::renderPrice($aPriceRange['maximumPrice'], '', false,
					$symbol); ?>
        </a>
		<?php
	}

	public static function renderInterestedPeople($post, $aAtts = [], $isReturn = false)
	{
		$favoriteIconClass = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
		if ($isReturn) {
			$aAtts['icon'] = 'la la-user';

			return [
				'type'       => 'text',
				'postID'     => $post->ID,
				'value'      => GetSettings::getEventHostedByName($post),
				'icon'       => $favoriteIconClass,
				'isFavorite' => UserModel::isMyFavorite($post->ID) ? 'yes' : 'no'
			];
		}
		?>
        <span class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
			'wilcity-js-favorite')); ?> event_interested__2RxI- is-event"
              data-tooltip="<?php esc_html_e('Interested', 'wilcity-shortcodes'); ?>"
              data-post-id="<?php echo esc_attr($post->ID); ?>" data-tooltip-placement="top"><i
                    class="<?php echo esc_attr($favoriteIconClass); ?>"></i></span>
		<?php
	}

	public static function renderHostedBy($post, $aAtts = [], $isReturn = false)
	{
		if ($isReturn) {
			$aAtts['icon'] = 'la la-user';

			return [
				'type'  => 'text',
				'value' => [
					'name' => GetSettings::getEventHostedByName($post),
					'url'  => GetSettings::getEventHostedByUrl($post)
				],
				'icon'  => $aAtts['icon']
			];
		}

		$hostedByURL = GetSettings::getEventHostedByUrl($post);
		if (empty($hostedByURL)) {
			return '';
		}

		$target = GetSettings::getEventHostedByTarget($hostedByURL);
		?>
        <span class="event_by__23HUz">
            <?php esc_html_e('Hosted By', 'wilcity-shortcodes'); ?> <a
                    href="<?php echo esc_url(GetSettings::getEventHostedByUrl($post)); ?>"
                    target="<?php echo esc_url($target); ?>"
                    class="color-dark-2"><?php echo GetSettings::getEventHostedByName($post); ?></a>
        </span>
		<?php
	}

	public static function renderTextType($post, $key, $aAtts)
	{
		$val = GetSettings::getPostMeta($post->ID, $key);

		if (!isset($aAtts['icon'])) {
			$aAtts['icon'] = 'la la-refresh';
		}

		if (self::isApp($aAtts)) {
			return [
				'type'  => 'text',
				'value' => $val,
				'icon'  => $aAtts['icon']
			];
		}

		if (empty($val)) {
			return '';
		}

		?>
        <a class="text-ellipsis text-type" href="<?php echo esc_url(get_permalink($post->ID)); ?>">
            <i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo esc_html($val); ?>
        </a>
		<?php
	}

	public static function renderSelectType($post, $aAtts = [])
	{

	}

	public static function renderAddress($post, $aAtts = [], $isReturn = false)
	{
		$aThemeOptions = \Wiloke::getThemeOptions();
		$aListingAddress = GetSettings::getListingMapInfo($post->ID);

		if (!empty($aListingAddress) && !empty($aListingAddress['lat']) &&
			($aListingAddress['lat'] != $aListingAddress['lng'])) :
			$mapPageUrl = add_query_arg(
				[
					'title' => urlencode($post->post_title),
					'lat'   => $aListingAddress['lat'],
					'lng'   => $aListingAddress['lng'],
					'type'  => $post->post_type
				],
				get_permalink($aThemeOptions['map_page'])
			);

			if (!isset($aAtts['icon'])) {
				$aAtts['icon'] = 'la la-map-marker';
			}

			if (self::isApp($aAtts) || $isReturn) {
				return [
					'type'  => 'google_address',
					'value' => [
						'address'          => stripslashes($aListingAddress['address']),
						'mapUrl'           => $mapPageUrl,
						'googleMapAddress' => 'https://www.google.com/maps/search/' .
							urlencode($aListingAddress['address'])
					],
					'icon'  => $aAtts['icon']
				];
			}

			if (isset($aAtts['isSearchNearByMe']) && $aAtts['isSearchNearByMe']) {
				$aLatLng = ['lat' => $aListingAddress['lat'], 'lng' => $aListingAddress['lng']];
				$wrapperClass = 'text-ellipsis google-address js-print-distance';
			} else {
				$wrapperClass = 'text-ellipsis google-address';
				$aLatLng = '';
			}
			?>
            <a style="position: relative; padding-right: 61px" class="<?php echo esc_attr($wrapperClass); ?>"
               data-latlng="<?php echo esc_attr(json_encode($aLatLng)); ?>" href="<?php echo esc_url($mapPageUrl); ?>"
               data-tooltip="<?php echo esc_html(stripslashes($aListingAddress['address'])); ?>">
                <span><i class="<?php echo esc_attr($aAtts['icon']); ?> color-primary"></i><?php echo esc_html(stripslashes($aListingAddress['address'])); ?></span>
            </a>
		<?php
		endif;

		if (self::isApp($aAtts)) {
			return '';
		}
	}

	public static function renderExcerpt($post, $aAtts = [], $isReturn = false)
	{
		$tagLine = GetSettings::getTagLine($post->ID);
		if ((isset($aAtts['isApp']) && $aAtts['isApp']) || $isReturn) {
			return $tagLine;
		}
		if (!empty($tagLine)): ?>
            <div class="listing_tagline__1cOB3 text-ellipsis"><?php \Wiloke::ksesHTML($tagLine); ?></div>
		<?php endif;
	}

	public static function renderAverageReview($post, $aAtts = [], $isReturn = false)
	{
		if (ReviewController::isEnableRating()) :
			$averageReview = ReviewMetaModel::getAverageReviews($post->ID);
			$mode = ReviewController::getMode($post->post_type);
			if (self::isApp($aAtts) || $isReturn) {
				return [
					'mode'    => $mode,
					'average' => $averageReview
				];
			}
			if (!empty($averageReview)) :
				?>
                <div class="listing_rated__1y7qV">
                    <div class="rated-small_module__1vw2B rated-small_style-2__3lb7d">
                        <div class="rated-small_wrap__2Eetz" data-rated="<?php echo esc_html($averageReview); ?>"
                             data-tenmode="<?php echo ReviewController::toTenMode($averageReview, $mode); ?>">
                            <div class="rated-small_overallRating__oFmKR"><?php echo esc_html(number_format($averageReview,
									1)); ?></div>
                            <div class="rated-small_ratingWrap__3lzhB">
                                <div class="rated-small_maxRating__2D9mI"><?php echo ReviewController::getMode($post->post_type); ?></div>
                                <div class="rated-small_ratingOverview__2kCI_"><?php echo esc_html(ReviewMetaModel::getReviewQualityString($averageReview,
										$post->post_type)); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
			<?php endif;
		endif;
	}

	public static function getTermLink($aAtts, $oTerm)
	{
		if ($aAtts['term_redirect'] !== 'search_page') {
			return get_term_link($oTerm->term_id);
		}

		if (isset($aAtts['post_type']) && !empty($aAtts['post_type'])) {
			$aSearchArgs = [
				'postType'       => $aAtts['post_type'],
				$oTerm->taxonomy => $oTerm->term_id
			];

			if ($oTerm->taxonomy === 'listing_cat') {
				if (isset($aAtts['listing_location']) && !empty($aAtts['listing_location'])) {
					$aLocations = self::getAutoCompleteVal($aAtts['listing_location']);
					$aSearchArgs['listing_location'] = $aLocations[0];
				} else {
					if (is_tax('listing_location')) {
						$aSearchArgs['listing_location'] = get_queried_object_id();
					}
				}
			} else if ($oTerm->taxonomy === 'listing_location') {
				if (isset($aAtts['listing_cat']) && !empty($aAtts['listing_cat'])) {
					$aCats = self::getAutoCompleteVal($aAtts['listing_cat']);
					$aSearchArgs['listing_cat'] = $aCats[0];
				} else {
					if (is_tax('listing_cat')) {
						$aSearchArgs['listing_cat'] = get_queried_object_id();
					}
				}
			}

			return QueryHelper::buildSearchPageURL($aSearchArgs);
		}

		return GetSettings::getTermLink($oTerm);
	}
}
