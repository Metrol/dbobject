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
    const TABLE_NAME = 'pgtable1';

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
        $this->db->query('TRUNCATE public.' . self::TABLE_NAME);
    }

    public function testObjectInsertUpdate()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals(self::TABLE_NAME, $dbo->getDBTable()->getFQN());

        $sth = $this->db->query('SELECT last_value FROM objtest1_prikey_seq');
        $lastID = $sth->fetchObject()->last_value;

        $dbo->sometext = 'Howdy there';
        $dbo->anum     = 123456789;
        $newID = $dbo->save()->getId();

        $this->assertEquals($lastID + 1, $newID);

        $dboPull = new objtest1($this->db);
        $dboPull->load($newID);

        $this->assertEquals('Howdy there', $dboPull->sometext);
        $this->assertEquals(123456789,     $dboPull->anum);

        $dboPull->sometext = 'Hello there';
        $dboPull->save();

        $dboUpdate = (new objtest1($this->db))->load($newID);

        $this->assertEquals('Hello there', $dboUpdate->sometext);
    }

    public function testItemSets()
    {
        // Clear out any previous data in the test table
        $this->db->query('TRUNCATE public.objtest1');

        $inputRecords = [
            [
                'sometext' => 'Alpha',
                'anum'     => 1234
            ],
            [
                'sometext' => 'Bravo',
                'anum'     => 4321
            ],
            [
                'sometext' => 'Charlie',
                'anum'     => 3214
            ],
            [
                'sometext' => 'Delta',
                'anum'     => 2134
            ],
            [
                'sometext' => 'Echo',
                'anum'     => 1324
            ],
            [
                'sometext' => 'Foxtrot',
                'anum'     => 1243
            ],
            [
                'sometext' => 'Golf',
                'anum'     => 3412
            ],
            [
                'sometext' => 'Hotel',
                'anum'     => 4213
            ],
            [
                'sometext' => 'India',
                'anum'     => 3142
            ]
        ];

        foreach ( $inputRecords as $inputRecord )
        {
            $dbo = new objtest1($this->db);

            foreach ( $inputRecord as $field => $val )
            {
                $dbo->set($field, $val);
            }

            $dbo->save();
        }

        $set = new DBObject\Set(new objtest1($this->db));
        $set->addOrder('sometext')->run();
        $this->assertCount(9, $set);

        $topItem = $set->rewind()->current();

        $this->assertEquals('Alpha', $topItem->sometext);

        $set = new DBObject\Set(new objtest1($this->db));
        $set->addOrder('anum', 'DESC')->run();

        $this->assertCount(9, $set);
        $topItem = $set->rewind()->current();

        $this->assertEquals('Bravo', $topItem->sometext);
    }

    /**
     * Test a generic set of data
     *
     * @depends testItemSets
     */
    public function testGenericSets()
    {
        $set = new DBObject\Item\Set($this->db);

        $sql = $set->getSqlSelect();
        $sql->from('objtest1')
            ->fields(['sometext', 'anum'])
            ->order('sometext');
        $set->run();

        $this->assertCount(9, $set);
        $topItem = $set->rewind()->current();
        $this->assertEquals('Alpha', $topItem->sometext);
    }
}
