<?php
/**
* A task that can back up the database and assets and send them to target server.
*
* @package backup
*/
class BackupTask extends DailyTask {
	
	protected $title = 'Perform a backup task';
	
	protected $description = 'Creates a database dumpfile and an assets tarfile, and copies them to a specified location.';

	protected static $rm_path = '/bin/rm';
	protected static $mysqldump_path = '/usr/local/mysql/bin/mysqldump';
	protected static $gzip_path = '/usr/bin/gzip';
	protected static $tar_path = '/usr/bin/tar';
	protected static $scp_path = '/usr/bin/scp';
	
	// SSH keys must be set up to allow apache user to ssh to target server without password
	protected static $ssh_user = 'root';
	protected static $ssh_host = 'dev.stw.io';
	protected static $ssh_path = '/bigdata/backup';

	protected static $backup_db;
	protected static $backup_assets;
	protected static $transfer_backup;
	protected static $ip_array;


	public function init() {
		parent::init();

		//Get parameter overrides SiteConfig settings
		if(isset($_GET['db']) && $_GET['db'] != null){
			self::$backup_db = $_GET['db'];
		} else {
			self::$backup_db = SiteConfig::current_site_config()->BackupDatabaseEnabled;
		}

		if(isset($_GET['assets']) && $_GET['assets'] != null){
			self::$backup_assets = $_GET['assets'];
		} else {
			self::$backup_assets = SiteConfig::current_site_config()->BackupAssetsEnabled;
		}

		if(isset($_GET['ssh']) && $_GET['ssh'] != null){
			self::$transfer_backup = $_GET['ssh'];
		} else {
			self::$transfer_backup = SiteConfig::current_site_config()->BackupTransferEnabled;
			self::$ssh_user = SiteConfig::current_site_config()->SSHUser;
			self::$ssh_host = SiteConfig::current_site_config()->SSHHost;
			self::$ssh_path = SiteConfig::current_site_config()->SSHPath;
		}

		//Add SiteConfig IPs to set IPs
		$ip1 = SiteConfig::current_site_config()->IPAddressOne;
		$ip2 = SiteConfig::current_site_config()->IPAddressTwo;
		if($ip1 != null){
			self::$ip_array[] = $ip1;
		}
		if($ip2 != null){
			self::$ip_array[] = $ip2;
		}
	
		//Verify that task is being run by one of the chosen IPs
		$this->verify_ip();
		
	}
	
	public function process() {
		// Check for required binaries
		$rm = self::$rm_path;
		if ( ! $rm || !file_exists($rm) ) {
			$rm = trim(shell_exec('which rm'));
		}
		if ( ! $rm || !file_exists($rm) ) {
			user_error('Utility rm cannot be found.');
		}
		$mysqldump = self::$mysqldump_path;
		if ( ! $mysqldump || !file_exists($mysqldump) ) {
			$mysqldump = trim(shell_exec('which mysqldump'));
		}
		if ( ! $mysqldump || !file_exists($mysqldump) ) {
			user_error('Utility mysqldump cannot be found.');
		}
		$gzip = self::$gzip_path;
		if ( ! $gzip || !file_exists($gzip) ) {
			$gzip = trim(shell_exec('which gzip'));
		}
		if ( ! $gzip || !file_exists($gzip) ) {
			user_error('Utility gzip cannot be found.');
		}
		$tar = self::$tar_path;
		if ( ! $tar || !file_exists($tar) ) {
			$tar = trim(shell_exec('which tar'));
		}
		if ( ! $tar || !file_exists($tar) ) {
			user_error('Utility tar cannot be found.');
		}
		$scp = self::$scp_path;
		if ( ! $scp || !file_exists($scp) ) {
			$scp = trim(shell_exec('which scp'));
		}
		if ( ! $scp || !file_exists($scp) ) {
			user_error('Utility scp cannot be found.');
		}

		global $databaseConfig;
		$siteConfig = SiteConfig::current_site_config();

		// Remove all previous backup files
		$command = sprintf(
			'%s %s',
			$rm,
			ASSETS_PATH.DIRECTORY_SEPARATOR.$databaseConfig['database'].'*'
		);
		// var_dump($command);
		shell_exec($command);

		// Backup database
		if ( self::$backup_db ) {
			$database_backup_path = $databaseConfig['database'].'-'.date('Y-m-d-h-i-s').'.sql';
			$command = sprintf(
				'cd %s && %s -u %s %s -r %s %s && %s %s',
				escapeshellarg(ASSETS_PATH),
				$mysqldump,
				escapeshellarg($databaseConfig['username']),
				($databaseConfig['password'] ? '-p '.escapeshellarg($databaseConfig['password']):''),
				escapeshellarg($database_backup_path),
				escapeshellarg($databaseConfig['database']),
				$gzip,
				escapeshellarg($database_backup_path)
			);
			// var_dump($command);
			shell_exec($command);

		}
		
		// Backup assets
		if ( self::$backup_assets ) {
			$assets_backup_path = $databaseConfig['database'].'-assets-'.date('Y-m-d-h-i-s').'.tar.gz';
			$command = sprintf(
				'cd %s && %s zcpf %s assets',
				escapeshellarg(BASE_PATH),
				$tar,
				escapeshellarg(ASSETS_PATH.DIRECTORY_SEPARATOR.$assets_backup_path)
			);
			// var_dump($command);
			shell_exec($command);
		}

		// SCP them somewhere safe
		if ( self::$transfer_backup ) {

			//if backing up database but not assets
			if(self::$backup_db && !self::$backup_assets){
				$command = sprintf(
					'%s %s %s@%s:%s',
					$scp,
					escapeshellarg(ASSETS_PATH.DIRECTORY_SEPARATOR.$database_backup_path.'.gz'),
					self::$ssh_user,
					self::$ssh_host,
					self::$ssh_path
				);
			//if backing up assetes but not database
			} else if(!self::$backup_db && self::$backup_assets){
				$command = sprintf(
					'%s %s %s@%s:%s',
					$scp,
					escapeshellarg(ASSETS_PATH.DIRECTORY_SEPARATOR.$assets_backup_path),
					self::$ssh_user,
					self::$ssh_host,
					self::$ssh_path
				);
			//backing up both
			} else {
				$command = sprintf(
					'%s %s %s@%s:%s && %s %s %s@%s:%s',
					$scp,
					escapeshellarg(ASSETS_PATH.DIRECTORY_SEPARATOR.$database_backup_path.'.gz'),
					self::$ssh_user,
					self::$ssh_host,
					self::$ssh_path,
					$scp,
					escapeshellarg(ASSETS_PATH.DIRECTORY_SEPARATOR.$assets_backup_path),
					self::$ssh_user,
					self::$ssh_host,
					self::$ssh_path
				);
			}
			// var_dump($command);
			shell_exec($command);
		}
	}

	/**
	*	Determins the client's IP Address
	*
	*	@return String
	*/
	public function get_client_ip() {
	     $ipaddress = '';
	     if (array_key_exists('HTTP_CLIENT_IP', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	     else if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	     else if(array_key_exists('HTTP_X_FORWARDED', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	     else if(array_key_exists('HTTP_FORWARDED_FOR', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	     else if(array_key_exists('HTTP_FORWARDED', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_FORWARDED'];
	     else if(array_key_exists('REMOTE_ADDR', $_SERVER))
	         $ipaddress = $_SERVER['REMOTE_ADDR'];
	     else
	         $ipaddress = 'UNKNOWN';

	     return $ipaddress; 
	}
	
	/**
	*	Verifies the clients IP address, returns a permission failure if verification fails
	*
	*	@return SS_HTTPResponse
	*/
	public function verify_ip(){
		$client_ip = $this->get_client_ip();

		if(!in_array($client_ip, self::$ip_array)){

			return Security::permissionFailure($this, 'Your IP address does not match the nominated IP addresses for this task');
		}

	}

	/**
	* Sets the ip_array in the config
	*/
	public static function set_ip_array(Array $ip){
		self::$ip_array = $ip;
	}

	/**
	* Sets the SSH settings for SCP in the config
	*/
	public static function set_ssh_settings(Array $settings){
		self::$ssh_user = $settings['User'];
		self::$ssh_host = $settings['Host'];
		self::$ssh_path = $settings['Path'];
	}

}
