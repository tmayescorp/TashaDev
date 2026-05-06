<?php

namespace WilokeListingTools\Controllers\TransformAddListingData;

interface TransformAddListingInterface
{
    public  function input($input);
    public function format($format = null);
    public function output($std = null);
}
