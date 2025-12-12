<?php

namespace Olssonm\Swish;

/**
 * @property string $data
 * @property string $contentType
 * @property string $format
 */
class QRResult extends Resource
{
    public function __toString()
    {
        return $this->data;
    }

    public function toBase64()
    {
        return sprintf('data:%s;base64,%s', $this->contentType, base64_encode($this->data));
    }
}
