<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Exceptions\TransportException;
use JDWX\DNSQuery\Transport\AbstractSocketTransport;
use JDWX\DNSQuery\Transport\StreamSocketTransport;
use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\Socket\Socket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractSocketTransport::class )]
#[CoversClass( StreamSocketTransport::class )]
final class StreamSocketTransportTest extends TestCase {


    public function testSendForTcp() : void {
        $tcpDest = Socket::createBound( '127.0.0.1' );
        $transport = TransportFactory::tcp(
            '127.0.0.1', $tcpDest->localPort(), null, null, '127.0.0.1'
        );
        $transport->send( 'test data' );
        $sock = $tcpDest->accept();
        self::assertSame( "\x00\x09", $sock->readTimed( 2, 0, 1_000 ) );
        self::assertSame( 'test data', $sock->readTimed( 10, 0, 1 ) );
    }


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


}
