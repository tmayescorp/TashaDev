<?php
use WilokeListingTools\Framework\Helpers\General;

?>

<div class="ui bottom attached active tab segment" data-tab="addlisting">
  <h2 class="wiloke-add-listing-fields__title">
    <?php esc_html_e('Design Add Listing Fields', 'wiloke-design-addlisting'); ?>
  </h2>
  <div id="wiloke-design-fields">
    <div class="ui info message">
      <p>Refer to <a href="https://documentation.wilcity.com/" target="_blank">Documentation -> Add Listing</a> or
        Open a topic on <a href="https://wilcityservice.com/support/" target="_blank">wilcityservice.com/support/</a></p>
    </div>

    <div v-show="errorMsg.length" class="ui negative message">
      <p>{{errorMsg}}</p>
    </div>

    <div v-show="successMsg.length" class="ui positive message">
      <p>{{successMsg}}</p>
    </div>

    <div class="drag">
      <div class="ui grid">
        <div class="sixteen wide column">
          <div class="drag__block">
            <h3 class="drag__title">Available Fields</h3>
            <draggable v-model="aAvailableSections" class="dragArea drag__avai"
              :options="{group: {name: 'addListingFields'}}" @change="addedNewSectionInAvailableArea">
              <div v-for="(oSection, index) in aAvailableSections" :key="index" class="dragArea__item">
                <span class="dragArea__item-icon">
                  <i class="la la-arrows-v"></i>
                </span>
                <span class="dragArea__item-text">
                  <span v-html="oSection.heading"></span> <small>{{sectionName(oSection)}}</small>
                </span>
              </div>
            </draggable>
          </div>
        </div>
        <div class="sixteen wide column">
          <h3 class="drag__title">Used Fields</h3>
          <form action="#" id="wiloke-design-listing-form" class="ui form wiloke-form-has-icon"
            @submit.prevent="saveValue">
            <div v-show="errorMsg!=''" class="ui negative message">
              <p>{{errorMsg}}</p>
            </div>

            <div v-show="successMsg!=''" class="ui positive message">
              <p>{{successMsg}}</p>
            </div>

            <div class="drag__btn-wrap">
              <div class="drag__btn-group right">
                <button class="ui button red" @click.prevent="resetDefault">Reset Defaults</button>
                <button type="submit" class="ui green button"><i class="la la-save"></i>
                  <?php esc_html_e('Save Changes', 'wiloke-design-addlisting'); ?></button>
              </div>
            </div>

            <draggable v-model="usedSections" class="dragArea drag__used" @change="addedNewSectionInUsedArea"
              :options="{group:'addListingFields', handle: '.dragArea__form-title--icon'}">
              <div class="dragArea__block" v-for="(oUsedSection, index) in usedSections" :key="index">
                <div class="dragArea__form ui form field-wrapper segment">
                  <div class="dragArea__form-title" @click.prevent="expandBlockSettings">
                    <span class="dragArea__form-title--icon">
                      <i class="la la-arrows-v"></i>
                    </span>
                    <span class="dragArea__form-title--text">
                      <span v-html="getSectionHeading(oUsedSection, index)"></span>
                      <small>({{oUsedSection.type}})</small>
                      <input type="hidden" v-model='oUsedSection.type'>
                    </span>
                    <span class="dragArea__form-title--remove" @click.prevent="removeSection(index, oUsedSection)"
                      title="<?php esc_html_e('Remove Section', 'wiloke-design-addlisting'); ?>">
                      <i class="la la-times"></i>
                    </span>
                  </div>

                  <wil-field-groups :group="oUsedSection" :std="getSectionValue(index)"
                    v-on="{change: handleGroupChange(index)}"></wil-field-groups>
                </div>
              </div>
            </draggable>
          </form>

          <div v-show="errorMsg!=''" class="ui negative message">
            <p>{{errorMsg}}</p>
          </div>

          <div v-show="successMsg!=''" class="ui positive message">
            <p>{{successMsg}}</p>
          </div>

          <div class="mb-15">
            <button class="ui button green" @click.prevent="saveValue">Save Changes</button>
            <button class="ui button red" @click.prevent="resetDefault">Reset Defaults</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php do_action(
    'wilcity/wiloke-listing-tools/wiloke-tools-settings',
    General::detectPostTypeSubmission() == 'wiloke-listing-settings' ? 'listing' : General::detectPostTypeSubmission(),
    'addlisting'
  ); ?>
</div>
