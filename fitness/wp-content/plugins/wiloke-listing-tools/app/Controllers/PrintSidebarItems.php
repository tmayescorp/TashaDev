<?php

namespace WilokeListingTools\Controllers;

trait PrintSidebarItems
{
    public function printSidebarItems()
    {
        ?>
        <ul class="list_module__1eis9 list-none">
            <li v-for="section in sections" :class="[sidebarClass(section.key), 'wilcity-addlisting-sidebar-'+section.key]">
                <a class="list_link__2rDA1 text-ellipsis color-primary--hover"
                   :href="generateSectionKey(section.key, true)"
                   @click.prevent="scrollTo(section.key)">
                    <span class="list_icon__2YpTp">
                        <i :class="section.icon"></i>
                    </span>
                    <span class="list_text__35R07" v-html="section.heading"></span>
                    <span class="list_check__1FbUQ"></span>
                </a>
            </li>
        </ul>
        <?php
    }
}
