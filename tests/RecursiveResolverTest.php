<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\RecursiveResolver;
use JDWX\DNSQuery\RR\A;
use PHPUnit\Framework\TestCase;


/** Test the recursive resolver. */
class RecursiveResolverTest extends TestCase {


    /** Check that the default list of root servers is sane. */
    public function testDefaultRootNameServers() {
        $rrv = new RecursiveResolver();
        $check = $rrv->getRootNameServers();
        sort( $check );
        static::assertSame( NamedRootTest::$rootNameServersIPv4, $check );
    }


    /** Test the recursive resolver.
     * @throws Exception
     */
    public function testResolve() {
        $rrv = new RecursiveResolver( i_useDNSSEC: false );
        $xx = $rrv->query( 'www.icann.org' );
        $index = array_key_last( $xx );
        $rsp = $xx[ $index ];
        static::assertInstanceOf( ResponsePacket::class, $rsp );
        static::assertCount( 1, $rsp->answer );
        $rr = $rsp->answer[ 0 ];
        static::assertInstanceOf( A::class, $rr );
        static::assertSame( '192.0.32.7', $rr->address );
    }


    /** Try resolution with DNSSEC enabled.
     * @throws Exception
     */
    public function testDNSSEC() {
        $rrv = new RecursiveResolver( i_useDNSSEC: true );
        $rrv->setDebug( true );
        $xx = $rrv->query( 'www.icann.org' );
        $index = array_key_last( $xx );
        $rsp = $xx[ $index ];
        static::assertInstanceOf( ResponsePacket::class, $rsp );
        static::assertCount( 1, $rsp->answer );
        $rr = $rsp->answer[ 0 ];
        static::assertInstanceOf( A::class, $rr );
        static::assertSame( '192.0.32.7', $rr->address );
    }


}