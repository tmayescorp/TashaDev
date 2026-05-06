<?php

namespace WilokeListingTools\Framework\Helpers\Collection;

interface CollectionInterface
{
    public function __construct($input);
    
    public function except($target);
    public function pluck($pluck);
    public function deepPluck($pluck);
    public function output($std = null);
    public function format($format = 'default');
}
