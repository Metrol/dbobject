<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol_Libs
 * @version       2.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol;

use Metrol\DBTable;
use Metrol\DBSql;

/**
 * Describes the functionality that all DBObjects must be able to support.
 *
 */
interface DBObject
{
    /**
     * Flag set to specify that a load() has been attempted, and was successful
     * in pulling back a record to populate this object.
     *
     * @const integer
     */
    const LOADED = 1;

    /**
     * Flag set to specify that a load() has not been attempted.  The object
     * should be in it's initial state
     *
     * @const integer
     */
    const NOT_LOADED = 0;

    /**
     * Flag set to specific a load() was attempted, but a matching record could
     * not be found.
     *
     * @const integer
     */
    const NOT_FOUND = 86;

    /**
     * Set a value for a field
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($fieldName, $value);

    /**
     * Get a value from a field
     *
     * @param string $fieldName
     *
     * @return mixed|null
     */
    public function get($fieldName);

    /**
     * Initiates a query to the database to pull in a record.  This is entirely
     * based on the primary key value.  If one is not provided, the value
     * already stored in this object will be used.
     *
     * @param mixed $primaryKeyValue
     *
     * @return $this
     *
     * @throws \UnderflowException If no primary key is specified.
     */
    public function load($primaryKeyValue = null);

    /**
     * Loads a single record based on the WHERE criteria and optional bindings
     * provided.
     *
     * Any value that is to be bound must have a quesion mark "?" used as a
     * place holder.
     *
     * @param string $where
     * @param mixed|array  $bindings
     *
     * @return $this
     */
    public function loadFromWhere($where, $bindings = null);

    /**
     * Will update a record if a primary key value has been set and the object
     * has been marked as "Loaded".  Otherwise, a new record will be written.
     *
     * Any time a save is called, the values in this object will be updated.
     * For new records, check getId() for automatically generated primary key
     * values.
     *
     * @return $this
     */
    public function save();

    /**
     * Deletes a record based on the primary key value.  Does nothing if no
     * primary key value has been set.
     *
     * @return $this
     */
    public function delete();

    /**
     * Manually set the primary key value
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setId($value);

    /**
     * Fetch the primary key value of this object, or NULL if not set.
     *
     * @return mixed|null
     */
    public function getId();

    /**
     * Provide the database connection used for this item
     *
     * @return \PDO
     */
    public function getDb();

    /**
     * Provide the SQL Driver used to create the queries for this item
     *
     * @return DBSql\DriverInterface
     *
     * @throws \UnexpectedValueException When no engine is found
     */
    public function getSqlDriver();

    /**
     * Provide the database table that was set in this object
     *
     * @return DBTable
     */
    public function getDBTable();
}
