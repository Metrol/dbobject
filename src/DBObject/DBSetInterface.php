<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBObject;

/**
 * Define the methods used to support a set of DBObjects
 *
 */
interface DBSetInterface extends ItemSetInterface
{
    /**
     * Adds an item to the set
     *
     */
    public function add(DBObject $dbo): static;

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
    public function getPk(int|string $pkVal): ?Item;

    /**
     * Fetch a list of all the primary key values in the list.  If no primary
     * key, an empty array will be returned.
     *
     */
    public function getPkValues(): array;

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
    public function addOrder(string $fieldName, string $direction = null): static;

    /**
     * Clear out all field ordering that may have been specified
     *
     */
    public function clearOrder(): static;
}
