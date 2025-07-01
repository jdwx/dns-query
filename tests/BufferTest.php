<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Transport\Buffer;
use JDWX\DNSQuery\Transport\BufferInterface;
use JDWX\DNSQuery\Transport\BufferTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Buffer::class )]
final class BufferTest extends TestCase {


    public function testAppend() : void {
        $buffer = new Buffer( 'Foo' );
        self::assertSame( 3, $buffer->append( 'Bar' ) );
        self::assertSame( 'FooBar', $buffer->getData() );
        self::assertSame( 0, $buffer->tell() );
    }


    public function testAtEnd() : void {
        $buffer = new Buffer( 'Foo' );
        self::assertFalse( $buffer->atEnd() );
        $buffer->consume( 3 );
        self::assertTrue( $buffer->atEnd() );
    }


    public function testConstruct() : void {
        $buffer = new Buffer( 'FooBarBaz' );
        self::assertSame( 'FooBarBaz', $buffer->getData() );
        self::assertSame( 0, $buffer->tell() );
    }


    public function testConsume() : void {
        $buffer = new Buffer( 'FooBarBaz' );
        self::assertSame( 'Foo', $buffer->consume( 3 ) );
        self::assertSame( 'Bar', $buffer->consume( 3 ) );
        self::assertSame( 'Baz', $buffer->consume( 3 ) );
    }


    public function testConsumeIPv4() : void {
        $buffer = new Buffer( "\x01\x02\x03\x04" );
        self::assertSame( '1.2.3.4', $buffer->consumeIPv4() );
    }


    public function testConsumeIPv6() : void {
        $buffer = new Buffer( "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F" );
        self::assertSame( '1:203:405:607:809:a0b:c0d:e0f', $buffer->consumeIPv6() );
    }


    public function testConsumeLabel() : void {
        $buffer = new Buffer( "\x03Foo\x03Bar\x00" );
        self::assertSame( 'Foo', $buffer->consumeLabel() );
        self::assertSame( 'Bar', $buffer->consumeLabel() );
        self::assertSame( '', $buffer->consumeLabel() );
    }


    public function testConsumeName() : void {
        $buffer = new Buffer( "\x03Foo\x03Bar\x00" );
        self::assertSame( 'Foo.Bar', $buffer->consumeName() );
    }


    public function testConsumeNameArray() : void {
        $buffer = new Buffer( "\x03Foo\x07Bar.Baz\x03Qux\0\x04Quux\xc0\x04" );
        $r = $buffer->consumeNameArray();
        self::assertSame( [ 'Foo', 'Bar.Baz', 'Qux' ], $r );
        $r = $buffer->consumeNameArray();
        self::assertSame( [ 'Quux', 'Bar.Baz', 'Qux' ], $r );
        self::expectException( OutOfBoundsException::class );
        $buffer->consumeNameArray();
    }


    public function testConsumeUINT16() : void {
        $buffer = new Buffer( "\x01\x02\x03\x04\x05\x06\x07\x08" );
        self::assertSame( 0x102, $buffer->consumeUINT16() );
        self::assertSame( 0x304, $buffer->consumeUINT16() );
        self::assertSame( 0x506, $buffer->consumeUINT16() );
        self::assertSame( 0x708, $buffer->consumeUINT16() );
        self::expectException( OutOfBoundsException::class );
        $buffer->consumeUINT16();
    }


    public function testConsumeUINT32() : void {
        $buffer = new Buffer( "\x01\x02\x03\x04\x05\x06\x07\x08" );
        self::assertSame( 0x01020304, $buffer->consumeUINT32() );
        self::assertSame( 0x05060708, $buffer->consumeUINT32() );
        self::expectException( OutOfBoundsException::class );
        $buffer->consumeUINT32();
    }


    public function testConsumeUINT8() : void {
        $buffer = new Buffer( "\x01\x02\x03\x04" );
        self::assertSame( 1, $buffer->consumeUINT8() );
        self::assertSame( 2, $buffer->consumeUINT8() );
        self::assertSame( 3, $buffer->consumeUINT8() );
        self::assertSame( 4, $buffer->consumeUINT8() );
        self::expectException( OutOfBoundsException::class );
        $buffer->consumeUINT8();
    }


    public function testExpandNamePointer() : void {
        $buffer = new Buffer( "\x03Foo\x06BarBaz\x03Qux\0\x04Quux\xc0\x04" );
        $r = $buffer->expandNamePointer( 0 );
        self::assertSame( [ 'Foo', 'BarBaz', 'Qux' ], $r );
        $r = $buffer->expandNamePointer( 4 );
        self::assertSame( [ 'BarBaz', 'Qux' ], $r );
        $r = $buffer->expandNamePointer( 11 );
        self::assertSame( [ 'Qux' ], $r );
        $r = $buffer->expandNamePointer( 16 );
        self::assertSame( [ 'Quux', 'BarBaz', 'Qux' ], $r );
        self::expectException( OutOfBoundsException::class );
        $buffer->expandNamePointer( 100 );
    }


    public function testExpandNamePointerForLoop() : void {
        $buffer = new Buffer( "\x03Foo\x06BarBaz\x03Qux\0\x04Quux\xc0\x10" ); // Pointer points to itself
        self::expectException( InvalidArgumentException::class );
        $buffer->expandNamePointer( 16 );
    }


    public function testExpandNamePointerForLoop2() : void {
        $buffer = new Buffer( "\x03Foo\xC0\x00" ); // Pointer points to start
        self::expectException( InvalidArgumentException::class );
        $buffer->expandNamePointer( 0 );
    }


    public function testSeek() : void {
        $buffer = new Buffer( 'FooBarBaz' );
        self::assertSame( 0, $buffer->tell() );

        $st = $buffer->consume( 3 );
        self::assertSame( 'Foo', $st );

        $buffer->seek( 0 );
        $st = $buffer->consume( 3 );
        self::assertSame( 'Foo', $st );

        $buffer->seek( -3, SEEK_CUR );
        self::assertSame( 0, $buffer->tell() );
        $st = $buffer->consume( 3 );
        self::assertSame( 'Foo', $st );

        $buffer->seek( -3, SEEK_END );
        self::assertSame( 6, $buffer->tell() );
        $st = $buffer->consume( 3 );
        self::assertSame( 'Baz', $st );

        $buffer->seek( -100000 );
        self::assertSame( 0, $buffer->tell() );

        self::expectException( InvalidArgumentException::class );
        $buffer->seek( 0, -1 );
    }


    public function testTryFillForData() : void {
        $buffer = new class( 'Foo', 'Bar' ) implements BufferInterface {


            use BufferTrait;


            public function __construct( string $i_stData, private ?string $nstMoreData ) {
                $this->stData = $i_stData;
            }


            protected function fetchData() : ?string {
                $nst = $this->nstMoreData;
                $this->nstMoreData = null;
                return $nst;
            }


        };
        self::assertSame( 'FooBar', $buffer->consume( 6 ) );
    }


    public function testTryFillForNoData() : void {
        $buffer = new Buffer( 'Foo' );
        self::assertSame( 'Foo', $buffer->consume( 3 ) );
        self::expectException( OutOfBoundsException::class );
        $buffer->consume( 3 );
    }


}
