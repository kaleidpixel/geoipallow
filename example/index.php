<?php
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'src/GeoIPAllow.php';

use kaleidpixel\GeoIPAllow;

$now        = date( 'Y-m-d' );
$before_str = <<<EOL
## IP address of its In-House server.
Allow from 103.xxx.xxx.xxx
Allow from 203.xxx.xxx.xxx
EOL;

$ip = new GeoIPAllow(
	[
		'server'         => 'apache',
		'ipv'            => 4,
		'country'        => 'US',
		'output_path'    => __DIR__ . DIRECTORY_SEPARATOR . '.htaccess',
		'add_before_str' => $before_str
	]
);

$ip->read();
