<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2022, Michael Collette
 */

namespace Metrol\Tests;

use DateTime;
use Metrol\DBObject;
use Metrol\DBTable;
use PDO;

/**
 * A sample database object that directly extends DBObject using a
 * PostgreSQL table.
 *
 * @property integer  $primaryKeyID The primary key
 * @property string   $stringone    VarChar 50
 * @property string   $stringtwo    Char 5
 * @property string   $stringthree  Text
 * @property integer  $numberone    Regular integer
 * @property float    $numbertwo    Numeric field with 8 digits, 4 precision
 * @property integer  $numberthree  Big integer
 * @property integer  $numberfour   Small integer
 * @property float    $numberfive   Double precision
 * @property float    $numbersix    Money
 * @property float    $numberseven  Real
 * @property DateTime $dateone      Date only
 * @property DateTime $datetwo      Timestamp
 * @property DateTime $datethree    Timestamp with Timezone
 * @property DateTime $timeone      Time only
 * @property DateTime $timetwo      Time with Timezone
 * @property string   $jsonone      Simple JSON string
 * @property string   $xmarkuplang  XML type
 * @property string   $yeahnay      Enumerated Field
 * @property boolean  $trueorfalse  Boolean field
 * @property array    $xypoint      Point
 * @property boolean  $falsedef     Default value testing
 * @property boolean  $truedef     Default value testing
 *
 */
class objtest1 extends DBObject
{
    const TBL_NAME = 'pgtable1';

    public function __construct(PDO $db)
    {
        $table = new DBTable\PostgreSQL(self::TBL_NAME);
        $table->runFieldLookup($db);

        parent::__construct($table, $db);
    }
}
