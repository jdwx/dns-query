<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Legacy;


use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Lookups;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;
use JDWX\DNSQuery\Legacy\Resolver;
use JDWX\DNSQuery\Legacy\RR\A;
use JDWX\DNSQuery\Legacy\RR\CNAME;
use JDWX\DNSQuery\Legacy\RR\MX;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Test the Resolver class. */
#[CoversClass( Resolver::class )]
final class ResolverTestDisabled extends TestCase {


    /** Check the results of a query for Google's MX server. */
    public static function googleMXResponseCheck( ?ResponsePacket $rsp ) : void {

        self::assertInstanceOf( ResponsePacket::class, $rsp );
        self::assertSame( QR::RESPONSE->value, $rsp->header->qr );
        self::assertCount( 1, $rsp->question );
        self::assertCount( 1, $rsp->answer );

        $mx = $rsp->answer[ 0 ];
        assert( $mx instanceof MX );
        self::assertSame( 10, $mx->preference );
        self::assertSame( 'smtp.google.com', $mx->exchange );

    }


    /** Test that CNAME queries return the CNAME and indirect RR both in the answer field,
     * as expected by default.
     *
     * @throws Exception
     */
    public function testCNAME() : void {
        $dns = new Resolver();
        $rsp = $dns->query( 'www.jdw.sx' );
        $foundCNAME = false;
        $foundA = false;
        $fromName = null;
        $toName = null;
        self::assertCount( 2, $rsp->answer );
        foreach ( $rsp->answer as $rr ) {
            if ( $rr instanceof CNAME ) {
                self::assertSame( 'www.jdw.sx', $rr->name );
                $fromName = $rr->cname;
                $foundCNAME = true;
            }
            if ( $rr instanceof A ) {
                self::assertSame( '204.13.89.4', $rr->address );
                $toName = $rr->name;
                $foundA = true;
            }
        }
        self::assertTrue( $foundCNAME );
        self::assertTrue( $foundA );
        self::assertSame( $fromName, $toName );

    }


    /** With strict query mode, the resolver should return a non-error response with no
     * answers when a CNAME is encountered.
     * @throws Exception
     */
    public function testCNAMEStrict() : void {
        $dns = ( new Resolver() )->setStrictQueryMode();
        $rsp = $dns->query( 'www.icann.org' );
        self::assertCount( 0, $rsp->answer );
        self::assertSame( Lookups::E_NONE, $rsp->header->rCode );
    }


    /**
     * @throws Exception
     */
    public function testDNSGetRecordDropIn() : void {
        $rExpected = dns_get_record( 'google.com', DNS_MX );
        $rActual = Resolver::dns_get_record( 'google.com', DNS_MX );
        $this->compareRRArrays( $rExpected, $rActual );

        $rExpected = dns_get_record( 'iana.org', DNS_A );
        $rActual = Resolver::dns_get_record( 'iana.org', DNS_A );
        $this->compareRRArrays( $rExpected, $rActual );

        $rExpected = dns_get_record( 'www.amazon.com', DNS_CNAME );
        $rActual = Resolver::dns_get_record( 'www.amazon.com', DNS_CNAME );
        $this->compareRRArrays( $rExpected, $rActual );

        $rExpected = dns_get_record( 'org.', DNS_SOA );
        assert( is_array( $rExpected ) );
        $rActual = Resolver::dns_get_record( 'org.', DNS_SOA );
        assert( is_array( $rActual ) );

        # This is not ideal, but serial numbers change frequently and if you are querying a round-robin
        # set of default resolvers, you may get a different serial number each time.
        $rExpected[ 0 ][ 'serial' ] = '0';
        $rActual[ 0 ][ 'serial' ] = '0';
        $this->compareRRArrays( $rExpected, $rActual );

    }


    /**
     * @throws Exception
     */
    public function testQueryCloudflarePublicDNS() : void {
        $ns = [ '1.1.1.1', '1.0.0.1' ];
        $dns = ( new Resolver() )->setNameServers( $ns );
        $result = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $result );
    }


    /**
     * @throws Exception
     */
    public function testQueryGooglePublicDNS() : void {

        $ns = [ '8.8.8.8', '8.8.4.4' ];
        $dns = new Resolver( $ns );
        $result = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $result );

    }


    /**
     * @throws Exception
     */
    public function testQueryNoServersSpecified() : void {
        $dns = new Resolver();
        $rsp = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $rsp );
    }


    /**
     * @throws Exception
     */
    public function testQueryTCP() : void {
        $dns = new Resolver( '1.1.1.1' );
        $dns->setUseTCP();
        $result = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $result );
    }


    /**
     * @param list<array<string, mixed>> $rExpected
     * @param list<array<string, mixed>> $rActual
     *
     * Helper to compare two sets of RRs.
     *
     * It zeros out the TTLs as they can vary between successive queries if you are querying a name
     * server (like 1.1.1.1) that is actually a cluster of servers.
     */
    private function compareRRArrays( array $rExpected, array $rActual ) : void {
        foreach ( $rActual as & $row ) {
            $row[ 'ttl' ] = 0;
        }
        foreach ( $rExpected as & $row ) {
            $row[ 'ttl' ] = 0;
        }
        self::assertSame( $rExpected, $rActual );
    }


}
