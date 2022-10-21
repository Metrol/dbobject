<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2022, Michael Collette
 */

namespace Metrol\Tests;

use PDO;

/**
 * Populate the DB with some data for testing sets
 *
 */
class objTestPopulate
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Plug some data on in
     *
     */
    public function run(): void
    {
        $this->clearTable();

        $obj = new objtest1($this->db);
        $obj->stringone = 'That be stringy';
        $obj->stringtwo = 'DDFFE';
        $obj->stringthree = 'Let me tell you a story about a man named Jed';
        $obj->numberone  = 123;
        $obj->numbertwo  = 456.432;
        $obj->numberthree = 314159265359;
        $obj->numberfour = 233;
        $obj->numberfive = 345.4532543523;
        $obj->numbersix  = 3660.43;
        $obj->numberseven = 414123.324;
        $obj->dateone     = '2020-07-04';
        $obj->datetwo     = '2020-07-05 12:34';
        $obj->datethree   = '2020-07-05 12:34 PST';
        $obj->timeone     = '1:25 pm';
        $obj->timetwo     = '1:25 pm EDT';
        $obj->jsonone     = '"{ x: 234 }"';
        $obj->yeahnay     = 'No';
        $obj->trueorfalse = true;
        $obj->xypoint     = [34, 65];
        $obj->save();

        $obj = new objtest1($this->db);
        $obj->stringone = 'Little text';
        $obj->stringtwo = 'ABCD';
        $obj->stringthree = 'Flinstones. Meet the Flinstones. They are the modern stone age family.';
        $obj->numberone  = 321;
        $obj->numbertwo  = 456.432;
        $obj->numberthree = 271828182846;
        $obj->numberfour = 35;
        $obj->numberfive = 847.46857210523;
        $obj->numbersix  = 60.87;
        $obj->numberseven = 54981;
        $obj->dateone     = '2019-02-14';
        $obj->datetwo     = '2019-02-15 12:34';
        $obj->datethree   = '2019-02-16 12:34 CDT';
        $obj->timeone     = '10:13 pm';
        $obj->timetwo     = '10:25 pm EDT';
        $obj->jsonone     = '"{ y: 782 }"';
        $obj->yeahnay     = 'Yes';
        $obj->trueorfalse = true;
        $obj->xypoint     = [34, 65];
        $obj->save();

        $obj = new objtest1($this->db);
        $obj->stringone = 'Something to say';
        $obj->stringtwo = 'G2345';
        $obj->stringthree = 'Having nightmares about Matlock';
        $obj->numberone  = 6516516;
        $obj->numbertwo  = 866.4571;
        $obj->numberthree = 161803398875;
        $obj->numberfour = 86;
        $obj->numberfive = 65.98135561116;
        $obj->numbersix  = 75.24;
        $obj->numberseven = 6543999;
        $obj->dateone     = '2038-05-19';
        $obj->datetwo     = '2038-05-20 12:34';
        $obj->datethree   = '2038-05-21 12:34 UTC';
        $obj->timeone     = '11:45 am';
        $obj->timetwo     = '11:02 pm MDT';
        $obj->jsonone     = '"{ y: 782 }"';
        $obj->yeahnay     = 'Yes';
        $obj->trueorfalse = true;
        $obj->xypoint     = [34, 65];
        $obj->save();

        $obj = new objtest1($this->db);
        $obj->stringone = 'Still making objects!';
        $obj->stringtwo = 'AAAA4';
        $obj->stringthree = 'All work and no play makes Jack a dull boy';
        $obj->numberone  = 8422133;
        $obj->numbertwo  = 158.4571;
        $obj->numberthree = 299792458;
        $obj->numberfour = 99;
        $obj->numberfive = 81.51496618304;
        $obj->numbersix  = 215.87;
        $obj->numberseven = 519311;
        $obj->dateone     = '2017-11-19';
        $obj->datetwo     = '2017-11-20 1:34';
        $obj->datethree   = '2017-11-21 1:34 MST';
        $obj->timeone     = '1:45 am';
        $obj->timetwo     = '1:02 pm CST';
        $obj->jsonone     = '"{ y: 782 }"';
        $obj->yeahnay     = 'Yes';
        $obj->trueorfalse = true;
        $obj->xypoint     = [56, 87];
        $obj->save();
    }

    /**
     * Clear out the DB
     *
     */
    public function clearTable(): void
    {
        $dbObj = new objtest1($this->db);
        $table = $dbObj->getDBTable()->getFQNQuoted();

        $sql = 'TRUNCATE ' . $table . ' RESTART IDENTITY';

        $this->db->query($sql);
    }

}
