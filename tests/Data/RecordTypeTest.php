<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\Transport\Buffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RecordType::class )]
final class RecordTypeTest extends TestCase {


    public function testConsume() : void {
        $data = new Buffer( pack( 'n', RecordType::A->value ) );
        self::assertSame( RecordType::A, RecordType::consume( $data ) );
    }


    public function testConsumeForInsufficientData() : void {
        $data = new Buffer( 'a' );
        self::expectException( \OutOfBoundsException::class );
        RecordType::consume( $data );
    }


    public function testConsumeForInvalid() : void {
        $data = new Buffer( 'nope' );
        self::expectException( RecordTypeException::class );
        RecordType::consume( $data );
    }


    public function testFromBinary() : void {
        $data = pack( 'n', RecordType::A->value );
        self::assertSame( RecordType::A, RecordType::fromBinary( $data ) );
        $data = pack( 'n', RecordType::CNAME->value );
        self::assertSame( RecordType::CNAME, RecordType::fromBinary( $data ) );
    }


    public function testFromBinaryForInvalidLength() : void {
        self::expectException( RecordTypeException::class );
        RecordType::fromBinary( 'a' );
    }


    public function testFromBinaryForInvalidValue() : void {
        $data = pack( 'n', 9999 );
        self::expectException( RecordTypeException::class );
        RecordType::fromBinary( $data );
    }


    public function testFromName() : void {
        self::assertSame( RecordType::A, RecordType::fromName( 'A' ) );
        self::assertSame( RecordType::CNAME, RecordType::fromName( 'CnAmE' ) );
        self::assertSame( RecordType::MX, RecordType::fromName( 'Mx' ) );
        self::assertSame( RecordType::TXT, RecordType::fromName( 'txt' ) );
        self::assertSame( RecordType::ANY, RecordType::fromName( 'ANY' ) );
        self::assertSame( RecordType::ANY, RecordType::fromName( '*' ) );
        self::expectException( RecordTypeException::class );
        RecordType::fromName( 'FOO' );
    }


    public function testFromPhpId() : void {
        self::assertSame( RecordType::A, RecordType::fromPhpId( DNS_A ) );
        self::assertSame( RecordType::AAAA, RecordType::fromPhpId( DNS_AAAA ) );
        self::assertSame( RecordType::ANY, RecordType::fromPhpId( DNS_ALL ) );
        self::assertSame( RecordType::ANY, RecordType::fromPhpId( DNS_ANY ) );
        self::assertSame( RecordType::CNAME, RecordType::fromPhpId( DNS_CNAME ) );
        self::assertSame( RecordType::MX, RecordType::fromPhpId( DNS_MX ) );
        self::expectException( RecordTypeException::class );
        RecordType::fromPhpId( 9999 );
    }


    public function testIdToNameForInvalid() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::idToName( 9999 );
    }


    public function testIdToNameForValid() : void {
        self::assertSame( 'A', RecordType::idToName( RecordType::A->value ) );
        self::assertSame( 'ANY', RecordType::idToName( RecordType::ANY->value ) );
    }


    public function testIs() : void {
        self::assertTrue( RecordType::A->is( RecordType::A->value ) );
        self::assertFalse( RecordType::A->is( RecordType::CNAME->value ) );
        self::assertTrue( RecordType::A->is( RecordType::A ) );
        self::assertFalse( RecordType::A->is( RecordType::CNAME ) );
        self::assertTrue( RecordType::A->is( 1 ) );
        self::assertFalse( RecordType::A->is( 3 ) );
    }


    public function testIsValidId() : void {
        self::assertTrue( RecordType::isValidId( RecordType::A->value ) );
        self::assertFalse( RecordType::isValidId( 9999 ) );
    }


    public function testIsValidName() : void {
        self::assertTrue( RecordType::isValidName( '*' ) );
        self::assertTrue( RecordType::isValidName( 'A' ) );
        self::assertFalse( RecordType::isValidName( 'FOO' ) );
    }


    public function testNameToIdForInvalid() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::nameToId( 'FOO' );
    }


    public function testNameToIdForValid() : void {
        self::assertSame( RecordType::A->value, RecordType::nameToId( 'A' ) );
        self::assertSame( RecordType::ANY->value, RecordType::nameToId( 'ANY' ) );
    }


    public function testNormalize() : void {
        self::assertSame( RecordType::A, RecordType::normalize( 'A' ) );
        self::assertSame( RecordType::A, RecordType::normalize( RecordType::A->value ) );
        self::assertSame( RecordType::A, RecordType::normalize( RecordType::A ) );
    }


    public function testNormalizeForInvalidInt() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::normalize( 9999 );
    }


    public function testNormalizeForInvalidString() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::normalize( 'FOO' );
    }


    public function testPhpIdToId() : void {
        self::assertSame( RecordType::A->value, RecordType::phpIdToId( DNS_A ) );
        self::assertSame( RecordType::ANY->value, RecordType::phpIdToId( DNS_ANY ) );
        self::expectException( RecordTypeException::class );
        RecordType::phpIdToId( 9999 );
    }


    public function testPhpIdToName() : void {
        self::assertSame( 'A', RecordType::phpIdToName( DNS_A ) );
        self::assertSame( 'ANY', RecordType::phpIdToName( DNS_ANY ) );
        self::expectException( RecordTypeException::class );
        RecordType::phpIdToName( 9999 );
    }


    public function testToBinary() : void {
        self::assertSame( pack( 'n', RecordType::A->value ), RecordType::A->toBinary() );
    }


    public function testTryConsumeInvalid() : void {
        $data = new Buffer( pack( 'n', 9999 ) );
        self::assertNull( RecordType::tryConsume( $data ) );
    }


    public function testTryFromBinaryInvalidLength() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::tryFromBinary( 'a' );
    }


    public function testTryFromBinaryInvalidValue() : void {
        $data = pack( 'n', 9999 );
        self::assertNull( RecordType::tryFromBinary( $data ) );
    }


    public function testTryFromBinaryValid() : void {
        $data = pack( 'n', RecordType::A->value );
        self::assertSame( RecordType::A, RecordType::tryFromBinary( $data ) );
    }


    public function testTryFromName() : void {
        self::assertSame( RecordType::A, RecordType::tryFromName( 'A', true ) );
        self::assertSame( RecordType::CNAME, RecordType::tryFromName( 'CnAmE' ) );
        self::assertSame( RecordType::MX, RecordType::tryFromName( 'Mx' ) );
        self::assertSame( RecordType::TXT, RecordType::tryFromName( 'txt' ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromName( 'ANY' ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromName( '*' ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromName( 'ALL' ) );
        self::assertNull( RecordType::tryFromName( 'FOO' ) );
    }


    public function testTryIdToName() : void {
        self::assertSame( 'A', RecordType::tryIdToName( RecordType::A->value ) );
        self::assertNull( RecordType::tryIdToName( 9999 ) );
    }


    public function testTryNameToId() : void {
        self::assertSame( RecordType::A->value, RecordType::tryNameToId( 'A' ) );
        self::assertNull( RecordType::tryNameToId( 'FOO' ) );
    }


    public function testTryPhpIdToId() : void {
        self::assertSame( RecordType::A->value, RecordType::tryPhpIdToId( DNS_A ) );
        self::assertNull( RecordType::tryPhpIdToId( 9999 ) );
    }


    public function testTryPhpIdToName() : void {
        self::assertSame( 'A', RecordType::tryPhpIdToName( DNS_A ) );
        self::assertSame( 'ANY', RecordType::tryPhpIdToName( DNS_ALL ) );
        self::assertSame( 'ANY', RecordType::tryPhpIdToName( DNS_ANY ) );
        self::assertNull( RecordType::tryPhpIdToName( 9999 ) );
    }


}
