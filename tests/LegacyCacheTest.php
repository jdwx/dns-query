<?php


declare( strict_types = 1 );


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.0.0
 *
 */


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Resolver;
use PHPUnit\Framework\TestCase;


/**
 * This test uses the Google public DNS servers to perform a resolution test;
 * this should work on *nix and Windows, but will require an internet connection.
 *
 */
class LegacyCacheTest extends TestCase {

    /**
     * function to test the cache
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function testCache() : void {
        $cache_file = '/tmp/net_dns2_test.cache';
        if ( file_exists( $cache_file ) ) {
            unlink( $cache_file );
        }

        $r = new Resolver( [
            'nameservers' => [ '8.8.8.8', '8.8.4.4' ],
            'use_cache' => true,
            'cache_type' => 'file',
            'cache_file' => $cache_file,
        ] );


        $r->query( 'google.com', 'MX' );

        unset( $r );  # Must get the FileCache destructor to run.
        clearstatcache();  # Clear the stat cache so the check will really measure the file size.

        static::assertFileExists( $cache_file );
        static::assertGreaterThan( 0, filesize( $cache_file ) );
    }
}
