<div id="wil-author-results" class="row wilcity-grid">
    <wil-async-grid
        page-now="author"
        endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX.'/v2/listings')); ?>"
        v-on:max-posts="maxPosts"
        :is-random-premium="true"
        column-classes="<?php echo esc_attr(WilokeThemeOptions::getOptionDetail('author_items_per_row',
            'col-lg-4 col-md-4 col-sm-6')); ?>"
        type="grid"
        :query-args="queryArgs">
        <template v-slot:after-grid="{page, maxPages, postsPerPage, isLoading}">
            <wil-pagination v-if="maxPages > 1 && !isLoading" name="after-grid"
                            @change="handlePaginationChange"
                            :max-pages="maxPages"
                            :current-page="page"></wil-pagination>
        </template>
    </wil-async-grid>
</div>

