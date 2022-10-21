<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\Tests;

use PHPUnit\Framework\TestCase;
use Metrol\DBConnect;

/**
 * Test working with DBObject Set
 *
 */
class PostgreSQLObjectSetTest extends TestCase
{
    /**
     * File where I put the DB credentials
     *
     */
    const DB_CREDENTIALS = 'etc/postgresql_test.ini';

    private objtestSet1 $testSet;

    /**
     * Connect to the database to make the $db property available for
     * testing.
     *
     */
    public function setUp(): void
    {
        if ( isset($this->testSet) )
        {
            return;
        }

        (new DBConnect\Load\INI(self::DB_CREDENTIALS))->run();
        $db = DBConnect\Connect\Bank::get();

        $dbObj = new objtest1($db);

        $populate = new objTestPopulate($db);
        $populate->run();

        $this->testSet = new objtestSet1($dbObj);
    }

    public function tearDown(): void
    {
        $populate = new objTestPopulate($this->testSet->getDb());
        $populate->clearTable();
    }

    public function testRunAndClear(): void
    {
        $rowCount = $this->testSet->runForCount();

        $this->assertEquals(4, $rowCount);

        $this->testSet->run();

        $this->assertEquals(4, $this->testSet->count());

        $this->testSet->clear();

        $this->assertEquals(0, $this->testSet->count());
    }

    public function testFilters(): void
    {
        $this->testSet->addFilter('stringtwo = ?', 'ABCD')->run();

        $this->assertEquals(1, $this->testSet->count());

        $dbObj = $this->testSet->top();

        $this->assertEquals('Little text', $dbObj->get('stringone') );

        $this->testSet->clear()->clearFilter();
        $this->assertEquals(0, $this->testSet->count());

        $where = 'stringone = :str1 and stringtwo = :str2';
        $binding = [
            ':str1' => 'Something to say',
            ':str2' => 'G2345'
        ];

        $this->testSet->addFilterNamedBindings($where, $binding)->run();
        $this->assertEquals(1, $this->testSet->count());

        $dbObj = $this->testSet->top();
        $this->assertEquals(6516516, $dbObj->get('numberone') );
    }

}
