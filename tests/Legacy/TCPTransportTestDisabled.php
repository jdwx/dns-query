<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Network\TCPTransport;
use JDWX\DNSQuery\Legacy\Packet\RequestPacket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Socket;


/** Additional coverage tests for the TCPTransport class. */
#[CoversClass( TCPTransport::class )]
final class TCPTransportTestDisabled extends TestCase {


    /**
     * @throws Exception
     */
    public function testTCPTransport() : void {
        $req = new RequestPacket( 'google.com', 'MX' );
        $tcp = new TCPTransport( '1.1.1.1' );
        $tcp->sendRequest( $req );
        $rsp = $tcp->receiveResponse();
        ResolverTestDisabled::googleMXResponseCheck( $rsp );
    }


    /**
     * @throws \Exception
     */
    public function testTCPTransportSocketReadTooShort() : void {
        $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
        $port = random_int( 2048, 65535 );
        socket_bind( $socket, '127.0.0.1', $port );
        socket_listen( $socket );

        $req = new RequestPacket( 'google.com', 'MX' );
        $udp = new TCPTransport( '127.0.0.1', $port );

        $socketClient = socket_accept( $socket );
        assert( $socketClient instanceof Socket );

        $udp->sendRequest( $req );
        socket_recv( $socketClient, $buf, 1024, 0 );
        socket_send( $socketClient, pack( 'n', 5 ), 2, 0 );
        socket_send( $socketClient, 'Nope!', 5, 0 );
        socket_close( $socketClient );
        socket_close( $socket );

        self::expectException( Exception::class );
        $udp->receiveResponse();
    }


}

