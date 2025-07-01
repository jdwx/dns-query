<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Transport\SocketTransport;
use JDWX\DNSQuery\Transport\TransportBuffer;
use JDWX\Socket\Socket;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TransportBuffer::class )]
final class TransferBufferTest extends TestCase {


    public function testConsume() : void {
        [ $local, $remote ] = Socket::createPair();
        $sock = new SocketTransport( $local, 0, 10_000 );
        $remote->write( 'FooBar' );
        $buffer = new TransportBuffer( $sock );
        self::assertSame( 'Foo', $buffer->consume( 3 ) );
        self::assertSame( 'Bar', $buffer->consume( 3 ) );
        self::expectException( \OutOfBoundsException::class );
        self::assertSame( '', $buffer->consume( 3 ) );

    }


}
