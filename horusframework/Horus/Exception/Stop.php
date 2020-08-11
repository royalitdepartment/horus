<?php
namespace Horus\Exception;

/**
 * Stop Exception
 *
 * This Exception is thrown when the Horus application needs to abort
 * processing and return control flow to the outer PHP script.
 *
 * @package Horus
 * @author  Michael Darko
 * @since   1.0.0
 */
class Stop extends \Exception
{
}
