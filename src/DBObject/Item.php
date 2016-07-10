<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

/**
 * Acts as a generic holding object that can be fed dynamic information
 *
 */
class Item implements \JsonSerializable, \Iterator
{
    /**
     * The data for this object in key/value pairs
     *
     * @var array
     */
    protected $_objData;

    /**
     * Instantiate the object
     *
     */
    public function __construct()
    {
        $this->_objData = array();
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
     * Provide the object data to support json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->_objData;
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
     * Provide a list of fields that have been set in this object
     *
     * @return string[]
     */
    public function keys()
    {
        return array_keys($this->_objData);
    }

    /**
     * Provide the entire contents of the data array being stored here
     *
     * @return array
     */
    public function getData()
    {
        return $this->_objData;
    }

    /**
     * Resets the data in this object.
     *
     * @return $this
     */
    public function clear()
    {
        $this->_objData = array();

        return $this;
    }

    /* -- Support for SPL interfaces from this point down -- */

    /**
     * How many fields have been set
     *
     * @return int
     */
    public function count()
    {
        return count($this->_objData);
    }

    /**
     *
     * @return $this
     */
    public function rewind()
    {
        reset($this->_objData);

        return $this;
    }

    /**
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->_objData);
    }

    /**
     *
     * @return string
     */
    public function key()
    {
        return key($this->_objData);
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->_objData);
    }

    /**
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->_objData) !== null;
    }
}
