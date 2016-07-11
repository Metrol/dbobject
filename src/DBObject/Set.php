<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBObject;
use Metrol\DBSql;

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

        $this->getSqlSelect()->from( $item->getDBTable()->getName() );
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
     * Do not allow a UNION interface to be used here
     *
     * @throws \Exception
     */
    public function getSqlUnion()
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

        return $this;
    }
}
