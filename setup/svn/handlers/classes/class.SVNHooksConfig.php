<?php
class SVNHooksConfig {
	/**
	 * Hooks handler root dir
	 *
	 * @var string
	 */
	public $root_dir;
	/**
	 * FlySpray config wrapper
	 *
	 * @var FSConfig
	 */
	public $fs_config;
	
	/**
	 * Handler debug state
	 *
	 * @var bool
	 */
	public $debug = false;
	
	/**
	 * SVN executables masks
	 *
	 * @var array
	 */
	private $masks = array(
		'svn' => array(
			'w'	=> 'call "C:\Program Files\Subversion\bin\%s.exe"',
			'u'	=> '%s'),
		'svnlook' => array(
			'w'	=> 'call "C:\Program Files\Subversion\bin\%s.exe"',
			'u'	=> '%s'),
	);
	
	/**
	 * svn command
	 *
	 * @var string
	 */
	public $svn = 'svn';
	/**
	 * svnlook command
	 *
	 * @var string
	 */
	public $svnlook = 'svnlook';
	
	/**
	 * Singleton instance
	 *
	 * @var SVNHooksConfig
	 */
	private static $_instance;
	
	/**
	 * Gets the only config instance
	 *
	 * @return SVNHooksConfig
	 */
	public static function get_instance() {
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 *
	 */
	private function __construct() {
        if (isset($_ENV['OS'])) {
            $os_idx = stripos($_ENV['OS'], 'win') !== false ? 'w' : 'u';
        } else {
            $os_idx = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'w' : 'u'; 
        }
		foreach ($this->masks as $cmd => $os_masks) {
			if (isset($os_masks[$os_idx])) $this->$cmd = sprintf($os_masks[$os_idx], $this->$cmd);
		}
		
		$this->root_dir = realpath(dirname(__FILE__) . '/../');
		$this->fs_config = new FSConfig();
	}
}
?>