<?php

namespace OrzOrc\DDnsUpdate\Exception;

class UpdaterFailException extends UpdaterException
{
    public function __construct(string $updater = '', string $action = '', \Throwable $previous = null)
    {
        $message = $updater . ' execution failed when ' . $action . ' because ' . $previous->getMessage();
        parent::__construct($message, $previous->getCode(), $previous);
    }
}
