<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBObject;
use Metrol\DBSql\SelectInterface;
use PDO;
use Exception;

/**
 * Handles generating and storing a set of DBObjects supporting the CRUD
 * interface.
 *
 */
class Set extends DBObject\Item\Set implements DBSetInterface
{
    /**
     * The object type that will be making up this set.
     *
     * @var CrudInterface
     */
    protected $_objItem;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
     * @param CrudInterface $item
     */
    public function __construct(CrudInterface $item)
    {
        parent::__construct($item->getDb());

        $this->_objItem = $item;

        $this->getSqlSelect()->from( $item->getDBTable()->getFQN() );
    }

    /**
     * Adds an item to the set
     *
     * @param CrudInterface $dbo
     *
     * @return $this
     */
    public function add(CrudInterface $dbo)
    {
        if ( $dbo instanceof $this->_objItem )
        {
            $this->_objDataSet[] = $dbo;
        }

        return $this;
    }

    /**
     * Fetching the first item off the top of the list
     *
     * @return CrudInterface
     */
    public function top()
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Deletes the item at the specified index from the database, and removes
     * it from the set.
     *
     * @param integer $index
     *
     * @return $this
     */
    public function delete($index)
    {
        if ( isset($this->_objDataSet[$index]) )
        {
            /**
             * @var CrudInterface $obj
             */
            $obj = $this->get($index);
            $this->remove($index);

            $obj->delete();
        }

        return $this;
    }

    /**
     * Deletes all the records in this set from the database and empties all
     * the items stored here.
     *
     * @param boolean $transactionFlag
     *
     * @return $this
     */
    public function deleteAll($transactionFlag = true)
    {
        // Automatically disable if already in a transaction
        if ( $this->getDb()->inTransaction() )
        {
            $transactionFlag = false;
        }

        if ( $transactionFlag )
        {
            $this->getDb()->beginTransaction();
        }

        /**
         * @var CrudInterface $item
         */
        foreach ( $this as $item )
        {
            $item->delete();
        }

        $this->clear();

        if ( $transactionFlag )
        {
            $this->getDb()->commit();
        }

        return $this;
    }

    /**
     * Saves all the items in this set with transaction support
     *
     * @param boolean $transactionFlag
     *
     * @return $this
     */
    public function save($transactionFlag = true)
    {
        // Automatically disable if already in a transaction
        if ( $this->getDb()->inTransaction() )
        {
            $transactionFlag = false;
        }

        if ( $transactionFlag )
        {
            $this->getDb()->beginTransaction();
        }

        /**
         * @var CrudInterface $item
         */
        foreach ( $this as $item )
        {
            $item->save();
        }

        if ( $transactionFlag )
        {
            $this->getDb()->commit();
        }

        return $this;
    }

    /**
     * Fetches an item based on the primary key value
     *
     * @param mixed $pkVal
     *
     * @return CrudInterface | null
     */
    public function getPk($pkVal)
    {
        $pkField = $this->_objItem->getPrimaryKeyField();

        if ( $pkField === null )
        {
            return null;
        }

        /**
         * @var CrudInterface|null $dbObj
         */
        $dbObj = $this->find($pkField, $pkVal);

        return $dbObj;
    }

    /**
     * Fetch a list of all the primary key values in the list.  If no primary
     * key, an empty array will be returned.
     *
     * @return array
     */
    public function getPkValues(): array
    {
        $pkField = $this->_objItem->getPrimaryKeyField();

        if ( $pkField === null )
        {
            return [];
        }

        return $this->getFieldValues($pkField);
    }

    /**
     * Generates a new DBObject that can be stored in this set.
     * This is also used by the run() method to know which kind of object to
     * populate.
     *
     * @return CrudInterface
     */
    public function getNewItem()
    {
        return clone $this->_objItem;
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
        catch ( Exception $e )
        {
            return $this;
        }

        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) )
        {
            $item = $this->getNewItem();

            foreach ( $row as $field => $value )
            {
                $item->set($field, $value);
                $item->setLoadStatus(CrudInterface::LOADED);
            }

            $this->_objDataSet[] = $item;
        }

        return $this;
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
     * Adds a filter where a field's value must exist within a sub-select SQL
     *
     * @param string          $fieldName Which field to filter on
     * @param SelectInterface $sql       The SQL to look for values
     *
     * @return $this
     */
    public function addValueInSQL($fieldName, SelectInterface $sql)
    {
        $this->getSqlSelect()->whereInSub($fieldName, $sql);

        return $this;
    }

    /**
     * Clear all the filters from the SQL statement
     *
     * @return $this
     */
    public function clearFilter()
    {
        $this->getSqlSelect()->whereReset();

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
     * Clear out all field ordering that may have been specified
     *
     * @return $this
     */
    public function clearOrder()
    {
        $this->getSqlSelect()->orderReset();

        return $this;
    }

    /**
     * Limit the number of rows that will be returned
     *
     * @param integer $rowCount
     *
     * @return $this
     */
    public function setLimit($rowCount)
    {
        $this->getSqlSelect()->limit( intval($rowCount) );

        return $this;
    }

    /**
     * Set the offset for where to start the result set
     *
     * @param integer $startRow
     *
     * @return $this
     */
    public function setOffset($startRow)
    {
        $this->getSqlSelect()->offset($startRow);

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

    /* The following is to prevent the use of anything but a SELECT statement */

    /**
     * Do not allow a WITH interface to be used here
     *
     * @throws Exception
     */
    public function getSqlWith()
    {
        throw new Exception('WITH statements not supported for DBObject Set');
    }

    /**
     * Do not allow a WITH interface to be used here
     *
     * @throws Exception
     */
    public function getNewSqlWith()
    {
        throw new Exception('WITH statements not supported for DBObject Set');
    }

    /**
     * Do not allow a UNION interface to be used here
     *
     * @throws Exception
     */
    public function getSqlUnion()
    {
        throw new Exception('UNION statements not supported for DBObject Set');
    }

    /**
     * Do not allow a UNION interface to be used here
     *
     * @throws Exception
     */
    public function getNewSqlUnion()
    {
        throw new Exception('UNION statements not supported for DBObject Set');
    }

    /**
     * Do not allow RAW sql to be used here
     *
     * @param string $sql
     *
     * @throws Exception
     */
    public function setRawSQL($sql)
    {
        throw new Exception('Raw SQL not supported for DBObject Set');
    }

    /**
     * Extend the parent to properly report the kind of object being returned
     *
     * @return CrudInterface
     */
    public function current()
    {
        return current($this->_objDataSet);
    }

    /**
     * Extend the parent to properly report the kind of object being returned
     *
     * @return DBObject
     */
    public function next()
    {
        return next($this->_objDataSet);
    }
}
