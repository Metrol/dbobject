<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBTable;
use PDO;
use UnderflowException;

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
     * should be in its initial state
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
     */
    public function get(string $field): mixed;

    /**
     * Sets a value for a field
     *
     */
    public function set(string $field, mixed $value): static;

    /**
     * Set the primary key value for this object
     *
     */
    public function setId(int|string $value): static;

    /**
     * Provide the primary key value
     *
     */
    public function getId(): int|string|null;

    /**
     * Provides the primary key field for this object
     *
     */
    public function getPrimaryKeyField(): string|null;

    /**
     * Saves the object out to the database.
     *
     * If the record has been loaded, an update will be attempted.  If not
     * loaded, then a new record will be added.
     *
     */
    public function save(): static;

    /**
     * Pulls in the information from a single record based on the value/values
     * of the primary keys.
     *
     * For records with a single primary key, that value may be passed directly
     * into this method.  Otherwise, the fields in question must have had
     * their values already set.
     *
     * @throws UnderflowException When no primary keys are specified
     */
    public function load(int|string|null $primaryKeyValue = null): static;

    /**
     * Allows the caller to specify exactly the criteria to be used to load
     * a record.
     *
     */
    public function loadFromWhere(string $where, mixed $binding = null): static;

    /**
     * Provide the load status of the object based on the constants of the
     * interface.
     *
     */
    public function getLoadStatus(): int;

    /**
     * Set the load status manually
     *
     */
    public function setLoadStatus(int $loadStatus): static;

    /**
     * Returns true if the load status has been marked as LOADED.  Otherwise,
     * returns false.
     *
     */
    public function isLoaded(): bool;

    /**
     * Returns true if the load status is not marked LOADED.  False if loaded.
     *
     */
    public function isNotLoaded(): bool;

    /**
     * Delete the loaded record from the database.
     * Does nothing if no record is loaded.
     *
     */
    public function delete(): static;

    /**
     * Provide the database connection used for this item
     *
     */
    public function getDb(): PDO;

    /**
     * Provide the database table to be used for this DB Item
     *
     */
    public function getDBTable(): DBTable;
}
