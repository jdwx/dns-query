<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Buffer;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( WriteBuffer::class )]
final class WriteBufferTest extends TestCase {


    public function testAppend() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo' );
        $wri->append( 'Bar', 'Baz' );
        self::assertSame( 'FooBarBaz', strval( $wri ) );
    }


    public function testClear() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo' );
        $wri->clear();
        self::assertSame( '', strval( $wri ) );
        $wri->append( 'Bar' );
        self::assertSame( 'Bar', strval( $wri ) );
    }


    public function testEnd() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo' );
        self::assertSame( 'Foo', $wri->end() );
        self::assertSame( '', strval( $wri ) );
    }


}
