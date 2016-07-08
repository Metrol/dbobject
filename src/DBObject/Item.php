<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use Metrol\DBObject;
use Metrol\DBTable;
use Metrol\DBSql;
use PDO;

/**
 * Maps itself to a database record for the purposes of creating, updating,
 * and deleting the information.
 *
 */
class Item implements DBObject
{
    /**
     * The database table that this item will be acting as a front end for
     *
     * @var DBTable
     */
    protected $_objTable;

    /**
     * The database connection used to talk to this object
     *
     * @var PDO
     */
    protected $_objDb;

    /**
     * The record data for this object in key/value pairs
     *
     * @var array
     */
    protected $_objData;

    /**
     * Tracks the load status for the item
     *
     * @var integer
     */
    protected $_objLoadStatus;

    /**
     * Instantiate the object
     *
     * @param DBTable $table
     * @param PDO     $databaseConnection
     *
     */
    public function __construct(DBTable $table, PDO $databaseConnection)
    {
        $this->_objTable      = $table;
        $this->_objDb         = $databaseConnection;
        $this->_objData       = array();
        $this->_objLoadStatus = self::NOT_LOADED;
    }

    /**
     *
     * @param string $field
     *
     * @return mixed|null
     */
    public function __get($field)
    {
        return $this->get($field);
    }

    /**
     * @param string $field
     * @param mixed $value
     *
     * @return $this
     */
    public function __set($field, $value)
    {
        return $this->set($field, $value);
    }

    /**
     * @param string $field
     *
     * @return boolean
     */
    public function __isset($field)
    {
        $rtn = false;

        if ( isset($this->_objData[$field]) )
        {
            $rtn = true;
        }

        return $rtn;
    }

    /**
     *
     * @param string $field
     *
     * @return mixed|null
     */
    public function get($field)
    {
        $rtn = null;

        if ( !$this->_objTable->fieldExists($field) )
        {
            return null;
        }

        if ( isset($this->_objData[$field]) )
        {
            $rtn = $this->_objData[$field];
        }

        return $rtn;
    }

    /**
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($field, $value)
    {
        if ( !$this->_objTable->fieldExists($field) )
        {
            return $this;
        }

        $fieldObj = $this->getDBTable()->getField($field);

        $this->_objData[$field] = $fieldObj->getPHPValue($value);

        return $this;
    }

    /**
     * Provides the primary key field for this object
     *
     * @return string|null
     */
    public function getPrimaryKeyField()
    {
        $rtn = null;

        $keys = $this->_objTable->getPrimaryKeys();

        if ( count($keys) > 0 )
        {
            $rtn = $keys[0];
        }

        return $rtn;
    }

    /**
     * Set the primary key value for this object
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setId($value)
    {
        $pkField = $this->getPrimaryKeyField();

        if ( $pkField !== null )
        {
            $this->set($pkField, $value);
        }

        return $this;
    }

    /**
     * Provide the primary key value
     *
     * @return mixed|null
     */
    public function getId()
    {
        $rtn = null;

        $pkField = $this->getPrimaryKeyField();

        if ( $pkField !== null )
        {
            $rtn = $this->get($pkField);
        }

        return $rtn;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ( $this->getLoadStatus() == self::NOT_LOADED )
        {
            $this->insertRecord();
        }

        if ( $this->getLoadStatus() == self::LOADED )
        {
            $this->updateRecord();
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function load($primaryKeyValue = null)
    {
        $db = $this->_objDb;

        $tableName  = $this->_objTable->getName();
        $primaryKey = $this->_objTable->getPrimaryKeys()[0];

        if ( $primaryKeyValue == null )
        {
            $id = $this->getId();
        }
        else
        {
            $id = $primaryKeyValue;
        }

        if ( $id == null )
        {
            throw new \UnderflowException('No primary key value specified. Unable to load');
        }

        $sql = $this->getSqlDriver()->select()
                    ->from( $tableName )
                    ->where( $primaryKey . ' = ?', $id);

        $statement = $db->prepare($sql->output());
        $statement->execute($sql->getBindings());

        if ( $statement->rowCount() == 0 )
        {
            $this->_objData = [];
            $this->_objPrimaryKeyValue = null;
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
     * @inheritdoc
     */
    public function loadFromWhere($where, $binding = null)
    {
        if ( $binding !== null and !is_array($binding) )
        {
            $binding = [$binding];
        }

        $db = $this->_objDb;

        $tableName  = $this->_objTable->getName();

        $sql = $this->getSqlDriver()
            ->select()
            ->from( $tableName );

        if ( !empty($binding) )
        {
            $sql->where($where, $binding);
        }
        else
        {
            $sql->where($where);
        }

        $sql->limit(1);

        $statement = $db->prepare($sql->output());
        $statement->execute($sql->getBindings());

        if ( $statement->rowCount() == 0 )
        {
            $this->_objData = [];
            $this->_objPrimaryKeyValue = null;
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
     * Provide the load status of the object based on the constants of the
     * interface.
     *
     * @return integer
     */
    public function getLoadStatus()
    {
        return $this->_objLoadStatus;
    }

    /**
     * Delete the loaded record from the database.
     * Does nothing if no record is loaded.
     *
     * @return $this
     */
    public function delete()
    {
        if ( $this->getLoadStatus() !== self::LOADED )
        {
            return $this;
        }

        return $this;
    }

    /**
     * Provide the database connection used for this item
     *
     * @return PDO
     */
    public function getDb()
    {
        return $this->_objDb;
    }

    /**
     * Provide the database table to be used for this DB Item
     *
     * @return DBTable
     */
    public function getDBTable()
    {
        return $this->_objTable;
    }

    /**
     * Provide the SQL Driver based on the type of DBTable provided
     *
     * @return DBSql\DriverInterface
     *
     * @throws \UnexpectedValueException  When no engine is found
     */
    public function getSqlDriver()
    {
        if ( $this->_objTable instanceof DBTable\PostgreSQL )
        {
            return DBSql::PostgreSQL();
        }

        throw new \UnexpectedValueException('Unsupported SQL Engine Requested');
    }

    /**
     * Insert a new record from the data in this object
     *
     */
    protected function insertRecord()
    {
        $fetchKey    = false;
        $primaryKeys = $this->getDBTable()->getPrimaryKeys();
        $fields      = $this->getDBTable()->getFields();

        $insert = $this->getSqlDriver()->insert();
        $insert->table($this->getDBTable()->getFQN());
        $insData = array();

        foreach ( $fields as $field )
        {
            $fieldName = $field->getName();

            if ( $this->__isset($fieldName) )
            {
                $value = $field->getSqlBoundValue($this->get($fieldName));
                $insData[ $fieldName ] = $value;
            }
        }

        // Nothing to insert?  Time to leave.
        if ( empty($insData) )
        {
            return;
        }

        $insert->fieldValues($insData);

        if ( $this->_objTable instanceof DBTable\PostgreSQL )
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
    }

    /**
     * Update an existing record
     *
     */
    protected function updateRecord()
    {
        $primaryKeys = $this->getDBTable()->getPrimaryKeys();

        // Cannot update a record without a primary key
        if ( empty($primaryKeys) )
        {
            return;
        }

        $update = $this->getSqlDriver()->update();

        $update->table($this->getDBTable()->getFQN());

        foreach ( $this->_objData as $fieldName => $value )
        {
            // Primary keys do not be updated here.  They're criteria for what
            // will be updated.
            if ( in_array($fieldName, $primaryKeys) )
            {
                continue;
            }

            // The field must exist in the table
            if ( !$this->getDBTable()->fieldExists($fieldName) )
            {
                continue;
            }

            $bindValue = $this->getDBTable()
                ->getField($fieldName)
                ->getSqlBoundValue($value);

            $update->fieldValue($fieldName, '?', $bindValue);
        }

        foreach ( $primaryKeys as $primaryKey )
        {
            $bindValue = $this->getDBTable()->getField($primaryKey)
                ->getSqlBoundValue( $this->get($primaryKey));

            $update->where($primaryKey . ' = ?', $bindValue);
        }

        $statement = $this->_objDb->prepare($update->output());
        $statement->execute($update->getBindings());
    }
}
