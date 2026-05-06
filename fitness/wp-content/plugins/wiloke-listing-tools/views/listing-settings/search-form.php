<?php

use WilokeListingTools\Framework\Helpers\General;

?>
<div data-tab="search-form" class="ui bottom attached tab segment">
    <h2 class="wiloke-add-listing-fields__title">Design Search Form</h2>

    <div class="ui info message">
        <p>Refer to <a href="https://documentation.wilcity.com/knowledgebase/setting-up-the-main-search-form/"
                target="_blank">Documentation</a> or Open a topic on <a href="https://wilcityservice.com/support/"
                target="_blank">https://wilcityservice.com/support/</a>
        </p>
    </div>

    <div id="wiloke-design-search-form">
        <div class="drag">
            <div class="ui grid">
                <div class="sixteen wide column">

                    <div class="drag__block">
                        <h3 class="drag__title">Available Fields</h3>
                        <draggable v-model="oAvailableFields" class="dragArea drag__avai"
                            :options="{group: {name: 'wilSearchFormItems'}}" @change="addedNewSectionInAvailableArea">
                            <div v-for="(oField, index) in oAvailableFields" :key="index" class="dragArea__item">
                                <span class="dragArea__item-icon">
                                    <i class="la la-arrows-v"></i>
                                </span>
                                <span class="dragArea__item-text">
                                    {{oField.label}} <small>({{oField.type}})</small>
                                </span>
                            </div>
                        </draggable>
                    </div>

                </div>
                <div class="sixteen wide column">
                    <h3 class="drag__title">Used Fields</h3>
                    <form action="#" id="wiloke-design-search-form-form" class="ui form" @submit.prevent="saveChanges">

                        <div v-show="successMsg!=''" class="ui positive message"><i class="la la-certificate"></i>
                            {{successMsg}}
                        </div>
                        <div v-show="errorMsg!=''" class="ui negative message"><i class="la la-certificate"></i>
                            {{errorMsg}}
                        </div>

                        <div class="drag__btn-wrap">
                            <div class="drag__btn-group right">
                                <button class="ui button violet" @click.prevent="resetDefaults">Reset Settings</button>
                                <button type="submit" class="ui green button"><i class="la la-save"></i> Save Changes
                                </button>
                            </div>
                        </div>

                        <draggable class="dragArea drag__used" v-model="usedFields" @change="addedNewSectionInUsedArea"
                            :options="{group:'wilSearchFormItems', handle: '.dragArea__form-title--icon'}">
                            <template v-for="(usedField, index) in usedFields">
                                <div class="dragArea__block">
                                    <div :class="dragFormClass(usedField)">
                                        <div class="dragArea__form-title" @click.prevent="expandBlockSettings">
                                            <span class="dragArea__form-title--icon">
                                                <i class="la la-arrows-v"></i>
                                            </span>
                                            <span class="dragArea__form-title--text">
                                                {{usedField.label}}
                                                <small
                                                    v-if="usedFields.isCustomField && usedFields.isCustomField=='yes'">(Custom
                                                    Section)</small>
                                                <small>({{usedField.type}})</small>
                                                <input type="hidden" v-model='usedField.type'>
                                            </span>
                                            <span class="dragArea__form-title--remove"
                                                @click.prevent="removeSection(index, usedField)" title="Remove Section">
                                                <i class="la la-times"></i>
                                            </span>
                                        </div>

                                        <div class="dragArea__form-content hidden">
                                            <p v-if="usedFields.desc"><i>{{usedField.desc}}</i></p>
                                            <v-component v-on="{change: handleFieldChange(getFieldSettings(usedField)
                                            , index)}" :is="getComponent(usedField)" :std="usedField"
                                                :settings="getFieldSettings(usedField)"></v-component>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>

                        <div v-show="successMsg!=''" class="ui positive message"><i class="la la-certificate"></i>
                            {{successMsg}}
                        </div>
                        <div v-show="errorMsg!=''" class="ui negative message"><i class="la la-certificate"></i>
                            {{errorMsg}}
                        </div>
                        <div class="mb-15">
                            <button class="ui button green" @click.prevent="saveChanges">Save Changes</button>
                            <button class="ui button violet" @click.prevent="resetDefaults">Reset Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php do_action(
    'wilcity/wiloke-listing-tools/wiloke-tools-settings',
    General::detectPostTypeSubmission() == 'wiloke-listing-settings' ? 'listing' : General::detectPostTypeSubmission(),
    str_replace('.php', '', basename(__FILE__))
); ?>
</div>
