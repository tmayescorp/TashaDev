<?php
global $post, $wilcityArgs, $wilcityTabKey;
?>
<wil-single-nav-term v-if="currentTab === '<?php echo esc_attr($wilcityArgs['taxonomy']); ?>'"
                     :settings="getNavigation('<?php echo esc_attr($wilcityArgs['key']); ?>')"
                     :items="data.taxonomies['<?php echo esc_attr($wilcityArgs['taxonomy']); ?>']"></wil-single-nav-term>
