<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol_Libs
 * @version       2.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use PDO;
use Metrol\DBObject;

/**
 * Handles generating and storing a set of database records as object
 *
 */
class Set implements \Iterator
{
    /**
     * The record data for this object in key/value pairs
     *
     * @var array
     */
    protected $_objDataSet;

    /**
     * The database connection that will run the query
     *
     * @var PDO
     */
    protected $_objDb;

    /**
     * The object type that will be making up this set.
     *
     * @var DBObject\Item
     */
    protected $_objItem;

    /**
     * Instantiate the object and store the sample DB Item as a reference
     *
     * @param DBObject\Item $item
     * @param PDO           $db
     */
    public function __construct(DBObject\Item $item, PDO $db )
    {
        $this->_objDb      = $db;
        $this->_objItem    = $item;
        $this->_objDataSet = array();
    }

    public function __isset($key)
    {
        return isset($this->_objDataSet[ $key]);
    }

    public function count()
    {
        return count($this->_objDataSet);
    }

    public function rewind()
    {
        return reset($this->_objDataSet);
    }

    public function current()
    {
        return current($this->_objDataSet);
    }

    public function key()
    {
        return key($this->_objDataSet);
    }

    public function next()
    {
        return next($this->_objDataSet);
    }

    public function valid()
    {
        return key($this->_objDataSet) !== null;
    }
}
