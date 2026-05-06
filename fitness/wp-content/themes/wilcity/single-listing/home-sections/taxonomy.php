<?php
global $post, $wilcityArgs, $wilcityTabKey;

use \WilokeListingTools\Framework\Helpers\GetSettings;

if (empty($wilcityArgs['taxonomy'])) {
    return '';
}
$aTerms = GetSettings::getPostTerms($post->ID, $wilcityArgs['taxonomy']);
if (!is_array($aTerms)) {
    return '';
}
$wilcityTabKey = $wilcityArgs['taxonomy'];

$maxItems = isset($wilcityArgs['maximumItemsOnHome']) && !empty($wilcityArgs['maximumItemsOnHome']) ? abs
($wilcityArgs['maximumItemsOnHome']) : 0;

?>

<?php global $post; ?>
<wil-single-nav-wrapper v-if="currentTab === 'home'"
                        :settings="getNavigation('<?php echo esc_attr($wilcityArgs['key']); ?>')"
                        id="single-home-<?php echo esc_attr($wilcityArgs['key']); ?>">
    <template v-slot:default="{settings}">
        <div class="content-box_body__3tSRB">
            <wil-boxes-icon-items :items="data.taxonomies['<?php echo $wilcityArgs['taxonomy']; ?>']"
                                  id="wil-home-taxonomy-<?php echo esc_attr($wilcityArgs['taxonomy']); ?>"
                                  :max-items="<?php echo abs($maxItems); ?>"
            ></wil-boxes-icon-items>
        </div>
        <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
    </template>
</wil-single-nav-wrapper>
