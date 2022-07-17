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
class DNSSECTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testDOFlag() {
        $dns = new Resolver( '8.8.8.8' );
        $dns->setDNSSEC();
        $rsp = $dns->query( 'org.', 'SOA' );
        static::assertSame( 1, $rsp->header->ad );
        static::assertCount( 1, $rsp->additional );
        static::assertInstanceOf( OPT::class, $rsp->additional[ 0 ] );
        $opt = $rsp->additional[ 0 ];
        assert( $opt instanceof OPT );
        static::assertSame( 1, $opt->do );
    }


}