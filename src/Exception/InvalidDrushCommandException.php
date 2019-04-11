<?php

namespace Drupal\bitly_links\Exception;

use Exception;
use Throwable;

/**
 * Defines an exception thrown when an invalid bitly links drush command option is submitted.
 *
 * Class DrushCommandException
 *
 * @package Drupal\bitly_links\Exception
 */
class InvalidDrushCommandException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}