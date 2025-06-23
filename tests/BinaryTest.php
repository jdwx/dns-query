<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\Binary;
use JDWX\Strict\OK;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Binary::class )]
final class BinaryTest extends TestCase {


    public function testConsume() : void {
        $st = 'SomeData';
        $uOffset = 0;
        self::assertSame( 'Some', Binary::consume( $st, $uOffset, 4 ) );
        self::assertSame( 'Data', Binary::consume( $st, $uOffset, 4 ) );
        self::assertSame( 8, $uOffset );
        self::expectException( \OutOfBoundsException::class );
        Binary::consume( $st, $uOffset, 1 );
    }


    public function testConsumeNameLabel() : void {
        $st = "\x04test\xc0\x0c\x00";
        $uOffset = 0;
        self::assertSame( "\x04test", Binary::consumeNameLabel( $st, $uOffset ) );
        self::assertSame( "\xc0\x0c", Binary::consumeNameLabel( $st, $uOffset ) );
        self::assertSame( "\0", Binary::consumeNameLabel( $st, $uOffset ) );
        self::assertSame( 8, $uOffset );
        self::expectException( \OutOfBoundsException::class );
        Binary::consumeNameLabel( $st, $uOffset );
    }


    public function testConsumeUINT16() : void {
        $st = OK::pack( 'n', 12345 ) . OK::pack( 'n', 6789 );
        $uOffset = 0;
        self::assertSame( 12345, Binary::consumeUINT16( $st, $uOffset ) );
        self::assertSame( 6789, Binary::consumeUINT16( $st, $uOffset ) );
        self::assertSame( 4, $uOffset );
        self::expectException( \OutOfBoundsException::class );
        Binary::consumeUINT16( $st, $uOffset );
    }


    public function testConsumeUINT32() : void {
        $st = OK::pack( 'N', 123456789 ) . OK::pack( 'N', 2 );
        $uOffset = 0;
        self::assertSame( 123456789, Binary::consumeUINT32( $st, $uOffset ) );
        self::assertSame( 2, Binary::consumeUINT32( $st, $uOffset ) );
        self::assertSame( 8, $uOffset );
        self::expectException( \OutOfBoundsException::class );
        Binary::consumeUINT32( $st, $uOffset );
    }


    public function testPackLabel() : void {
        self::assertSame( "\x04test", Binary::packLabel( 'test' ) );
        self::expectException( \LengthException::class );
        Binary::packLabel( str_repeat( 'Nope', 16 ) );
    }


    public function testPackLabelForEmpty() : void {
        self::expectException( \LengthException::class );
        Binary::packLabel( '' );
    }


    public function testPackNameUncompressed() : void {
        self::assertSame( "\x03Foo\x06BarBaz\x03Qux\0", Binary::packNameUncompressed( 'Foo.BarBaz.Qux' ) );
        $r = [ 'BarBaz.Qux' => 0x123 ];
        self::assertSame( "\x03Foo\xc1\x23", Binary::packName( 'Foo.BarBaz.Qux', $r, 0 ) );
        self::expectException( \LengthException::class );
        Binary::packNameUncompressed( str_repeat( 'a', 64 ) . '.com' );
    }


    public function testPackPointer() : void {
        self::assertSame( "\xc0\x0c", Binary::packPointer( 12 ) );
        self::assertSame( "\xc1\x23", Binary::packPointer( 0x123 ) );
        self::expectException( \OutOfRangeException::class );
        Binary::packPointer( 0x4000 );
    }


    public function testPackUINT16() : void {
        self::assertSame( OK::pack( 'n', 12345 ), Binary::packUINT16( 12345 ) );
    }


    public function testPackUINT32() : void {
        self::assertSame( OK::pack( 'N', 123456789 ), Binary::packUINT32( 123456789 ) );
    }


    public function testSplitLabels() : void {
        $r = iterator_to_array( Binary::splitLabels( 'foo.bar.baz.qux' ) );
        self::assertSame( 'foo', array_key_first( $r ) );
        self::assertSame( 'foo.bar.baz.qux', array_shift( $r ) );
        self::assertSame( 'bar', array_key_first( $r ) );
        self::assertSame( 'bar.baz.qux', array_shift( $r ) );
        self::assertSame( 'baz', array_key_first( $r ) );
        self::assertSame( 'baz.qux', array_shift( $r ) );
        self::assertSame( 'qux', array_key_first( $r ) );
        self::assertSame( 'qux', array_shift( $r ) );
        self::assertNull( array_key_first( $r ) );
    }


    public function testUnpackPointer() : void {
        self::assertSame( 0x123, Binary::unpackPointer( "\xC1\x23" ) );
        self::assertNull( Binary::unpackPointer( "\0" ) );
        self::assertSame( 16383, Binary::unpackPointer( "\xFF\xFF" ) );
        self::assertNull( Binary::unpackPointer( "\x04test" ) );
    }


    public function testUnpackUINT16() : void {
        self::assertSame( 0x1234, Binary::unpackUINT16( "\x12\x34" ) );
        self::assertSame( 0x5678, Binary::unpackUINT16( "\x12\x34\x56\x78", 2 ) );
        self::expectException( \OutOfBoundsException::class );
        Binary::unpackUINT16( "\x12" );
    }


    public function testUnpackUINT32() : void {
        self::assertSame( 0x12345678, Binary::unpackUINT32( "\x12\x34\x56\x78" ) );
        self::assertSame( 0x9ABCDEF0, Binary::unpackUINT32( "\x12\x34\x56\x78\x9A\xBC\xDE\xF0", 4 ) );
        self::expectException( \OutOfBoundsException::class );
        Binary::unpackUINT32( "\x12\x34\x56" );
    }


}
