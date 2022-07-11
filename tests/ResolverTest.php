<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\TestCase;


class ResolverTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testQueryNoServersSpecified() {
        $dns = new Resolver();
        $result = $dns->query( 'google.com', 'mx' );
        $this->googleMXResponseCheck( $result );
    }


    /**
     * @throws Exception
     */
    public function testQueryGooglePublicDNS() : void {

        $ns = [ '8.8.8.8', '8.8.4.4' ];
        $dns = new Resolver( $ns );
        $result = $dns->query( 'google.com', 'mx' );
        $this->googleMXResponseCheck( $result );

    }


    /**
     * @throws Exception
     */
    public function testQueryCloudflarePublicDNS() : void {
        $ns = [ '1.1.1.1', '1.0.0.1' ];
        $dns = ( new Resolver() )->setNameServers( $ns );
        $result = $dns->query( 'google.com', 'mx' );
        $this->googleMXResponseCheck( $result );
    }


    private function googleMXResponseCheck( ResponsePacket $result ) :void {

        static::assertInstanceOf( ResponsePacket::class, $result );

        static::assertSame( Lookups::QR_RESPONSE, $result->header->qr );

        static::assertIsArray( $result->question );
        static::assertCount( 1, $result->question );

        static::assertIsArray( $result->answer );
        static::assertCount( 1, $result->answer );

        $mx = $result->answer[0];
        assert( $mx instanceof MX );

        static::assertInstanceOf( MX::class, $mx );
        static::assertSame( 10, $mx->preference);
        static::assertSame( 'smtp.google.com', $mx->exchange );

    }


}
