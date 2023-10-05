# Geo IP Allow

A streamlined tool designed to generate .htaccess configurations, allowing web access exclusively from IP addresses of a
specified country. Enhance your site's security and target your audience more effectively.

# Document

Coding is quite simple. The options are only simple, so you won't get lost.

## Options

|                                 Option | Description                                                                                                          |
|---------------------------------------:|:---------------------------------------------------------------------------------------------------------------------|
|        **server**<br>_default: apache_ | Apache or Nginx                                                                                                      |
|                **ipv**<br>_default: 0_ | Specify the version of the IP Address with a single digit (4 or 6).                                                  |
|           **country**<br>_default: US_ | Specify the name of the country using the ISO code (Alpha-2 code) as defined in the ISO 3166 international standard. |
|    **output_path**<br>_default: empty_ | File path including the name of the file to output the results.                                                      |
| **add_before_str**<br>_default: empty_ | .                                                                                                                    |
|  **add_after_str**<br>_default: empty_ | .                                                                                                                    |

## Methods

|                 Method | Parameter                                                                            | Description                                                                               |
|-----------------------:|:-------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------|
|             **read()** | bool $echo = false<br>bool $force = false                                            | Create a list of IP addresses. If already created, cache it for one day.                  |
|           **delete()** | none                                                                                 | Delete the IP address added with the read method.                                         |
|  **ipListEndPoints()** | none                                                                                 | Wrapper method for the constant IP_LIST_ENDPOINTS.                                        |
| **getCIDRRangeIPv4()** | int $range = 0                                                                       | Calculate CIDR.                                                                           |
|           **is_cli()** | none                                                                                 | Check if the type of interface between the web server and PHP is CLI.                     |
| **curl_get_content()** | string $url = ''<br>array $header = []<br>string $method = 'GET'<br>array $data = [] | Retrieve the HTTP code and content of the specified URL.                                  |
|         **download()** | string $file_path = ''<br>string $mime_type = null                                   | Output the header in the web browser to download the file and initiate the file download. |

## Basic markup

What follows is the simplest coding.

### Example 1
```php
<?php
namespace exampleproject;

use kaleidpixel\GeoIPAllow;

class JPIPAllow {
	/**
	 * @var GeoIPAllow
	 */
	protected $geoIpAllow = null;

	public function __construct( array $setting = [] ) {
		$setting['country'] = 'JP';

		if ( empty( $setting['output_path'] ) ) {
			$setting['output_path'] = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . '.htaccess';
		}

		$this->geoIpAllow = new GeoIPAllow( $setting );
	}

	/**
	 * @param bool $echo
	 * @param bool $force
	 *
	 * @return void
	 */
	public function read( bool $echo = false, bool $force = false ): void {
		$this->geoIpAllow->read( $echo, $force );
	}
}

```

### Example 2
```php
<?php
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

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
		'country'        => 'JP',
		'output_path'    => __DIR__ . DIRECTORY_SEPARATOR . '.htaccess',
		'add_before_str' => $before_str
	]
);

$ip->read(true);

```

The source code shown above will work on the built-in web server. It also operates in CLI, so choose whichever you
prefer.

If you want to run it on the built-in web server, execute the command shown below and then access it with a web browser.

```shell
$ php -S localhost:8080

```

If you want to run it in CLI, execute the command shown below. The path where the file is outputted will be displayed as
a result.

```shell
$ php ./example/index.php

```

# License

MIT License  
Copyright (c) 2023 Kaleid Pixel
