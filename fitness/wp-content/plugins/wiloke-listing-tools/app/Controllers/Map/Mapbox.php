<?php

namespace WilokeListingTools\Controllers\Map;

class Mapbox
{
    /**
     * @param $aContext
     *
     * @return mixed|string
     */
    protected function parseContext($aContext)
    {
        $aParsedContext = [];
        if (is_array($aContext) && !empty($aContext)) {
            foreach ($aContext as $aItem) {
                $aParseID                     = explode('.', $aItem['id']);
                $aParsedContext[$aParseID[0]] = $aParseID[1];
            }
        }
        
        return $aParsedContext;
    }
    
    public function getGeocoder($address, $aArgs = [])
    {
        $aArgs = wp_parse_args(
            $aArgs,
            ['limit' => 10]
        );
        
        $aStdArgs = [
            'access_token' => \WilokeThemeOptions::getOptionDetail('mapbox_api'),
            'limit'        => $aArgs['limit'],
            'autocomplete' => true
        ];
        
        $aArgs = wp_parse_args($aArgs, $aStdArgs);
        
        if (!empty(\WilokeThemeOptions::getOptionDetail('general_search_restriction'))) {
            $aArgs['country'] = \WilokeThemeOptions::getOptionDetail('general_search_restriction');
        }
        
        $request = wp_remote_get(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode($address).'.json',
            [
                'headers' => ['referer' => home_url('/')],
                'body'    => apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/controller/mapbox/query-args',
                    $aArgs
                )
            ]);
        
        $aResponse = [];
        
        if (is_wp_error($request)) {
            return $aResponse; // Bail early
        }
        
        $body = wp_remote_retrieve_body($request);
        $aRawResponse = json_decode($body, true);
        
        if (!empty($aRawResponse) && isset($aRawResponse['features'])) {
            foreach ($aRawResponse['features'] as $aFeature) {
                $aContext = $this->parseContext($aFeature['context']);
                if (empty($aContext)) {
                    $aContext = $this->parseContext([['id' => $aFeature['id']]]);
                }
                $aResponse[] = [
                    'coordinate' => [
                        'lat' => $aFeature['center'][1],
                        'lng' => $aFeature['center'][0]
                    ],
                    'name'       => $aFeature['place_name'],
                    'icon'       => 'la la-map',
                    'context'    => $aContext,
                    'type'       => 'geocoder'
                ];
            }
        }
        
        return $aResponse;
    }
}
