<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2025, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBSql;
use PDO;
use Iterator;
use Countable;
use JsonSerializable;
use Exception;

/**
 * Handles generating and storing a set of DBObjects supporting the CRUD
 * interface.
 *
 * @template T
 * @implements Iterator<int, T>
 */
class Set implements DBSetInterface, Iterator, Countable, JsonSerializable
{
    /**
     * PDO DB engine values
     *
     * @const string
     */
    const POSTGRESQL     = 'pgsql';
    const MYSQL          = 'mysql';

    /**
     * The object type that will be making up this set.
     *
     */
    protected CrudInterface $_objItem;

    /**
     * The record data for this object in key/value pairs
     *
     * @var T[]
     */
    protected array $_objDataSet = [];

    /**
     * The database connection to be used for the queries to be run
     *
     */
    protected PDO $_db;

    /**
     * SQL SELECT Driver used to build the query that populates this set
     *
     */
    protected DBSql\SelectInterface $_sqlSelect;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
     */
    public function __construct(CrudInterface $dbObject)
    {
        $this->_objItem = $dbObject;
        $this->_db      = $dbObject->getDb();

        $this->initSqlDriver();
    }

    /**
     * Provide the object data to support json_encode
     *
     */
    public function jsonSerialize(): array
    {
        return $this->_objDataSet;
    }

    /**
     * Provide the database connection used in this listing
     *
     */
    public function getDb(): PDO
    {
        return $this->_db;
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
     * Adds a filter with named bindings.  The addFilter() method only supports
     * question marks as placeholders.
     *
     */
    public function addFilterNamedBindings(string $whereClause, array $bindings): static
    {
        $sqlSel = $this->getSqlSelect();
        $sqlSel->where($whereClause);
        $sqlSel->setBindings($bindings);

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
    public function addValueInSQL(string $fieldName, DBSql\SelectInterface $sql): static
    {
        $this->getSqlSelect()->whereInSub($fieldName, $sql);

        return $this;
    }

    /**
     * Clear all the filters and bindings from the SQL statement
     *
     */
    public function clearFilter(): static
    {
        $this->getSqlSelect()->whereReset()->initBindings();

        return $this;
    }

    /**
     * Add a sort field to the ordering of this set
     *
     */
    public function addOrder(string $fieldName, string|null $direction = null): static
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
    public function addDBObjectFilter(CrudInterface $dbo, string|null $keyField = null): static
    {
        $field = $keyField;

        if ( is_null($field) )
        {
            $field = $dbo->getDBTable()->getPrimaryKeys()[0];
        }

        $this->getSqlSelect()->where( $field.' = ?', $dbo->getId() );

        return $this;
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     */
    public function run(): static
    {
        try
        {
            $sth = $this->getDb()->prepare($this->_sqlSelect->output());
            $sth->execute($this->_sqlSelect->getBindings());
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
     * Run the assembled query, but only fetch the count of the records.
     *
     */
    public function runForCount(): int
    {
        try
        {
            $sth = $this->getDb()->prepare($this->_sqlSelect->output());
            $sth->execute($this->_sqlSelect->getBindings());
        }
        catch ( Exception )
        {
            return 0;
        }

        return $sth->rowCount();
    }

    /**
     * Provide the entire result set
     *
     * @return T[]
     */
    public function output(): array
    {
        return $this->_objDataSet;
    }

    /**
     * Provide a quick check for the data set being empty or not
     *
     */
    public function isEmpty(): bool
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
     */
    public function isNotEmpty(): bool
    {
        if ( count($this->_objDataSet) > 0 )
        {
            return true;
        }

        return false;
    }

    /**
     * Fetch a single item based on the index value of the data set
     *
     * @return T|null
     */
    public function get(int $index): CrudInterface|null
    {
        $rtn = null;

        if ( isset($this->_objDataSet[$index]) )
        {
            $rtn = $this->_objDataSet[$index];
        }

        return $rtn;
    }

    /**
     * Adds an item to the set
     *
     */
    public function add(CrudInterface $dbo): static
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
     * @return T|false
     */
    public function top(): CrudInterface|false
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Reverse the order of the items in the data set
     *
     */
    public function reverse(): static
    {
        $this->_objDataSet = array_reverse($this->_objDataSet);

        return $this;
    }

    /**
     * Find the first item with specified field matching the specified value
     *
     * @return T|null
     */
    public function find(string $fieldName, mixed $findValue): CrudInterface|null
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
     * Find the index of the first item with specified field matching the
     * specified value
     *
     */
    public function findIndex(string $fieldName, mixed $findValue): int|null
    {
        $rtn = null;

        foreach ( $this->_objDataSet as $itemIdx => $item )
        {
            if ( $item->get($fieldName) == $findValue )
            {
                $rtn = $itemIdx;
                break;
            }
        }

        return $rtn;
    }

    /**
     * Find all items with the specified field matching the specified value
     *
     * @return T[]
     */
    public function findAll(string $fieldName, mixed $findValue): array
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
     * Fetch a list of values for a specific field from the dataset as a simple
     * array.
     *
     */
    public function getFieldValues(string $fieldName): array
    {
        $rtn = [];

        foreach ( $this->_objDataSet as $item )
        {
            $rtn[] = $item->get($fieldName);
        }

        return $rtn;
    }

    /**
     * Provide the item that has the largest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * @return T|null
     */
    public function max(string $fieldName): CrudInterface|null
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
     * @return T|null
     */
    public function min(string $fieldName): CrudInterface|null
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
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     */
    public function clear(): static
    {
        $this->_objDataSet = [];

        return $this;
    }

    /**
     * Remove the specified index from the set.
     *
     */
    public function remove(int $index): static
    {
        if ( isset($this->_objDataSet[$index]) )
        {
            unset($this->_objDataSet[$index]);
        }

        return $this;
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
     * @return T|null
     */
    public function getPk(int|string|null $pkVal): CrudInterface|null
    {
        if ( is_null($pkVal) )
        {
            return null;
        }

        $pkField = $this->_objItem->getPrimaryKeyField();

        if ( is_null($pkField) )
        {
            return null;
        }

        return $this->find($pkField, $pkVal);
    }

    /**
     * Fetch a list of all the primary key values in the list.  If no primary
     * key, an empty array will be returned.
     *
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
     * @return T
     */
    public function getNewItem(): CrudInterface
    {
        return clone $this->_objItem;
    }

    /**
     * Initialize the SQL driver and fill in the table into the FROM clause
     *
     */
    private function initSqlDriver(): void
    {
        $driverType = $this->getDb()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sqlDriver = null;

        switch ( $driverType )
        {
            case self::POSTGRESQL:
                $sqlDriver = new DBSql\PostgreSQL;
                break;

            case self::MYSQL:
                $sqlDriver = new DBSql\MySQL;
                break;
        }

        if ( is_null($sqlDriver) )
        {
            return;
        }

        $this->_sqlSelect = $sqlDriver->select();
        $this->_sqlSelect->from($this->_objItem->getDBTable()->getFQN());
    }

    /**
     * Provide the SQL SELECT statement
     *
     */
    public function getSqlSelect(): DBSql\SelectInterface
    {
        return $this->_sqlSelect;
    }

    /* -- Support for SPL interfaces from this point down -- */

    public function count(): int
    {
        return count($this->_objDataSet);
    }

    public function rewind(): static
    {
        reset($this->_objDataSet);

        return $this;
    }

    /**
     * @return T|false
     */
    public function current(): CrudInterface|false
    {
        return current($this->_objDataSet);
    }

    public function key(): int
    {
        return key($this->_objDataSet);
    }

    /**
     * @return T|null
     */
    public function next(): CrudInterface|false
    {
        return next($this->_objDataSet);
    }

    public function valid(): bool
    {
        return key($this->_objDataSet) !== null;
    }
}
