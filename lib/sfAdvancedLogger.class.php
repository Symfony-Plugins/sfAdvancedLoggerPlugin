<?php

/**
* Advanced logger for Symfony
*
* @version $Id$
* @copyright 2007 Romain Cambien
*/

/**
* Configuration
*
* logging.yml
* all:
*   loggers:
*     sf_advanced_logger:
*        class: sfAdvancedLogger
*        param:
*          php_level: 4095 # E_ALL | E_STRIC
*          exception: on # log uncaught exceptions
*          email:
*            - level: 0 # SF_LOG_EMERG
*              to: alertes@domain.net
*              subject: Only emerg errors
*            - min_level: 5 # SF_LOG_NOTICE and up
*              to: dev@domain.net
*              subject: From notice to emerg errors
*            - type: [PHP, Exception] # Only PHP and uncaught exception
*              to: default@domain.net
*              subject: errors on PHP errors and uncaught exceptions
*            - no_type: [PHP] # none of PHP errors
*              to: default@domain.net
*              subject: errors that aren't PHP errors
*            - min_level: 5 # SF_LOG_NOTICE and up
*              type: sfView
*              to:
*                - dev@domain.net
*                - graph@domain.net
*              subject: notice to emerg errors raised by sfView
*          file:
*            - level: 0 # SF_LOG_EMERG
*              file: %SF_LOG_DIR%/emerg.log
*            - min_level: 5 # SF_LOG_NOTICE and up
*              file: %SF_LOG_DIR%/notice_and_up.log
*            - type: [PHP, Exception] # Only PHP and uncaught exception
*              file: %SF_LOG_DIR%/php.log
*            - min_level: 5 # SF_LOG_NOTICE and up
*              type: View
*              file: %SF_LOG_DIR%/view.log
*/

/**
 * sfAdvancedLogger
 *
 * @package sfAdvancedLogger
 * @access public
 **/
class sfAdvancedLogger {
	protected $php2sfCode = array(
		E_ERROR             => SF_LOG_ERR    ,
		E_WARNING           => SF_LOG_WARNING,
		E_PARSE             => SF_LOG_EMERG  ,
		E_NOTICE            => SF_LOG_NOTICE ,
		E_CORE_ERROR        => SF_LOG_ERR    ,
		E_CORE_WARNING      => SF_LOG_WARNING,
		E_COMPILE_ERROR     => SF_LOG_ERR    ,
		E_COMPILE_WARNING   => SF_LOG_WARNING,
		E_USER_ERROR        => SF_LOG_ERR    ,
		E_USER_WARNING      => SF_LOG_WARNING,
		E_USER_NOTICE       => SF_LOG_NOTICE ,
		E_STRICT            => SF_LOG_NOTICE ,
		E_RECOVERABLE_ERROR => SF_LOG_EMERG  ,
	);
	
	private $logs  = array();
	
	/**
	 * Initializes the logger.
	 *
	 * @param array Logger options
	 */
	public function initialize($options = array()) {
		/** PHP logger */
		if (!empty($options['php_level'])) {
			set_error_handler(array($this, 'error_handler'), $options['php_level']);
		}
		/** Uncaught exception */
		if (!empty($options['exception'])) {
			set_exception_handler(array($this, 'exception_handler'));
			sfMixer::register('sfException:printStackTrace',  array($this, 'exception_handler'));
		}
		/** Email alert */
		if (!empty($options['email'])) {
			foreach($options['email'] as $email_log) {
				if (isset($email_log['to'])) {
					$email_log['log_type'] = 'email';
					$email_log['to'] = (array)$email_log['to'];
					$email_log['from'] = (
						empty($email_log['from'])
						?'logger'
						:$email_log['from']
					);
					$email_log['subject'] = (
						empty($email_log['subject'])
						?'Symfony logger'
						:$email_log['subject']
					);
					$email_log['messages'] = array();
					
					if (isset($email_log['type'])){
						$email_log['type'] = (array)$email_log['type'];
					}
					$this->logs[] = $email_log;
				}
			}
		}
		/** File logger */
		if (!empty($options['file'])) {
			foreach($options['file'] as $file_log) {
				if (isset($file_log['file'])) {
					$file_log['log_type'] = 'file';
					
					/** Check directory and file */
					$dir = dirname($file_log['file']);
					if (!is_dir($dir)) {
						mkdir($dir, 0777, 1);
					}
					if (!is_writable($dir) || (file_exists($file_log['file']) && !is_writable($file_log['file']))) {
					  throw new sfFileException(sprintf('Unable to open the log file "%s" for writing', $file_log['file']));
					}

					if (isset($file_log['type'])) {
						$file_log['type'] = (array)$file_log['type'];
					}
					
					$this->logs[] = $file_log;
				}
			}
		}
	}
	
	/**
	 * Logs a message.
	 *
	 * @param string Message
	 * @param string Message priority
	 * @param string Message priority name
	 */
	public function log($message, $priority, $priorityName) {
		/** Try to find log type */
		if (preg_match('/^\s*{([^}]+)}\s*.+?$/', $message, $matches)) {
			$type = $matches[1];
		}
		else {
			$type = '';
		}
		
		foreach($this->logs as &$log) {
			if (
				!(
					isset($log['level'])
					&&
					$log['level'] != $priority
				)
				&&
			    !(
					isset($log['min_level'])
					&&
					$log['min_level'] < $priority
				)
				&&
			    !(
					isset($log['type'])
					&&
					!in_array($type, $log['type'])
				)
				&&
				!(
					isset($log['no_type'])
					&&
					in_array($type, $log['no_type'])
				)
			) {
				switch ($log['log_type']) {
					case 'email':
						$log['messages'][] = "$priorityName - $message";
						break;
					case 'file':
						file_put_contents($log['file'], "$priorityName - $message\n", FILE_APPEND | LOCK_EX);
						break;
				}
			}
		}
	}
	
	/**
	 * Shutdown function
	 */
	public function shutdown() {
		foreach($this->logs as $log) {
			switch ($log['log_type']) {
				case 'email':
					if (!empty($log['messages'])) {
						$mail = new sfMail();
						$mail->initialize();
						$mail->setMailer('sendmail');
						$mail->setCharset('utf-8');
						$mail->setFrom($log['from']);
						foreach($log['to'] as $to) {
							$mail->addAddress($to);
						}
						$mail->setSubject(date('Y-m-d H:i:s')." : ".$log['subject']);
						$mail->setBody(implode("\n", $log['messages']));
						$mail->send();
					}
					break;
			}
		}
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
		sfLogger::getInstance()->log(
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
		sfLogger::getInstance()->log(
			"{Exception} ".
			$exception->getMessage().
			" at ".
			$exception->getFile().
			" on line ".
			$exception->getLine(),
			SF_LOG_ALERT
		);
	}
}