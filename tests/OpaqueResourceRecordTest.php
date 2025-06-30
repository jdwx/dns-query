<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Buffer;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\OpaqueResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OpaqueResourceRecord::class )]
final class OpaqueResourceRecordTest extends TestCase {


    public function testBinaryDataHandling() : void {
        // Test with binary data including null bytes
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD\x00";
        $record = new OpaqueResourceRecord( [ 'binary', 'test' ], 254, 1, 300, $binaryData );

        self::assertSame( $binaryData, $record->stData );

        $array = $record->toArray();
        self::assertSame( $binaryData, $array[ 'rdata' ] );
    }


    public function testClass() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertSame( 'IN', $record->class() );
    }


    public function testClassUnknownClass() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 99999, 300, 'data' );

        // When class is unknown, it throws an exception when trying to get the name
        self::expectException( RecordClassException::class );
        self::expectExceptionMessage( 'Unknown record class: 99999' );

        $record->class();
    }


    public function testClassValue() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 255, 300, 'data' );
        self::assertSame( 255, $record->classValue() );
    }


    public function testConstructor() : void {
        $name = [ 'example', 'com' ];
        $type = 99; // Unknown type
        $class = 1; // IN
        $ttl = 3600;
        $data = 'opaque data';

        $record = new OpaqueResourceRecord( $name, $type, $class, $ttl, $data );

        self::assertSame( $name, $record->rName );
        self::assertSame( $type, $record->uType );
        self::assertSame( $class, $record->uClass );
        self::assertSame( $ttl, $record->uTtl );
        self::assertSame( $data, $record->stData );
    }


    public function testFromArray() : void {
        $data = [
            'name' => [ 'test', 'example' ],
            'type' => 255,
            'class' => 1,
            'ttl' => 300,
            'rdata' => 'test data',
        ];

        $record = OpaqueResourceRecord::fromArray( $data );

        self::assertSame( [ 'test', 'example' ], $record->rName );
        self::assertSame( 'test.example', $record->name() );
        self::assertSame( 255, $record->uType );
        self::assertSame( 1, $record->uClass );
        self::assertSame( 300, $record->uTtl );
        self::assertSame( 'test data', $record->stData );
    }


    public function testFromArrayPartialData() : void {
        $data = [
            'name' => [ 'partial' ],
            'type' => 100,
        ];

        $record = OpaqueResourceRecord::fromArray( $data );

        self::assertSame( [ 'partial' ], $record->rName );
        self::assertSame( 100, $record->uType );
        self::assertSame( 0, $record->uClass );
        self::assertSame( 0, $record->uTtl );
        self::assertSame( '', $record->stData );
    }


    public function testFromArrayWithDefaults() : void {
        $data = [];

        $record = OpaqueResourceRecord::fromArray( $data );

        self::assertSame( [], $record->rName );
        self::assertSame( 0, $record->uType );
        self::assertSame( 0, $record->uClass );
        self::assertSame( 0, $record->uTtl );
        self::assertSame( '', $record->stData );
    }


    public function testFromBuffer() : void {
        // Create a buffer with DNS wire format data
        // Name: example.com (compressed format would be: 07 65 78 61 6D 70 6C 65 03 63 6F 6D 00)
        $wireData = "\x07example\x03com\x00" . // Name
            "\x00\x01" .                 // Type (A record = 1)
            "\x00\x01" .                 // Class (IN = 1)
            "\x00\x00\x0E\x10" .         // TTL (3600)
            "\x00\x04" .                 // RD Length (4 bytes)
            "\x01\x02\x03\x04";          // RData (1.2.3.4)

        $buffer = new Buffer( $wireData );
        $record = OpaqueResourceRecord::fromBuffer( $buffer );

        self::assertSame( [ 'example', 'com' ], $record->rName );
        self::assertSame( 1, $record->uType );
        self::assertSame( 1, $record->uClass );
        self::assertSame( 3600, $record->uTtl );
        self::assertSame( "\x01\x02\x03\x04", $record->stData );
    }


    public function testFromString() : void {
        $wireData = "\x07example\x03com\x00" . // Name
            "\x00\xFF" .                 // Type (unknown = 255)
            "\x00\x01" .                 // Class (IN = 1)
            "\x00\x00\x01\x2C" .         // TTL (300)
            "\x00\x05" .                 // RD Length (5 bytes)
            'hello';                     // RData

        $record = OpaqueResourceRecord::fromString( $wireData );

        self::assertSame( [ 'example', 'com' ], $record->rName );
        self::assertSame( 255, $record->uType );
        self::assertSame( 1, $record->uClass );
        self::assertSame( 300, $record->uTtl );
        self::assertSame( 'hello', $record->stData );
    }


    public function testGetClassKnownClass() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        $class = $record->getClass();
        self::assertSame( RecordClass::IN, $class );
    }


    public function testGetClassUnknownClass() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 99999, 300, 'data' );

        self::expectException( RecordClassException::class );
        self::expectExceptionMessage( 'Unknown record class: 99999' );

        $record->getClass();
    }


    public function testGetName() : void {
        $name = [ 'subdomain', 'example', 'net' ];
        $record = new OpaqueResourceRecord( $name, 1, 1, 300, 'data' );

        self::assertSame( $name, $record->getName() );
    }


    public function testGetRDataThrowsException() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support getRData()' );

        $record->getRData();
    }


    public function testGetRDataValueExThrowsException() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support getRDataValueEx()' );

        $record->getRDataValueEx( 'any' );
    }


    public function testGetRDataValueThrowsException() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support getRDataValue()' );

        $record->getRDataValue( 'any' );
    }


    public function testGetTTL() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 86400, 'data' );

        self::assertSame( 86400, $record->getTTL() );
        self::assertSame( 86400, $record->ttl() );
    }


    public function testGetTypeKnownType() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        $type = $record->getType();
        self::assertSame( RecordType::A, $type );
    }


    public function testGetTypeUnknownType() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 99999, 1, 300, 'data' );

        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record type: 99999' );

        $record->getType();
    }


    public function testHasRDataValue() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertFalse( $record->hasRDataValue( 'any' ) );
        self::assertFalse( $record->hasRDataValue( 'address' ) );
        self::assertFalse( $record->hasRDataValue( '' ) );
    }


    public function testIsClass() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertTrue( $record->isClass( RecordClass::IN ) );
        self::assertTrue( $record->isClass( 'IN' ) );
        self::assertTrue( $record->isClass( 1 ) );

        self::assertFalse( $record->isClass( RecordClass::CH ) );
        self::assertFalse( $record->isClass( 'CH' ) );
        self::assertFalse( $record->isClass( 3 ) );
    }


    public function testIsType() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 5, 1, 300, 'data' );

        self::assertTrue( $record->isType( RecordType::CNAME ) );
        self::assertTrue( $record->isType( 'CNAME' ) );
        self::assertTrue( $record->isType( 5 ) );

        self::assertFalse( $record->isType( RecordType::A ) );
        self::assertFalse( $record->isType( 'A' ) );
        self::assertFalse( $record->isType( 1 ) );
    }


    public function testLargeTypeAndClassValues() : void {
        // Test with maximum 16-bit values
        $record = new OpaqueResourceRecord( [ 'max' ], 65535, 65535, 4294967295, 'max values' );

        self::assertSame( 65535, $record->uType );
        self::assertSame( 65535, $record->uClass );
        self::assertSame( 4294967295, $record->uTtl );
    }


    public function testName() : void {
        $record = new OpaqueResourceRecord( [ 'host', 'domain', 'tld' ], 1, 1, 300, 'data' );

        self::assertSame( 'host.domain.tld', $record->name() );
    }


    public function testNameWithEmptyArray() : void {
        $record = new OpaqueResourceRecord( [], 1, 1, 300, 'data' );

        self::assertSame( '', $record->name() );
    }


    public function testOffsetExists() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        // OpaqueResourceRecord doesn't have RData fields, so nothing should exist
        self::assertFalse( isset( $record[ 'address' ] ) );
        self::assertFalse( isset( $record[ 'any' ] ) );
        self::assertFalse( isset( $record[ '0' ] ) );
    }


    public function testOffsetGet() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        // Should throw exception as OpaqueResourceRecord doesn't support RData access
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support getRDataValueEx()' );

        $value = $record[ 'address' ];
        unset( $value );
    }


    public function testOffsetSet() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support setRDataValue()' );

        $record[ 'address' ] = 'value';
    }


    public function testOffsetUnset() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'Cannot unset RData values in a resource record.' );

        unset( $record[ 'address' ] );
    }


    public function testSetRDataValueThrowsException() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support setRDataValue()' );

        $record->setRDataValue( 'any', 'value' );
    }


    public function testToArray() : void {
        $record = new OpaqueResourceRecord(
            [ 'www', 'example', 'com' ],
            99,
            1,
            7200,
            'some data'
        );

        $array = $record->toArray();

        self::assertArrayHasKey( 'name', $array );
        self::assertArrayHasKey( 'type', $array );
        self::assertArrayHasKey( 'class', $array );
        self::assertArrayHasKey( 'ttl', $array );
        self::assertArrayHasKey( 'rdata', $array );

        self::assertSame( 'www.example.com', $array[ 'name' ] );
        // Type 99 is SPF in the enum, so it returns 'SPF' not 99
        self::assertSame( 'SPF', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 7200, $array[ 'ttl' ] );
        self::assertSame( 'some data', $array[ 'rdata' ] );
    }


    public function testToArrayWithNameAsArray() : void {
        $record = new OpaqueResourceRecord(
            [ 'mail', 'example', 'org' ],
            15, // MX
            1,  // IN
            3600,
            'mx data'
        );

        $array = $record->toArray( true );

        self::assertSame( [ 'mail', 'example', 'org' ], $array[ 'name' ] );
        self::assertSame( 'MX', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 3600, $array[ 'ttl' ] );
        self::assertSame( 'mx data', $array[ 'rdata' ] );
    }


    public function testToStringThrowsException() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OpaqueResourceRecord does not support __toString()' );

        $x = (string) $record;
        unset( $x );
    }


    public function testType() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 2, 1, 300, 'data' );

        self::assertSame( 'NS', $record->type() );
    }


    public function testTypeUnknownType() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 99999, 1, 300, 'data' );

        // When type is unknown, it throws an exception when trying to get the name
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record type: 99999' );

        $record->type();
    }


    public function testTypeValue() : void {
        $record = new OpaqueResourceRecord( [ 'test' ], 65535, 1, 300, 'data' );

        self::assertSame( 65535, $record->typeValue() );
    }


}