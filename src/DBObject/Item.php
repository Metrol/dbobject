<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

use JsonSerializable;
use Iterator;

/**
 * Acts as a generic holding object that can be fed dynamic information
 *
 */
class Item implements ItemInterface, JsonSerializable, Iterator
{
    /**
     * The data for this object in key/value pairs
     *
     */
    protected array $_objData = [];

    /**
     * Instantiate the object
     *
     */
    public function __construct()
    {
    }

    /**
     *
     */
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
     */
    public function __isset(mixed $field): bool
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
     */
    public function jsonSerialize(): array
    {
        return $this->_objData;
    }

    public function get(string $field): mixed
    {
        $rtn = null;

        if ( isset($this->_objData[$field]) )
        {
            $rtn = $this->_objData[$field];
        }

        return $rtn;
    }

    public function set(string $field, mixed $value): static
    {
        $this->_objData[$field] = $value;

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

    /* -- Support for SPL interfaces from this point down -- */

    /**
     * How many fields have been set
     *
     */
    public function count(): int
    {
        return count($this->_objData);
    }

    public function rewind(): void
    {
        reset($this->_objData);
    }

    public function current(): mixed
    {
        return current($this->_objData);
    }

    public function key(): string
    {
        return key($this->_objData);
    }

    public function next(): void
    {
        next($this->_objData);
    }

    public function valid(): bool
    {
        return key($this->_objData) !== null;
    }
}
