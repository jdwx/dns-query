<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Exceptions\TransportException;
use JDWX\DNSQuery\Transport\AbstractTransport;
use JDWX\DNSQuery\Transport\SocketTransport;
use JDWX\Socket\Socket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractTransport::class )]
#[CoversClass( SocketTransport::class )]
final class SocketTransportTest extends TestCase {


    public function testReceive() : void {
        $udpDest = Socket::createBound( '127.0.0.1', 0, SOCK_DGRAM );
        $transport = SocketTransport::udp( '127.0.0.1', $udpDest->localPort() );
        $udpDest->sendTo( 'test data', null, '127.0.0.1', $transport->getSocket()->localPort() );
        $data = $transport->receive();
        self::assertSame( 'test data', $data );
    }


    public function testSend() : void {
        $udpDest = Socket::createBound( '127.0.0.1', 0, SOCK_DGRAM );
        $transport = SocketTransport::udp(
            '127.0.0.1', $udpDest->localPort(), null, null, '127.0.0.1'
        );
        $transport->send( 'test data' );
        self::assertSame( 'test data', $udpDest->read( 1024 ) );
    }


    public function testSendForTcp() : void {
        $tcpDest = Socket::createBound( '127.0.0.1' );
        $transport = SocketTransport::tcp(
            '127.0.0.1', $tcpDest->localPort(), null, null, '127.0.0.1'
        );
        $transport->send( 'test data' );
        $sock = $tcpDest->accept();
        self::assertSame( 'test data', $sock->read( 1024 ) );
    }


    public function testSendForTimeout() : void {
        $stPath = tempnam( sys_get_temp_dir(), 'test-sock.' );
        unlink( $stPath );
        $sock = Socket::createBound( $stPath );
        $transport = SocketTransport::unix( $stPath, SOCK_STREAM, 0, 10_000 );
        $sock1 = $transport->getSocket();
        $sock1->setNonBlock();
        $st = str_repeat( 'Whatever', 8192 );
        $sock1->write( $st );
        self::expectException( TransportException::class );
        $transport->send( 'Nope!' );
        unlink( $stPath );
        unset( $sock );
    }


    public function testSetTimeout() : void {
        $uPort = 32768 + random_int( 0, 20000 );
        $transport = SocketTransport::udp( '127.0.0.1', $uPort );
        $transport->setTimeout( 0, 10_000 );
        self::assertNull( $transport->receive() );
    }


}
