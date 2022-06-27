<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Exceptions\InvalidUuidException;
use Olssonm\Swish\Util\Uuid;

class Resource implements \ArrayAccess, \Countable, \JsonSerializable
{
    protected array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __set($key, $value)
    {
        if (in_array($key, ['id', 'instructionUUID'])) {
            if (!Uuid::validate($value)) {
                throw new InvalidUuidException;
            }
        }

        $this->attributes[$key] = $value;
    }

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->{$key} = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return \array_key_exists($key, $this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return \array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return $this->attributes;
    }
}
