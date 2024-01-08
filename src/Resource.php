<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Exceptions\InvalidUuidException;
use Olssonm\Swish\Util\Uuid;

class Resource implements \ArrayAccess, \Countable, \JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __set(string $key, mixed $value)
    {
        if (in_array($key, ['id', 'instructionUUID'])) {
            if (!Uuid::validate($value)) {
                throw new InvalidUuidException();
            }
        }

        $this->attributes[$key] = $value;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key];
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value): void
    {
        $this->{$key} = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key): void
    {
        unset($this->attributes[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key): mixed
    {
        return \array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
    }

    #[\ReturnTypeWillChange]
    public function count(): int
    {
        return \count($this->attributes);
    }

    /**
     * @return array<string, mixed>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
