<?php

namespace Drupal\bitly_links\Exception;

use Exception;
use Throwable;

/**
 * Defines an exception thrown when content type doesn't have a bitly links field enabled.
 *
 * Class UnsupportedContentTypeException
 *
 * @package Drupal\bitly_links\Exception
 */
class UnsupportedContentTypeException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}