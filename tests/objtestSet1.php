<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2022, Michael Collette
 */

namespace Metrol\Tests;

use Metrol\DBObject;

class objtestSet1 extends DbObject\Set
{
    public function __construct(objtest1 $dbObject)
    {
        parent::__construct($dbObject);
    }
}
