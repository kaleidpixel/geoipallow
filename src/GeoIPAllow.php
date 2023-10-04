<?php
/**
 * PHP 7.3 or later
 *
 * @package    KALEIDPIXEL
 * @author     KAZUKI Otsuhata
 * @copyright  2023 (C) Kaleid Pixel
 * @license    MIT License
 * @version    1.0.0
 **/

namespace kaleidpixel;

use DateTime;
use Exception;
use finfo;

class GeoIPAllow {
	const IP_LIST_ENDPOINTS = [
		'google'                         => 'https://www.gstatic.com/ipranges/goog.json',
		'googlebot'                      => 'https://developers.google.com/static/search/apis/ipranges/googlebot.json',
		'google-special-crawlers'        => 'https://developers.google.com/static/search/apis/ipranges/special-crawlers.json',
		'google-user-triggered-fetchers' => 'https://developers.google.com/static/search/apis/ipranges/user-triggered-fetchers.json',
		'apnic'                          => 'https://ftp.apnic.net/stats/apnic/delegated-apnic-latest',
	];

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $now = '';

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $server = '';

	/**
	 * @var int
	 * @since 1.0.0
	 */
	protected $ipv = 4;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $country = '';

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $output_path = '';

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $add_before_str = '';

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $add_after_str = '';

	public function __construct( array $setting = [] ) {
		$this->now = date( 'Y-m-d' );

		if ( !empty( $setting['server'] ) ) {
			$this->server = mb_strtolower( (string) $setting['server'] );
		} else {
			$this->server = 'apache';
		}

		if ( !empty( $setting['ipv'] ) ) {
			$this->ipv = (int) $setting['ipv'];
		} else {
			$this->ipv = 4;
		}

		if ( !empty( $setting['country'] ) ) {
			$this->country = mb_strtoupper( (string) $setting['country'] );
		} else {
			$this->country = 'US';
		}

		if ( !empty( $setting['output_path'] ) ) {
			$this->output_path = $setting['output_path'];
		} else {
			$this->output_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . '.htaccess';
		}

		if ( !empty( $setting['add_before_str'] ) ) {
			$this->add_before_str = $setting['add_before_str'] . "\n\n";
		}

		if ( !empty( $setting['add_after_str'] ) ) {
			$this->add_after_str = $setting['add_after_str'] . "\n\n";
		}
	}

	/**
	 * Get this class name.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function getClassName(): string {
		return get_called_class();
	}

	/**
	 * Wrapper method for the constant IP_LIST_ENDPOINTS.
	 *
	 * @return string[]
	 * @since 1.0.0
	 */
	public static function ipListEndPoints(): array {
		return self::IP_LIST_ENDPOINTS;
	}

	/**
	 * Calculate CIDR.
	 *
	 * @param int $range
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public static function getCIDRRangeIPv4( int $range = 0 ): int {
		return 32 - log( $range, 2 );
	}

	/**
	 * Check if the type of interface between the web server and PHP is CLI.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_cli(): bool {
		return (
			defined( 'STDIN' ) ||
			PHP_SAPI === 'cli' ||
			( stristr( PHP_SAPI, 'cgi' ) && getenv( 'TERM' ) ) ||
			array_key_exists( 'SHELL', $_ENV ) ||
			( empty( $_SERVER['REMOTE_ADDR'] ) && !isset( $_SERVER['HTTP_USER_AGENT'] ) && count( $_SERVER['argv'] ) > 0 ) ||
			!array_key_exists( 'REQUEST_METHOD', $_SERVER )
		);
	}

	/**
	 * Retrieve the HTTP code and content of the specified URL.
	 *
	 * @param string $url
	 * @param array  $header
	 * @param string $method
	 * @param array  $data
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function curl_get_content( string $url = '', array $header = [], string $method = 'GET', array $data = [] ): array {
		$result = array();
		$url    = strip_tags( str_replace( array( '"', "'", '`', '´', '¨' ), '', trim( $url ) ) );
		$url    = filter_var( $url, FILTER_SANITIZE_URL );

		if ( !empty( $url ) ) {
			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
			curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
			curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );

			if ( mb_strtoupper( $method ) === 'POST' ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
			}

			if ( is_array( $header ) && !empty( $header ) ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
			}

			$result['body'] = curl_exec( $ch );
			$result['url']  = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

			if ( defined( 'CURLINFO_HTTP_CODE' ) ) {
				$result['http_code'] = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			} else {
				$result['http_code'] = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
			}

			curl_close( $ch );
		}

		return $result;
	}

	/**
	 * Output the header in the web browser to download the file and initiate the file download.
	 *
	 * @param string      $file_path
	 * @param string|null $mime_type
	 *
	 * @return void
	 * @since  1.0.0
	 * @link   https://qiita.com/fallout/items/3682e529d189693109eb
	 */
	public static function download( string $file_path = '', string $mime_type = null ): void {
		if ( !is_readable( $file_path ) ) {
			die( $file_path );
		}

		$mime_type = ( isset( $mime_type ) ) ? $mime_type : ( new finfo( FILEINFO_MIME_TYPE ) )->file( $file_path );

		if ( !preg_match( '/\A\S+?\/\S+/', $mime_type ) ) {
			$mime_type = 'application/octet-stream';
		}

		header( 'Content-Type: ' . $mime_type );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Connection: close' );

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $file_path );
		exit;
	}

	/**
	 * @param string $filename
	 * @param string $start
	 * @param string $end
	 *
	 * @return string|bool
	 * @since 1.0.0
	 */
	public static function processTextBlockToFile( string $filename, string $start, string $end ) {
		$content = false;
		$start   = preg_quote( trim( $start ) );
		$end     = preg_quote( trim( $end ) );

		if ( file_exists( $filename ) ) {
			$content = file_get_contents( $filename );

			if ( $content === false ) {
				return false;
			}

			$pattern = "/$start.*?$end/s";
			$content = preg_replace( $pattern, '', $content );
		}

		return $content;
	}

	/**
	 * @param string $filename
	 * @param string $body
	 * @param string $start
	 * @param string $end
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function prependTextBlockToFile( string $filename, string $body, string $start, string $end ): bool {
		$content = self::processTextBlockToFile( $filename, $start, $end );
		$content = "$start$body$end$content";

		if ( file_put_contents( $filename, $content ) === false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param string $filename
	 * @param string $body
	 * @param string $start
	 * @param string $end
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function appendTextBlockToFile( string $filename, string $body, string $start, string $end ): bool {
		$content = self::processTextBlockToFile( $filename, $start, $end );
		$content = "$content$start$body$end";

		if ( file_put_contents( $filename, $content ) === false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Output.
	 *
	 * @param bool $echo
	 * @param bool $force
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function read( bool $echo = false, bool $force = false ) {
		$contents = $this->create( $force );

		if ( empty( $contents['body'] ) && $force === false ) {
			print_r( 'File is not contents.' );
		} elseif ( $this->is_cli() ) {
			print_r( 'Output path: ' . str_replace( 'file://', '', $contents['url'] ) );
		} elseif ( $echo ) {
			print_r( "<pre>${contents['body']}</pre>" );
		} else {
			$this->download( $contents['url'] );
		}
	}

	/**
	 * @param bool $echo
	 *
	 * @return bool|void
	 * @since 1.0.0
	 */
	public function delete( bool $echo = false ) {
		$content = self::processTextBlockToFile( $this->output_path, $this->getHeaderStr(), $this->getFooterStr() );
		$result  = file_put_contents( $this->output_path, $content );

		if ( $echo === true || $this->is_cli() === true ) {
			print_r( $result === false ? 'File processed unsuccessfully!' : 'File processed successfully!' );
		} else {
			return $result !== false;
		}
	}

	/**
	 * Create file.
	 *
	 * @param bool $force
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function create( bool $force = false ): array {
		$create = false;

		if ( file_exists( $this->output_path ) ) {
			$file_time = null;

			try {
				$content = file_get_contents( $this->output_path );

				if ( $content !== false ) {
					$start   = preg_quote( trim( $this->getHeaderStr() ) );
					$end     = preg_quote( trim( $this->getFooterStr() ) );
					$pattern = "/$start.*?$end/s";

					if ( preg_match( $pattern, $content, $matches ) ) {
						if ( preg_match( '/add\s([0-9]{4}-[0-9]{2}-[0-9]{2})/', $matches[0], $m ) ) {
							$file_time = new DateTime( $m[1] );
						}
					}
				}
			} catch ( Exception $e ) {
			}

			if ( !is_null( $file_time ) ) {
				try {
					$now  = new DateTime( $this->now );
					$diff = $now->diff( $file_time );

					if ( $diff->days > 0 ) {
						$create = true;
					}
				} catch ( Exception $e ) {
				}
			}

			unset( $file_time, $now, $diff );
		} else {
			$create = true;
		}

		if ( $create === true || $force === true ) {
			$this->prependTextBlockToFile( $this->output_path, $this->getIpAllowList(), $this->getHeaderStr(), $this->getFooterStr() );
		}

		return $this->curl_get_content( "file:///$this->output_path" );
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	protected function getHeaderStr(): string {
		return "### ADD {$this->getClassName()} ###\n";
	}

	/**
	 * @return string
	 * @since 1.0.0
	 */
	protected function getFooterStr(): string {
		return "### END {$this->getClassName()} ###\n";
	}

	/**
	 * Get IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getIpAllowList(): string {
		$head = <<<EOL
###################################################
# Restrict access to IP addresses within $this->country only. #
# add $this->now                                  #
###################################################
EOL;

		if ( $this->server !== 'nginx' ) {
			$result = <<<EOL
$head
SetEnvIf User-Agent "msnbot" allowbot
SetEnvIf User-Agent "bingbot" allowbot

Order Deny,Allow
Deny from All

## Allow bot
Allow from env=allowbot

## Private IP Address
Allow from 127.0.0.1
Allow from 10.0.0.0/8
Allow from 172.16.0.0/12
Allow from 192.168.0.0/16

EOL;
		} else {
			// It is not possible to determine if the Nginx configuration file is written correctly.
			// I am waiting for a pull request from you :D.
			$result = <<<EOL
$head
set \$allow_access 0;

## Allow if User-Agent is search bot
map \$http_user_agent \$allow_bot {
    default         0;
    ~*(msnbot|bingbot) 1;
}

## Private IP Address
allow 127.0.0.1;
allow 10.0.0.0/8;
allow 172.16.0.0/12;
allow 192.168.0.0/16;

EOL;
		}

		$result .= "\n" . $this->add_before_str;
		$result .= $this->getGoogleIpAllowList();
		$result .= $this->getGooglebotIpAllowList();
		$result .= $this->getGoogleSpecialCrawlerIpAllowList();
		$result .= $this->getGoogleUserTriggeredFetchersIpAllowList();
		$result .= $this->getGeoIpAllow();
		$result .= $this->add_after_str;

		if ( $this->server === 'nginx' ) {
			// It is not possible to determine if the Nginx configuration file is written correctly.
			// I am waiting for a pull request from you :D.
			$result .= <<<EOL
deny all;

if (\$allow_bot = 1) {
	allow all;
	deny none;
}

EOL;
		}

		return $result;
	}

	/**
	 * Get Google IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getGoogleIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google'],
			[
				'############################',
				'# Google IP Address Ranges',
				"# {$this->ipListEndPoints()['google']}",
				'############################',
			]
		);
	}

	/**
	 * Get Googlebot IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getGooglebotIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['googlebot'],
			[
				'############################',
				'# Googlebot IP Address Ranges',
				"# {$this->ipListEndPoints()['googlebot']}",
				'############################',
			]
		);
	}

	/**
	 * Get Google special crawler IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getGoogleSpecialCrawlerIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google-special-crawlers'],
			[
				'############################',
				'# Google special crawler IP Address Ranges',
				"# {$this->ipListEndPoints()['google-special-crawlers']}",
				'############################',
			]
		);
	}

	/**
	 * Get Google user triggered fetchers IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getGoogleUserTriggeredFetchersIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google-user-triggered-fetchers'],
			[
				'############################',
				'# Google user triggered fetchers IP Address Ranges',
				"# {$this->ipListEndPoints()['google-user-triggered-fetchers']}",
				'############################',
			]
		);
	}

	/**
	 * Add Googlebot IP Allow List.
	 *
	 * @param string $endpoint
	 * @param array  $header
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function addGoogleIpAllowList( string $endpoint, array $header ): string {
		$contents = $this->curl_get_content( $endpoint );
		$result   = $header;

		if ( $contents['http_code'] === 200 ) {
			$lines = json_decode( $contents['body'] );
		} else {
			return implode( PHP_EOL, $result );
		}

		unset( $contents );

		foreach ( $lines->prefixes as $key => $prefixe ) {
			$ip = null;

			if ( !in_array( $this->ipv, [ 4, 6, 46 ], true ) ) {
				continue;
			}

			if ( isset( $prefixe->ipv4Prefix ) && in_array( $this->ipv, [ 4, 46 ], true ) ) {
				$ip = $prefixe->ipv4Prefix;
			}

			if ( isset( $prefixe->ipv6Prefix ) && in_array( $this->ipv, [ 6, 46 ], true ) ) {
				$ip = $prefixe->ipv6Prefix;
			}

			if ( empty( $ip ) ) {
				continue;
			}

			switch ( $this->server ) {
				default:
				case 'apache':
					$result[] = "Allow from $ip";
					break;
				case 'nginx':
					$result[] = "allow $ip;";
					break;
			}

			unset( $lines->prefixes[$key], $parts );
		}

		array_push( $result, '', '' );

		return implode( PHP_EOL, $result );
	}

	/**
	 * Get Geo IP Allow List.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function getGeoIpAllow(): string {
		$contents = $this->curl_get_content( $this->ipListEndPoints()['apnic'] );
		$result   = [
			'############################',
			'# Geo IP Address Ranges',
			"# {$this->ipListEndPoints()['apnic']}",
			'############################',
		];

		if ( $contents['http_code'] === 200 ) {
			$lines = explode( "\n", $contents['body'] );
		} else {
			return implode( PHP_EOL, $result );
		}

		unset( $contents );

		foreach ( $lines as $key => $line ) {
			$parts = explode( '|', $line );
			$ip    = null;

			if ( count( $parts ) < 5 || $parts[1] !== $this->country || !in_array( $this->ipv, [ 4, 6, 46 ], true ) ) {
				continue;
			}

			if ( $parts[2] == 'ipv4' && in_array( $this->ipv, [ 4, 46 ], true ) ) {
				$cidr = $this->getCIDRRangeIPv4( $parts[4] );
				$ip   = "${parts[3]}/$cidr";

				unset( $cidr );
			}

			if ( $parts[2] == 'ipv6' && in_array( $this->ipv, [ 6, 46 ], true ) ) {
				$ip = "${parts[3]}/${parts[4]}";
			}

			if ( empty( $ip ) ) {
				continue;
			}

			switch ( $this->server ) {
				default:
				case 'apache':
					$result[] = "Allow from $ip";
					break;
				case 'nginx':
					$result[] = "allow $ip;";
					break;
			}

			unset( $lines[$key] );
		}

		array_push( $result, '', '' );

		return implode( PHP_EOL, $result );
	}
}
