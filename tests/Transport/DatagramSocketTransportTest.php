<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Transport\AbstractSocketTransport;
use JDWX\DNSQuery\Transport\DatagramSocketTransport;
use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\Socket\Socket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractSocketTransport::class )]
#[CoversClass( DatagramSocketTransport::class )]
final class DatagramSocketTransportTest extends TestCase {


    public function testReceive() : void {
        $udpDest = Socket::createBound( '127.0.0.1', 0, SOCK_DGRAM );
        $transport = TransportFactory::udp( '127.0.0.1', $udpDest->localPort(), 0, 1_000 );
        assert( $transport instanceof DatagramSocketTransport );
        $udpDest->sendTo( 'test data', null, '127.0.0.1', $transport->getSocket()->localPort() );
        $data = $transport->receive();
        self::assertSame( 'test data', $data );
    }


    public function testSend() : void {
        $udpDest = Socket::createBound( '127.0.0.1', 0, SOCK_DGRAM );
        $transport = TransportFactory::udp(
            '127.0.0.1', $udpDest->localPort(), null, null, '127.0.0.1'
        );
        $transport->send( 'test data' );
        self::assertSame( 'test data', $udpDest->read( 1024 ) );
    }


}
