<?php

namespace Olssonm\Swish;

/**
 * @property string $token
 * @property string $format
 * @property int $size
 * @property int $border
 * @property bool $transparent
 */
class QR extends Resource
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->format = $this->format ?? 'png';
        $this->size = $this->size ?? 300;
        $this->transparent = $this->transparent ?? true;
    }
}
