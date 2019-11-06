<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBSql;

/**
 * Define the methods used to support a set of Items
 *
 */
interface ItemSetInterface
{
    /**
     * Provide the SQL SELECT statement
     *
     * @return DBSql\SelectInterface
     */
    public function getSqlSelect();

    /**
     * Sets raw SQL as the query to be run
     *
     * @param string $sql
     *
     * @return $this
     */
    public function setRawSQL($sql);

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
    public function setRawSQLBindings(array $bindings);

    /**
     * Run the assembled query and apply it to the data set
     *
     * @return $this
     */
    public function run();

    /**
     * Run the assembled query, but only fetch the count of the records.
     *
     * @return integer
     */
    public function runForCount();

    /**
     * Provide the entire result set
     *
     * @return Item[]
     */
    public function output();

    /**
     * Provide a quick check for the data set being empty or not
     *
     * @return boolean
     */
    public function isEmpty(): bool;

    /**
     * Provide a quick check for the data set being empty or not
     *
     * @return boolean
     */
    public function isNotEmpty(): bool;

    /**
     *
     * @return int
     */
    public function count(): int;

    /**
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     * @return $this
     */
    public function clear();

    /**
     * Fetch a single item based on the index value of the data set
     *
     * @param integer $index
     *
     * @return Item|null
     */
    public function get($index);

    /**
     * Remove the specified index from the set.
     *
     * @param integer $index
     *
     * @return $this
     */
    public function remove($index);

    /**
     * Fetching the first item off the top of the list
     *
     * @return Item
     */
    public function top();

    /**
     * Reverse the order of the items in the data set
     *
     * @return $this
     */
    public function reverse();

    /**
     * Find the first item with specified field matching the specified value
     *
     * @param string $fieldName
     * @param mixed  $findValue
     *
     * @return Item|null
     */
    public function find($fieldName, $findValue);

    /**
     * Find all items with the specified field matching the specified value
     *
     * @param string $fieldName
     * @param mixed  $findValue
     *
     * @return Item[]
     */
    public function findAll($fieldName, $findValue);

    /**
     * Provide the item that has the largest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * @param string $fieldName
     *
     * @return Item|null
     */
    public function max($fieldName);

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
    public function min($fieldName);

    /**
     * Fetch a list of values for a specific field from the dataset as a simple
     * array.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getFieldValues($fieldName);
}
