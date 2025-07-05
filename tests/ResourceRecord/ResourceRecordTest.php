<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( ResourceRecord::class )]
final class ResourceRecordTest extends TestCase {


    public function testArrayAccessExists() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( isset( $rr->getRData() [ 'address' ] ) );
        self::assertFalse( isset( $rr->getRData()[ 'nonexistent' ] ) );
    }


    public function testArrayAccessGet() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( '192.0.2.123', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testBinaryDataHandling() : void {
        // Test with binary data including null bytes
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD\x00";
        $record = new ResourceRecord( [ 'binary', 'test' ], 254, 1, 300, $binaryData );

        self::assertSame( $binaryData, $record->tryGetRDataValue( 'rdata' ) );

        $array = $record->toArray();
        self::assertSame( $binaryData, $array[ 'rdata' ] );
    }


    public function testClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );

        self::assertSame( 'IN', $record->class() );
    }


    public function testClassUnknownClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 23456, 300, '1.2.3.4' );
        self::assertSame( 'CLASS23456', $record->class() );
    }


    public function testClassValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 1, $rr->classValue() ); // IN class value
    }


    public function testClassValue2() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 255, 300, '1.2.3.4' );
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
        self::assertSame( '192.0.2.123', $rr->tryGetRDataValue( 'address' ) );
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
        $type = 999; // Unknown type
        $class = 1; // IN
        $ttl = 3600;
        $data = 'opaque data';

        $record = new ResourceRecord( $name, $type, $class, $ttl, $data );

        self::assertSame( $name, $record->getName() );
        self::assertSame( $type, $record->typeValue() );
        self::assertSame( $class, $record->classValue() );
        self::assertSame( $ttl, $record->getTTL() );
        self::assertSame( $data, $record->tryGetRDataValue( 'rdata' ) );
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
        self::assertSame( 'test data', $record->tryGetRDataValue( 'rdata' ) );
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
        self::assertSame( '192.0.2.1', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testFromArrayForInvalidNameFormat() : void {
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


    public function testFromArrayForMinimalARecord() : void {
        $data = [ 'name' => 'foo', 'type' => 'A', 'address' => '1.2.3.4' ];

        $record = ResourceRecord::fromArray( $data );

        self::assertSame( [ 'foo' ], $record->getName() );
        self::assertSame( RecordType::A, $record->getType() );
        self::assertSame( RecordClass::IN, $record->getClass() );
        self::assertGreaterThan( 0, $record->getTTL() );
        self::assertSame( '1.2.3.4', $record->tryGetRDataValue( 'address' ) );
    }


    public function testFromArrayForMissingClass() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'address' => '192.0.2.1',
        ];
        $rr = ResourceRecord::fromArray( $data );
        self::assertSame( RecordClass::IN, $rr->getClass() );
    }


    public function testFromArrayForMissingType() : void {
        $data = [
            'name' => 'example.com',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        self::expectException( RecordException::class );
        self::expectExceptionMessage( 'Missing record type' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayForNonsenseRData() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'class' => 'IN',
            'ttl' => 3600,
            'rdata' => 125, // Three, sir! Three!
        ];
        self::expectException( RecordDataException::class );
        self::expectExceptionMessage( 'Invalid RData format' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayForPartialData() : void {
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
        self::assertSame( '', $record->tryGetRDataValue( 'rdata' ) );
    }


    public function testFromArrayForWithNestedRData() : void {
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
        self::assertSame( '192.0.2.1', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testFromArrayForWithoutTTL() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        $rr = ResourceRecord::fromArray( $data );
        self::assertSame( 86400, $rr->ttl() ); // Default TTL
    }


    public function testFromString2() : void {
        $rr = ResourceRecord::fromString( 'test.example.com. 3600 IN A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->tryGetRDataValue( 'address' ) );

        $rr = ResourceRecord::fromString( 'test.example.com. "3600" "In" a 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->tryGetRDataValue( 'address' ) );

        $rr = ResourceRecord::fromString( 'test.example.com A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertGreaterThan( 0, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->tryGetRDataValue( 'address' ) );
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
        self::assertSame( [ '', 'This is a test', '' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesMixed() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar" baz' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesMixed2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar baz" qux' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar baz', 'qux' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesUnclosed() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForQuotesWithEmbeddedNewline() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\nan embedded newline.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\nan embedded newline." ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEmbeddedNull() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\0an embedded null.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\0an embedded null." ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEmbeddedTab() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\ta tab.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\ta tab." ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedBackslash() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test with an \\ escaped backslash"' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test with an \\ escaped backslash' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuote() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with an escaped quote."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with an escaped quote.' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuote2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "\\"escaped quote\\""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '"escaped quote"' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testFromStringForQuotesWithEscapedQuotes() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with escaped quotes\\"."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with escaped quotes".' ], $rr->tryGetRDataValue( 'text' ) );
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
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );

        $class = $record->getClass();
        self::assertSame( RecordClass::IN, $class );
    }


    public function testGetClassUnknownClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 12345, 300, '1.2.3.4' );
        self::expectException( RecordClassException::class );
        self::expectExceptionMessage( 'Unknown record class: 12345' );
        $record->getClass();
    }


    public function testGetName() : void {
        $name = [ 'subdomain', 'example', 'net' ];
        $record = new ResourceRecord( $name, 1, 1, 300, '1.2.3.4' );

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
        self::assertTrue( isset( $rData[ 'address' ] ) );
        self::assertSame( '192.0.2.123', $rData[ 'address' ] );
    }


    public function testGetRDataValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $value = $rr->tryGetRDataValue( 'address' );
        self::assertSame( '192.0.2.123', $value );
    }


    public function testGetRDataValueMissing() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertNull( $rr->tryGetRDataValue( 'nonexistent' ) );
    }


    public function testGetRDataValueThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );
        self::assertSame( '1.2.3.4', $record->getRDataValue( 'address' ) );
        self::expectException( RecordDataException::class );
        $record->getRDataValue( 'any' );
    }


    public function testGetTTL() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 86400, '1.2.3.4' );

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
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );

        $type = $record->getType();
        self::assertSame( RecordType::A, $type );
    }


    public function testGetTypeUnknownType() : void {
        $record = new ResourceRecord( [ 'test' ], 12345, 1, 300, 'data' );
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record type: 12345' );
        $record->getType();
    }


    public function testHasRDataValue() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );

        self::assertFalse( $record->hasRDataValue( 'any' ) );
        self::assertTrue( $record->hasRDataValue( 'address' ) );
        self::assertFalse( $record->hasRDataValue( '' ) );
    }


    public function testIsClass() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );

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


    public function testName() : void {
        $record = new ResourceRecord( [ 'host', 'domain', 'tld' ], 1, 1, 300, '1.2.3.4' );

        self::assertSame( 'host.domain.tld', $record->name() );
    }


    public function testNameWithEmptyArray() : void {
        $record = new ResourceRecord( [], 1, 1, 300, '1.2.3.4' );

        self::assertSame( '', $record->name() );
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


    public function testSetName() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        $rr->setName( 'new.example.com' );
        self::assertSame( 'new.example.com', $rr->name() );
        $rr->setName( [ 'another', 'example', 'com' ] );
        self::assertSame( 'another.example.com', $rr->name() );
    }


    public function testSetRData() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '1.2.3.4' ] );
        $rr->setRData( [ 'address' => '2.3.4.5' ] );
        self::assertSame( '2.3.4.5', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testSetRDataValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setRDataValue( 'address', '192.0.2.124' );
        self::assertSame( '192.0.2.124', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testSetRDataValueInvalidKey() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::expectException( RecordDataException::class );
        self::expectExceptionMessage( 'Invalid RData key' );
        $rr->setRDataValue( 'invalid', 'value' );
    }


    public function testSetRDataValueThrowsException() : void {
        $record = new ResourceRecord( [ 'test' ], 1, 1, 300, '1.2.3.4' );
        self::expectException( RecordDataException::class );
        self::expectExceptionMessage( 'Invalid RData key' );
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
        self::expectExceptionMessage( 'Invalid TTL' );
        $rr->setTTL( 20_000_000_000 );
    }


    public function testSetType() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $rr->setType( 'AAAA' );
        self::assertSame( 'AAAA', $rr->type() );
    }


    public function testToArray() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $array = $rr->toArray();

        self::assertArrayHasKey( 'name', $array );
        self::assertArrayHasKey( 'type', $array );
        self::assertArrayHasKey( 'class', $array );
        self::assertArrayHasKey( 'ttl', $array );
        self::assertArrayNotHasKey( 'rdata', $array );
        self::assertArrayHasKey( 'address', $array );

        self::assertSame( 'example.com', $array[ 'name' ] );
        self::assertSame( 'A', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 3600, $array[ 'ttl' ] );
        self::assertSame( '192.0.2.123', $array[ 'address' ] );
    }


    public function testToArrayForUnknownType() : void {
        $record = new ResourceRecord(
            [ 'www', 'example', 'com' ],
            999,
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
        self::assertSame( 'TYPE999', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 7200, $array[ 'ttl' ] );
        self::assertSame( 'some data', $array[ 'rdata' ] );
    }


    public function testToArrayWithNameAsArray() : void {
        $record = new ResourceRecord(
            [ 'example', 'org' ],
            15, // MX
            1,  // IN
            3600,
            '10 mail.example.org'
        );

        $array = $record->toArray( true );

        self::assertSame( [ 'example', 'org' ], $array[ 'name' ] );
        self::assertSame( 'MX', $array[ 'type' ] );
        self::assertSame( 'IN', $array[ 'class' ] );
        self::assertSame( 3600, $array[ 'ttl' ] );
        self::assertSame( 10, $array[ 'preference' ] );
        self::assertSame( [ 'mail', 'example', 'org' ], $array[ 'exchange' ] );
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


    public function testToStringForOpaque() : void {
        $record = new ResourceRecord( [ 'test' ], 9999, 1, 300, 'data' );
        self::assertSame( 'test 300 IN TYPE9999 64617461', strval( $record ) );
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
        $record = new ResourceRecord( [ 'test' ], 12345, 1, 300, 'data' );
        self::assertSame( 'TYPE12345', $record->type() );
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
