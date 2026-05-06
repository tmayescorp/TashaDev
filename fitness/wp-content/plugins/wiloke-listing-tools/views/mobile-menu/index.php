<div id="wil-mobile-menu-settings">
    <wil-tabs wrapper-classes="semantic-tabs ui top attached tabular menu" key-prefix="<?php echo esc_attr($_GET['page']); ?>" default-active="settings">
        <template v-slot:default="{active}">
            <wil-tab tab-key="settings" tab-name="Bottom Tab Navigator" :active="active" heading="Bottom Tab Navigator Settings">
                <template v-slot:info>
                    <div class="ui info message">
                        <p>
                            You can add maximum 5 menu items to the Main Menu
                        </p>
                    </div>
                </template>

                <template v-slot:content>
                    <wil-bottom-tab-navigator></wil-bottom-tab-navigator>
                </template>
            </wil-tab>

            <wil-tab tab-key="secondary-menu" tab-name="Secondary Navigator" :active="active" heading="Secondary Settings">
                <template v-slot:content>
                    <wil-secondary-navigator></wil-secondary-navigator>
                </template>
            </wil-tab>
        </template>
        
    </wil-tabs>
    <wil-icons-modal :status="iconsModalStatus" :std="selectedIcon"></wil-icons-modal>
</div>
