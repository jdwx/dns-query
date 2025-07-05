<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\ResourceRecord\AbstractRData;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractRData::class )]
#[CoversClass( RData::class )]
final class RDataTest extends TestCase {


    public function testConstruct() : void {
        $rMap = [
            'foo' => RDataType::UINT8,
            'bar' => RDataType::UINT16,
        ];
        $rValues = [
            'foo' => 123,
            'bar' => 12345,
        ];
        $rdata = new RData( $rMap, $rValues );
        self::assertSame( 123, $rdata[ 'foo' ] );
        self::assertSame( 12345, $rdata[ 'bar' ] );
    }


    public function testConstructForMissingValue() : void {
        self::expectException( RecordDataException::class );
        $x = new RData( 'A', [] );
        unset( $x );
    }


    public function testConstructForRR() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600, [ 'address' => '1.2.3.4' ] );
        $rValues = [ 'address' => '2.3.4.5' ];
        $rdata = new RData( $rr, $rValues );
        self::assertSame( '2.3.4.5', $rdata[ 'address' ] );
    }


    public function testCount() : void {
        $rMap = [
            'foo' => RDataType::UINT8,
            'bar' => RDataType::UINT16,
        ];
        $rValues = [
            'foo' => 123,
            'bar' => 12345,
        ];
        $rdata = new RData( $rMap, $rValues );
        self::assertCount( 2, $rdata );
    }


    public function testFromParsedString() : void {
        $rMap = [
            'foo' => RDataType::UINT8,
            'bar' => RDataType::UINT16,
        ];
        $rParsedStrings = [ '123', '12345' ];
        $rdata = RData::fromParsedString( $rMap, $rParsedStrings );
        self::assertSame( 123, $rdata[ 'foo' ] );
        self::assertSame( 12345, $rdata[ 'bar' ] );

        self::expectException( RecordDataException::class );
        RData::fromParsedString( $rMap, [] );
    }


    public function testFromParsedStringForExtraData() : void {
        $rMap = [
            'foo' => RDataType::UINT8,
            'bar' => RDataType::UINT16,
        ];
        $rParsedStrings = [ '123', '12345', 'extra' ];
        self::expectException( RecordDataException::class );
        RData::fromParsedString( $rMap, $rParsedStrings );
    }


    public function testGetValue() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertSame( '1.2.3.4', $rData->getValue( 'address' ) );
    }


    public function testGetValueForInvalidKey() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::expectException( RecordDataException::class );
        $rData->getValue( 'nonexistent' );
    }


    public function testHasValue() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertTrue( $rData->hasValue( 'address' ) );
        self::assertFalse( $rData->hasValue( 'nonexistent' ) );
    }


    public function testMap() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        $rMap = $rData->map();
        self::assertSame( [ 'address' => RDataType::IPv4Address ], $rMap );
    }


    public function testNormalize() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        self::assertInstanceOf( RData::class, $rData );
        self::assertSame( '1.2.3.4', $rData[ 'address' ] );

        $x = RData::normalize( 'A', $rData );
        self::assertSame( $rData, $x );

        $x = RData::normalize( 999, 'Foo' );
        self::assertInstanceOf( OpaqueRData::class, $x );
        self::assertSame( 'Foo', $x[ 'rdata' ] );
    }


    public function testNormalizeForNoMap() : void {
        self::expectException( RecordDataException::class );
        RData::normalize( 999, [ 'foo' => 'bar' ] );
    }


    public function testOffsetExists() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertTrue( isset( $rData[ 'address' ] ) );
        self::assertFalse( isset( $rData[ 'nonexistent' ] ) );
    }


    public function testOffsetGet() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertSame( '1.2.3.4', $rData[ 'address' ] );
        self::assertNull( $rData[ 'nonexistent' ] );
    }


    public function testOffsetSet() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        $rData[ 'address' ] = '2.3.4.5';
        self::assertSame( '2.3.4.5', $rData->rDataValues[ 'address' ] );
    }


    public function testOffsetSetForInvalidKey() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::expectException( RecordDataException::class );
        $rData[ 'nonexistent' ] = 'value';
    }


    public function testOffsetUnset() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::expectException( \LogicException::class );
        unset( $rData[ 'address' ] );
    }


    public function testSetValue() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        $rData->setValue( 'address', '2.3.4.5' );
        self::assertSame( '2.3.4.5', $rData->rDataValues[ 'address' ] );
    }


    public function testSetValueForInvalidKey() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::expectException( RecordDataException::class );
        $rData->setValue( 'nonexistent', 'value' );
    }


    public function testToArray() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertSame( [ 'address' => '1.2.3.4' ], $rData->toArray() );
    }


    public function testToString() : void {
        $rData = RData::normalize( 'A', '1.2.3.4' );
        assert( $rData instanceof RData );
        self::assertSame( '1.2.3.4', (string) $rData );
    }


}
