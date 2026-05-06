<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Models\EventModel;

trait TraitSetEventData
{
    private $aEventDataPrepare = [
        'objectID'    => '%d',
        'parentID'    => '%d',
        'frequency'   => '%s',
        'starts'      => '%s',
        'endsOn'      => '%s',
        'openingAt'   => '%s',
        'closedAt'    => '%s',
        'specifyDays' => '%s'
    ];

    public function updateEventData()
    {
        if (empty($this->aEventCalendar)) {
            return true;
        }

        $aPrepares                        = [];
        $this->aEventCalendar['objectID'] = $this->listingID;
        if (!empty($this->parentListingID)) {
            $this->aEventCalendar['parentID'] = $this->parentListingID;
        }

        foreach ($this->aEventCalendar as $key => $val) {
            $aPrepares[] = $this->aEventDataPrepare[$key];
        }

        return EventModel::updateEventData(
            $this->listingID,
            [
                'values'   => $this->aEventCalendar,
                'prepares' => $aPrepares
            ]
        );
    }
}
