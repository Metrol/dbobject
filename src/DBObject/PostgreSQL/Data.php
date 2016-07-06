<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       2.0
 * @package       Sourcing
 * @copyright (c) 2016, Sourcing
 */

namespace Metrol\DBObject\PostgreSQL;

/**
 * Handles the basic setting and getting of field values for a PostgreSQL
 * item.
 *
 */
class Data
{
    /**
     * The record data for this object in key/value pairs
     *
     * @var array
     */
    protected $_objData = array();

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
        $this->_objData[$field] = $value;

        return $this;
    }

    /**
     * Provide the list of fields that have been assigned some value in this
     * object.  For a complete list of fields use DBTable's methods instead
     *
     * @return string{}
     */
    public function keys()
    {
        return array_keys($this->_objData);
    }
}
