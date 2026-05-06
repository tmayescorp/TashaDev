<?php

namespace WilokeListingTools\Controllers;

class ModalController
{
    public function __construct()
    {
        add_action('wilcity/footer/vue-popup-wrapper', [$this, 'printPortalModal']);
    }

    public function printPortalModal()
    {
        ?>
        <portal-target name="wil-modal"></portal-target>
        <portal-target name="wil-search-field-modal"></portal-target>
        <?php
    }
}
