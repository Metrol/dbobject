<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @package       Metrol/DBObject
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBObject;

/**
 * Define the methods that must be supported for items
 *
 */
interface ItemInterface
{
    /**
     * Provide the value of a specific field.
     *
     */
    public function get(string $field): mixed;

    /**
     * Set the value of a field
     *
     */
    public function set(string $field, mixed $value): static;

    /**
     * Provide a list of fields that have been set in this object
     *
     */
    public function keys(): array;

    /**
     * Provide the entire contents of the data array being stored here
     *
     */
    public function getData(): array;

    /**
     * Resets the data in this object.
     *
     */
    public function clear(): static;

    /**
     * How many fields have been set
     *
     */
    public function count(): int;
}
