<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject\Item;

use Countable;
use Exception;
use Iterator;
use JsonSerializable;
use Metrol\DBObject\Item;
use Metrol\DBSql;
use PDO;
use PDOStatement;

/**
 * Handles generating and storing a set of database records as object
 *
 */
class Set implements Iterator, Countable, JsonSerializable
{
    /**
     * PDO DB engine values
     *
     * @const string
     */
    const POSTGRESQL     = 'pgsql';
    const MYSQL          = 'mysql';
    const SQLITE         = 'sqlite';

    /**
     * Flags for determining which SQL engine, or none at all, to use
     *
     * @const string
     */
    const SQL_USE_SELECT = 'select';
    const SQL_USE_UNION  = 'union';
    const SQL_USE_WITH   = 'with';
    const SQL_USE_RAW    = 'raw';

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
     * The SQL factory for the Select, With, and Union query creators
     *
     * @var DBSql\DriverInterface
     */
    protected $_sqlDriver;

    /**
     * SQL SELECT Driver used to build the query that populates this set
     *
     * @var DBSql\SelectInterface
     */
    protected $_sqlSelect;

    /**
     * SQL WITH Driver used to build the query that populates this set
     *
     * @var DBSql\WithInterface
     */
    protected $_sqlWith;

    /**
     * SQL UNION Driver used to build the query that populates this set
     *
     * @var DBSql\UnionInterface
     */
    protected $_sqlUnion;

    /**
     * Raw SQL text passed in to be run
     *
     * @var string SQL
     */
    protected $_sqlRaw;

    /**
     * Data bindings to apply to raw SQL.  Does not apply to other SQL engines.
     *
     * @var array
     */
    protected $_sqlBinding;

    /**
     * Which SQL engine to look to when run() is called
     *
     * @var string
     */
    protected $_sqlToUse;

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
        $this->_sqlToUse   = self::SQL_USE_SELECT;
        $this->_sqlBinding = [];

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
     * Provide the object data to support json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->_objDataSet;
    }

    /**
     * Generates a new Item that can be stored in this set.
     *
     * @return Item
     */
    public function getNewItem()
    {
        return new Item;
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     * @return $this
     */
    public function run()
    {
        try
        {
            $sth = $this->getRunStatement();
        }
        catch ( Exception $e)
        {
            return $this;
        }

        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) )
        {
            $item = $this->getNewItem();

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
        try
        {
            $statement = $this->getRunStatement();
        }
        catch ( Exception $e)
        {
            return 0;
        }

        return $statement->rowCount();
    }

    /**
     * Select the correct SQL engine and return a PDO statement that can be
     * worked with.
     *
     * @return PDOStatement
     *
     * @throws Exception
     */
    protected function getRunStatement()
    {
        switch ( $this->_sqlToUse )
        {
            case self::SQL_USE_SELECT:
                $sth = $this->getDb()->prepare($this->_sqlSelect->output());
                $sth->execute($this->_sqlSelect->getBindings());
                break;

            case self::SQL_USE_WITH:
                $sth = $this->getDb()->prepare($this->_sqlWith->output());
                $sth->execute($this->_sqlWith->getBindings());
                break;

            case self::SQL_USE_UNION:
                $sth = $this->getDb()->prepare($this->_sqlUnion->output());
                $sth->execute($this->_sqlUnion->getBindings());
                break;

            case self::SQL_USE_RAW:
                $sth = $this->getDb()->prepare($this->_sqlRaw);
                $sth->execute($this->_sqlBinding);
                break;

            default:
                throw new Exception('Unknown SQL engine specifiec');
        }

        return $sth;
    }

    /**
     * Provide the SQL SELECT statement
     *
     * @return DBSql\SelectInterface
     */
    public function getSqlSelect()
    {
        if ( !is_object($this->_sqlSelect) )
        {
            $this->_sqlSelect = $this->_sqlDriver->select();
        }

        $this->_sqlToUse = self::SQL_USE_SELECT;

        return $this->_sqlSelect;
    }

    /**
     * Creates a new SQL statement to be used for this set, replacing the
     * previous one.
     *
     * @return DBSql\SelectInterface
     */
    public function getNewSqlSelect()
    {
        $this->_sqlSelect = $this->_sqlDriver->select();
        $this->_sqlToUse  = self::SQL_USE_SELECT;

        return $this->_sqlSelect;
    }

    /**
     * Provide the SQL WITH statement
     *
     * @return DBSql\WithInterface
     */
    public function getSqlWith()
    {
        if ( !is_object($this->_sqlWith) )
        {
            $this->_sqlWith = $this->_sqlDriver->with();
        }

        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlWith;
    }

    /**
     * Creates a new SQL statement to be used for this set, replacing the
     * previous one.
     *
     * @return DBSql\WithInterface
     */
    public function getNewSqlWith()
    {
        $this->_sqlWith  = $this->_sqlDriver->with();
        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlWith;
    }

    /**
     * Provide the SQL UNION statement
     *
     * @return DBSql\UnionInterface
     */
    public function getSqlUnion()
    {
        if ( !is_object($this->_sqlUnion) )
        {
            $this->_sqlUnion = $this->_sqlDriver->union();
        }

        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlUnion;
    }

    /**
     * Creates a new SQL statement to be used for this set, replacing the
     * previous one.
     *
     * @return DBSql\UnionInterface
     */
    public function getNewSqlUnion()
    {
        $this->_sqlUnion = $this->_sqlDriver->union();
        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlUnion;
    }

    /**
     * Sets raw SQL as the query to be run
     *
     * @param string $sql
     *
     * @return $this
     */
    public function setRawSQL($sql)
    {
        $this->_sqlRaw = $sql;

        $this->_sqlToUse = self::SQL_USE_RAW;

        return $this;
    }

    /**
     * Sets data bindings for the RawSQL.
     *
     * This is completely ignored by any other SQL engine type.  You need to
     * bind with those specific engines if doing so manually.
     *
     * @param array $bindings
     *
     * @return $this
     */
    public function setRawSQLBindings(array $bindings)
    {
        $this->_sqlBinding = $bindings;

        return $this;
    }

    /**
     * Adds a data binding for the RawSQL
     *
     * This is completely ignored by any other SQL engine type.  You need to
     * bind with those specific engines if doing so manually.
     *
     * @param string $label
     * @param mixed  $value
     *
     * @return $this
     */
    public function addRawSQLBinding($label, $value)
    {
        $this->_sqlBinding[$label] = $value;

        return $this;
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
     * Remove the specified index from the set.
     *
     * @param integer $index
     *
     * @return $this
     */
    public function remove($index)
    {
        if ( isset($this->_objDataSet[$index]) )
        {
            unset($this->_objDataSet[$index]);
        }

        return $this;
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
     * Reverse the order of the items in the data set
     *
     * @return $this
     */
    public function reverse()
    {
        $this->_objDataSet = array_reverse($this->_objDataSet);

        return $this;
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
     * Provide the item that has the largest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * @param string $fieldName
     *
     * @return Item|null
     */
    public function max($fieldName)
    {
        if ( $this->count() == 0 )
        {
            return null;
        }

        $topItem = $this->top();

        foreach ( $this->_objDataSet as $item )
        {
            if ( $item->get($fieldName) === null )
            {
                continue;
            }

            if ( $item->get($fieldName) > $topItem->get($fieldName) )
            {
                $topItem = $item;
            }
        }

        return $topItem;
    }

    /**
     * Provide the item that has the smallest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * Null values are not used in the comparisons.
     *
     * @param string $fieldName
     *
     * @return Item|null
     */
    public function min($fieldName)
    {
        if ( $this->count() == 0 )
        {
            return null;
        }

        $topItem = $this->top();

        foreach ( $this->_objDataSet as $item )
        {
            if ( $item->get($fieldName) === null )
            {
                continue;
            }

            if ( $item->get($fieldName) < $topItem->get($fieldName) )
            {
                $topItem = $item;
            }
        }

        return $topItem;
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
        $driverType = $this->getDb()->getAttribute(PDO::ATTR_DRIVER_NAME);

        $driver = null;

        switch ( $driverType )
        {
            case self::POSTGRESQL:
                $this->_sqlDriver = new DBSql\PostgreSQL;
                break;

            case self::MYSQL:
                $this->_sqlDriver = new DBSql\MySQL;
                break;
        }
    }

    /**
     * Provide a quick check for the data set being empty or not
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if ( count($this->_objDataSet) == 0 )
        {
            return true;
        }

        return false;
    }

    /**
     * Provide a quick check for the data set being empty or not
     *
     * @return boolean
     */
    public function isNotEmpty()
    {
        if ( count($this->_objDataSet) > 0 )
        {
            return true;
        }

        return false;
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
     * @return Item
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
