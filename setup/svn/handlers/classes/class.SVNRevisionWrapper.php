<?php
class SVNRevisionWrapper {
	/**
	 * Revision nr
	 *
	 * @var int
	 */
	public $revision;
	/**
	 * Revision author
	 *
	 * @var string
	 */
	public $author;
	/**
	 * Revision 
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Revision comment
	 *
	 * @var string
	 */
	public $comment;
	/**
	 * Revision comment. Each comment line is an array element
	 *
	 * @var array
	 */
	public $comment_arr;
	
	/**
	 * Constructor
	 *
	 * @param int $rev revision nr
	 * @param array $data revision data from executing svnlook info
	 */
	public function __construct($rev = 0, array $data = null) {
		$this->revision = $rev;
		if (!is_null($data)) {
			$this->author = $data[0];
			$this->date = $data[1];
			unset($data[0]);
			unset($data[1]);
			unset($data[2]);
			$this->comment = implode("\r\n", $data);
			$this->comment_arr = array_values($data);
		}
	}
}
?>