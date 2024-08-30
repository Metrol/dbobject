<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBObject;


/**
 * Define the methods used to support a set of DBObjects
 *
 */
interface DBSetInterface
{
    /**
     * Add a filter with bound values
     *
     */
    public function addFilter(string $whereClause, mixed $bindings = null): static;

    /**
     * Clear all the filters from the SQL statement
     *
     */
    public function clearFilter(): static;

    /**
     * Add a sort field to the ordering of this set
     *
     */
    public function addOrder(string $fieldName, string|null $direction = null): static;

    /**
     * Clear out all field ordering that may have been specified
     *
     */
    public function clearOrder(): static;

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
     * Adds an item to the set
     *
     */
    public function add(CrudInterface $dbo): static;

    /**
     * Removes all the objects from the set.  Does not remove them from the DB
     *
     */
    public function clear(): static;

    /**
     * Deletes the item at the specified index from the database, and removes
     * it from the set.
     *
     */
    public function delete(int $index): static;

    /**
     * Deletes all the records in this set from the database and empties all
     * the items stored here.
     *
     */
    public function deleteAll(bool $transactionFlag = true): static;

    /**
     * Saves all the items in this set with transaction support
     *
     */
    public function save(bool $transactionFlag = true): static;

    /**
     * Fetches an item based on the primary key value
     *
     */
    public function getPk(int|string $pkVal): ?CrudInterface;

    /**
     * Fetch a list of all the primary key values in the list.  If no primary
     * key, an empty array will be returned.
     *
     */
    public function getPkValues(): array;

    /**
     * Fetch a single item based on the index value of the data set
     *
     */
    public function get(int $index): ?CrudInterface;

    /**
     * Remove the specified index from the set.
     *
     */
    public function remove(int $index): static;

    /**
     * Fetching the first item off the top of the list
     *
     */
    public function top(): CrudInterface|false;

    /**
     * Reverse the order of the items in the data set
     *
     */
    public function reverse(): static;

    /**
     * Find the first item with specified field matching the specified value
     *
     */
    public function find(string $fieldName, mixed $findValue): ?CrudInterface;

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
    public function max(string $fieldName): ?CrudInterface;

    /**
     * Provide the item that has the smallest value for the specified field.
     * If all the field values in question are null, the top item in the list
     * is returned.
     *
     * Null values are not used in the comparisons.
     *
     */
    public function min(string $fieldName): ?CrudInterface;

    /**
     * Fetch a list of values for a specific field from the dataset as a simple
     * array.
     *
     */
    public function getFieldValues(string $fieldName): array;
}
