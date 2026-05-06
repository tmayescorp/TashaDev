<div class="searchform">
    <form class="form ui" action="<?php echo esc_url(admin_url('admin.php')); ?>" method="GET">
        <div class="equal width fields">
            <input type="hidden" name="paged" value="<?php echo esc_attr($this->paged); ?>">
            <input type="hidden" name="page" value="<?php echo esc_attr($this->slug); ?>">
            <div class="search-field field">
                <label for="payment_status"><?php esc_html_e('Status', 'wiloke-listing-tools'); ?></label>
                <select id="payment_status" class="ui dropdown" name="payment_status">
                    <?php
                    foreach (wilokeListingToolsRepository()->get('invoices:status') as $status => $title) :
                        ?>
                        <option value="<?php echo esc_attr($status); ?>"
                            <?php selected($status,
                                $this->aFilters['payment_status']); ?>>
                            <?php echo esc_html($title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field field">
                <label for="filter-by-date"><?php esc_html_e('Date', 'wiloke-listing-tools'); ?></label>
                <select id="filter-by-date" class="ui dropdown" name="date">
                    <?php
                    foreach ($this->aFilterByDate as $date => $title):
                        ?>
                        <option
                            value="<?php echo esc_attr($date); ?>" <?php selected($date, $this->aFilters['date']);
                        ?>><?php echo
                            esc_html($title); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div id="filter-by-period" class="transition hidden search-field two fields wiloke-has-dependency"
                     data-dependency='<?php echo json_encode(['name' => 'date', 'value' => 'period']); ?>'>
                    <div class="field">
                        <input class="wiloke_datepicker" type="text" name="from" value=""
                               placeholder="<?php esc_html_e('Date Start', 'wiloke-listing-tools'); ?>">
                    </div>
                    <div class="field">
                        <input class="wiloke_datepicker" type="text" name="to" value=""
                               placeholder="<?php esc_html_e('Date End', 'wiloke-listing-tools') ?>">
                    </div>
                </div>
            </div>
            <div class="search-field field">
                <label for="filter-by-gateway"><?php esc_html_e('Gateway', 'wiloke-listing-tools'); ?></label>
                <select id="filter-by-gateway" class="ui dropdown" name="gateway">
                    <?php foreach (
                        wilokeListingToolsRepository()->get('payment:gateways') as $gateway =>
                        $gatewayName
                    ) : ?>
                        <option value="<?php echo esc_attr($gateway); ?>"
                            <?php selected($gateway, $this->aFilters['gateway']); ?>>
                            <?php echo esc_html($gatewayName); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field field">
                <label for="posts_per_page"><?php esc_html_e('Posts Per Page', 'wiloke-listing-tools'); ?></label>
                <input id="posts_per_page" type="text" name="posts_per_page"
                       value="<?php echo esc_attr($this->postPerPages); ?>">
            </div>
            <div class="search-field field">
                <label for="posts_per_page"><?php esc_html_e('Apply', 'wiloke-listing-tools'); ?></label>
                <input type="submit" class="button ui basic green"
                       value="<?php esc_html_e('Filter', 'wiloke-listing-tools'); ?>">
            </div>
        </div>
    </form>
</div>
