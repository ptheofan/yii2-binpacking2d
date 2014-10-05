<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 02/10/14
 * Time: 18:11
 */

namespace ptheofan\binpacking2d;

use yii\base\Object;

class Node extends Object implements INode
{
    public $x = 0;
    public $y = 0;
    public $w;
    public $h;
    public $used = false;
    public $right;
    public $down;
    public $fit;
}