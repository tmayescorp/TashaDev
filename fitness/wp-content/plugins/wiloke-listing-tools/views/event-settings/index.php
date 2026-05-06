<div id="wil-event-settings">
    <wil-tabs wrapper-classes="semantic-tabs ui top attached tabular menu" key-prefix="<?php echo esc_attr($_GET['page']); ?>" default-active="event-general">
        <template v-slot:default="{active}">
            <wil-tab tab-key="event-general" tab-name="General Settings" :active="active" heading="Event Settings">
                <template v-slot:content>
                    <wil-event-general></wil-event-general>
                </template>
            </wil-tab>
            <wil-tab tab-key="addlisting" tab-name="Field Settings" :active="active" heading="Field Settings">
                <template v-slot:content>
                    <wil-design-add-listing />
                </template>
            </wil-tab>
            <wil-tab tab-key="single-content" tab-name="Single Content" :active="active" heading="Single Content">
                <template v-slot:info>
                    <div class="ui info message">
                        <a href="https://documentation.wilcity.com/knowledgebase/how-to-build-custom-field-block-on-single-listing/" target="_blank">How to build Custom Field Block on Single Listing?</a>
                    </div>
                </template>
                <template v-slot:content>
                    <wil-event-content post-type="<?php echo esc_attr
                    (\WilokeListingTools\Framework\Helpers\General::detectPostTypeSubmission()); ?>"></wil-event-content>
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
