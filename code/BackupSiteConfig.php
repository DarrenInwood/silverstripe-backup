<?php
/**
* Data extension to added Backup functionality to the Settings part of the CMS
*
* @package backup
*/
class BackupSiteConfig extends DataExtension {
	
	public static $db = array(
		'BackupDatabaseEnabled' => 'Boolean',
		'BackupAssetsEnabled' => 'Boolean',
		'BackupTransferEnabled' => 'Boolean',
		'IPAddressOne' => 'Varchar(250)',
		'IPAddressTwo' => 'Varchar(250)',
		'SSHUser' => 'Varchar(250)',
		'SSHHost' => 'Varchar(250)',
		'SSHPath' => 'Varchar(250)',
	);

	public function updateCMSFields(FieldList $fields) {
		// Grab the latest database dumps made
		$links = '<div id="backup-latest-links">';
		$dir = scandir(ASSETS_PATH);
		foreach($dir as $file) {
			$extn = pathinfo(ASSETS_PATH.DIRECTORY_SEPARATOR.$file, PATHINFO_EXTENSION);
			if ( $extn != 'gz' ) {
				continue;
			}
	
			$links .= sprintf(
				'<p><a href="%s">%s</a></p>'."\n",
				ASSETS_DIR.DIRECTORY_SEPARATOR.$file,
				$file
			);
		}
		if ( $links == '' ) {
			$links = '<p>No backups so far.</p>';
		}
		$links .= '</div>';

		$fields->addFieldsToTab(
			'Root.Backup',
			array(
				new HeaderField('backup-heading', 'Backup'),
				new LiteralField('backup-explanation', '<p>This module backs up the database and assets, and optionally copies them to a target server via SCP.</p>'),
				new CheckboxField('BackupDatabaseEnabled', 'Back up the database?'),
				new CheckboxField('BackupAssetsEnabled', 'Back up the assets folder?'),
				new CheckboxField('BackupTransferEnabled', 'Transfer the backup files via SSH?'),
				$ip = new TextField('IPAddressOne', 'Nominated IP Address'),
				$ip2 = new TextField('IPAddressTwo', 'Alternate IP Address'),
				new LiteralField('backup-ssh', '<p>* SSH transfer requires the webserver user to have SSH authorized key set up on the target server.</p>'),
				new TextField('SSHUser', 'SSH user'),
				new TextField('SSHHost', 'SSH host'),
				$path = new TextField('SSHPath', 'SSH path'),
				new HeaderField('backup-latest-heading', 'Latest backups', 4),
				new LiteralField('backup-latest-links', $links),
				new LiteralField('backupNow', 
					'<input type="button" id="backup-action" value="Back up now" class="ss-ui-button ui-button" role="button" aria-disabled="false">'.
					'<script type="text/javascript">'.
						'jQuery(\'#backup-action\').click(function() {'.
							'jQuery("<p id=\'waiter\' class=\'message notice\'>Backing up, please wait.</p>").insertAfter(jQuery("#Form_EditForm_backup-heading"));'.
							'jQuery(\'html\').css(\'cursor\', \'wait\');'.
							'jQuery.ajax(\'backup/BackupNow\', {'.
								'\'async\':\'false\','.
								'\'success\': function(json){'.
									'jQuery(\'html\').css(\'cursor\', \'default\');'.
									'data = JSON.parse(json);'.
									'if(data.Status == "Success"){'.
										'jQuery("#backup-latest-links").html(data.Data);'.
										'jQuery("#waiter").removeClass(\'notice\');'.
										'jQuery("#waiter").addClass(\'good\');'.
										'jQuery("#waiter").html("Backup Complete");'.
									'} else {'.
										'jQuery("#waiter").removeClass(\'notice\');'.
										'jQuery("#waiter").addClass(\'bad\');'.
										'jQuery("#waiter").html(data.Data);'.
									'}'.

								'}'. 
							'}); '. 
						'});'.
					'</script>'
				),
			)
		);

		$path->setRightTitle('Use the full server path, eg. /srv/backups/server-name');
		$ip->setRightTitle('The IP address from which the task can be called.');
		$ip2->setRightTitle('An alternate IP address from which the task can be called.');
	}


}
