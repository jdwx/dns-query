<?php /** @noinspection PhpMethodNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Transport\Pool\PooledTransportWrapper;
use JDWX\DNSQuery\Transport\Pool\TransportPool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TransportPool::class )]
class TransportPoolTest extends TestCase {


    public function testAcquireCreatesNewConnection() : void {
        $pool = TransportPool::default();
        $transport1 = $pool->acquire( 'udp', '8.8.8.8' );
        self::assertSame( 1, $pool->count() );
        self::assertSame( 1, $pool->getActiveCount() );
        self::assertSame( 0, $pool->getIdleCount() );
        $pool->release( $transport1 );
    }


    public function testAcquireDifferentEndpointsCreatesSeparateConnections() : void {
        $pool = TransportPool::default();

        $transport1 = $pool->acquire( 'udp', '8.8.8.8' );
        $transport2 = $pool->acquire( 'udp', '1.1.1.1' );

        self::assertNotSame( $transport1, $transport2 );
        self::assertSame( 2, $pool->count() );
        self::assertSame( 2, $pool->getActiveCount() );
    }


    public function testAcquireReusesIdleConnection() : void {
        $pool = TransportPool::default();

        // Acquire and release a connection
        $transport1 = $pool->acquire( 'udp', '8.8.8.8' );
        $pool->release( $transport1 );

        // Acquire again - should get the same connection
        $transport2 = $pool->acquire( 'udp', '8.8.8.8' );

        self::assertSame( 1, $pool->count() );
        assert( $transport1 instanceof PooledTransportWrapper );
        assert( $transport2 instanceof PooledTransportWrapper );
        self::assertSame( $transport1->getConnection(), $transport2->getConnection() );
    }


    public function testDifferentTransportTypesCreateSeparateConnections() : void {
        $pool = TransportPool::default();

        $transport1 = $pool->acquire( 'udp', '8.8.8.8' );
        $transport2 = $pool->acquire( 'tcp', '8.8.8.8' );

        self::assertNotSame( $transport1, $transport2 );
        self::assertSame( 2, $pool->count() );
    }


    public function testFlushClearsAllConnections() : void {
        $pool = TransportPool::default();

        $pool->acquire( 'udp', '8.8.8.8' );
        $pool->acquire( 'udp', '1.1.1.1' );

        self::assertSame( 2, $pool->count() );

        $pool->flush();

        self::assertSame( 0, $pool->count() );
        self::assertSame( 0, $pool->getActiveCount() );
        self::assertSame( 0, $pool->getIdleCount() );
    }


    public function testReleaseReturnsConnectionToPool() : void {
        $pool = TransportPool::default();

        $transport1 = $pool->acquire( 'udp', '8.8.8.8' );
        $pool->release( $transport1 );

        self::assertSame( 1, $pool->count() );
        self::assertSame( 0, $pool->getActiveCount() );
        self::assertSame( 1, $pool->getIdleCount() );
    }


}