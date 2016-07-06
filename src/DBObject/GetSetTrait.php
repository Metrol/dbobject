<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\DBObject;

/**
 * Handles the basic setting and getting of field values for a DBObject
 *
 *
 */
trait GetSetTrait
{
    /**
     * The record data for this object in key/value pairs
     *
     * @var array
     */
    protected $_objData = array();

    /**
     * Keep track of the primary key value
     *
     * @var mixed
     */
    protected $_objPrimaryKeyValue = null;

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
}
