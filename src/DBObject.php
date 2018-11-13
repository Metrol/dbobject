<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol;

use Metrol;
use PDO;

/**
 * Maps itself to a database record for the purposes of creating, updating,
 * and deleting the information.
 *
 */
class DBObject extends Metrol\DBObject\Item
    implements Metrol\DBObject\CrudInterface
{
    /**
     * The virtual primary key field that can be used in place of having to
     * know the actual field name for most uses.
     *
     * @const string
     */
    const VIRTUAL_PK_FIELD = 'id';

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
     * Tracks the load status for the item
     *
     * @var integer
     */
    protected $_objLoadStatus;

    /**
     * The last SQL statement called
     *
     * @var DBSql\StatementInterface
     */
    protected $_sqlStatement;

    /**
     * Instantiate the object
     *
     * @param DBTable $table
     * @param PDO     $databaseConnection
     *
     */
    public function __construct(DBTable $table, PDO $databaseConnection)
    {
        parent::__construct();

        $this->_objTable      = $table;
        $this->_objDb         = $databaseConnection;
        $this->_objLoadStatus = self::NOT_LOADED;
    }

    /**
     * @param string $field
     *
     * @return boolean
     */
    public function __isset($field)
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
     * Fetches a value from the specified field
     *
     * @param string $field
     *
     * @return mixed|null
     */
    public function get($field)
    {
        $rtn = null;

        if ( $this->getDBTable()->fieldExists($field) )
        {
            $rtn = parent::get($field);
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
     * @param string $field
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($field, $value)
    {
        if ( $this->getDBTable()->fieldExists($field) )
        {
            parent::set($field, $this->getDBTable()->getField($field)
                                     ->getPHPValue($value));
        }
        elseif ( $field == self::VIRTUAL_PK_FIELD )
        {
            $this->setId($value);
        }

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
     * Saves the object out to the database.
     *
     * If the record has been loaded, an update will be attempted.  If not
     * loaded, then a new record will be added.
     *
     * @return $this
     */
    public function save()
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
     * @param mixed $primaryKeyValue
     *
     * @return $this
     *
     * @throws \UnderflowException When no primary keys are specified
     */
    public function load($primaryKeyValue = null)
    {
        $primaryKey = $this->getDBTable()->getPrimaryKeys()[0];

        if ( $primaryKeyValue === null )
        {
            $id = $this->getId();
        }
        else
        {
            $id = $primaryKeyValue;
        }

        if ( $id === null )
        {
            throw new \UnderflowException('No primary key value specified. Unable to load');
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
     * @param string $where The WHERE clause to be passed to the SQL engine
     * @param mixed|array $binding Values to bind to the WHERE clause
     *
     * @return $this
     */
    public function loadFromWhere($where, $binding = null)
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

        $this->setId( $this->get($this->getPrimaryKeyField()) );

        $this->_objLoadStatus = self::LOADED;

        return $this;
    }

    /**
     * Returns true if the load status has been marked as LOADED.  Otherwise,
     * returns false.
     *
     * @return boolean
     */
    public function isLoaded()
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
     * @return boolean
     */
    public function isNotLoaded()
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
    public function getLoadStatus()
    {
        return $this->_objLoadStatus;
    }

    /**
     * Set the load status manually
     *
     * @param integer $loadStatus
     *
     * @return $this
     */
    public function setLoadStatus($loadStatus)
    {
        switch ( intval($loadStatus) )
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
     * @return $this
     */
    public function delete()
    {
        $primaryKeys = $this->getDBTable()->getPrimaryKeys();

        // Cannot delete a record without a primary key
        if ( empty($primaryKeys) )
        {
            return $this;
        }

        // This record must be loaded before it can be deleted so that primary
        // key values are available
        if ( $this->getLoadStatus() !== self::LOADED )
        {
            return $this;
        }

        $delete = $this->getSqlDriver()->delete();
        $delete->table( $this->getDBTable()->getFQN() );

        foreach ( $primaryKeys as $primaryKey )
        {
            $pkVal = $this->getDBTable()
                ->getField($primaryKey)
                ->getSqlBoundValue($this->get($primaryKey));

            $delete->where($primaryKey.' = ?', $pkVal );
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
     * Fetch the last SQL statement that this object ran
     *
     * @return DBSql\StatementInterface
     */
    public function getLastSqlStatement()
    {
        return $this->_sqlStatement;
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

        foreach ( $this as $fieldName => $value )
        {
            // Primary keys do not get updated here.  They're criteria for what
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

        $statement = $this->getDb()->prepare($update->output());
        $statement->execute($update->getBindings());

        $this->_sqlStatement = $update;
    }
}
