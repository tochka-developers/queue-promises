<?php

namespace Tochka\Promises\Exceptions;

class NonSerializableException extends \RuntimeException
{
    public function __construct(\Throwable $throwable)
    {
        parent::__construct(
            sprintf(
                'Exception [%s]: %s',
                get_class($throwable),
                $throwable->getMessage(),
            ),
            (int) $throwable->getCode(),
        );
    }
}
