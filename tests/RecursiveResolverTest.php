<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;
use JDWX\DNSQuery\Legacy\RecursiveResolver;
use JDWX\DNSQuery\Legacy\RR\A;
use JDWX\DNSQuery\Legacy\RR\CNAME;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Test the recursive resolver. */
#[CoversClass( RecursiveResolver::class )]
final class RecursiveResolverTest extends TestCase {


    /** Try resolution with DNSSEC enabled.
     * @throws Exception
     */
    public function testDNSSEC() : void {
        $rrv = new RecursiveResolver( i_useDNSSEC: true );
        $rrv->setDebug( true );
        $xx = $rrv->query( 'www.icann.org' );
        $index = array_key_last( $xx );
        $rsp = $xx[ $index ];
        self::assertInstanceOf( ResponsePacket::class, $rsp );
        echo "Moment of truth:\n";
        var_dump( $rsp->answer );
        self::assertCount( 2, $rsp->answer );
        $rr = $rsp->answer[ 0 ];
        assert( $rr instanceof CNAME );
        self::assertSame( 'www.icann.org.cdn.cloudflare.net', $rr->cname );
    }


    /** Check that the default list of root servers is sane. */
    public function testDefaultRootNameServers() : void {
        $rrv = new RecursiveResolver();
        $check = $rrv->getRootNameServers();
        sort( $check );
        self::assertSame( NamedRootTest::$rootNameServersIPv4, $check );
    }


    /** Test the recursive resolver.
     * @throws Exception
     */
    public function testResolve() : void {
        $rrv = new RecursiveResolver( i_useDNSSEC: false );
        $xx = $rrv->query( 'xs.jdw.sx' );
        $index = array_key_last( $xx );
        $rsp = $xx[ $index ];
        self::assertInstanceOf( ResponsePacket::class, $rsp );
        // var_dump( $rsp->answer ); # Needed to see changes in published records.
        self::assertCount( 1, $rsp->answer );
        $rr = $rsp->answer[ 0 ];
        assert( $rr instanceof A );
        self::assertSame( '204.13.89.4', $rr->address );
    }


}