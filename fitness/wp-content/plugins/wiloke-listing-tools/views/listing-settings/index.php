<div id="wil-listing-settings">
    <wil-tabs wrapper-classes="semantic-tabs ui top attached tabular menu" key-prefix="<?php echo esc_attr($_GET['page']); ?>" default-active="addlisting">
        <template v-slot:default="{active}">
            <wil-tab tab-key="addlisting" tab-name="Add Listing" :active="active" heading="Design Add Listing Fields">
                <template v-slot:info>
                    <div class="ui info message">
                        <p>
                            Refer to
                            <a href="https://documentation.wilcity.com/knowledgebase/setting-up-the-main-search-form/" target="_blank">Documentation</a>
                            or Open a topic on <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a>
                        </p>
                    </div>
                </template>

                <template v-slot:content>
                    <wil-design-add-listing></wil-design-add-listing>
                </template>
            </wil-tab>
            <wil-tab tab-key="listing-card" tab-name="Listing Card" :active="active" heading="Design Search Form">
                <template v-slot:info>
                    <div class="info ui message">
                        Adding Custom Field to Listing Card: Please read <a target="_blank" href="https://documentation.wilcity.com/knowledgebase/printing-custom-field-to-listing-card/">Printing Custom Field to Listing Card</a> to know more. <br />
                    </div>
                </template>
                <template v-slot:content>
                    <wil-listing-card />
                </template>
            </wil-tab>

            <wil-tab tab-key="review-settings" tab-name="Review Settings" :active="active">
                <template v-slot:info>
                    <div class="ui info message">
                        <p>Refer to <a href="https://documentation.wilcity.com/knowledgebase/how-review-system-works/" target="_blank">Documentation</a> or Open a topic on <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a></p>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-reviews />
                </template>
            </wil-tab>

            <wil-tab tab-key="single-highlightboxes" tab-name="Single Highlight Boxes" :active="active">
                <template v-slot:warning>
                    <div class="field">
                        <p class="ui message info">Only the owner of listing can see these boxes.</p>
                    </div>
                </template>
                <template v-slot:info>
                    <div class="ui info message">
                        <p>
                            Refer to
                            <a href="https://documentation.wilcity.com/knowledgebase/how-to-customize-single-highlight-box/" target="_blank">Documentation</a> or Open a topic on
                            <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a>
                        </p>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-single-highlight-boxes />
                </template>
            </wil-tab>

            <wil-tab tab-key="single-navigation" tab-name="Single Navigation" :active="active">
                <template v-slot:warning>
                    <h3 class="ui heading">Click and drag a tab name to rearrange the order.</h3>
                </template>

                <template v-slot:info>
                    <div class="ui message info">
                        <a href="https://documentation.wilcity.com/knowledgebase/how-to-build-custom-field-block-on-single-listing/" target="_blank">How to build Custom Field Block on Single Listing?</a>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-single-nav />
                </template>
            </wil-tab>

            <wil-tab tab-key="single-sidebar" tab-name="Single Sidebar" :active="active">
                <template v-slot:info>
                    <div class="ui info message">
                        <p>Refer to <a href="https://documentation.wilcity.com/knowledgebase/how-to-customize-single-directory-sidebar/" target="_blank">Documentation</a> or Open a topic on <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a></p>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-single-sidebar />
                </template>
            </wil-tab>

            <wil-tab tab-key="main-search-form" tab-name="Main Search Form" :active="active">
                <template v-slot:info>
                    <div class="ui info message">
                        <p>
                            Refer to
                            <a href="https://documentation.wilcity.com/knowledgebase/setting-up-the-main-search-form/" target="_blank">Documentation</a>
                            or Open a topic on <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a>
                        </p>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-main-search-form />
                </template>
            </wil-tab>

            <wil-tab tab-key="hero-search-form" tab-name="Hero Search Form" :active="active">
                <template v-slot:warning>
                    <div class="ui warning message">
                        <p>You can add maximum 3 fields to the Hero Search Fields.</p>
                    </div>
                </template>
                <template v-slot:info>
                    <div class="ui info message">
                        <p>Refer to <a href="https://documentation.wilcity.com/knowledgebase/kingcomposer-setting-up-hero-search-form/ ​​​ ​​" target="_blank">Documentation</a> or Open a topic on <a href="https://support.wilcity.com/" target="_blank">wilcityservice.com/support/</a></p>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-hero-search-form />
                </template>
            </wil-tab>

            <wil-tab tab-key="schema-markup" tab-name="Schema Markup" :active="active">
                <template v-slot:content>
                    <wil-schema-markup />
                </template>
            </wil-tab>

        </template>
    </wil-tabs>
    <wil-icons-modal :status="iconsModalStatus" :std="selectedIcon"></wil-icons-modal>
</div>
