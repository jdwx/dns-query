<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\Packet\Packet;
use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\ALL;
use JDWX\DNSQuery\RR\ANY;
use JDWX\DNSQuery\RR\DS;
use JDWX\DNSQuery\RR\MX;
use JDWX\DNSQuery\RR\RR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RecordType::class )]
final class RecordTypeTest extends TestCase {


    public function testClassNameToId() : void {
        self::assertSame( RecordType::A->value, RecordType::classNameToId( A::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::classNameToId( ALL::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::classNameToId( ANY::class ) );
        self::assertSame( RecordType::DS->value, RecordType::classNameToId( DS::class ) );
        self::assertSame( RecordType::MX->value, RecordType::classNameToId( MX::class ) );
        self::expectException( RecordTypeException::class );
        RecordType::classNameToId( 'Foo' );
    }


    public function testClassNameToName() : void {
        self::assertSame( 'A', RecordType::classNameToName( A::class ) );
        self::assertSame( 'ANY', RecordType::classNameToName( ALL::class ) );
        self::assertSame( 'ANY', RecordType::classNameToName( ANY::class ) );
        self::assertSame( 'DS', RecordType::classNameToName( DS::class ) );
        self::assertSame( 'MX', RecordType::classNameToName( MX::class ) );
        self::expectException( RecordTypeException::class );
        RecordType::classNameToName( $this::class );
    }


    public function testConsume() : void {
        $data = pack( 'n', RecordType::A->value );
        $offset = 0;
        self::assertSame( RecordType::A, RecordType::consume( $data, $offset ) );
    }


    public function testConsumeForInsufficientData() : void {
        $data = 'a';
        $offset = 0;
        self::expectException( \OutOfBoundsException::class );
        RecordType::consume( $data, $offset );
    }


    public function testConsumeForInvalid() : void {
        $data = 'nope';
        $offset = 0;
        self::expectException( RecordTypeException::class );
        RecordType::consume( $data, $offset );
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


    public function testFromClassName() : void {
        self::assertSame( RecordType::A, RecordType::fromClassName( A::class ) );
        self::assertSame( RecordType::ANY, RecordType::fromClassName( ALL::class ) );
        self::assertSame( RecordType::ANY, RecordType::fromClassName( ANY::class ) );
        self::assertSame( RecordType::DS, RecordType::fromClassName( DS::class ) );
        self::assertSame( RecordType::MX, RecordType::fromClassName( MX::class ) );
    }


    public function testFromClassNameForNonExistentClass() : void {
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record class' );
        RecordType::fromClassName( 'Foo' );
    }


    public function testFromClassNameForNonRRClass() : void {
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record class' );
        RecordType::fromClassName( $this::class );
    }


    public function testFromClassNameForUnknownRR() : void {

        $rr = new class() extends RR {


            protected function rrToString() : string {
                return '';
            }


            protected function rrGet( Packet $i_packet ) : ?string {
                return null;
            }


            protected function rrSet( Packet $i_packet ) : bool {
                return false;
            }


            protected function rrFromString( array $i_rData ) : bool {
                return false;
            }


        };
        self::expectException( RecordTypeException::class );
        self::expectExceptionMessage( 'Unknown record class' );
        RecordType::fromClassName( $rr::class );
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


    public function testIdToClassName() : void {
        self::assertSame( A::class, RecordType::idToClassName( RecordType::A->value ) );
        self::assertSame( ANY::class, RecordType::idToClassName( RecordType::ANY->value ) );
        self::expectException( RecordTypeException::class );
        RecordType::idToClassName( 9999 );
    }


    public function testIdToNameForInvalid() : void {
        $this->expectException( RecordTypeException::class );
        RecordType::idToName( 9999 );
    }


    public function testIdToNameForValid() : void {
        self::assertSame( 'A', RecordType::idToName( RecordType::A->value ) );
        self::assertSame( 'ANY', RecordType::idToName( RecordType::ANY->value ) );
    }


    public function testIsValidClassName() : void {
        self::assertTrue( RecordType::isValidClassName( A::class ) );
        self::assertFalse( RecordType::isValidClassName( $this::class ) );
        self::assertFalse( RecordType::isValidClassName( 'Foo' ) );
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


    public function testNameToClassName() : void {
        self::assertSame( A::class, RecordType::nameToClassName( 'A' ) );
        self::assertSame( ANY::class, RecordType::nameToClassName( 'Any' ) );
        self::assertSame( MX::class, RecordType::nameToClassName( 'mx' ) );
        self::expectException( RecordTypeException::class );
        RecordType::nameToClassName( 'FOO' );
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


    public function testPhpIdToClassName() : void {
        self::assertSame( A::class, RecordType::phpIdToClassName( DNS_A ) );
        self::assertSame( ANY::class, RecordType::phpIdToClassName( DNS_ANY ) );
        self::expectException( RecordTypeException::class );
        RecordType::phpIdToClassName( 9999 );
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


    public function testToClassName() : void {
        self::assertSame( A::class, RecordType::A->toClassName() );
        self::assertSame( ANY::class, RecordType::ANY->toClassName() );
        self::expectException( RecordTypeException::class );
        RecordType::ZZZ_TEST_ONLY_DO_NOT_USE->toClassName();
    }


    public function testTryClassNameToId() : void {
        self::assertSame( RecordType::A->value, RecordType::tryClassNameToId( A::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::tryClassNameToId( ALL::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::tryClassNameToId( ANY::class ) );
        self::assertSame( RecordType::DS->value, RecordType::tryClassNameToId( DS::class ) );
        self::assertSame( RecordType::MX->value, RecordType::tryClassNameToId( MX::class ) );
        self::assertNull( RecordType::tryClassNameToId( 'Foo' ) );
    }


    public function testTryClassNameToName() : void {
        self::assertSame( 'A', RecordType::tryClassNameToName( A::class ) );
        self::assertSame( 'ANY', RecordType::tryClassNameToName( ALL::class ) );
        self::assertSame( 'ANY', RecordType::tryClassNameToName( ANY::class ) );
        self::assertSame( 'DS', RecordType::tryClassNameToName( DS::class ) );
        self::assertSame( 'MX', RecordType::tryClassNameToName( MX::class ) );
        self::assertNull( RecordType::tryClassNameToName( $this::class ) );
    }


    public function testTryConsumeInvalid() : void {
        $data = pack( 'n', 9999 );
        $offset = 0;
        self::assertNull( RecordType::tryConsume( $data, $offset ) );
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


    public function testTryFromClassName() : void {
        self::assertSame( RecordType::A, RecordType::tryFromClassName( A::class ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromClassName( ALL::class ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromClassName( ANY::class ) );
        self::assertSame( RecordType::DS, RecordType::tryFromClassName( DS::class ) );
        self::assertSame( RecordType::MX, RecordType::tryFromClassName( MX::class ) );
        self::assertNull( RecordType::tryFromClassName( $this::class ) );
        self::assertNull( RecordType::tryFromClassName( 'Foo' ) );
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


    public function testTryIdToClassName() : void {
        self::assertSame( A::class, RecordType::tryIdToClassName( RecordType::A->value ) );
        self::assertNull( RecordType::tryIdToClassName( 9999 ) );
    }


    public function testTryIdToName() : void {
        self::assertSame( 'A', RecordType::tryIdToName( RecordType::A->value ) );
        self::assertNull( RecordType::tryIdToName( 9999 ) );
    }


    public function testTryNameToClassName() : void {
        self::assertSame( A::class, RecordType::tryNameToClassName( 'A' ) );
        self::assertNull( RecordType::tryNameToClassName( 'FOO' ) );
    }


    public function testTryNameToId() : void {
        self::assertSame( RecordType::A->value, RecordType::tryNameToId( 'A' ) );
        self::assertNull( RecordType::tryNameToId( 'FOO' ) );
    }


    public function testTryPhpIdToClassName() : void {
        self::assertSame( A::class, RecordType::tryPhpIdToClassName( DNS_A ) );
        self::assertSame( ALL::class, RecordType::tryPhpIdToClassName( DNS_ALL ) );
        self::assertSame( ANY::class, RecordType::tryPhpIdToClassName( DNS_ANY ) );
        self::assertNull( RecordType::tryPhpIdToClassName( 9999 ) );
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
