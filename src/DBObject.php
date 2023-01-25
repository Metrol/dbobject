<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol;

use Metrol;
use Metrol\DBObject\{CrudInterface, ItemInterface};
use PDO;
use JsonSerializable;
use Iterator;
use UnderflowException;

/**
 * Maps itself to a database record for the purposes of creating, updating,
 * and deleting the information.
 *
 */
class DBObject implements CrudInterface, ItemInterface, JsonSerializable, Iterator
{
    /**
     * The virtual primary key field that can be used in place of having to
     * know the actual field name for most uses.
     *
     */
    const VIRTUAL_PK_FIELD = 'id';

    /**
     * The data for this object in key/value pairs
     *
     */
    protected array $_objData = [];

    /**
     * The database table that this item will be acting as a front end for
     *
     */
    protected DBTable $_objTable;

    /**
     * The database connection used to talk to this object
     *
     */
    protected PDO $_objDb;

    /**
     * Tracks the load status for the item
     *
     */
    protected int $_objLoadStatus = self::NOT_LOADED;

    /**
     * The last SQL statement called
     *
     */
    protected DBSql\StatementInterface $_sqlStatement;

    /**
     * Instantiate the object
     *
     */
    public function __construct(DBTable $table, PDO $databaseConnection)
    {
        $this->_objTable = $table;
        $this->_objDb    = $databaseConnection;
    }

    public function __get(string $field): mixed
    {
        return $this->get($field);
    }

    /**
     *
     */
    public function __set(string $field, mixed $value)
    {
        return $this->set($field, $value);
    }

    /**
     *
     */
    public function __isset(mixed $field): bool
    {
        $rtn = false;

        if ( $this->getDBTable()->fieldExists($field) )
        {
            $rtn = true;
        }
        elseif ( $field == self::VIRTUAL_PK_FIELD )
        {
            $rtn =  true;
        }

        return $rtn;
    }

    /**
     * Provide the object data to support json_encode
     *
     */
    public function jsonSerialize(): array
    {
        return $this->_objData;
    }

    /**
     * Fetches a value from the specified field
     *
     */
    public function get(string $field): mixed
    {
        $rtn = null;

        if ( $this->getDBTable()->fieldExists($field) )
        {
            if ( isset($this->_objData[$field]) )
            {
                $fldValue = $this->_objData[$field];

                $rtn = $this->getDBTable()->getField($field)->getPHPValue($fldValue);
            }

        }
        elseif ( $field == self::VIRTUAL_PK_FIELD )
        {
            $rtn = $this->getId();
        }

        return $rtn;
    }

    /**
     * Sets a value for a field
     *
     */
    public function set(string $field, mixed $value): static
    {
        if ( $this->getDBTable()->fieldExists($field) )
        {
            $this->_objData[$field] = $this->getDBTable()
                                           ->getField($field)
                                           ->getPHPValue($value);
        }
        elseif ( $field == self::VIRTUAL_PK_FIELD )
        {
            $this->setId($value);
        }

        return $this;
    }

    /**
     * Provide a list of fields that have been set in this object
     *
     */
    public function keys(): array
    {
        return array_keys($this->_objData);
    }

    /**
     * Provide the entire contents of the data array being stored here
     *
     */
    public function getData(): array
    {
        return $this->_objData;
    }

    /**
     * Resets the data in this object.
     *
     */
    public function clear(): static
    {
        $this->_objData = [];

        return $this;
    }

    /**
     * Provides the primary key field for this object
     *
     */
    public function getPrimaryKeyField(): ?string
    {
        $rtn = null;

        $keys = $this->getDBTable()->getPrimaryKeys();

        if ( count($keys) > 0 )
        {
            $rtn = $keys[0];
        }

        return $rtn;
    }

    /**
     * Set the primary key value for this object
     *
     */
    public function setId(int|string $value = null): static
    {
        $pkField = $this->getPrimaryKeyField();

        if ( ! is_null($pkField) )
        {
            $this->set($pkField, $value);
        }

        return $this;
    }

    /**
     * Provide the primary key value
     *
     */
    public function getId(): int|string|null
    {
        $rtn = null;

        $pkField = $this->getPrimaryKeyField();

        if ( ! is_null($pkField) )
        {
            $rtn = $this->get($pkField);
        }

        return $rtn;
    }

    /**
     * Saves the object out to the database.
     *
     * If the record has been loaded, an update will be attempted.  If not
     * loaded, then a new record will be added.
     *
     */
    public function save(): static
    {
        if ( $this->getLoadStatus() !== self::LOADED )
        {
            $this->insertRecord();
        }

        if ( $this->getLoadStatus() == self::LOADED )
        {
            $this->updateRecord();
        }

        $this->_objLoadStatus = self::LOADED;

        return $this;
    }

    /**
     * Pulls in the information from a single record based on the value/values
     * of the primary keys.
     *
     * For records with a single primary key, that value may be passed directly
     * into this method.  Otherwise, the fields in question must have had
     * their values already set.
     *
     */
    public function load(int|string $primaryKeyValue = null): static
    {
        $primaryKey = $this->getDBTable()->getPrimaryKeys()[0];

        if ( $primaryKeyValue === null )
        {
            $id = $this->getId();

            if ( is_null($id) )
            {
                throw new UnderflowException('No primary key value specified. Unable to load');
            }
        }
        else
        {
            $id = $primaryKeyValue;
        }


        $sql = $this->getSqlDriver()->select()
                    ->from( $this->getDBTable()->getFQN() )
                    ->where( $primaryKey . ' = ?', $id);

        $this->_sqlStatement = $sql;

        $statement = $this->getDb()->prepare($sql->output());
        $statement->execute($sql->getBindings());

        if ( $statement->rowCount() == 0 )
        {
            $this->clear();
            $this->_objLoadStatus = self::NOT_FOUND;

            return $this;
        }

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        foreach ( $result as $field => $value )
        {
            $this->set($field, $value);
        }

        $this->setId( $this->get($this->getPrimaryKeyField()) );
        $this->_objLoadStatus = self::LOADED;

        return $this;
    }

    /**
     * Allows the caller to specify exactly the criteria to be used to load
     * a record.
     *
     */
    public function loadFromWhere(string $where, mixed $binding = null): static
    {
        if ( $binding !== null and !is_array($binding) )
        {
            $binding = [$binding];
        }

        $sql = $this->getSqlDriver()
            ->select()
            ->from( $this->getDBTable()->getFQN() );

        $this->_sqlStatement = $sql;

        if ( $binding !== null )
        {
            $sql->where($where, $binding);
        }
        else
        {
            $sql->where($where);
        }

        $sql->limit(1);

        $statement = $this->getDb()->prepare($sql->output());
        $statement->execute($sql->getBindings());

        if ( $statement->rowCount() == 0 )
        {
            $this->clear();
            $this->_objLoadStatus = self::NOT_FOUND;

            return $this;
        }

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        foreach ( $result as $field => $value )
        {
            $this->set($field, $value);
        }

        $primaryKeyField = $this->getPrimaryKeyField();

        if ( ! is_null($primaryKeyField) )
        {
            $this->setId( $this->get($primaryKeyField) );
        }

        $this->_objLoadStatus = self::LOADED;

        return $this;
    }

    /**
     * Returns true if the load status has been marked as LOADED.  Otherwise,
     * returns false.
     *
     */
    public function isLoaded(): bool
    {
        if ( $this->_objLoadStatus === self::LOADED )
        {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the load status is not marked LOADED.  False if loaded.
     *
     */
    public function isNotLoaded(): bool
    {
        if ( $this->_objLoadStatus !== self::LOADED )
        {
            return true;
        }

        return false;
    }

    /**
     * Provide the load status of the object based on the constants of the
     * interface.
     *
     * @return integer
     */
    public function getLoadStatus(): int
    {
        return $this->_objLoadStatus;
    }

    /**
     * Set the load status manually
     *
     */
    public function setLoadStatus(int $loadStatus): static
    {
        switch ( $loadStatus )
        {
            case self::LOADED:
                $this->_objLoadStatus = self::LOADED;
                break;

            case self::NOT_LOADED:
                $this->_objLoadStatus = self::NOT_LOADED;
                break;

            case self::NOT_FOUND:
                $this->_objLoadStatus = self::NOT_FOUND;
                break;
        }

        return $this;
    }

    /**
     * Delete the loaded record from the database.
     * Does nothing if no record is loaded.
     *
     */
    public function delete(): static
    {
        // This record must be loaded before it can be deleted so that primary
        // key values are available
        if ( $this->getLoadStatus() !== self::LOADED )
        {
            return $this;
        }

        $primaryKeys = $this->getDBTable()->getPrimaryKeys();

        // Cannot delete a record without a primary key
        if ( empty($primaryKeys) )
        {
            return $this;
        }

        $delete = $this->getSqlDriver()->delete();
        $delete->table( $this->getDBTable()->getFQN() );

        foreach ( $primaryKeys as $primaryKey )
        {
            $pKeyFv = $this->getDBTable()
                ->getField($primaryKey)
                ->getSqlBoundValue($this->get($primaryKey));

            $marker = $pKeyFv->getValueMarker();
            $value  = $pKeyFv->getBoundValues()[$marker];

            $delete->where($primaryKey.' = ' . $marker );
            $delete->setBinding($marker, $value);
        }

        $statement = $this->getDb()->prepare($delete->output());
        $statement->execute($delete->getBindings());

        $this->_sqlStatement = $delete;

        $this->_objLoadStatus = self::NOT_LOADED;

        return $this;
    }

    /**
     * Provide the database connection used for this item
     *
     */
    public function getDb(): PDO
    {
        return $this->_objDb;
    }

    /**
     * Provide the database table to be used for this DB Item
     *
     */
    public function getDBTable(): DBTable
    {
        return $this->_objTable;
    }

    /**
     * Provide the SQL Driver based on the type of DBTable provided
     *
     * @return DBSql\DriverInterface
     */
    public function getSqlDriver(): DBSql\DriverInterface
    {
        return DBSql::PostgreSQL();

        // @TODO Once DBTable implements Mysql I can adjust which driver to use
        // if ( $this->_objTable instanceof DBTable\PostgreSQL )
        // {
        //     return DBSql::PostgreSQL();
        // }
        // throw new UnexpectedValueException('Unsupported SQL Engine Requested');
    }

    /**
     * Insert a new record from the data in this object
     *
     */
    protected function insertRecord(): void
    {
        $fetchKey    = false;
        $primaryKeys = $this->getDBTable()->getPrimaryKeys();
        $fields      = $this->getDBTable()->getFields();

        $insert = $this->getSqlDriver()->insert();
        $insert->table($this->getDBTable()->getFQN());

        foreach ( $fields as $field )
        {
            $fieldName = $field->getName();

            // Do not include a primary key field that doesn't have a value
            // assigned.  At this point, going to assume an auto incrementing
            // field.
            if ( in_array($fieldName, $primaryKeys) )
            {
                if ( empty($this->get($fieldName)) )
                {
                    continue;
                }
            }

            $tblFv   = $field->getSqlBoundValue($this->get($fieldName));
            $marker  = $tblFv->getValueMarker();
            $binding = $tblFv->getBoundValues();

            if ( $marker === null )
            {
                continue;
            }

            $sqlFv = new DBSql\Field\Value($fieldName);
            $sqlFv->setBoundValues( $binding )
                ->setValueMarker( $marker );

            $insert->addFieldValue($sqlFv);
        }

        // PostgreSQL needs to have a RETURNING statement added with the
        // primary keys for the table assigned to it.
        if ( $this->getDBTable() instanceof DBTable\PostgreSQL )
        {
            if ( !empty($primaryKeys) )
            {
                foreach ( $primaryKeys as $primaryKey )
                {
                    $insert->returning($primaryKey);
                    $fetchKey = true;
                }
            }
        }

        $statement = $this->getDb()->prepare($insert->output());
        $statement->execute($insert->getBindings());

        if ( $fetchKey )
        {
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            foreach ( $primaryKeys as $primaryKey )
            {
                $this->set($primaryKey, $result[$primaryKey]);
            }
        }

        $this->_sqlStatement = $insert;
    }

    /**
     * Update an existing record
     *
     */
    protected function updateRecord(): void
    {
        $primaryKeys = $this->getDBTable()->getPrimaryKeys();
        $fields      = $this->getDBTable()->getFields();

        // Cannot update a record without a primary key
        if ( empty($primaryKeys) )
        {
            return;
        }

        $update = $this->getSqlDriver()->update();
        $update->table($this->getDBTable()->getFQN());

        foreach ( $fields as $field )
        {
            $fieldName = $field->getName();

            // Primary keys do not get updated here.  They're criteria for what
            // will be updated.
            if ( in_array($fieldName, $primaryKeys) )
            {
                continue;
            }

            $tblFv = $field->getSqlBoundValue($this->get($fieldName));
            $marker  = $tblFv->getValueMarker();
            $binding = $tblFv->getBoundValues();

            if ( $marker === null )
            {
                continue;
            }

            $sqlFv = new DBSql\Field\Value($fieldName);
            $sqlFv->setBoundValues( $binding )
                ->setValueMarker( $marker );

            $update->addFieldValue($sqlFv);
        }

        foreach ( $primaryKeys as $primaryKey )
        {
            $pKeyFv = $this->getDBTable()
                          ->getField($primaryKey)
                          ->getSqlBoundValue( $this->get($primaryKey) );

            $fieldName = $pKeyFv->getFieldName();
            $marker    = $pKeyFv->getValueMarker();
            $value     = $pKeyFv->getBoundValues()[$marker];

            $update->where($fieldName . ' = ' . $marker);
            $update->setBinding($marker, $value);
        }

        $sql = $update->output();
        $sqlBinding = $update->getBindings();

        $statement = $this->getDb()->prepare($sql);
        $statement->execute($sqlBinding);

        $this->_sqlStatement = $update;
    }

    /* -- Support for SPL interfaces from this point down -- */

    /**
     * How many fields have been set
     *
     */
    public function count(): int
    {
        return count($this->_objData);
    }

    public function rewind(): static
    {
        reset($this->_objData);

        return $this;
    }

    public function current(): mixed
    {
        return current($this->_objData);
    }

    public function key(): string
    {
        return key($this->_objData);
    }

    public function next(): mixed
    {
        return next($this->_objData);
    }

    public function valid(): bool
    {
        return key($this->_objData) !== null;
    }
}
