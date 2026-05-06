<?php

namespace WilokeListingTools\Controllers\Map;

use WilokeThemeOptions;

class GoogleMap
{
    private $address;
    private $findType;

    private function buildQuery()
    {
        $aQuery = [
            'key'      => WilokeThemeOptions::getOptionDetail('general_google_web_service_api'),
            'language' => WilokeThemeOptions::getOptionDetail('general_google_language')
        ];

        switch ($this->findType) {
            case 'textsearch':
            case 'placedetails':
                $aQuery['query'] = $this->address;
                break;
            case 'geocode':
                $aQuery['address']       = $this->address;
                $aQuery['result_type']   =
                    'street_address,administrative_area_level_1,administrative_area_level_2,administrative_area_level_3,administrative_area_level_4,administrative_area_level_5,locality,postal_code,airport,neighborhood';
                $aQuery['location_type'] = 'GEOMETRIC_CENTER';
                break;
        }

        if ($restriction = WilokeThemeOptions::getOptionDetail('general_search_restriction')) {
            if (($this->findType) == 'geocode') {
                $aQuery['components'] = 'country:'.$restriction;
            } else {
                $aQuery['region'] = $restriction;
            }
        }

        return $aQuery;
    }

    // $findType: geocode or textsearch
    public function getGeocoder($address, $aArgs = [])
    {
        $aArgs          = wp_parse_args(
            $aArgs,
            [
                'findType' => 'textsearch'
            ]
        );
        $this->address  = $address;
        $this->findType = $aArgs['findType'];

        switch ($this->findType) {
            case 'geocode':
                $googleUrl = "https://maps.googleapis.com/maps/api/geocode/json";
                break;
            case 'textsearch':
                $googleUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json";
                break;
            case 'placedetails':
                $googleUrl = "https://maps.googleapis.com/maps/api/place/details/output";
                break;
        }

        $request = wp_remote_get(
            $googleUrl,
            [
                'headers'    => ['referer' => home_url('/')],
                'body'       => apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/controller/google-map/query-args',
                    $this->buildQuery()
                ),
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36',
                'method'     => 'GET'
            ]
        );

        $aResponse = [];
        if (is_wp_error($request)) {
            return false; // Bail early
        }

        $body         = wp_remote_retrieve_body($request);
        $aRawResponse = json_decode($body, true);
        // response status will be 'OK', if able to geocode given address
        if ($aRawResponse['status'] == 'OK') {
            // get the important data
            if (isset($aArgs['limit']) && !empty($aArgs['limit'])) {
                $aRawResponse['results'] = array_slice($aRawResponse['results'], 0, 2);
            }

            foreach ($aRawResponse['results'] as $aResult) {
                $aResponse[] = [
                    'coordinate' => $aResult['geometry']['location'],
                    'name'       => $aResult['formatted_address'],
                    'icon'       => 'la la-map',
                    'context'    => $aResult['types'],
                    'place_id'   => $aResult['place_id'],
                    'status'     => 'success',
                    'type'       => 'geocoder'
                ];
            }

            return $aResponse;
        } elseif ($aRawResponse['status'] === 'REQUEST_DENIED') {
            $aResponse[] = [
                'coordinate' => '',
                'name'       => $aRawResponse['error_message'],
                'icon'       => 'la la-map',
                'context'    => '',
                'place_id'   => '',
                'status'     => 'error',
                'type'       => 'geocoder'
            ];

            return $aResponse;
        } else {
            return false;
        }
    }
}
