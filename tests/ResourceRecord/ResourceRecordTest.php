<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord\AbstractResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\Transport\Buffer;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractResourceRecord::class )]
#[CoversClass( ResourceRecord::class )]
final class ResourceRecordTest extends TestCase {


    public function testAAAARecord() : void {
        $rr = new ResourceRecord( 'example.com', 'AAAA', 'IN', 3600,
            [ 'address' => '2001:db8::1' ] );
        self::assertSame( 'AAAA', $rr->type() );
        self::assertSame( '2001:db8::1', $rr->getRDataValue( 'address' ) );
    }


    public function testArrayAccessExists() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( isset( $rr->getRData() [ 'address' ] ) );
        self::assertFalse( isset( $rr->getRData()[ 'nonexistent' ] ) );
    }


    public function testArrayAccessGet() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' ) );
    }


    public function testBinaryDataHandling() : void {
        // Test with binary data including null bytes
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD\x00";
        $record = new ResourceRecord( [ 'binary', 'test' ], 254, 1, 300, $binaryData );

        self::assertSame( $binaryData, $record->getRDataValue( 'rdata' ) );

        $array = $record->toArray();
        self::assertSame( $binaryData, $array[ 'rdata' ] );
    }


    public function testCNAMERecord() : void {
        $rr = new ResourceRecord( 'www.example.com', 'CNAME', 'IN', 3600,
            [ 'cname' => [ 'canonical', 'example', 'com' ] ] );
        self::assertSame( 'CNAME', $rr->type() );
        self::assertSame( [ 'canonical', 'example', 'com' ], $rr->getRDataValue( 'cname' ) );
    }


    public function testClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertSame( 'IN', $record->class() );
    }


    public function testClassUnknownClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 99999, 300, 'data' );

        // When class is unknown, it throws an exception when trying to get the name
        self::expectException( RecordClassException::class );
        self::expectExceptionMessage( 'Invalid record class: 99999' );

        $record->class();
    }


    public function testClassValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 1, $rr->classValue() ); // IN class value
    }


    public function testClassValue2() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 255, 300, 'data' );
        self::assertSame( 255, $record->classValue() );
    }


    public function testConstruct() : void {
        $rr = new ResourceRecord( 'test.example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
    }


    public function testConstructWithArrayName() : void {
        $rr = new ResourceRecord( [ 'test', 'example', 'com' ], 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( [ 'test', 'example', 'com' ], $rr->getName() );
    }


    public function testConstructWithDefaults() : void {
        $rr = new ResourceRecord( 'example.com', 'A', null, null,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 86400, $rr->ttl() ); // Default TTL
    }


    public function testConstructWithRDataValues() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' ) );
    }


    public function testConstructWithRecordClassEnum() : void {
        $class = RecordClass::IN;
        $rr = new ResourceRecord( 'example.com', 'A', $class, 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'IN', $rr->class() );
    }


    public function testConstructWithRecordTypeEnum() : void {
        $type = RecordType::A;
        $rr = new ResourceRecord( 'example.com', $type, 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'A', $rr->type() );
    }


    public function testConstructor() : void {
        $name = [ 'example', 'com' ];
        $type = 99; // Unknown type
        $class = 1; // IN
        $ttl = 3600;
        $data = 'opaque data';

        $record = new ResourceRecord( $name, $type, $class, $ttl, $data );

        self::assertSame( $name, $record->getName() );
        self::assertSame( $type, $record->typeValue() );
        self::assertSame( $class, $record->classValue() );
        self::assertSame( $ttl, $record->getTTL() );
        self::assertSame( $data, $record->getRDataValue( 'rdata' ) );
    }


    public function testFromArray() : void {
        $data = [
            'name' => [ 'test', 'example' ],
            'type' => 255,
            'class' => 1,
            'ttl' => 300,
            'rdata' => 'test data',
        ];

        $record = ResourceRecord::fromArray( $data );

        self::assertSame( [ 'test', 'example' ], $record->getName() );
        self::assertSame( 'test.example', $record->name() );
        self::assertSame( 255, $record->typeValue() );
        self::assertSame( 1, $record->classValue() );
        self::assertSame( 300, $record->getTTL() );
        self::assertSame( 'test data', $record->getRDataValue( 'rdata' ) );
    }


    public function testFromArray2() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'class' => 'IN',
            'ttl' => 3600,
            'address' => '192.0.2.1',
        ];
        $rr = ResourceRecord::fromArray( $data );
        self::assertSame( 'example.com', $rr->name() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( '192.0.2.1', $rr->getRDataValue( 'address' ) );
    }


    public function testFromArrayInvalidNameFormat() : void {
        $data = [
            'name' => 123, // Invalid name format
            'type' => 'A',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Invalid record name format: must be string or array' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayMissingClass() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'address' => '192.0.2.1',
        ];
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Missing required field: class' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayMissingType() : void {
        $data = [
            'name' => 'example.com',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Missing required field: type' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayPartialData() : void {
        $data = [
            'name' => [ 'partial' ],
            'type' => 100,
            'rdata' => '',
        ];

        $record = ResourceRecord::fromArray( $data );

        self::assertSame( [ 'partial' ], $record->getName() );
        self::assertSame( 100, $record->typeValue() );
        self::assertSame( RecordClass::IN, $record->getClass() );
        self::assertGreaterThan( 0, $record->getTTL() );
        self::assertSame( '', $record->getRDataValue( 'rdata' ) );
    }


    public function testFromArrayWithDefaults() : void {
        $data = [];

        $record = ResourceRecord::fromArray( $data );

        self::assertSame( [], $record->getName() );
        self::assertSame( 0, $record->typeValue() );
        self::assertSame( 0, $record->classValue() );
        self::assertSame( 0, $record->getTTL() );
        self::assertSame( '', $record->stData );
    }


    public function testFromArrayWithNestedRData() : void {
        $data = [
            'name' => [ 'example', 'com' ],
            'type' => 'A',
            'class' => 'IN',
            'ttl' => 3600,
            'rdata' => [
                'address' => '192.0.2.1',
            ],
        ];
        $rr = ResourceRecord::fromArray( $data );
        self::assertSame( 'example.com', $rr->name() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( '192.0.2.1', $rr->getRDataValue( 'address' ) );
    }


    public function testFromArrayWithoutTTL() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        $rr = ResourceRecord::fromArray( $data );
        self::assertSame( 86400, $rr->ttl() ); // Default TTL
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
        $record = ResourceRecord::fromBuffer( $buffer );

        self::assertSame( [ 'example', 'com' ], $record->getName() );
        self::assertSame( 1, $record->typeValue() );
        self::assertSame( 1, $record->classValue() );
        self::assertSame( 3600, $record->getTTL() );
        self::assertSame( "\x01\x02\x03\x04", $record->stData );
    }


    public function testFromString() : void {
        $wireData = "\x07example\x03com\x00" . // Name
            "\x00\xFF" .                 // Type (unknown = 255)
            "\x00\x01" .                 // Class (IN = 1)
            "\x00\x00\x01\x2C" .         // TTL (300)
            "\x00\x05" .                 // RD Length (5 bytes)
            'hello';                     // RData

        $record = ResourceRecord::fromString( $wireData );

        self::assertSame( [ 'example', 'com' ], $record->getName() );
        self::assertSame( 255, $record->typeValue() );
        self::assertSame( 1, $record->classValue() );
        self::assertSame( 300, $record->getTTL() );
        self::assertSame( 'hello', $record->stData );
    }


    public function testFromString2() : void {
        $rr = ResourceRecord::fromString( 'test.example.com. 3600 IN A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );

        $rr = ResourceRecord::fromString( 'test.example.com. "3600" "In" a 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );

        $rr = ResourceRecord::fromString( 'test.example.com A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertGreaterThan( 0, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );
    }


    public function testFromStringForBadClass() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 FOO A 1.2.3.4' );
    }


    public function testFromStringForBadRData() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN A 1.2.3' );
    }


    public function testFromStringForBadTTLHuge() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 100000000000000 IN A' );
    }


    public function testFromStringForBadTTLNegative() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. -20 IN A 1.2.3.4' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForBadType() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN FOO 1.2.3.4' );
    }


    public function testFromStringForDefaultClassIN() : void {
        $rr = ResourceRecord::fromString( 'example.com. A 1.2.3.4' );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForDefaultClassINWithTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 A 1.2.3.4' );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertSame( 3600, $rr->ttl() );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForDefaultTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. IN A 1.2.3.4' );
        # I'm not saying this is right or wrong, but it is the current behavior.
        self::assertSame( 86400, $rr->ttl() );
    }


    public function testFromStringForEmpty() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( '' );
    }


    public function testFromStringForExtraRData() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN A 1.2.3.4 5.6.7.8' );
    }


    public function testFromStringForFirstClass() : void {
        $rr = ResourceRecord::fromString( 'example.com. IN 12345 A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl() );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForFirstTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. 12345 IN A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl() );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForMultipleTTL() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 7200 IN A 1.2.3.4' );
    }


    public function testFromStringForNoTypeOrValue() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN' );
    }


    public function testFromStringForNoTypeWithTTL() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN 1.2.3.4' );
    }


    public function testFromStringForNoTypeWithoutTTL() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. IN 1.2.3.4' );
    }


    public function testFromStringForQuotedName() : void {
        $rr = ResourceRecord::fromString( '"Sure, why not?" 3600 IN A 1.2.3.4' );
        # Still making up my mind about this behavior.
        self::assertSame( '"sure, why not?"', $rr->name() );
    }


    public function testFromStringForQuotesEmpty() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "" "This is a test" ""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '', 'This is a test', '' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesMixed() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar" baz' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesMixed2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar baz" qux' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar baz', 'qux' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesUnclosed() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForQuotesWithEmbeddedNewline() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\nan embedded newline.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\nan embedded newline." ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEmbeddedNull() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\0an embedded null.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\0an embedded null." ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEmbeddedTab() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\ta tab.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\ta tab." ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedBackslash() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test with an \\ escaped backslash"' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test with an \\ escaped backslash' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuote() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with an escaped quote."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with an escaped quote.' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuote2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "\\"escaped quote\\""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '"escaped quote"' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuotes() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with escaped quotes\\"."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with escaped quotes".' ], $rr->getRDataValue( 'text' ) );
    }


    public function testFromStringForTotallyInvalid() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'invalid' );
    }


    public function testFromStringForTwoClasses() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. IN IN A 1.2.3.4' );
    }


    public function testFromStringForUnimplementedType() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN UNIMPLEMENTED 1.2.3.4' );
    }


    public function testFromStringForWrongRRClass() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN SAY_WHAT 1.2.3.4' );
    }


    public function testGetClass() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertInstanceOf( RecordClass::class, $rr->getClass() );
        self::assertSame( 'IN', $rr->getClass()->name );
    }


    public function testGetClassKnownClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        $class = $record->getClass();
        self::assertSame( RecordClass::IN, $class );
    }


    public function testGetClassUnknownClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 99999, 300, 'data' );

        self::expectException( RecordClassException::class );
        self::expectExceptionMessage( 'Unknown record class: 99999' );

        $record->getClass();
    }


    public function testGetName() : void {
        $name = [ 'subdomain', 'example', 'net' ];
        $record = new ResourceRecord( $name, 1, 1, 300, 'data' );

        self::assertSame( $name, $record->getName() );
    }


    public function testGetName2() : void {
        $rr = new ResourceRecord( 'test.example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( [ 'test', 'example', 'com' ], $rr->getName() );
    }


    public function testGetRData() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rData = $rr->getRData();
        self::assertArrayHasKey( 'address', $rData );
        self::assertInstanceOf( RDataValue::class, $rData[ 'address' ] );
        self::assertSame( '192.0.2.123', $rData[ 'address' ]->value );
    }


    public function testGetRDataThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support getRData()' );

        $record->getRData();
    }


    public function testGetRDataValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $value = $rr->getRDataValue( 'address' );
        self::assertInstanceOf( RDataValue::class, $value );
        self::assertSame( '192.0.2.123', $value->value );
    }


    public function testGetRDataValueExThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support getRDataValueEx()' );

        $record->getRDataValueEx( 'any' );
    }


    public function testGetRDataValueMissing() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertNull( $rr->getRDataValue( 'nonexistent' ) );
    }


    public function testGetRDataValueThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support getRDataValue()' );

        $record->getRDataValue( 'any' );
    }


    public function testGetTTL() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 86400, 'data' );

        self::assertSame( 86400, $record->getTTL() );
        self::assertSame( 86400, $record->ttl() );
    }


    public function testGetTTL2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 3600, $rr->getTTL() );
    }


    public function testGetType() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertInstanceOf( RecordType::class, $rr->getType() );
        self::assertSame( 'A', $rr->getType()->name );
    }


    public function testGetTypeKnownType() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        $type = $record->getType();
        self::assertSame( RecordType::A, $type );
    }


    public function testGetTypeUnknownType() : void {
        $record = new ResourceRecord( [ 'test' ], 99999, 1, 300, 'data' );

        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record type: 99999' );

        $record->getType();
    }


    public function testHasRDataValue() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertFalse( $record->hasRDataValue( 'any' ) );
        self::assertFalse( $record->hasRDataValue( 'address' ) );
        self::assertFalse( $record->hasRDataValue( '' ) );
    }


    public function testIsClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::assertTrue( $record->isClass( RecordClass::IN ) );
        self::assertTrue( $record->isClass( 'IN' ) );
        self::assertTrue( $record->isClass( 1 ) );

        self::assertFalse( $record->isClass( RecordClass::CH ) );
        self::assertFalse( $record->isClass( 'CH' ) );
        self::assertFalse( $record->isClass( 3 ) );
    }


    public function testIsClass2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isClass( 1 ) );
        self::assertFalse( $rr->isClass( 'CH' ) );
    }


    public function testIsType() : void {
        $record = new ResourceRecord( [ 'test' ], 5, 1, 300, 'data' );

        self::assertTrue( $record->isType( RecordType::CNAME ) );
        self::assertTrue( $record->isType( 'CNAME' ) );
        self::assertTrue( $record->isType( 5 ) );

        self::assertFalse( $record->isType( RecordType::A ) );
        self::assertFalse( $record->isType( 'A' ) );
        self::assertFalse( $record->isType( 1 ) );
    }


    public function testIsType2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( $rr->isType( 'A' ) );
        self::assertTrue( $rr->isType( 1 ) );
        self::assertFalse( $rr->isType( 'AAAA' ) );
    }


    public function testLargeTypeAndClassValues() : void {
        // Test with maximum 16-bit values
        $record = new ResourceRecord( [ 'max' ], 65535, 65535, 4294967295, 'max values' );

        self::assertSame( 65535, $record->typeValue() );
        self::assertSame( 65535, $record->classValue() );
        self::assertSame( 4294967295, $record->getTTL() );
    }


    public function testMXRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        self::assertSame( 'MX', $rr->type() );
        self::assertSame( 10, $rr->getRDataValue( 'preference' ) );
        self::assertSame( [ 'mail', 'example', 'com' ], $rr->getRDataValue( 'exchange' ) );
    }


    public function testNSRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'NS', 'IN', 3600,
            [ 'nsdname' => [ 'ns1', 'example', 'com' ] ] );
        self::assertSame( 'NS', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr->getRDataValue( 'nsdname' ) );
    }


    public function testName() : void {
        $record = new ResourceRecord( [ 'host', 'domain', 'tld' ], 1, 1, 300, 'data' );

        self::assertSame( 'host.domain.tld', $record->name() );
    }


    public function testNameWithEmptyArray() : void {
        $record = new ResourceRecord( [], 1, 1, 300, 'data' );

        self::assertSame( '', $record->name() );
    }


    public function testOffsetExists() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        // ResourceRecord doesn't have RData fields, so nothing should exist
        self::assertFalse( isset( $record[ 'address' ] ) );
        self::assertFalse( isset( $record[ 'any' ] ) );
        self::assertFalse( isset( $record[ '0' ] ) );
    }


    public function testOffsetExists2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        self::assertTrue( isset( $rr->getRData()[ 'address' ] ) );
        self::assertFalse( isset( $rr->getRData()[ 'nonexistent' ] ) );
    }


    public function testOffsetGet() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        // Should throw exception as ResourceRecord doesn't support RData access
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support getRDataValueEx()' );

        $value = $record[ 'address' ];
        unset( $value );
    }


    public function testOffsetGet2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        self::assertSame( '1.2.3.4', $rr->getRDataValue( 'address' ) );
    }


    public function testOffsetSet() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support setRDataValue()' );

        $record[ 'address' ] = 'value';
    }


    public function testOffsetSet2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        $rr->getRData()[ 'address' ] = '5.6.7.8';
        self::assertSame( '5.6.7.8', $rr->getRDataValueEx( 'address' )->value );
    }


    public function testOffsetUnset() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'Cannot unset RData values in a resource record.' );

        unset( $record[ 'address' ] );
    }


    public function testOffsetUnset2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        self::expectException( LogicException::class );
        unset( $rr->getRData()[ 'address' ] );
    }


    public function testPTRRecord() : void {
        $rr = new ResourceRecord( '1.2.0.192.in-addr.arpa', 'PTR', 'IN', 3600,
            [ 'ptrdname' => [ 'example', 'com' ] ] );
        self::assertSame( 'PTR', $rr->type() );
        self::assertSame( [ 'example', 'com' ], $rr->getRDataValue( 'ptrdname' ) );
    }


    public function testSOARecord() : void {
        $rr = new ResourceRecord( 'example.com', 'SOA', 'IN', 3600, [
            'mname' => [ 'ns1', 'example', 'com' ],
            'rname' => [ 'admin', 'example', 'com' ],
            'serial' => 2023010101,
            'refresh' => 3600,
            'retry' => 1800,
            'expire' => 604800,
            'minimum' => 86400,
        ] );
        self::assertSame( 'SOA', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr->getRDataValue( 'mname' ) );
        self::assertSame( [ 'admin', 'example', 'com' ], $rr->getRDataValue( 'rname' ) );
        self::assertSame( 2023010101, $rr->getRDataValue( 'serial' ) );
        self::assertSame( 3600, $rr->getRDataValue( 'refresh' ) );
        self::assertSame( 1800, $rr->getRDataValue( 'retry' ) );
        self::assertSame( 604800, $rr->getRDataValue( 'expire' ) );
        self::assertSame( 86400, $rr->getRDataValue( 'minimum' ) );
    }


    public function testSetClass() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setClass( 'CH' );
        self::assertSame( 'CH', $rr->class() );
    }


    public function testSetDefaultTTL() : void {
        // Save original default TTL
        $originalTTL = 86400; // Current default

        // Set a new default TTL
        ResourceRecord::setDefaultTTL( 7200 );

        // Create a record without explicit TTL
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', null,
            [ 'address' => '192.0.2.123' ] );

        self::assertSame( 7200, $rr->ttl() );

        // Test that fromString also uses the new default
        $rr2 = ResourceRecord::fromString( 'example.com. IN A 192.0.2.123' );
        self::assertSame( 7200, $rr2->ttl() );

        // Restore original default TTL for other tests
        ResourceRecord::setDefaultTTL( $originalTTL );
    }


    public function testSetDefaultTTLFromArray() : void {
        // Save original default TTL
        $originalTTL = 86400;

        // Set a new default TTL
        ResourceRecord::setDefaultTTL( 3600 );

        // Create a record from array without explicit TTL
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'class' => 'IN',
            'address' => '192.0.2.123',
        ];
        $rr = ResourceRecord::fromArray( $data );

        self::assertSame( 3600, $rr->ttl() );

        // Restore original default TTL
        ResourceRecord::setDefaultTTL( $originalTTL );
    }


    public function testSetRDataValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setRDataValue( 'address', '192.0.2.124' );
        self::assertSame( '192.0.2.124', $rr->getRDataValue( 'address' ) );
    }


    public function testSetRDataValueInvalidKey() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Invalid RData key: invalid' );
        $rr->setRDataValue( 'invalid', 'value' );
    }


    public function testSetRDataValueThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support setRDataValue()' );

        $record->setRDataValue( 'any', 'value' );
    }


    public function testSetTTL() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setTTL( 7200 );
        self::assertSame( 7200, $rr->ttl() );
    }


    public function testSetTTLInvalidNegative() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Invalid TTL -1' );
        $rr->setTTL( -1 );
    }


    public function testSetTTLInvalidTooLarge() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Invalid TTL 2147483648' );
        $rr->setTTL( 2147483648 );
    }


    public function testSetType() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setType( 'AAAA' );
        self::assertSame( 'AAAA', $rr->type() );
    }


    public function testTXTRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'TXT', 'IN', 3600,
            [ 'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ] ] );
        self::assertSame( 'TXT', $rr->type() );
        self::assertSame( [ 'v=spf1', 'include:_spf.example.com', '~all' ], $rr->getRDataValue( 'text' ) );
    }


    public function testToArray() : void {
        $record = new ResourceRecord(
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


    public function testToArray2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $array = $rr->toArray();

        self::assertArrayHasKey( 'name', $array );
        self::assertArrayHasKey( 'type', $array );
        self::assertArrayHasKey( 'class', $array );
        self::assertArrayHasKey( 'ttl', $array );
        self::assertArrayHasKey( 'rdata', $array );

        self::assertSame( 'example.com', $array[ 'name' ] );
        self::assertSame( 'A', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 3600, $array[ 'ttl' ] );
        self::assertSame( '192.0.2.123', $array[ 'rdata' ][ 'address' ] );
    }


    public function testToArrayMXRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $array = $rr->toArray();

        self::assertSame( 'MX', $array[ 'type' ] );
        self::assertSame( 10, $array[ 'rdata' ][ 'preference' ] );
        self::assertSame( [ 'mail', 'example', 'com' ], $array[ 'rdata' ][ 'exchange' ] );
    }


    public function testToArrayWithNameAsArray() : void {
        $record = new ResourceRecord(
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


    public function testToArrayWithNameAsArray2() : void {
        $rr = new ResourceRecord( 'test.example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $array = $rr->toArray( true );

        self::assertSame( [ 'test', 'example', 'com' ], $array[ 'name' ] );
    }


    public function testToString() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $string = (string) $rr;
        self::assertStringContainsString( 'example.com', $string );
        self::assertStringContainsString( '3600', $string );
        self::assertStringContainsString( 'IN', $string );
        self::assertStringContainsString( 'A', $string );
        self::assertStringContainsString( '192.0.2.123', $string );
    }


    public function testToStringMXRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $string = (string) $rr;
        self::assertStringContainsString( 'example.com', $string );
        self::assertStringContainsString( '3600', $string );
        self::assertStringContainsString( 'IN', $string );
        self::assertStringContainsString( 'MX', $string );
        self::assertStringContainsString( '10', $string );
        self::assertStringContainsString( 'mail.example.com', $string );
    }


    public function testToStringTXTRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'TXT', 'IN', 3600,
            [ 'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ] ] );
        $string = (string) $rr;
        self::assertStringContainsString( 'example.com', $string );
        self::assertStringContainsString( '3600', $string );
        self::assertStringContainsString( 'IN', $string );
        self::assertStringContainsString( 'TXT', $string );
        self::assertStringContainsString( 'v=spf1', $string );
    }


    public function testToStringThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, 'data' );

        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'ResourceRecord does not support __toString()' );

        $x = (string) $record;
        unset( $x );
    }


    public function testType() : void {
        $record = new ResourceRecord( [ 'test' ], 2, 1, 300, 'data' );

        self::assertSame( 'NS', $record->type() );
    }


    public function testType2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        self::assertSame( 'A', $rr->type() );
    }


    public function testTypeUnknownType() : void {
        $record = new ResourceRecord( [ 'test' ], 99999, 1, 300, 'data' );

        // When type is unknown, it throws an exception when trying to get the name
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record type: 99999' );

        $record->type();
    }


    public function testTypeValue() : void {
        $record = new ResourceRecord( [ 'test' ], 65535, 1, 300, 'data' );

        self::assertSame( 65535, $record->typeValue() );
    }


    public function testTypeValue2() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        self::assertSame( RecordType::A->value, $rr->typeValue() ); // A record type value
    }


}
