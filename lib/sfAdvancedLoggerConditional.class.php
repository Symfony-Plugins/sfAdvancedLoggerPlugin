<?php

/**
 * Advanced logger conditional logger
 *
 * @version $Id$
 * @copyright 2007 Romain Cambien
 */

/**
 * sfAdvancedLoggerConditional
 *
 * This class provide an aggregade logger with simple log conditions
 *
 * Example :
 *
 * loggers:
 *   sf_conditional:
 *     class: sfAdvancedLoggerConditional
 *     param:
 *       type: sfView
 *       min_level: 5 # SF_LOG_NOTICE and up
 *       loggers:
 *         sf_file:
 *           class: sfFileLogger
 *           param:
 *             file: %SF_LOG_DIR%/%SF_APP%_%SF_ENVIRONMENT%.view.log
 *         sf_email:
 *           class: sfAdvancedLoggerEmail
 *           param:
 *             to: Webmaster <webmaster@symfony-project.org>
 *             from: Logger <logger@symfony-project.org>
 *             subject : Error on sfView
 *         sf_db:
 *           class: sfAdvancedLoggerDB
 *           param:
 *             model: Logger
 *
 * Param :
 *   - level     : Only log this level of error [http://www.csh.rit.edu/~jon/projects/pear/Log/guide.html#log-levels Click for more information]
 *   - min_level : Log errors starting at this level
 *   - type      : Log error of this type ( '{TYPE} error message' ). You can use an array for multiple type
 *   - no_type   : Don't log error of this type. You can use an array for multiple type
 *   - loggers   : list of the logger(s) to call if the condition(s) match
 *
 * @package sfAdvancedLogger
 * @access public
 **/

class sfAdvancedLoggerConditional extends sfAggregateLogger {
	private $conditions = array();

	/**
	 * Initializes the sub logger.
	 *
	 * @param  sfEventDispatcher $dispatcher  A sfEventDispatcher instance
	 * @param  array             $options     An array of options.
	 *
	 * @return Boolean      true, if initialization completes successfully, otherwise false.
	 */
	public function initialize (sfEventDispatcher $dispatcher, $options = array()) {
		// convert string level to int
		if (isset($options['exact_level'])) {
			// convert to array
			$options['exact_level'] = (array)$options['exact_level'];
			foreach ((array)$options['exact_level'] as $pos => $level) {
				if (!is_int($level)) {
					$options['exact_level'][$pos] = constant('sfLogger::'.strtoupper($level));
				}
			}
		}

		$this->conditions = $options;

		return parent::initialize($dispatcher, $options);
	}

	/**
	 * Log a message
	 *
	 * @param string $message   Message
	 * @param string $priority  Message priority
	 */
	public function doLog($message, $priority) {
		/** Try to find log type */
		if (preg_match('/^\s*{([^}]+)}\s*.+?$/', $message, $matches)) {
			$type = $matches[1];
		}
		else {
			$type = '';
		}

		if (
			!(
				isset($this->conditions['exact_level'])
				&&
				!in_array($priority, $this->conditions['exact_level'])
			)
			&&
			!(
				isset($this->conditions['type'])
				&&
				!in_array($type, (array)$this->conditions['type'])
			)
			&&
			!(
				isset($this->conditions['no_type'])
				&&
				in_array($type, $this->conditions['no_type'])
			)
		) {
			parent::doLog($message, $priority);
		}
	}

	/**
	 * Shutdown the logger
	 *
	 */
	public function shutdown() {
		foreach ($this->loggers as $logger) {
			$logger->shutdown();
		}
	}
}
?>