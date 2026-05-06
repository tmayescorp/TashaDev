<?php

namespace WilokeListingTools\Controllers;

trait PrintAddListingFields
{
    public function printAddListingFields($post)
    {
        ?>
        <form v-cloak
              id="wilcity-addlisting-form"
              type="POST"
              action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" v-on:keyup.enter.prevent="preventToSubmit">
            <ul v-cloak v-show="errorMsgs.length" class="list-none mt-20 mb-20" style="padding: 0;">
                <li v-for="err in errorMsgs" style="color: #d61313;" class="alert_content__1ntU3" v-html="err"></li>
            </ul>

            <div v-for="section in sections" :id="generateSectionKey(section.key)"
                 :class="['content-box_module__333d9 content-box_lg__3v3a-', section.fieldStatus]">
                <wil-header-group :heading="section.heading"
                                  :settings="section"
                                  :icon="section.icon"></wil-header-group>
                <wil-lazy-load-component :id="`wil-add-listing-group-${section.key}`" height="50px;">
                    <template v-slot:default="isInView">
                        <wil-fields-group v-if="isInView"
                                          v-on="{change: handleFieldChange(section.key)}"
                                          :value="getValue(section.key)"
                                          :settings="section"
                                          component="addlisting"
                                          post-type="<?php echo empty($_GET['listing_type']) ? '' :
                                              esc_attr($_GET['listing_type']); ?>"

                                          :wil-fields="section.fieldGroups"></wil-fields-group>
                    </template>
                </wil-lazy-load-component>
            </div>

            <ul v-cloak v-show="errorMsgs.length" class="list-none mt-20 mb-20" style="padding: 0;">
                <li v-for="err in errorMsgs" style="color: #d61313;" class="alert_content__1ntU3" v-html="err"></li>
            </ul>

            <button type="submit" class="wil-btn wil-btn--primary wil-btn--round wil-btn--lg wil-btn--block"
                    :class="submitBtnClass" @click.prevent="handlePreview">
                <?php echo !\WilokeThemeOptions::isEnable('addlisting_skip_preview_step') ?
                    esc_html__('Save &amp; Preview', 'wiloke-listing-tools') :
                    esc_html__('Submit', 'wiloke-listing-tools'); ?>
                <div class="pill-loading_module__3LZ6v" v-show="isSubmitting">
                    <div :class="pillLoadingClass"></div>
                </div>
            </button>
        </form>
        <?php
    }
}
