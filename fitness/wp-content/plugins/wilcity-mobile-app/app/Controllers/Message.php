<?php

namespace WILCITY_APP\Controllers;

trait Message
{
    public function error($msg)
    {
        return [
          'status' => 'error',
          'msg'    => $msg
        ];
    }
}
