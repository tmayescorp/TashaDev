<?php

namespace WILCITY_APP\Controllers;

trait BuildQuery
{
    private function buildSingleQuery($aData)
    {
        $aArgs = [
          'post_status'    => 'publish',
          'post_type'      => get_post_type($aData['target']),
          'orderby'        => 'post_title',
          'order'          => 'DESC',
          'posts_per_page' => 1
        ];
        
        if (is_numeric($aData['target'])) {
            $aArgs['p'] = abs($aData['target']);
        } else {
            $aArgs['name'] = $aData['target'];
        }
        
        if (isset($aData['lat']) && !empty($aData['lat']) && isset($aData['lng']) && !empty($aData['lng'])) {
            $aQuery['oAddress'] = [
              'lat'    => $aData['lat'],
              'lng'    => $aData['lng'],
              'radius' => isset($aData['radius']) ? $aData['radius'] : 10,
              'unit'   => isset($aData['unit']) ? $aData['unit'] : 'km',
            ];
        }
        
        return $aArgs;
    }
}
