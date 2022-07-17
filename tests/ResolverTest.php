<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\TestCase;


/** Test the Resolver class. */
class ResolverTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testQueryNoServersSpecified() {
        $dns = new Resolver();
        $rsp = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $rsp );
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
    public function testQueryCloudflarePublicDNS() : void {
        $ns = [ '1.1.1.1', '1.0.0.1' ];
        $dns = ( new Resolver() )->setNameServers( $ns );
        $result = $dns->query( 'google.com', 'mx' );
        self::googleMXResponseCheck( $result );
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
     * @throws Exception
     */
    public function testDNSGetRecordDropIn() : void {
        $rExpected = dns_get_record( 'google.com', DNS_MX );
        $rActual = Resolver::dns_get_record( 'google.com', DNS_MX );
        $this->compareRRArrays( $rExpected, $rActual );

        $rExpected = dns_get_record( 'google.com', DNS_A );
        $rActual = Resolver::dns_get_record( 'google.com', DNS_A );
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


    /** Helper to compare two sets of RRs.
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
        static::assertSame( $rExpected, $rActual );
    }


    /** Check the results of a query for Google's MX server. */
    public static function googleMXResponseCheck( ResponsePacket $rsp ) :void {

        static::assertInstanceOf( ResponsePacket::class, $rsp );

        static::assertSame( Lookups::QR_RESPONSE, $rsp->header->qr );

        static::assertIsArray( $rsp->question );
        static::assertCount( 1, $rsp->question );

        static::assertIsArray( $rsp->answer );
        static::assertCount( 1, $rsp->answer );

        $mx = $rsp->answer[0];
        assert( $mx instanceof MX );

        static::assertInstanceOf( MX::class, $mx );
        static::assertSame( 10, $mx->preference);
        static::assertSame( 'smtp.google.com', $mx->exchange );

    }


}
