<?php

/**
* Advanced logger email logger
*
* @version $Id$
* @copyright 2007 Romain Cambien
*/

/**
 * sfAdvancedLoggerEmail
 *
 * This logger allow to send error by emails
 * 
 * Example :
 * 
 * loggers :
 *   sf_email:
 *     class: sfAdvancedLoggerEmail
 *     param:
 *       to: Webmaster <webmaster@symfony-project.org>
 *       from: Logger <logger@symfony-project.org>
 *       subject : Error on sfView
 * 
 * Param :
 * 
 *   - to      : Destination Email address. You can use an array for multiple destinations
 *   - from    : Sender email address
 *   - subject : Email subject
 * 
 * @package sfAdvancedLogger
 * @access public
 **/

class sfAdvancedLoggerEmail extends sfLogger {
	private $to;
	private $from;
	private $subject;
	private $body;

	/**
	 * Initializes the sub logger.
	 *
	 * @param  sfEventDispatcher $dispatcher  A sfEventDispatcher instance
	 * @param  array             $options     An array of options.
	 *
	 * @return Boolean      true, if initialization completes successfully, otherwise false.
	 */
	public function initialize (sfEventDispatcher $dispatcher, $options = array()) {
		if (!class_exists('Swift')) {
			throw new sfException('Missing required Swift class');
		}
		
		if (empty($options['to'])) {
			throw new sfException('Missing destination address for email logger');
		}
		$this->to   = (array)$options['to'];
		$this->from = (
			empty($options['from'])
			?'Symfony Logger <logger>'
			:$options['from']
		);
		$this->subject = (
			empty($options['subject'])
			?'Symfony logger report'
			:$options['subject']
		);
		
		return parent::initialize($dispatcher, $options);
	}
	
	/**
	 * Log a message
	 *
	 * @param string $message   Message
	 * @param string $priority  Message priority
	 */
	public function doLog($message, $priority) {
		$this->body .= $this->getPriorityName($priority)." - $message\n";
	}
	
	/**
	 * Shutdown the logger
	 *
	 */
	public function shutdown() {
		if (!empty($this->body)) {
			$mail = new Swift(new Swift_Connection_NativeMail());
			$recipients = new Swift_RecipientList();
			foreach($this->to as $to) {
				$recipients->add($to);
			}
			$mail->send(
				new Swift_Message(
					date('Y-m-d H:i:s')." : ".$this->subject,
					$this->body
				),
				$recipients,
				new Swift_Address($this->from)
			);
		}
	}
}
?>