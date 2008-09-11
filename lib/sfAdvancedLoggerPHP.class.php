<?php

/**
* Advanced logger PHP logger
*
* @version $Id$
* @copyright 2007 Romain Cambien
*/

/**
 * sfAdvancedLoggerPHP
 *
 * This logger just call the symfony logger when a PHP error is raised
 * 
 * Example :
 * 
 * loggers:
 *   sf_php:
 *     class: sfAdvancedLoggerPHP
 *     param:
 *       php_level: 4095
 *       exception: on
 *
 * Param :
 *  
 *  - php_level : The PHP error mask to log. (http://php.net/manual/en/ref.errorfunc.php#ini.error-reporting for more information)
 *  - exception : Log the uncaught exceptions
 * 
 * @package sfAdvancedLogger
 * @access public
 **/

class sfAdvancedLoggerPHP extends sfLogger {
	protected $php2sfCode = array(
		E_ERROR             => sfLogger::ERR    ,
		E_WARNING           => sfLogger::WARNING,
		E_PARSE             => sfLogger::EMERG  ,
		E_NOTICE            => sfLogger::NOTICE ,
		E_CORE_ERROR        => sfLogger::ERR    ,
		E_CORE_WARNING      => sfLogger::WARNING,
		E_COMPILE_ERROR     => sfLogger::ERR    ,
		E_COMPILE_WARNING   => sfLogger::WARNING,
		E_USER_ERROR        => sfLogger::ERR    ,
		E_USER_WARNING      => sfLogger::WARNING,
		E_USER_NOTICE       => sfLogger::NOTICE ,
		E_STRICT            => sfLogger::NOTICE ,
		E_RECOVERABLE_ERROR => sfLogger::EMERG  ,
	);

	/**
	 * Initializes the sub logger.
	 *
	 * @param  sfEventDispatcher $dispatcher  A sfEventDispatcher instance
	 * @param  array             $options     An array of options.
	 *
	 * @return Boolean      true, if initialization completes successfully, otherwise false.
	 */
	public function initialize (sfEventDispatcher $dispatcher, $options = array()) {
		/** PHP Errors */
		if (!empty($options['php_level'])) {
			set_error_handler(array($this, 'error_handler'), $options['php_level']);
		}
		/** Uncaught exception */
		if (!empty($options['exception'])) {
			set_exception_handler(array($this, 'exception_handler'));
			sfMixer::register('sfException:printStackTrace',  array($this, 'exception_handler'));
		}
		
		return parent::initialize($dispatcher, $options);
	}
	
	/**
	 * Log a message
	 *
	 * @param string $message   Message
	 * @param string $priority  Message priority
	 */
	public function doLog($message, $priority) {
		/** Nothing to do */
	}
	
	/**
	 * Shutdown the logger
	 *
	 */
	public function shutdown() {
		/** Nothing to do */
	}

	/**
	 * Custom error handler for PHP
	 *
	 * @param integer Error code
	 * @param string  Error message
	 * @param string  File that trigger the error
	 * @param integer Line of the file that trigger the error
	 */
	public function error_handler ($errno, $errstr, $errfile, $errline) {
		sfContext::getInstance()->getLogger()->log(
			"{PHP} $errstr at $errfile on line $errline",
			(isset($this->php2sfCode[$errno])?$this->php2sfCode[$errno]:SF_LOG_ALERT)
		);
	}
	
	/**
	 * Custom exception handler for PHP
	 *
	 * @param Exception The uncaughted exception
	 */
	public function exception_handler ($exception1, $exception2 = null) {
		$exception = (empty($exception2)?$exception1:$exception2);
		sfContext::getInstance()->getLogger()->log(
			sprintf(
				"{Exception} %s at %s on line %s",
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			),
			sfLogger::ALERT
		);
	}
}
?>