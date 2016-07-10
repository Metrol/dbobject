<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\DBObject\Item;

use Metrol\DBObject\Item;
use Metrol\DBSql;
use PDO;

/**
 * Handles generating and storing a set of database records as object
 *
 */
class Set implements \Iterator, \Countable
{
    const POSTGRESQL     = 'pgsql';
    const MYSQL          = 'mysql';
    const SQLITE         = 'sqlite';

    /**
     * The record data for this object in key/value pairs
     *
     * @var Item[]
     */
    protected $_objDataSet;

    /**
     * The database connection to be used for the queries to be run
     *
     * @var PDO
     */
    protected $_db;

    /**
     * SQL SELECT Driver used to build the query that populates this set
     *
     * @var DBSql\SelectInterface
     */
    protected $_sql;

    /**
     * Instantiate the object set.
     * Stores the database connection locally.
     * Initializes the SQL driver to be used.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->_db         = $db;
        $this->_objDataSet = array();

        $this->initSqlDriver();
    }

    /**
     * Check for a field name existing in the data set
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->_objDataSet[$key]);
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     * @return $this
     */
    public function run()
    {
        $statement = $this->getDb()->prepare($this->_sql->output());
        $statement->execute($this->_sql->getBindings());

        while ( $row = $statement->fetch(PDO::FETCH_ASSOC) )
        {
            $item = new Item;

            foreach ( $row as $field => $value )
            {
                $item->set($field, $value);
            }

            $this->_objDataSet[] = $item;
        }

        return $this;
    }

    /**
     * Run the assembled query, but only fetch the count of the records.
     *
     * @return integer
     */
    public function runForCount()
    {
        $statement = $this->getDb()->prepare($this->_sql->output());
        $statement->execute($this->_sql->getBindings());

        return $statement->rowCount();
    }

    /**
     * Provide the SQL SELECT statement in work
     *
     * @return DBSql\SelectInterface
     */
    public function getSqlSelect()
    {
        return $this->_sql;
    }

    /**
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     * @return $this
     */
    public function clear()
    {
        $this->_objDataSet = [];

        return $this;
    }

    /**
     * Fetch a list of values for a specific field from the dataset as a simple
     * array.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getFieldValues($fieldName)
    {
        $rtn = [];

        foreach ( $this->_objDataSet as $item )
        {
            $rtn[] = $item->get($fieldName);
        }

        return $rtn;
    }

    /**
     * Fetch a single item based on the index value of the data set
     *
     * @param integer $index
     *
     * @return Item|null
     */
    public function get($index)
    {
        $rtn = null;

        if ( isset($this->_objDataSet[$index]) )
        {
            $rtn = $this->_objDataSet[$index];
        }

        return $rtn;
    }

    /**
     * Fetching the first item off the top of the list
     *
     * @return Item
     */
    public function top()
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Find the first item with specified field matching the specified value
     *
     * @param string $fieldName
     * @param mixed  $findValue
     *
     * @return Item|null
     */
    public function find($fieldName, $findValue)
    {
        $rtn = null;

        foreach ( $this->_objDataSet as $item )
        {
            if ( $item->get($fieldName) == $findValue )
            {
                $rtn = $item;
                break;
            }
        }

        return $rtn;
    }

    /**
     * Find all items with the specified field matching the specified value
     *
     * @param string $fieldName
     * @param mixed  $findValue
     *
     * @return Item[]
     */
    public function findAll($fieldName, $findValue)
    {
        $rtn = [];

        foreach ( $this->_objDataSet as $item )
        {
            if ( $item->get($fieldName) == $findValue )
            {
                $rtn[] = $item;
            }
        }

        return $rtn;
    }

    /**
     * Provide the entire result set
     *
     * @return Item[]
     */
    public function getDataSet()
    {
        return $this->_objDataSet;
    }

    /**
     * Provide the database connection used in this listing
     *
     * @return PDO
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Initialize the SQL driver and fill in the table into the FROM clause
     *
     */
    protected function initSqlDriver()
    {
        $driverType = $this->getDb()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $driver = null;

        switch ( $driverType )
        {
            case self::POSTGRESQL:
                $this->_sql = new DBSql\PostgreSQL\Select;
                break;

            case self::MYSQL:
                $this->_sql = new DBSql\MySQL\Select;
                break;
        }
    }

    /* -- Support for SPL interfaces from this point down -- */

    /**
     *
     * @return int
     */
    public function count()
    {
        return count($this->_objDataSet);
    }

    /**
     *
     * @return $this
     */
    public function rewind()
    {
        reset($this->_objDataSet);

        return $this;
    }

    /**
     *
     * @return Item
     */
    public function current()
    {
        return current($this->_objDataSet);
    }

    /**
     *
     * @return integer
     */
    public function key()
    {
        return key($this->_objDataSet);
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->_objDataSet);
    }

    /**
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->_objDataSet) !== null;
    }
}
