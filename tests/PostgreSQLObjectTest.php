<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\Tests;

use Metrol\DBObject;
use Metrol\DBTable;
use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;

/**
 * Test reading/writing information into and out of some test tables in a
 * PostgreSQL database.
 *
 */
class PostgreSQLObjectTest extends PHPUnit_Framework_TestCase
{
    /**
     * File where I put the DB credentials
     *
     * @const string
     */
    const DB_CREDENTIALS = 'etc/db.ini';

    /**
     * The table used for testing
     *
     * @const string
     */
    const TABLE_NAME = 'public.pgtable1';

    /**
     * The database to perform tests on
     *
     * @var PDO
     */
    private $db;

    /**
     * The table being worked with for testing
     *
     * @var DBTable\PostgreSQL
     */
    private $table;

    /**
     * Connect to the database so as to make the $db property available for
     * testing.
     *
     */
    public function setUp()
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
    }

    /**
     * Disconnect from the database
     *
     */
    public function tearDown()
    {
        $this->clearTable();
        $this->db = null;
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

    public function testObjectInsertAndLoad()
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

    public function testObjectUpdate()
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
    }

    public function testInsertComplexField()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals(self::TABLE_NAME, $dbo->getDBTable()->getFQN());

        $dbo->stringone = 'Howdy There';
        $dbo->xypoint   = [123.99, 456.88];
        $dbo->save();

        $newID = $dbo->id;

        $dbo = new objtest1($this->db);
        $dbo->load($newID);

        $this->assertCount(2, $dbo->xypoint);
        $this->assertEquals(123.99, $dbo->xypoint[0]);
        $this->assertEquals(456.88, $dbo->xypoint[1]);
    }
}
