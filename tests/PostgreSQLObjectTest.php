<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol\DBObject
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\Tests;

use Metrol\DBTable;
use Metrol\DBObject;

/**
 * Test reading/writing information into and out of some test tables in a
 * PostgreSQL database.
 *
 */
class PostgreSQLObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * File where I put the DB credentials
     *
     * @const
     */
    const DB_CREDENTIALS = 'etc/db.ini';

    /**
     * The database to perform tests on
     *
     * @var \PDO
     */
    private $db;

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
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ];

        $this->db = new \PDO($dsn, $ini['DBUSER'], $ini['DBPASS'], $opts);
    }

    /**
     * Disconnect from the database
     *
     */
    public function tearDown()
    {
        // $this->db->query('TRUNCATE public.objtest1');
        $this->db = null;
    }

    public function testObjectInsertUpdate()
    {
        $dbo = new objtest1($this->db);

        $this->assertEquals('public.objtest1', $dbo->getDBTable()->getFQN());

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

    public function testSets()
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
}
