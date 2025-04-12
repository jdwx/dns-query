<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\OPT;
use PHPUnit\Framework\TestCase;


/** Basic DNSSEC test.
 *
 * TODO: Expand this dramatically.
 */
final class DNSSECTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testDOFlag() : void {
        $dns = new Resolver( '8.8.8.8' );
        $dns->setDNSSEC();
        $rsp = $dns->query( 'org.', 'SOA' );
        self::assertSame( 1, $rsp->header->ad );
        self::assertCount( 1, $rsp->additional );
        $opt = $rsp->additional[ 0 ];
        assert( $opt instanceof OPT );
        self::assertSame( 1, $opt->do );
    }


}