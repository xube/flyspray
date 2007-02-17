<?php

/**
 * EasySwift Response Tracker
 * Please read the LICENSE file
 * @copyright Chris Corbyn <chris@w3style.co.uk>
 * @author Chris Corbyn <chris@w3style.co.uk>
 * @package EasySwift
 * @license GNU Lesser General Public License
 */
 
/**
 * EasySwift, Swift Response Tracker.
 * Updates properties in EasySwift when a response is received by Swift.
 * @package EasySwift
 * @author Chris Corbyn <chris@w3style.co.uk>
 */
class Swift_Plugin_EasySwiftResponseTracker extends Swift_Events_Listener
{
	/**
	 * The target object to update
	 * @var EasySwift
	 */
	var $target = null;
	
	/**
	 * Constructor
	 * @param EasySwift The instance of EasySwift to run against
	 */
	function Swift_Plugin_EasySwiftResponseTracker(&$obj)
	{
		$this->target =& $obj;
	}
	/**
	 * Response listener method
	 * @param Swift_Events_ResponseEvent The event occured in Swift
	 */
	function responseReceived(&$e)
	{
		$this->target->lastResponse = $e->getString();
		$this->target->responseCode = $e->getCode();
	}
}
