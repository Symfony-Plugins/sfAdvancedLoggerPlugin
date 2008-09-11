<?php

/**
* Advanced logger interface for sub logger
*
* @version $Id$
* @copyright 2007 Romain Cambien
*/

/**
 * sfAdvancedLoggerInterface
 *
 * @package sfAdvancedLogger
 * @access public
 **/
interface sfAdvancedLoggerInterface {
	/**
	 * Initializes the sub logger.
	 *
	 * @param array Logger options
	 */
	public function initialize($options = array());
	
	/**
	 * Log a message
	 *
	 * @param string  $message The message to log
	 * @param integer $priority The priority code
	 * @param string  $priorityName The priority name
	 */
	public function log($message, $priority, $priorityName);
	
	/**
	 * Shutdown the logger
	 *
	 */
	public function shutdown();
}
?>