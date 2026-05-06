<?php

namespace WilokeListingTools\Framework\Helpers;


class VideoHelper
{
    public static function parseVideoToUpload($aRawVideos)
    {
        if (empty($aRawVideos)) {
            return [];
        }
        
        $aVideos = [];
        foreach ($aRawVideos as $aVideo) {
            $aVideos[] = ['value' => $aVideo['src']];
        }
    
        return $aVideos;
    }
    
    public static function parseVideoToDB($aRawVideos)
    {
        if (empty($aRawVideos)) {
            return [];
        }
    
        $aVideos = [];
        foreach ($aRawVideos as $order => $aValue) {
            if (strpos($aValue['value'], 'youtube') !== -1) {
                $aValue['value'] = preg_replace_callback(
                    '/&.*/',
                    function () {
                        return '';
                    },
                    $aValue['value']
                );
            }
    
            $aVideos[$order]['src']       = Validation::deepValidation($aValue['value']);
            $aVideos[$order]['thumbnail'] = '';
        };
    
        return $aVideos;
    }
    
    public static function sureVideoDoesNotExceedPlan($aPlanSettings, $aItems)
    {
        return PlanHelper::sureItemsDoNotExceededPlan($aPlanSettings, 'maximumVideos', $aItems);
    }
}
