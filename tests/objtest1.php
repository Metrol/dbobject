<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\Tests;

use Metrol\DBObject;
use Metrol\DBTable;

/**
 * A sample database object that directly extends DBObject\Item using a
 * PostgreSQL table.
 *
 * @property string  $sometext
 * @property integer $anum
 */
class objtest1 extends DBObject\Item
{
    const TBL_NAME = 'objtest1';

    /**
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $table = new DBTable\PostgreSQL(self::TBL_NAME);
        $table->runFieldLookup($db);

        parent::__construct($table, $db);
    }
}
