<?php

/**
* Advanced logger DB logger
*
* @version $Id$
* @copyright 2007 Romain Cambien
*/

/**
 * sfAdvancedLoggerDB
 *
 * This logger allow to store logs in database.
 * 
 * Example : 
 * 
 * loggers:
 *   sf_db:
 *     class: sfAdvancedLoggerDB
 *     param:
 *       model: Logger
 * 
 * Param :
 *   - model: the model name, mandatory fields are : message, priority, priority_name
 * 
 * Schema example :
 * 
 * propel:
 *   logger:
 *       id:
 *       created_at:
 *       priority:      { type: integer,      required: true }
 *       priority_name: { type: varchar(50),  required: true }
 *       message:       { type: varchar(250), required: true }
 *  
 * @package sfAdvancedLogger
 * @access public
 **/

class sfAdvancedLoggerDB extends sfLogger {
	private $model;
	
	/**
	 * Initializes the sub logger.
	 *
	 * @param  sfEventDispatcher $dispatcher  A sfEventDispatcher instance
	 * @param  array             $options     An array of options.
	 *
	 * @return Boolean      true, if initialization completes successfully, otherwise false.
	 */
	public function initialize (sfEventDispatcher $dispatcher, $options = array()) {
		if(empty($options['model']) || !class_exists($options['model'])) {
			throw new sfException('Missing model or model wasn\'t generated for db logger');
		}
		
		$object = new $options['model'];
		
		if(!method_exists($object, 'setPriority')) {
			throw new sfException('Missing field \'priority\' in model '.$options['model'].' for db logger');
		}
		if(!method_exists($object, 'setPriorityName')) {
					throw new sfException('Missing field \'priority_name\' in model '.$options['model'].' for db logger');
		}
		if(!method_exists($object, 'setMessage')) {
					throw new sfException('Missing field \'message\' in model '.$options['model'].' for db logger');
		}
		
		$this->model = $options['model'];

		return parent::initialize($dispatcher, $options);
	}
	
	/**
	 * Log a message
	 *
	 * @param string $message   Message
	 * @param string $priority  Message priority
	 */
	public function doLog($message, $priority) {
		$object = new $this->model;
		$object->setMessage($message);
		$object->setPriority($priority);
		$object->setPriorityName($this->getPriorityName($priority));
		$object->save();
	}
	
	/**
	 * Shutdown the logger
	 *
	 */
	public function shutdown() {
		/** Nothing to do */
	}
}
?>