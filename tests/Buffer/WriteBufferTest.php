<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Buffer;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use OutOfBoundsException;
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


    public function testAppendEmptyString() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo', '', 'Bar' );
        self::assertSame( 'FooBar', strval( $wri ) );
    }


    public function testAppendInteger() : void {
        $wri = new WriteBuffer();
        $wri->append( 123 );
        $wri->append( 456, 789 );
        self::assertSame( '123456789', strval( $wri ) );
    }


    public function testAppendMixed() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Test', 123, 'Mix', 456 );
        self::assertSame( 'Test123Mix456', strval( $wri ) );
    }


    public function testAppendReturnsOffset() : void {
        $wri = new WriteBuffer();
        $offset1 = $wri->append( 'Hello' );
        $offset2 = $wri->append( 'World' );
        $offset3 = $wri->append( '!' );

        self::assertSame( 0, $offset1 );
        self::assertSame( 5, $offset2 );
        self::assertSame( 10, $offset3 );
    }


    public function testClear() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo' );
        $wri->clear();
        self::assertSame( '', strval( $wri ) );
        $wri->append( 'Bar' );
        self::assertSame( 'Bar', strval( $wri ) );
    }


    public function testClearEmptyBuffer() : void {
        $wri = new WriteBuffer();
        $wri->clear();
        self::assertSame( '', strval( $wri ) );
    }


    public function testComplexScenario() : void {
        $wri = new WriteBuffer();

        // Build a buffer
        $wri->append( 'Header:' );
        $offset2 = $wri->append( '0000' );
        $wri->append( ':Data' );

        // Update the counter
        $wri->set( $offset2, '1234' );
        self::assertSame( 'Header:1234:Data', strval( $wri ) );

        // Extract parts
        $header = $wri->shift( 7 );
        self::assertSame( 'Header:', $header );
        self::assertSame( '1234:Data', strval( $wri ) );

        // Get the rest
        $remainder = $wri->end();
        self::assertSame( '1234:Data', $remainder );
        self::assertSame( '', strval( $wri ) );
    }


    public function testEnd() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Foo' );
        self::assertSame( 'Foo', $wri->end() );
        self::assertSame( '', strval( $wri ) );
    }


    public function testEndEmptyBuffer() : void {
        $wri = new WriteBuffer();
        self::assertSame( '', $wri->end() );
        self::assertSame( '', strval( $wri ) );
    }


    public function testEndMultipleCalls() : void {
        $wri = new WriteBuffer();
        $wri->append( 'First' );
        self::assertSame( 'First', $wri->end() );

        $wri->append( 'Second' );
        self::assertSame( 'Second', $wri->end() );

        self::assertSame( '', $wri->end() );
    }


    public function testLength() : void {
        $wri = new WriteBuffer();
        self::assertSame( 0, $wri->length() );

        $wri->append( 'Hello' );
        self::assertSame( 5, $wri->length() );

        $wri->append( ' World' );
        self::assertSame( 11, $wri->length() );

        $wri->clear();
        self::assertSame( 0, $wri->length() );
    }


    public function testLengthWithMultibyteCharacters() : void {
        $wri = new WriteBuffer();
        $wri->append( '€' ); // 3 bytes in UTF-8
        self::assertSame( 3, $wri->length() );

        $wri->append( '你好' ); // 6 bytes in UTF-8 (3 bytes each)
        self::assertSame( 9, $wri->length() );
    }


    public function testSet() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello World' );

        $wri->set( 6, 'PHP' );
        self::assertSame( 'Hello PHPld', strval( $wri ) );
    }


    public function testSetAtBeginning() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $wri->set( 0, 'Bye' );
        self::assertSame( 'Byelo', strval( $wri ) );
    }


    public function testSetAtEnd() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $wri->set( 5, ' World' );
        self::assertSame( 'Hello World', strval( $wri ) );
    }


    public function testSetNegativeOffset() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $this->expectException( OutOfBoundsException::class );
        $this->expectExceptionMessage( 'Offset -1 is out of bounds.' );
        $wri->set( -1, 'X' );
    }


    public function testSetOffsetTooLarge() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $this->expectException( OutOfBoundsException::class );
        $this->expectExceptionMessage( 'Offset 10 is out of bounds.' );
        $wri->set( 10, 'X' );
    }


    public function testSetWithInteger() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Test 000' );

        $wri->set( 5, '123' );
        self::assertSame( 'Test 123', strval( $wri ) );
    }


    public function testShift() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello World' );

        $shifted = $wri->shift( 6 );
        self::assertSame( 'Hello ', $shifted );
        self::assertSame( 'World', strval( $wri ) );
    }


    public function testShiftEntireBuffer() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $shifted = $wri->shift( 5 );
        self::assertSame( 'Hello', $shifted );
        self::assertSame( '', strval( $wri ) );
    }


    public function testShiftLengthTooLarge() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $this->expectException( OutOfBoundsException::class );
        $this->expectExceptionMessage( 'Length 10 is out of bounds.' );
        $wri->shift( 10 );
    }


    public function testShiftMultipleTimes() : void {
        $wri = new WriteBuffer();
        $wri->append( 'ABCDEFGHIJ' );

        $part1 = $wri->shift( 3 );
        $part2 = $wri->shift( 3 );
        $part3 = $wri->shift( 4 );

        self::assertSame( 'ABC', $part1 );
        self::assertSame( 'DEF', $part2 );
        self::assertSame( 'GHIJ', $part3 );
        self::assertSame( '', strval( $wri ) );
    }


    public function testShiftNegativeLength() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $this->expectException( OutOfBoundsException::class );
        $this->expectExceptionMessage( 'Length -1 is out of bounds.' );
        $wri->shift( -1 );
    }


    public function testShiftZeroLength() : void {
        $wri = new WriteBuffer();
        $wri->append( 'Hello' );

        $shifted = $wri->shift( 0 );
        self::assertSame( '', $shifted );
        self::assertSame( 'Hello', strval( $wri ) );
    }


    public function testToString() : void {
        $wri = new WriteBuffer();
        self::assertSame( '', (string) $wri );

        $wri->append( 'Test String' );
        self::assertSame( 'Test String', (string) $wri );
    }


}