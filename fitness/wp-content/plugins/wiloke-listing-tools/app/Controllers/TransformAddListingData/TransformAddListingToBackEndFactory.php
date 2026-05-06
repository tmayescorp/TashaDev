<?php

namespace WilokeListingTools\Controllers\TransformAddListingData;

class TransformAddListingToBackEndFactory implements TransformAddListingDataFactory
{
    /**
     * @param     $fieldType
     * @param int $maximum
     *
     * @return TransformAddListingToBackEnd
     */
    public static function set($fieldType, $maximum = 100)
    {
       return new TransformAddListingToBackEnd($fieldType, $maximum);
    }
}
