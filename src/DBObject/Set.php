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
     */
    protected DBObject $_objItem;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
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
     */
    public function add(DBObject $dbo): static
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
     */
    public function top(): Item
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Deletes the item at the specified index from the database, and removes
     * it from the set.
     *
     */
    public function delete(int $index): static
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
     */
    public function deleteAll(bool $transactionFlag = true): static
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
     */
    public function save(bool $transactionFlag = true): static
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
     */
    public function getPk(int|string $pkVal): ?Item
    {
        $pkField = $this->_objItem->getPrimaryKeyField();

        if ( $pkField === null )
        {
            return null;
        }

        return $this->find($pkField, $pkVal);
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
     */
    public function getNewItem(): DBObject
    {
        return clone $this->_objItem;
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     */
    public function run(): static
    {
        try
        {
            $sth = $this->getRunStatement();
        }
        catch ( Exception )
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
     */
    public function addFilter(string $whereClause, mixed $bindings = null): static
    {
        $this->getSqlSelect()->where($whereClause, $bindings);

        return $this;
    }

    /**
     * Adds a filter where a field must have a value in one of the items in
     * an array.
     *
     */
    public function addValueInFilter(string $fieldName, array $values): static
    {
        $this->getSqlSelect()->whereIn($fieldName, $values);

        return $this;
    }

    /**
     * Adds a filter where a field's value must exist within a sub-select SQL
     *
     */
    public function addValueInSQL(string $fieldName, SelectInterface $sql): static
    {
        $this->getSqlSelect()->whereInSub($fieldName, $sql);

        return $this;
    }

    /**
     * Clear all the filters from the SQL statement
     *
     */
    public function clearFilter(): static
    {
        $this->getSqlSelect()->whereReset();

        return $this;
    }

    /**
     * Add a sort field to the ordering of this set
     *
     */
    public function addOrder(string $fieldName, string $direction = null): static
    {
        $this->getSqlSelect()->order($fieldName, $direction);

        return $this;
    }

    /**
     * Clear out all field ordering that may have been specified
     *
     */
    public function clearOrder(): static
    {
        $this->getSqlSelect()->orderReset();

        return $this;
    }

    /**
     * Limit the number of rows that will be returned
     *
     */
    public function setLimit(int $rowCount): static
    {
        $this->getSqlSelect()->limit($rowCount);

        return $this;
    }

    /**
     * Set the offset for where to start the result set
     *
     */
    public function setOffset(int $startRow): static
    {
        $this->getSqlSelect()->offset($startRow);

        return $this;
    }

    /**
     * Filters the list based on the primary key value of the DBObject passed
     * in.
     *
     */
    public function addDBObjectFilter(DBObject $dbo, string $keyField = null): static
    {
        $field = $keyField;

        if ( is_null($field) )
        {
            $field = $dbo->getDBTable()->getPrimaryKeys()[0];
        }

        $this->getSqlSelect()->where( $field.' = ?', $dbo->getId() );

        return $this;
    }
}
