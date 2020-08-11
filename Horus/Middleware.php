<?php
namespace Horus;

/**
 * Middleware
 *
 * @package Horus
 * @author  Michael Darko
 * @since   1.5.0
 */
abstract class Middleware
{
    /**
     * @var \Horus\Horus Reference to the primary application instance
     */
    protected $app;

    /**
     * @var mixed Reference to the next downstream middleware
     */
    protected $next;

    /**
     * Set application
     *
     * This method injects the primary Horus application instance into
     * this middleware.
     *
     * @param  \Horus\Horus $application
     */
    final public function setApplication($application)
    {
        $this->app = $application;
    }

    /**
     * Get application
     *
     * This method retrieves the application previously injected
     * into this middleware.
     *
     * @return \Horus\Horus
     */
    final public function getApplication()
    {
        return $this->app;
    }

    /**
     * Set next middleware
     *
     * This method injects the next downstream middleware into
     * this middleware so that it may optionally be called
     * when appropriate.
     *
     * @param \Horus|\Horus\Middleware
     */
    final public function setNextMiddleware($nextMiddleware)
    {
        $this->next = $nextMiddleware;
    }

    /**
     * Get next middleware
     *
     * This method retrieves the next downstream middleware
     * previously injected into this middleware.
     *
     * @return \Horus\Horus|\Horus\Middleware
     */
    final public function getNextMiddleware()
    {
        return $this->next;
    }

    /**
     * Call
     *
     * Perform actions specific to this middleware and optionally
     * call the next downstream middleware.
     */
    abstract public function call();
}
