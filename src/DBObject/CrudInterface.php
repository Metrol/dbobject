<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

/**
 * Describes the basic operations needed for a database object
 *
 * @property mixed $id Primary key value, like calling getId() or setId()
 */
interface CrudInterface
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
     * Fetches a value from the specified field
     *
     * @param string $field
     *
     * @return mixed|null
     */
    public function get($field);

    /**
     * Sets a value for a field
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($field, $value);

    /**
     * Set the primary key value for this object
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setId($value);

    /**
     * Provide the primary key value
     *
     * @return mixed|null
     */
    public function getId();

    /**
     * Provides the primary key field for this object
     *
     * @return string|null
     */
    public function getPrimaryKeyField();

    /**
     * Saves the object out to the database.
     *
     * If the record has been loaded, an update will be attempted.  If not
     * loaded, then a new record will be added.
     *
     * @return $this
     */
    public function save();

    /**
     * Pulls in the information from a single record based on the value/values
     * of the primary keys.
     *
     * For records with a single primary key, that value may be passed directly
     * into this method.  Otherwise, the fields in question must have had
     * their values already set.
     *
     * @param mixed $primaryKeyValue
     *
     * @return $this
     *
     * @throws \UnderflowException When no primary keys are specified
     */
    public function load($primaryKeyValue = null);

    /**
     * Allows the caller to specify exactly the criteria to be used to load
     * a record.
     *
     * @param string $where The WHERE clause to be passed to the SQL engine
     * @param mixed|array $binding Values to bind to the WHERE clause
     *
     * @return $this
     */
    public function loadFromWhere($where, $binding = null);

    /**
     * Provide the load status of the object based on the constants of the
     * interface.
     *
     * @return integer
     */
    public function getLoadStatus();

    /**
     * Set the load status manually
     *
     * @param integer $loadStatus
     *
     * @return $this
     */
    public function setLoadStatus($loadStatus);

    /**
     * Returns true if the load status has been marked as LOADED.  Otherwise,
     * returns false.
     *
     * @return boolean
     */
    public function isLoaded();

    /**
     * Returns true if the load status is not marked LOADED.  False if loaded.
     *
     * @return boolean
     */
    public function isNotLoaded();

    /**
     * Delete the loaded record from the database.
     * Does nothing if no record is loaded.
     *
     * @return $this
     */
    public function delete();

    /**
     * Provide the database connection used for this item
     *
     * @return \PDO
     */
    public function getDb();

    /**
     * Provide the database table to be used for this DB Item
     *
     * @return \Metrol\DBTable
     */
    public function getDBTable();
}
