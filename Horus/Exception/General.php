<?php
namespace Horus\Exception;

use \Horus\Http\Response;

/**
 * Stop Exception
 *
 * This is a general exception thrown from the Horus app
 *
 * @package Horus
 * @author  Michael Darko
 * @since   2.0.0
 */
class General extends \Exception {
	protected $response;

	public function __construct($throwable) {
		$this->response = new Response;
		$this->handleException($throwable);
	}

	/**
	 * Handles an exception
	 */
	protected function handleException($throwable) {
		$this->response->throwErr($throwable);
	}
}
