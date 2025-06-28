<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\AbstractResourceRecord;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractResourceRecord::class )]
#[CoversClass( ResourceRecord::class )]
final class ResourceRecordTest extends TestCase {


    public function testAAAARecord() : void {
        $rr = new ResourceRecord( 'example.com', 'AAAA', 'IN', 3600,
            [ 'address' => '2001:db8::1' ] );
        self::assertSame( 'AAAA', $rr->type() );
        self::assertSame( '2001:db8::1', $rr[ 'address' ] );
    }


    public function testArrayAccessExists() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( isset( $rr[ 'address' ] ) );
        self::assertFalse( isset( $rr[ 'nonexistent' ] ) );
    }


    public function testArrayAccessGet() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( '192.0.2.123', $rr[ 'address' ] );
    }


    public function testCNAMERecord() : void {
        $rr = new ResourceRecord( 'www.example.com', 'CNAME', 'IN', 3600,
            [ 'cname' => [ 'canonical', 'example', 'com' ] ] );
        self::assertSame( 'CNAME', $rr->type() );
        self::assertSame( [ 'canonical', 'example', 'com' ], $rr[ 'cname' ] );
    }


    public function testClassValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 1, $rr->classValue() ); // IN class value
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
            [ 'address' => new RDataValue( RDataType::IPv4Address, '192.0.2.123' ) ] );
        self::assertSame( '192.0.2.123', $rr[ 'address' ] );
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


    public function testFromArray() : void {
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
        self::assertSame( '192.0.2.1', $rr[ 'address' ] );
    }


    public function testFromArrayInvalidNameFormat() : void {
        $data = [
            'name' => 123, // Invalid name format
            'type' => 'A',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Invalid name format: must be string or array' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayMissingClass() : void {
        $data = [
            'name' => 'example.com',
            'type' => 'A',
            'address' => '192.0.2.1',
        ];
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Missing required field: class' );
        ResourceRecord::fromArray( $data );
    }


    public function testFromArrayMissingType() : void {
        $data = [
            'name' => 'example.com',
            'class' => 'IN',
            'address' => '192.0.2.1',
        ];
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Missing required field: type' );
        ResourceRecord::fromArray( $data );
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
        self::assertSame( '192.0.2.1', $rr[ 'address' ] );
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


    public function testFromString() : void {
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
        self::assertSame( [ '', 'This is a test', '' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesMixed() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar" baz' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesMixed2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar baz" qux' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar baz', 'qux' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesUnclosed() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForQuotesWithEmbeddedNewline() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\nan embedded newline.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\nan embedded newline." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEmbeddedNull() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\0an embedded null.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\0an embedded null." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEmbeddedTab() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\ta tab.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\ta tab." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedBackslash() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test with an \\ escaped backslash"' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test with an \\ escaped backslash' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuote() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with an escaped quote."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with an escaped quote.' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuote2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "\\"escaped quote\\""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '"escaped quote"' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuotes() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with escaped quotes\\"."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with escaped quotes".' ], $rr[ 'text' ] );
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


    public function testGetName() : void {
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


    public function testGetRDataValue() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        $value = $rr->getRDataValue( 'address' );
        self::assertInstanceOf( RDataValue::class, $value );
        self::assertSame( '192.0.2.123', $value->value );
    }


    public function testGetRDataValueMissing() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertNull( $rr->getRDataValue( 'nonexistent' ) );
    }


    public function testGetTTL() : void {
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


    public function testIsClass() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isClass( 1 ) );
        self::assertFalse( $rr->isClass( 'CH' ) );
    }


    public function testIsType() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertTrue( $rr->isType( 'A' ) );
        self::assertTrue( $rr->isType( 1 ) );
        self::assertFalse( $rr->isType( 'AAAA' ) );
    }


    public function testMXRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        self::assertSame( 'MX', $rr->type() );
        self::assertSame( 10, $rr[ 'preference' ] );
        self::assertSame( [ 'mail', 'example', 'com' ], $rr[ 'exchange' ] );
    }


    public function testNSRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'NS', 'IN', 3600,
            [ 'nsdname' => [ 'ns1', 'example', 'com' ] ] );
        self::assertSame( 'NS', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr[ 'nsdname' ] );
    }


    public function testPTRRecord() : void {
        $rr = new ResourceRecord( '1.2.0.192.in-addr.arpa', 'PTR', 'IN', 3600,
            [ 'ptrdname' => [ 'example', 'com' ] ] );
        self::assertSame( 'PTR', $rr->type() );
        self::assertSame( [ 'example', 'com' ], $rr[ 'ptrdname' ] );
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
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr[ 'mname' ] );
        self::assertSame( [ 'admin', 'example', 'com' ], $rr[ 'rname' ] );
        self::assertSame( 2023010101, $rr[ 'serial' ] );
        self::assertSame( 3600, $rr[ 'refresh' ] );
        self::assertSame( 1800, $rr[ 'retry' ] );
        self::assertSame( 604800, $rr[ 'expire' ] );
        self::assertSame( 86400, $rr[ 'minimum' ] );
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
        self::assertSame( '192.0.2.124', $rr[ 'address' ] );
    }


    public function testSetRDataValueInvalidKey() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Invalid RData key: invalid' );
        $rr->setRDataValue( 'invalid', 'value' );
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
        self::assertSame( [ 'v=spf1', 'include:_spf.example.com', '~all' ], $rr[ 'text' ] );
    }


    public function testToArray() : void {
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


}
