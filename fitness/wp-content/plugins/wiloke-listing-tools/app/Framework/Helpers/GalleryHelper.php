<?php

namespace WilokeListingTools\Framework\Helpers;

class GalleryHelper
{
    public static function gallerySkeleton($aItems, $previewSize = 'large')
    {
        if (empty($aItems)) {
            return [];
        }
        
        $previewSize = apply_filters('wiloke-listing-tools/listing-card/gallery-size', $previewSize);
        $aGallery    = [];
        foreach ($aItems as $id => $src) {
            $thumbnail  = wp_get_attachment_image_url($id, $previewSize);
            $src        = wp_get_attachment_image_url($id, 'full');
            $src        = !empty($src) ? $src : '';
            
            $aGallery[] = [
                'id'       => $id,
                'preview'  => !empty($thumbnail) ? $thumbnail : '',
                'src'      => $src,
                'full'     => $src,
                'fileName' => get_the_title($id)
            ];
        }
        
        return $aGallery;
    }
    
    public static function parseGalleryToDB($aRawGallery)
    {
        if (empty($aRawGallery)) {
            return [];
        }
        
        return array_reduce(
            $aRawGallery,
            function ($aAcummulator, $aImg) {
                return $aAcummulator + [$aImg['id'] => $aImg['src']];
            },
            []
        );
    }
    
    public static function sureGalleryDoesNotExceededPlan($aPlanSettings, $aItems)
    {
        return PlanHelper::sureItemsDoNotExceededPlan($aPlanSettings, 'maximumGalleryImages', $aItems);
    }
}
