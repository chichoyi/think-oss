<?php
/**
 * Created by PhpStorm.
 * User: chicho
 * Date: 2018/4/16
 * Time: 18:07
 */

namespace Chichoyi\ThinkOss\Facede;

use Chichoyi\ThinkOss\ThinkOss;

class Oss
{

    public static function __callStatic($method, $arguments)
    {
        $oss = new ThinkOss();
        return call_user_func_array(array($oss,$method), $arguments);
    }

}