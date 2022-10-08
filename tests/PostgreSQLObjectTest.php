<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\Tests;

use PHPUnit\Framework\TestCase;
use Metrol\DBTable;
use PDO;
use PDOException;

/**
 * Test reading/writing information into and out of some test tables in a
 * PostgreSQL database.
 *
 */
class PostgreSQLObjectTest extends TestCase
{
    /**
     * File where I put the DB credentials
     *
     */
    const DB_CREDENTIALS = 'etc/db.ini';

    /**
     * The table used for testing
     *
     */
    const TABLE_NAME = 'public.pgtable1';

    /**
     * The database to perform tests on
     *
     */
    private PDO $db;

    /**
     * The table being worked with for testing
     *
     */
    private DBTable\PostgreSQL $table;

    /**
     * Connect to the database to make the $db property available for
     * testing.
     *
     */
    public function setUp(): void
    {
        $ini = parse_ini_file(self::DB_CREDENTIALS);

        $dsn = 'pgsql:';
        $dsn .= implode(';', [
            'host=' .  $ini['DBHOST'],
            'port='.   $ini['DBPORT'],
            'dbname='. $ini['DBNAME']
        ]);

        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try
        {
            $this->db = new PDO($dsn, $ini['DBUSER'], $ini['DBPASS'], $opts);
        }
        catch ( PDOException $e )
        {
            echo 'Connection to database failed';
            exit;
        }

        $this->table = new DBTable\PostgreSQL(self::TABLE_NAME);

        $this->clearTable();
    }

    /**
     * Disconnect from the database
     *
     */
    private function clearTable()
    {
        $sql = 'TRUNCATE ' . self::TABLE_NAME . ' RESTART IDENTITY';

        $this->db->query($sql);
    }

    /**
     * Try a very basic insert, and then load that record back out from the DB
     *
     */
    public function xtestObjectInsertAndLoad()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals(self::TABLE_NAME, $dbo->getDBTable()->getFQN());

        $dbo->stringone = 'Howdy There';
        $dbo->save();

        $newID = $dbo->getId();

        $this->assertNotNull($newID);
        $this->assertEquals($dbo::LOADED, $dbo->getLoadStatus());

        $loadDbo = new objtest1($this->db);
        $loadDbo->load($newID);

        $this->assertEquals('Howdy There', $loadDbo->stringone);
    }

    /**
     * Inserts a new record, then loads it, then the record is updated in the
     * DB.
     */
    public function xtestObjectUpdate()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals(self::TABLE_NAME, $dbo->getDBTable()->getFQN());

        $dbo->stringone = 'Howdy There';
        $dbo->save();

        $newID = $dbo->getId();

        $this->assertNotNull($newID);
        $this->assertEquals($dbo::LOADED, $dbo->getLoadStatus());

        $loadDbo = new objtest1($this->db);
        $loadDbo->load($newID);

        $this->assertEquals('Howdy There', $loadDbo->stringone);

        $loadDbo->stringtwo = 'ABCDE';
        $loadDbo->save();

        $dbo = new objtest1($this->db);
        $dbo->load($newID);

        $this->assertEquals('ABCDE', $dbo->stringtwo);
    }

    /**
     * Create a new record with values going into a complex object.  This is
     * then loaded, updated, then loaded again to verify the update.
     *
     */
    public function xtestInsertUpdateComplexField()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals(self::TABLE_NAME, $dbo->getDBTable()->getFQN());

        $dbo->stringone = 'Howdy There';
        $dbo->xypoint   = [123.99, 456.88];
        $dbo->save();

        $newID = $dbo->id;

        $dbo = new objtest1($this->db);
        $dbo->load($newID);

        $this->assertTrue($dbo->isLoaded());
        $this->assertCount(2, $dbo->xypoint);
        $this->assertEquals(123.99, $dbo->xypoint[0]);
        $this->assertEquals(456.88, $dbo->xypoint[1]);

        $dbo->xypoint = [3.141592920, 1.6180339887];
        $dbo->save();

        $dbo = new objtest1($this->db);
        $dbo->load($newID);

        $this->assertTrue($dbo->isLoaded());
        $this->assertCount(2, $dbo->xypoint);
        $this->assertEquals(3.141592920, $dbo->xypoint[0]);
        $this->assertEquals(1.6180339887, $dbo->xypoint[1]);
    }

    /**
     * Give every field available to the test object a valid value
     *
     */
    public function testInsertUpdateValidData()
    {
        $varcharVal = 'I just love a parade of characters that are good.';
        $charVal    = 'XyZvE';
        $textVal    = <<<TEXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed sodales euismod mi
non iaculis. Proin interdum orci lorem, at blandit nibh cursus non. Vestibulum
eget erat vel massa convallis fringilla. Ut molestie mauris nec nibh sagittis,
non rhoncus neque consectetur. Nunc laoreet viverra ante non imperdiet. Nunc ut
nisl quis neque dapibus auctor et a metus. Curabitur accumsan rutrum libero, ut
dictum lorem vestibulum ut. Mauris efficitur in dui at tempus. Vivamus
tristique finibus purus sit amet convallis. Aliquam sollicitudin porta nisi, a
bibendum massa congue vel. Ut blandit velit id pulvinar maximus. Nullam quis
ipsum eu ex mattis ultrices id sit amet mauris.
TEXT;

        $dbo = new objtest1($this->db);

        $dbo->stringone   = $varcharVal;
        $dbo->stringtwo   = $charVal;
        $dbo->stringthree = $textVal;

        $dbo->numberone = 1245789865;
        $dbo->numbertwo = 2344.7435;
        $dbo->numberthree = 156151851365184631;
        $dbo->numberfour  = 127;
        $dbo->numberfive  = 4321.1234567;
        $dbo->numbersix   = 2345.32;
        $dbo->numberseven = 45684.23;

        $dbo->dateone     = '2010-07-04';
        $dbo->datetwo     = '2010-07-04 13:14:12';
        $dbo->datethree   = '2010-07-04 17:00-05';
        $dbo->timeone     = '12:45';
        $dbo->timetwo     = '15:23 EST';

        $dbo->jsonone     = json_encode(['key1' => 'abcde', 'key2' => 'qwerty']);
        $dbo->yeahnay     = 'Yes';
        $dbo->trueorfalse = true;
        $dbo->xypoint     = [78.123, -54.568];
        $dbo->save();

        $newID = $dbo->id;

        $dbo = new objtest1($this->db);
        $dbo->load($newID);

        $this->assertEquals($varcharVal, $dbo->stringone);
        $this->assertEquals($charVal, $dbo->stringtwo);
        $this->assertEquals($textVal, $dbo->stringthree);

        $this->assertEquals(1245789865, $dbo->numberone);  // Regular integer
        $this->assertEquals(2344.7435, $dbo->numbertwo);   // Numeric (8, 4)
        $this->assertEquals(156151851365184631, $dbo->numberthree); // Big Int
        $this->assertEquals(127, $dbo->numberfour);                 // Small Int
        // $this->assertEquals(4321.1234567, $dbo->numberfive);     // Dbl Prec
        // $this->assertEquals(2345.32, $dbo->numbersix);           // Money
        // $this->assertEquals(45684.23, $dbo->numberseven);        // Real

        $this->assertEquals('2010-07-04', $dbo->dateone->format('Y-m-d'));
        $this->assertEquals('2010-07-04 13:14:12', $dbo->datetwo->format('Y-m-d H:i:s'));
        // $this->assertEquals('2010-07-04 19:00:00-05', $dbo->datethree->format('Y-m-d H:i:s'));

        $this->assertEquals('12:45:00', $dbo->timeone);
        $this->assertEquals('15:23:00-05', $dbo->timetwo);

        $this->assertEquals('{"key1":"abcde","key2":"qwerty"}', $dbo->jsonone);

        $this->assertEquals('Yes', $dbo->yeahnay);

        $this->assertTrue($dbo->trueorfalse);

        $this->assertCount(2, $dbo->xypoint);
        $this->assertEquals(78.123, $dbo->xypoint[0]);
        $this->assertEquals(-54.568, $dbo->xypoint[1]);
    }
}
