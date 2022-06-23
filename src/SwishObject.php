<?php

namespace Olssonm\Swish;

class SwishObject implements \ArrayAccess, \Countable, \JsonSerializable
{
    protected array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function __set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function __get($key)
    {
        return $this->values[$key];
    }

    public function __isset($key)
    {
        return isset($this->values[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->{$key} = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return \array_key_exists($key, $this->values);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->values[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return \array_key_exists($key, $this->values) ? $this->values[$key] : null;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->values);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return $this->values;
    }
}
