<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use InvalidArgumentException;
use JDWX\DNSQuery\Binary;
use JDWX\Strict\OK;
use LengthException;
use OutOfBoundsException;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Binary::class )]
final class BinaryTest extends TestCase {


    public function testPackIPv4() : void {
        self::assertSame( "\x01\x02\x03\x04", Binary::packIPv4( '1.2.3.4' ) );
        self::expectException( InvalidArgumentException::class );
        Binary::packIPv4( 'invalid-ipv4' );
    }


    public function testPackIPv6() : void {
        self::assertSame(
            "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01",
            Binary::packIPv6( '2001:db8::1' )
        );
        self::expectException( InvalidArgumentException::class );
        Binary::packIPv6( 'invalid-ipv6' );
    }


    public function testPackLabel() : void {
        self::assertSame( "\x04test", Binary::packLabel( 'test' ) );
        self::expectException( LengthException::class );
        Binary::packLabel( str_repeat( 'Nope', 16 ) );
    }


    public function testPackLabelForEmpty() : void {
        self::expectException( LengthException::class );
        Binary::packLabel( '' );
    }


    public function testPackLabels() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $r = Binary::packLabels( [ 'foo', 'bar.baz', 'qux' ], $rLabelMap, $uOffset );
        self::assertSame( "\x03foo\x07bar.baz\x03qux\0", $r );
        $r = Binary::packLabels( [ 'test', 'bar.baz', 'qux' ], $rLabelMap, $uOffset );
        self::assertSame( "\x04test\xC0\x04", $r );
    }


    public function testPackName() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $st = Binary::packName( 'Foo.BarBaz.Qux', $rLabelMap, $uOffset );
        self::assertSame( "\x03Foo\x06BarBaz\x03Qux\0", $st );
        $uOffset += strlen( $st );
        $st = Binary::packName( 'Quux.BarBaz.Qux', $rLabelMap, $uOffset );
        self::assertSame( "\x04Quux\xc0\x04", $st );
        $uOffset += strlen( $st );
        self::assertSame( [ 'Foo.BarBaz.Qux' => 0, 'BarBaz.Qux' => 4, 'Qux' => 11, 'Quux.BarBaz.Qux' => 16 ], $rLabelMap );
        self::expectException( LengthException::class );
        Binary::packName( str_repeat( 'a', 64 ) . '.com', $rLabelMap, $uOffset );
    }


    public function testPackNameUncompressed() : void {
        self::assertSame( "\x03Foo\x06BarBaz\x03Qux\0", Binary::packNameUncompressed( 'Foo.BarBaz.Qux' ) );
        $r = [ 'BarBaz.Qux' => 0x123 ];
        self::assertSame( "\x03Foo\xc1\x23", Binary::packNameUncompressed( 'Foo.BarBaz.Qux', $r ) );
        self::expectException( LengthException::class );
        Binary::packNameUncompressed( str_repeat( 'a', 64 ) . '.com' );
    }


    public function testPackPointer() : void {
        self::assertSame( "\xc0\x0c", Binary::packPointer( 12 ) );
        self::assertSame( "\xc1\x23", Binary::packPointer( 0x123 ) );
        self::expectException( OutOfRangeException::class );
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


    public function testSplitLabelsArray() : void {
        $r = iterator_to_array( Binary::splitLabelsArray( [ 'foo', 'bar.baz', 'qux' ] ) );
        self::assertSame( 'foo', array_key_first( $r ) );
        self::assertSame( [ 'foo', 'bar.baz', 'qux' ], array_shift( $r ) );
        self::assertSame( 'bar.baz', array_key_first( $r ) );
        self::assertSame( [ 'bar.baz', 'qux' ], array_shift( $r ) );
        self::assertSame( 'qux', array_key_first( $r ) );
        self::assertSame( [ 'qux' ], array_shift( $r ) );
        self::assertNull( array_key_first( $r ) );
    }


    public function testUnpackIPv4() : void {
        self::assertSame( '1.2.3.4', Binary::unpackIPv4( "\x01\x02\x03\x04" ) );
        self::expectException( OutOfBoundsException::class );
        Binary::unpackIPv4( "\x01\x02\x03" );
    }


    public function testUnpackIPv6() : void {
        self::assertSame( '2001:db8::1', Binary::unpackIPv6( "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01" ) );
        self::expectException( OutOfBoundsException::class );
        Binary::unpackIPv6( "\x20\x01\x0d\xb8" );
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
        self::expectException( OutOfBoundsException::class );
        Binary::unpackUINT16( "\x12" );
    }


    public function testUnpackUINT32() : void {
        self::assertSame( 0x12345678, Binary::unpackUINT32( "\x12\x34\x56\x78" ) );
        self::assertSame( 0x9ABCDEF0, Binary::unpackUINT32( "\x12\x34\x56\x78\x9A\xBC\xDE\xF0", 4 ) );
        self::expectException( OutOfBoundsException::class );
        Binary::unpackUINT32( "\x12\x34\x56" );
    }


}
