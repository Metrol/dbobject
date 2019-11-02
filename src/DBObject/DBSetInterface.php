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
interface DBSetInterface extends ItemSetInterface
{
    /**
     * Adds an item to the set
     *
     * @param CrudInterface $dbo
     *
     * @return $this
     */
    public function add(CrudInterface $dbo);

    /**
     * Deletes the item at the specified index from the database, and removes
     * it from the set.
     *
     * @param integer $index
     *
     * @return $this
     */
    public function delete($index);

    /**
     * Deletes all the records in this set from the database and empties all
     * the items stored here.
     *
     * @param boolean $transactionFlag
     *
     * @return $this
     */
    public function deleteAll($transactionFlag = true);

    /**
     * Saves all the items in this set with transaction support
     *
     * @param boolean $transactionFlag
     *
     * @return $this
     */
    public function save($transactionFlag = true);

    /**
     * Fetches an item based on the primary key value
     *
     * @param mixed $pkVal
     *
     * @return CrudInterface | null
     */
    public function getPk($pkVal);

    /**
     * Add a filter with bound values
     *
     * @param string      $whereClause
     * @param mixed|array $bindings
     *
     * @return $this
     */
    public function addFilter($whereClause, $bindings = null);

    /**
     * Clear all the filters from the SQL statement
     *
     * @return $this
     */
    public function clearFilter();

    /**
     * Add a sort field to the ordering of this set
     *
     * @param string $fieldName
     * @param string $direction
     *
     * @return $this
     */
    public function addOrder($fieldName, $direction = null);

    /**
     * Clear out all field ordering that may have been specified
     *
     * @return $this
     */
    public function clearOrder();
}
