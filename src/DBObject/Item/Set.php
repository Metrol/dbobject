<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject\Item;

use Metrol\DBObject;
use Metrol\DBSql;

/**
 * Handles generating and storing a set of DBObjects
 *
 */
class Set implements \Iterator, \Countable
{
    /**
     * The record data for this object in key/value pairs
     *
     * @var DBObject[]
     */
    protected $_objDataSet;

    /**
     * The object type that will be making up this set.
     *
     * @var DBObject
     */
    protected $_objItem;

    /**
     * SQL SELECT Driver used to build the query that populates this set
     *
     * @var DBSql\SelectInterface
     */
    protected $_sql;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
     * @param DBObject $item
     */
    public function __construct(DBObject $item)
    {
        $this->_objItem    = $item;
        $this->_objDataSet = array();

        $this->initSqlDriver();
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->_objDataSet[ $key]);
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     * @return $this
     */
    public function run()
    {
        $statement = $this->_objItem->getDb()->prepare($this->_sql->output());
        $statement->execute($this->_sql->getBindings());

        while ( $row = $statement->fetch(\PDO::FETCH_ASSOC) )
        {
            $item = clone $this->_objItem;

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
        $statement = $this->_objItem->getDb()->prepare($this->_sql->output());
        $statement->execute($this->_sql->getBindings());

        return $statement->rowCount();
    }

    /**
     * Run the assembled query, but return only the information from the
     * specified field in an array.
     *
     * The data set stored in this object is not populated or affected in any
     * way by running this.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function runForField($fieldName)
    {
        $this->getSqlSelect()->fields([$fieldName]);

        $statement = $this->_objItem->getDb()->prepare($this->_sql->output());
        $statement->execute($this->_sql->getBindings());

        $rtn = [];

        while ( $row = $statement->fetch(\PDO::FETCH_ASSOC) )
        {
            $rtn[] = $row[$fieldName];
        }

        // Put the fields to come out back to the default
        $this->getSqlSelect()->fields(['*']);

        return $rtn;
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
     * Provide the entire result set
     *
     * @return array
     */
    public function getDataSet()
    {
        return $this->_objDataSet;
    }

    /**
     * Add a filter with bound values
     *
     * @param string $whereClause
     * @param mixed|array  $bindings
     *
     * @return $this
     */
    public function addFilter($whereClause, $bindings = null)
    {
        $this->getSqlSelect()->where($whereClause, $bindings);

        return $this;
    }

    /**
     * Adds a filter where a field must have a value in one of the items in
     * an array.
     *
     * @param string $fieldName Which field to filter on
     * @param array  $values    Values to match
     *
     * @return $this
     */
    public function addValueInFilter($fieldName, array $values)
    {
        $this->getSqlSelect()->whereIn($fieldName, $values);

        return $this;
    }

    /**
     * Add a sort field to the ordering of this set
     *
     * @param string $fieldName
     * @param string $direction
     *
     * @return $this
     */
    public function addOrder($fieldName, $direction = null)
    {
        $this->getSqlSelect()->order($fieldName, $direction);

        return $this;
    }

    /**
     * Filters the list based on the primary key value of the DBObject passed
     * in.
     *
     * @param DBObject $dbo
     * @param string   $keyField This is field in the table being queried
     *
     * @return $this
     */
    public function addDBObjectFilter(DBObject $dbo, $keyField = null)
    {
        $field = $keyField;

        if ( $field == null )
        {
            $field = $dbo->getDBTable()->getPrimaryKeys()[0];
        }

        $this->getSqlSelect()->where( $field.' = ?', $dbo->getId() );

        return $this;
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
     * Adds an item to the set
     *
     * @param DBObject $dbo
     *
     * @return $this
     */
    public function add(DBObject $dbo)
    {
        if ( $dbo instanceof $this->_objItem )
        {
            $this->_objDataSet[] = $dbo;
        }
    }

    /**
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     * @return $this
     */
    public function clearSet()
    {
        $this->_objDataSet = [];

        return $this;
    }

    /**
     * Initialize the SQL driver and fill in the table into the FROM clause
     *
     */
    protected function initSqlDriver()
    {
        $this->_sql = $this->_objItem->getSqlDriver()->select();
        $table      = $this->_objItem->getDBTable();

        $this->_sql->from( $table->getFQN() );
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
     * @return DBObject
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
