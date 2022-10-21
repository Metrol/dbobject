<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject\Item;

use Countable;
use Iterator;
use JsonSerializable;
use Metrol\DBObject\ItemSetInterface;
use Metrol\DBObject\Item;
use Metrol\DBSql;
use PDO;
use PDOStatement;

/**
 * Handles generating and storing a set of database records as object
 *
 */
class Set implements ItemSetInterface, Iterator, Countable, JsonSerializable
{
    /**
     * PDO DB engine values
     *
     * @const string
     */
    const POSTGRESQL     = 'pgsql';
    const MYSQL          = 'mysql';

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
    protected array $_objDataSet = [];

    /**
     * The database connection to be used for the queries to be run
     *
     */
    protected PDO $_db;

    /**
     * The SQL factory for the Select, With, and Union query creators
     *
     */
    protected DBSql\DriverInterface $_sqlDriver;

    /**
     * SQL SELECT Driver used to build the query that populates this set
     *
     */
    protected DBSql\SelectInterface $_sqlSelect;

    /**
     * SQL WITH Driver used to build the query that populates this set
     *
     */
    protected DBSql\WithInterface $_sqlWith;

    /**
     * SQL UNION Driver used to build the query that populates this set
     *
     */
    protected DBSql\UnionInterface $_sqlUnion;

    /**
     * Raw SQL text passed in to be run
     *
     */
    protected string $_sqlRaw;

    /**
     * Data bindings to apply to raw SQL.  Does not apply to other SQL engines.
     *
     */
    protected array $_sqlBinding = [];

    /**
     * Which SQL engine to look to when run() is called
     *
     */
    protected string $_sqlToUse = self::SQL_USE_SELECT;

    /**
     * Instantiate the object set.
     * Stores the database connection locally.
     * Initializes the SQL driver to be used.
     *
     */
    public function __construct(PDO $db)
    {
        $this->_db = $db;

        $this->initSqlDriver();
    }

    /**
     * Check for a field name existing in the data set
     *
     */
    public function __isset(mixed $key): bool
    {
        return isset($this->_objDataSet[$key]);
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
     * Generates a new Item that can be stored in this set.
     *
     */
    public function getNewItem(): Item
    {
        return new Item;
    }

    /**
     * Run the assembled query and apply it to the data set
     *
     */
    public function run(): static
    {
        $sth = $this->getRunStatement();

        if ( is_null($sth) )
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
     */
    public function runForCount(): int
    {
        $statement = $this->getRunStatement();

        if ( is_null($statement) )
        {
            return 0;
        }

        return $statement->rowCount();
    }

    /**
     * Provide the entire result set
     *
     * @return Item[]
     */
    public function output(): array
    {
        return $this->_objDataSet;
    }

    /**
     * Provide the entire result set (synonym for output())
     *
     * @return Item[]
     */
    public function getDataSet(): array
    {
        return $this->_objDataSet;
    }

    /**
     * Select the correct SQL engine and return a PDO statement that can be
     * worked with.
     *
     */
    protected function getRunStatement(): ?PDOStatement
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
                return null;
        }

        return $sth;
    }

    /**
     * Provide the SQL SELECT statement
     *
     */
    public function getSqlSelect(): DBSql\SelectInterface
    {
        if ( ! isset($this->_sqlSelect) )
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
     */
    public function getNewSqlSelect(): DBSql\SelectInterface
    {
        $this->_sqlSelect = $this->_sqlDriver->select();
        $this->_sqlToUse  = self::SQL_USE_SELECT;

        return $this->_sqlSelect;
    }

    /**
     * Provide the SQL WITH statement
     *
     */
    public function getSqlWith(): DBSql\WithInterface
    {
        if ( !isset($this->_sqlWith) )
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
     */
    public function getNewSqlWith(): DBSql\WithInterface
    {
        $this->_sqlWith  = $this->_sqlDriver->with();
        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlWith;
    }

    /**
     * Provide the SQL UNION statement
     *
     */
    public function getSqlUnion(): DBSql\UnionInterface
    {
        if ( !isset($this->_sqlUnion) )
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
     */
    public function getNewSqlUnion(): DBSql\UnionInterface
    {
        $this->_sqlUnion = $this->_sqlDriver->union();
        $this->_sqlToUse = self::SQL_USE_WITH;

        return $this->_sqlUnion;
    }

    /**
     * Sets raw SQL as the query to be run
     *
     */
    public function setRawSQL(string $sql): static
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
     */
    public function setRawSQLBindings(array $bindings): static
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
     */
    public function addRawSQLBinding(string $label, mixed $value): static
    {
        $this->_sqlBinding[$label] = $value;

        return $this;
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
     * Fetch a single item based on the index value of the data set
     *
     */
    public function get(int $index): ?Item
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
     * Fetching the first item off the top of the list
     *
     */
    public function top(): Item
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
     */
    public function find(string $fieldName, mixed $findValue): ?Item
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
     */
    public function max(string $fieldName): ?Item
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
     */
    public function min(string $fieldName): ?Item
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
     * @return Item[]
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
     * Provide the database connection used in this listing
     *
     */
    public function getDb(): PDO
    {
        return $this->_db;
    }

    /**
     * Initialize the SQL driver and fill in the table into the FROM clause
     *
     */
    protected function initSqlDriver(): void
    {
        $driverType = $this->getDb()->getAttribute(PDO::ATTR_DRIVER_NAME);

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

    public function current(): Item|false
    {
        return current($this->_objDataSet);
    }

    public function key(): int
    {
        return key($this->_objDataSet);
    }

    public function next(): Item|false
    {
        return next($this->_objDataSet);
    }

    public function valid(): bool
    {
        return key($this->_objDataSet) !== null;
    }
}
