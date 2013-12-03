<?php

Object::add_extension('SiteConfig', 'BackupSiteConfig');

//Set IPs that task can be run from. SiteConfig set IPs will be added to list. 
BackupTask::set_ip_array(array(
	'::1',
	'127.0.0.1'
	)
);

//Set SSH Settinsg for SCP transfer. Comment out to use default.
// BackupTask::set_ssh_settings(array(
// 	'User' => '',
// 	'Host' => '',
// 	'Path' => ''
// 	)
// );
