<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Routing\Controller;

class GalleryController extends Controller
{
    public function __construct()
    {
        // add_action('wilcity/footer/vue-popup-wrapper', [$this, 'printGalleryPlaceholder']);
    }

    public function printGalleryPlaceholder()
    {
        ?>
        <vue-gallery-slideshow v-if="showGallerySlideShow" :images="galleryImages" :index="galleryIndex" @close="closeGallery"></vue-gallery-slideshow>
<?php
    }
}
