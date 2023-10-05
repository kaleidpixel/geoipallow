<?php
/**
 * PHP 7.3 or later
 *
 * @package    exampleproject
 * @author     Example
 * @copyright  2023 (C) Example
 * @license    MIT License
 * @version    1.0.0
 **/

namespace exampleproject;

use kaleidpixel\GeoIPAllow;

class JPIPAllow {
	/**
	 * @var GeoIPAllow
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function read( bool $echo = false, bool $force = false ): void {
		$this->geoIpAllow->read( $echo, $force );
	}
}
