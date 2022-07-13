<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Network\Socket;
use JDWX\DNSQuery\TransportManager;
use PHPUnit\Framework\TestCase;


class TransportManagerTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testTransportManager() {
        $mgr = new TransportManager( null, null, 5 );
        static::assertCount( 0, $mgr );
        $udp = $mgr->acquire( Socket::SOCK_DGRAM, "1.1.1.1", 53 );
        static::assertCount( 0, $mgr );
        $mgr->release( $udp );
        /** @noinspection PhpUndefinedFieldInspection */
        $udp->foo = true;
        static::assertCount( 1, $mgr );

        $udp = $mgr->acquire( Socket::SOCK_DGRAM, "1.1.1.1", 53 );
        static::assertTrue( $udp->foo );
        static::assertCount( 0, $mgr );
    }


    /**
     * @throws Exception
     */
    public function testTransportManagerDoubleUp() {
        $mgr = new TransportManager( null, null, 5 );
        $udp = $mgr->acquire( Socket::SOCK_DGRAM, "1.1.1.1", 53 );
        $udp2 = $mgr->acquire( Socket::SOCK_DGRAM, "1.1.1.1", 53 );
        static::assertCount( 0, $mgr );

        $mgr->release( $udp );
        static::assertCount( 1, $mgr );

        /** @noinspection PhpUndefinedFieldInspection */
        $udp2->foo = true;
        $mgr->release( $udp2 );
        static::assertCount( 1, $mgr );

        $udp3 = $mgr->acquire( Socket::SOCK_DGRAM, "1.1.1.1", 53 );
        /** @noinspection PhpUndefinedFieldInspection */
        static::assertTrue( $udp3->foo );
        static::assertCount( 0, $mgr );
    }


}