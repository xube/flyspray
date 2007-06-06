<?php
/**
 * SVN Information Class, SVNInfo
 * Class to fetch various Information from a SVN Server.
 *
 * Simple Example:
 * <code>
 * <?php
 * include "svninfo.class.php";
 *
 * $svninfo = new SVNnfo();
 *
 * $svninfo->setRepository("http://svn.example.com/branches/myproject", "foouser", "barpassword");
 *
 * $currentRevision = $svninfo->getCurrentRevision();
 *
 * // Get the SVN-Log from Revision 1900 to the HEAD-Revision
 * $svnlog = $svninfo->getLog(1900, $currentRevision);
 *
 * // This is also possible:
 * $svninfo->setRepository("http://svn.example.com/branches/myproject/file.php", "foouser", "barpassword");
 * $svnlog = $svninfo->getLog(0, 100);
 *
 * </code>
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @author Florian Schmitz <floele@gmail.com>
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @version 0.6
 * @package SVNInfo
 */

/**
 * SVN Information Class, SVNInfo
 * Class to fetch various Information from a SVN Server.
 * @package SVNInfo
 */
class SVNInfo {

	/**
	 * URL of the Repository
	 * @var string
	 */
	var $_reposURL;
	/**
	 * Username to authenticate to the Repository
	 * @var string
	 */
	var $_reposUsername;
	/**
	 * Password to authenticate to the Repository
	 * @var string
	 */
	var $_reposPassword;
	/**
	 * Header Request Method (PROPFIND, REPORT, OPTIONS, GET, ...)
	 * @var string
	 */
	var $_RequestType;
	/**
	 * Request Body
	 * @var string
	 */
	var $_RequestData;

	/**
	 * Set Repository Connect-Information
	 * @param string $url URL of the Repository
	 * @param string $username User to authenticate to the Repository (optional)
	 * @param string $password Password to authenticate to the Repository (optional)
	 */
	function setRepository($url, $username="", $password="") {
		$this->_reposURL 		= $url;
		$this->_reposUsername 	= $username;
		$this->_reposPassword 	= $password;
	}

	/**
	 * Set everything needed and send the Request to the SVN Server
	 * @return string Source-Code of the Result
	 */
	function _startRequest() {
        // Init connection
        $urlinfo = parse_url($this->_reposURL);
        $fp = fsockopen($urlinfo['host'], 80, $errorno, $errorstr, 10);

        if (!$fp) {
            return '';
        }

        stream_set_blocking($fp, 0);

        // Build request
        $request = array();
        $request[] = sprintf('%s %s HTTP/1.1', $this->_RequestType, $urlinfo['path']);
        $request[] = sprintf('Host: %s', $urlinfo['host']);
        $request[] = sprintf('Content-Length: %d', strlen($this->_RequestData));
        $request[] = 'Content-Type: application/x-www-form-urlencoded';
        $request[] = 'Connection: Close';

        // HTTP auth?
        if ($this->_reposUsername) {
            $request[] = sprintf('Authorization: %s', base64_encode($this->_reposUsername . ':' . $this->_reposPassword));
        }

        fwrite($fp, implode("\r\n", $request) . "\r\n\r\n" . $this->_RequestData);

        // Read response
        $response = '';
        while (!feof($fp)) {
            $response .= fread($fp, 2048);
        }

        fclose($fp);

        return $response;
	}

	/**
	 * Retrieve the SVN-Log from Revision x to Revision y of the current Repository
	 * @param integer $startRevision First Log-Entry
	 * @param integer $endRevision Last Log-Entry
	 * @return array Array of Log-Arrays (Revision, Creator, Date, Comment)
	 */
	function getLog($startRevision, $endRevision) {
		// some sanity checks
		if ($startRevision > $endRevision) {
			$startRevision = $endRevision;
		}
		$latestRevision = $this->getCurrentRevision();
		if ($endRevision > $latestRevision) {
			$endRevision = $latestRevision;
		}

		// Form Request Body
		// Information from http://svn.collab.net/repos/svn/trunk/notes/webdav-protocol
		$request =  '<?xml version="1.0" encoding="utf-8" ?>';
		$request .= '<S:log-report xmlns:S="svn:">';
		$request .= '<S:start-revision>' . $startRevision . '</S:start-revision>';
		$request .= '<S:end-revision>' . $endRevision . '</S:end-revision>';
  		$request .= '<S:path></S:path>';
        //$request .= '<S:discover-changed-paths/>';
  		$request .= '</S:log-report>';

  		// Perform a REPORT-Request
  		$this->_RequestType = 'REPORT';
  		// Include the Request Body
  		$this->_RequestData = $request;

  		// Send Request and fetch the Result from the Server
  		$body = $this->_startRequest();

  		// Get CDATA from the Log-Elements
  		$versionNames 	= $this->getElementContents($body, 'D:version-name');
  		$creatorNames 	= $this->getElementContents($body, 'D:creator-displayname');
  		$dates	 		= $this->getElementContents($body, 'S:date');
  		$comments		= $this->getElementContents($body, 'D:comment');

		if (!is_array($versionNames)) {
			return false;
		}

  		// Combine the Data to one single Array
  		$nrentries = count($versionNames);
  		for ($i = 0; $i < $nrentries; $i++) {
  			$logentries[] = array(
  			'version-name' 			=> $versionNames[$i],
  			'creator-displayname' 	=> $creatorNames[$i],
  			'date' 					=> $dates[$i],
  			'comment' 				=> $comments[$i]
  			);
  		}

  		// Return the Log
  		return $logentries;
	}

	/**
	 * Retrieve Filechanges to a given Revision
	 * @return array Array of Filechanges (added, modified, deleted)
	 */
	function getLogFileChanges($revision) {
		// Form Request Body
		// Information from http://svn.collab.net/repos/svn/trunk/notes/webdav-protocol
		$request =  '<?xml version="1.0" encoding="utf-8" ?>';
		$request .= '<S:log-report xmlns:S="svn:">';
		$request .= '<S:start-revision>' . $revision . '</S:start-revision>';
		$request .= '<S:end-revision>' . $revision . '</S:end-revision>';
		$request .= '<S:path></S:path>';
		$request .= '<S:discover-changed-paths/>';
  		$request .= '</S:log-report>';

  		// Perform a REPORT-Request
  		$this->_RequestType = 'REPORT';
  		// Include the Request Body
  		$this->_RequestData = $request;

  		// Send Request and fetch the Result from the Server
  		$body = $this->_startRequest();

  		// Get Modification-Info
  		$modifications = array();

  		$modifications['added']			= $this->getElementContents($body, 'S:added-path');
  		$modifications['added_ext']		= $this->getElementContents($body, 'S:added-path');
  		$modifications['modified']		= $this->getElementContents($body, 'S:modified-path');
  		$modifications['modified_ext']	= $this->getElementContents($body, 'S:modified-path');
  		$modifications['deleted'] 		= $this->getElementContents($body, 'S:deleted-path');
  		$modifications['deleted_ext']	= $this->getElementContents($body, 'S:deleted-path');

		// Return the Log
		return $modifications;
	}

	/**
	 * Retrieve the Revision-Number of a corresponding Date
	 * @return integer Revision-Number
	 */
	function getDatedRevision($date) {
		// Form Request Body
		// Information from http://svn.collab.net/repos/svn/trunk/notes/webdav-protocol
		$request =  '<?xml version="1.0" encoding="utf-8" ?>';
  		$request .= '<S:dated-rev-report xmlns:S="svn:" xmlns:D="DAV:">';
  		$request .= '<D:creationdate>' . gmdate("Y-m-d\\TH:i:s.000000\\Z", $date) . '</D:creationdate>';
  		$request .= '</S:dated-rev-report>';

  		// Perform a REPORT-Request
  		$this->_RequestType = 'REPORT';
  		// Include the Request Body
  		$this->_RequestData = $request;

  		// Send Request and fetch the Result from the Server
  		$body = $this->_startRequest();

		$datedRevision = $this->getElementContents($body, 'D:version-name');

		if (!is_array($datedRevision)) {
			return false;
		}

		$datedRevision = $datedRevision[0];

		return (int)$datedRevision;
	}

	/**
	 * Retrieve the current Revision-Number of the SVN Url
	 * @return integer Revision-Number
	 */
	function getCurrentRevision() {
		return $this->getDatedRevision(time());
	}

	/**
	 * Get the contents of each XML element in $xml as array
     * Usually we wouldn't want to use regular expressions for parsing, but this
     * particular task can be done reliably with them since the escaping used in XML
     * is compatible to regular expressions.
	 * @param string $xml
     * @param string $element without < and > of course
     * @return array like array('1123', '1234', '1235')
	 */
    function getElementContents($xml, $element)
    {
        $matches = array();
        preg_match_all('|<(' . preg_quote($element, '|') . ')>(.*)</\1>|Uuis', $xml, $matches);
        return $matches[2];
    }

}

?>