<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Transport;


use JDWX\DNSQuery\Transport\PseudowireTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( PseudowireTransport::class )]
final class PseudowireTransportTest extends TestCase {


    public function testReceive() : void {
        $transport = new PseudowireTransport();
        $transport->sendFarEnd( 'test data' );
        self::assertSame( 'test data', $transport->receive() );

        self::assertNull( $transport->receive() );
    }


    public function testReceiveForTooBig() : void {
        $transport = new PseudowireTransport();
        $transport->sendFarEnd( 'FooBarBaz' );
        self::assertSame( 'Foo', $transport->receive( 3 ) );
        self::assertSame( 'Bar', $transport->receive( 3 ) );
        self::assertSame( 'Baz', $transport->receive( 3 ) );
    }


    public function testSend() : void {
        $transport = new PseudowireTransport();
        $transport->send( 'test data' );
        self::assertSame( 'test data', $transport->receiveFarEnd() );
    }


}
