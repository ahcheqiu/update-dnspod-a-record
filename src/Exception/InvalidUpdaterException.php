<?php

namespace OrzOrc\DDnsUpdate\Exception;

class InvalidUpdaterException extends UpdaterException
{
    public function __construct(string $className, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($className . ' is not a valid updater', $code, $previous);
    }
}
