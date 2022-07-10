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


use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\OPT;


require_once 'Net_DNS2.php';

/**
 * Test class to test the DNSSEC logic
 *
 */
class Tests_Net_DNS2_DNSSECTest extends PHPUnit\Framework\TestCase
{
    /**
     * function to test the TSIG logic
     *
     * @return void
     * @access public
     *
     * @throws JDWX\DNSQuery\Exception
     */
    public function testDNSSEC() : void
    {
        $ns = [ '8.8.8.8', '8.8.4.4' ];

        $r = new Resolver([ 'nameservers' => $ns ]);

        $r->dnssec = true;

        $result = $r->query('org', 'SOA' );

        static::assertTrue(($result->header->ad == 1));
        static::assertTrue(($result->additional[0] instanceof OPT));
        assert( $result->additional[0] instanceof OPT );
        static::assertTrue(($result->additional[0]->do == 1));
    }
}
