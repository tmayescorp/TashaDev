<?php

namespace WilokeListingTools\Framework\Helpers\Collection;

class ArrayCollectionFactory implements CollectionFactory
{
    public static function set($input)
    {
        return new ArrayCollection($input);
    }
}
