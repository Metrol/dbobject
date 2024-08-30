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
     */
    public function getSqlSelect(): DBSql\SelectInterface;

    /**
     * Sets raw SQL as the query to be run
     *
     */
    public function setRawSQL(string $sql): static;

    /**
     * Sets data bindings for the RawSQL.
     *
     * This is completely ignored by any other SQL engine type.  You need to
     * bind with those specific engines if doing so manually.
     *
     */
    public function setRawSQLBindings(array $bindings): static;

    /**
     * Run the assembled query and apply it to the data set
     *
     */
    public function run(): static;

    /**
     * Run the assembled query, but only fetch the count of the records.
     *
     */
    public function runForCount(): int;

    /**
     * Provide the entire result set
     *
     */
    public function output(): array;

    /**
     * Provide a quick check for the data set being empty or not
     *
     */
    public function isEmpty(): bool;

    /**
     * Provide a quick check for the data set being empty or not
     *
     */
    public function isNotEmpty(): bool;

    /**
     * How many items in the set
     *
     */
    public function count(): int;

    /**
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     */
    public function clear(): static;

    /**
     * Fetch a single item based on the index value of the data set
     *
     */
    public function get(int $index): Item|null;

    /**
     * Remove the specified index from the set.
     *
     */
    public function remove(int $index): static;

    /**
     * Fetching the first item off the top of the list
     *
     */
    public function top(): Item;

    /**
     * Reverse the order of the items in the data set
     *
     */
    public function reverse(): static;

    /**
     * Find the first item with specified field matching the specified value
     *
     */
    public function find(string $fieldName, mixed $findValue): Item|null;

    /**
     * Find all items with the specified field matching the specified value
     *
     */
    public function findAll(string $fieldName, mixed $findValue): array;

    /**
     * Provide the item that has the largest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     */
    public function max(string $fieldName): Item|null;

    /**
     * Provide the item that has the smallest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * Null values are not used in the comparisons.
     *
     */
    public function min(string $fieldName): Item|null;

    /**
     * Fetch a list of values for a specific field from the dataset as a simple
     * array.
     *
     */
    public function getFieldValues(string $fieldName): array;
}
