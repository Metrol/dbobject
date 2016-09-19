<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBObject;
use PDO;

/**
 * Handles generating and storing a set of DBObjects
 *
 */
class Set extends DBObject\Item\Set
{
    /**
     * The object type that will be making up this set.
     *
     * @var DBObject
     */
    protected $_objItem;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
     * @param DBObject $item
     */
    public function __construct(DBObject $item)
    {
        parent::__construct($item->getDb());

        $this->_objItem = $item;

        $this->getSqlSelect()->from( $item->getDBTable()->getFQN() );
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
            /** @var DBObject $obj */
            $obj = $this->get($index);
            $this->remove($index);

            $obj->delete();
        }

        return $this;
    }

    /**
     * Generates a new DBObject that can be stored in this set.
     * This is also used by the run() method to know which kind of object to
     * populate.
     *
     * @return DBObject
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
        $sth = $this->getRunStatement();

        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) )
        {
            $item = $this->getNewItem();

            foreach ( $row as $field => $value )
            {
                $item->set($field, $value);
                $item->setLoadStatus(DBObject::LOADED);
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
     * @throws \Exception
     */
    public function getSqlWith()
    {
        throw new \Exception('WITH statements not supported for DBObject Set');
    }

    /**
     * Do not allow a WITH interface to be used here
     *
     * @throws \Exception
     */
    public function getNewSqlWith()
    {
        throw new \Exception('WITH statements not supported for DBObject Set');
    }

    /**
     * Do not allow a UNION interface to be used here
     *
     * @throws \Exception
     */
    public function getSqlUnion()
    {
        throw new \Exception('UNION statements not supported for DBObject Set');
    }

    /**
     * Do not allow a UNION interface to be used here
     *
     * @throws \Exception
     */
    public function getNewSqlUnion()
    {
        throw new \Exception('UNION statements not supported for DBObject Set');
    }

    /**
     * Do not allow RAW sql to be used here
     *
     * @param string $sql
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setRawSQL($sql)
    {
        throw new \Exception('Raw SQL not supported for DBObject Set');
    }
}
