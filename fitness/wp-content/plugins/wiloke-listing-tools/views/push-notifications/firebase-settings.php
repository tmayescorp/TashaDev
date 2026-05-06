<?php use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\Time; ?>
<div data-tab="firebase-settings" class="ui bottom attached tab active segment">
    <h2 class="wiloke-add-listing-fields__title">Firebase Settings (*)</h2>
    <p class="ui message red">Caution: This setting is required to use Wilcity App</p>
    <p class="ui message info">To complete the following settings, please read <a
            href="https://documentation.wilcity.com/knowledgebase/firebase-configuration/" target="_blank">Firebase
            Configuration</a> tutorial</p>
    <form v-cloak id="wiloke-firease-settings" method="post" action="#" :class="formClass">
        <div v-show="errorMsg!=''" class="ui negative message">
            <p>{{errorMsg}}</p>
        </div>

        <div class="ui segment">
            <p><strong>Upload Google Private Key File Status: <span v-if="isFirebaseFileUploaded=='yes'"
                                                                    style="color: green">Uploaded</span><span v-else
                                                                                                              style="color: red">Still not upload yet</span>
                </strong></p>
            <div :class="uploadFirebaseWrapper">
                <input @change="uploadingFile" type="file" placeholder="Upload firebase json">
            </div>
            <div class="ui message red" v-if="fileFirebaseError">
                {{fileFirebaseError}}
            </div>
            <div class="ui message green" v-else-if="fileFirebaseSuccess">
                {{fileFirebaseSuccess}}
            </div>
        </div>

        <div class="ui segment">
            <h3>Firebase Message Configuration</h3>
            <div class="field">
                <label>API KEY:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.apiKey">
            </div>
            <div class="field">
                <label>Auth Domain:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.authDomain">
            </div>
            <div class="field">
                <label>Database URL:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.databaseURL">
            </div>
            <div class="field">
                <label>Project ID:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.projectID">
            </div>
            <div class="field">
                <label>Storage Bucket:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.storageBucket">
            </div>
            <div class="field">
                <label>Messaging Sender ID:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.messagingSenderId">
            </div>
            <div class="field">
                <label>App ID:</label>
                <input type="text" v-model="oFirebaseChatConfiguration.appId">
            </div>
            <div class="ui message green" v-if="firebaseChatSuccess">
                {{firebaseChatSuccess}}
            </div>
            <div class="ui message red" v-else-if="firebaseChatError">
                {{firebaseChatError}}
            </div>
            <div class="field">
                <button class="ui button green" @click.prevent="updateFirebaseChatConfiguration">Save Message
                    Configuration
                </button>
            </div>
        </div>

        <div class="ui segment">
            <h3>Notification Debug</h3>
            <p>
                Enable this feature to debug your Notification issue. Please read <a
                    href="https://documentation.wilcity.com/knowledgebase/how-can-test-notification-debug/">Notification
                    Code Status</a> to
                know more
            </p>
            <div class="field">
                <div class="ui toggle checkbox" :class="{'checked': toggleDebug=='enable'}">
                    <input type="checkbox" v-model='toggleDebug' true-value="enable" false-value="disable">
                    <label>Enable / Disable</label>
                </div>
            </div>

            <?php
            $aDebugLog = Firebase::getDebug();

            if (!empty($aDebugLog)) :
            ?>

            <hr>
            <ul class="ui list">
                <?php foreach ($aDebugLog as $aLog) : ?>
                    <li><?php echo $aLog['msg'] . ' ' . Time::toDateFormat($aLog['timestamp']); ?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="ui segment">
            <h3>Device Token (This is for testing only)</h3>
            <p>
                To get your device token, please read <a href="https://documentation.wilcity
                .com/knowledgebase/how-to-get-my-device-token/">How to get my Device Token?</a>
            </p>
            <div class="ui message green" v-if="testInfoSuccess">
                {{testInfoSuccess}}
            </div>
            <div class="ui message red" v-else-if="fileFirebaseSuccess">
                {{testInfoError}}
            </div>

            <div class="field">
                <label>Device Token</label>
                <input type="text" v-model="oTestInfo.deviceToken">
            </div>

            <div class="field">
                <button class="ui button green" @click.prevent="updateTestInfo">Save Changes</button>
            </div>
        </div>

    </form>
</div>
