<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Exceptions\TransportException;
use JDWX\DNSQuery\Transport\DatagramSocketTransport;
use JDWX\DNSQuery\Transport\SocketTransport;
use JDWX\DNSQuery\Transport\StreamSocketTransport;
use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\Socket\Socket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( SocketTransport::class )]
final class SocketTransportTest extends TestCase {


    public function testSendForTimeout() : void {
        $stPath = tempnam( sys_get_temp_dir(), 'test-sock.' );
        unlink( $stPath );
        $sock = Socket::createBound( $stPath );
        $transport = TransportFactory::unix( $stPath, SOCK_STREAM, 0, 10_000 );
        assert( $transport instanceof StreamSocketTransport );
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
        $transport = TransportFactory::udp( '127.0.0.1', $uPort );
        assert( $transport instanceof DatagramSocketTransport );
        $transport->setTimeout( 0, 10_000 );
        self::assertNull( $transport->receive() );
    }


}
