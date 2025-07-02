<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Network\Socket;
use JDWX\DNSQuery\Legacy\Network\TransportManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Test the TransportManager class. */
#[CoversClass( TransportManager::class )]
final class TransportManagerTestDisabled extends TestCase {


    /**
     * @throws Exception
     * @suppress PhanUndeclaredProperty
     */
    public function testTransportManager() : void {
        $mgr = new TransportManager( null, null, 5 );
        self::assertCount( 0, $mgr );
        $udp = $mgr->acquire( Socket::SOCK_DGRAM, '1.1.1.1', 53 );
        self::assertCount( 0, $mgr );
        $mgr->release( $udp );
        /**
         * @noinspection PhpDynamicFieldDeclarationInspection
         * @phpstan-ignore property.notFound
         */
        $udp->foo = true;
        self::assertCount( 1, $mgr );

        $udp = $mgr->acquire( Socket::SOCK_DGRAM, '1.1.1.1', 53 );
        /** @phpstan-ignore property.notFound */
        self::assertTrue( $udp->foo );
        self::assertCount( 0, $mgr );
    }


    /**
     * @throws Exception
     * @suppress PhanUndeclaredProperty
     */
    public function testTransportManagerDoubleUp() : void {
        $mgr = new TransportManager( null, null, 5 );
        $udp = $mgr->acquire( Socket::SOCK_DGRAM, '1.1.1.1', 53 );
        $udp2 = $mgr->acquire( Socket::SOCK_DGRAM, '1.1.1.1', 53 );
        self::assertCount( 0, $mgr );

        $mgr->release( $udp );
        self::assertCount( 1, $mgr );

        /**
         * @noinspection PhpDynamicFieldDeclarationInspection
         * @phpstan-ignore property.notFound
         */
        $udp2->foo = true;
        $mgr->release( $udp2 );
        self::assertCount( 1, $mgr );

        $udp3 = $mgr->acquire( Socket::SOCK_DGRAM, '1.1.1.1', 53 );
        /**
         * @noinspection PhpUndefinedFieldInspection
         * @phpstan-ignore property.notFound
         */
        self::assertTrue( $udp3->foo );
        self::assertCount( 0, $mgr );
    }


}