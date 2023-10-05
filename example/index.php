<?php
// While this is a sample source and directly requires files within the src directory,
// in practice, you would require the autoload.php from the vendor directory.
// require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'GeoIPAllow.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'JPIPAllow.php';

use exampleproject\JPIPAllow;

$before_str = <<<EOL
## IP address of its In-House server.
Allow from 103.xxx.xxx.xxx
Allow from 203.xxx.xxx.xxx
EOL;

$ip = new JPIPAllow(
	[
		'server'         => 'apache',
		'output_path'    => __DIR__ . DIRECTORY_SEPARATOR . '.htaccess',
		'add_before_str' => $before_str
	]
);

$ip->read( true );
