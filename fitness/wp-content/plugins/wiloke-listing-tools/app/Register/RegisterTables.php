<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\AlterTable\AlterTableHaveBeenThere;
use WilokeListingTools\AlterTable\AlterTableLatLng;
use WilokeListingTools\AlterTable\AlterTableBusinessHours;
use WilokeListingTools\AlterTable\AlterTableBusinessHourMeta;
use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\AlterTable\AlterTablePaymentMeta;
use WilokeListingTools\AlterTable\AlterTableInvoices;
use WilokeListingTools\AlterTable\AlterTablePaymentPlanRelationship;
use WilokeListingTools\AlterTable\AlterTablePlanRelationships;
use WilokeListingTools\AlterTable\AlterTableReviewMeta;
use WilokeListingTools\AlterTable\AlterTableEventsData;
use WilokeListingTools\AlterTable\AlterTableMessage;
use WilokeListingTools\AlterTable\AlterTableFollower;
use WilokeListingTools\AlterTable\AlterTableFavoritesStatistic;
use WilokeListingTools\AlterTable\AlterTableUserLatLng;
use WilokeListingTools\AlterTable\AlterTableViewStatistic;
use WilokeListingTools\AlterTable\AlterTableSharesStatistic;
use WilokeListingTools\AlterTable\AlterTableNotifications;
use WilokeListingTools\AlterTable\AlterTableInvoiceMeta;

class RegisterTables
{
    public function __construct()
    {
        add_action('wilcity/wiloke-listing-tools/after-plugin-activated', [$this, 'createTables']);
//        add_action('admin_init', [$this, 'createTables']);
    }

    public function addNewestTable()
    {
        new AlterTableUserLatLng;
        new AlterTableHaveBeenThere;
    }

    public function createTables()
    {
        new AlterTableBusinessHours;
        new AlterTableBusinessHourMeta;
        new AlterTableFollower;
        new AlterTableLatLng;
        new AlterTablePaymentHistory;
        new AlterTablePaymentMeta;
        new AlterTableInvoices;
        new AlterTablePlanRelationships;
        new AlterTableReviewMeta;
        new AlterTableEventsData;

        new AlterTableMessage;
        new AlterTableFavoritesStatistic;
        new AlterTableViewStatistic;
        new AlterTableSharesStatistic;
        new AlterTableNotifications;
        new AlterTableInvoiceMeta;
        new AlterTableUserLatLng;
//        new AlterTableHaveBeenThere;
    }
}
