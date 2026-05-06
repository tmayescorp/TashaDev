<?php

namespace WilokeListingTools\Controllers\TransformAddListingData;

class TransformAddListingToFrontEndFactory implements TransformAddListingDataFactory
{
    public static function set($fieldType, $maximum = 100)
    {
        return new TransformAddListingToFrontEnd();
    }
}
