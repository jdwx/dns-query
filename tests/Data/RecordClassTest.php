<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Buffer\Buffer;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\Strict\OK;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RecordClass::class )]
final class RecordClassTest extends TestCase {


    public function testAnyToId() : void {
        self::assertSame( RecordClass::IN->value, RecordClass::anyToId( RecordClass::IN ) );
        self::assertSame( RecordClass::IN->value, RecordClass::anyToId( RecordClass::IN->value ) );
        self::assertSame( RecordClass::IN->value, RecordClass::anyToId( 'in' ) );
        self::assertSame( 12345, RecordClass::anyToId( 12345 ) );
        self::assertSame( 12345, RecordClass::anyToId( 'CLASS12345' ) );
        self::expectException( RecordClassException::class );
        RecordClass::anyToId( 'FOO' );
    }


    public function testAnyToName() : void {
        self::assertSame( RecordClass::IN->name, RecordClass::anyToName( RecordClass::IN ) );
        self::assertSame( RecordClass::IN->name, RecordClass::anyToName( RecordClass::IN->value ) );
        self::assertSame( RecordClass::IN->name, RecordClass::anyToName( 'in' ) );
        self::assertSame( 'CLASS12345', RecordClass::anyToName( 12345 ) );
        self::assertSame( 'CLASS12345', RecordClass::anyToName( 'CLASS12345' ) );
    }


    public function testConsume() : void {
        $data = new Buffer( OK::pack( 'n', 1 ) . 'rest' );
        self::assertSame( RecordClass::IN, RecordClass::consume( $data ) );
        self::assertSame( 2, $data->tell() );
    }


    public function testConsumeForInsufficientData() : void {
        $data = new Buffer( 'a' );
        self::expectException( OutOfBoundsException::class );
        RecordClass::consume( $data );
    }


    public function testConsumeForInvalid() : void {
        $data = new Buffer( 'nope' );
        self::expectException( RecordClassException::class );
        RecordClass::consume( $data );
    }


    public function testFromBinary() : void {
        $data = OK::pack( 'n', 1 );
        self::assertSame( RecordClass::IN, RecordClass::fromBinary( $data ) );
    }


    public function testFromBinaryForInvalidLength() : void {
        self::expectException( RecordClassException::class );
        RecordClass::fromBinary( 'a' );
    }


    public function testFromBinaryForInvalidValue() : void {
        $data = pack( 'n', 999 );
        self::expectException( RecordClassException::class );
        RecordClass::fromBinary( $data );
    }


    public function testFromNameForEmpty() : void {
        self::expectException( RecordClassException::class );
        RecordClass::fromName( '' );
    }


    public function testFromNameForInvalid() : void {
        self::expectException( RecordClassException::class );
        RecordClass::fromName( 'FOO' );
    }


    public function testFromNameForValid() : void {
        self::assertSame( RecordClass::IN, RecordClass::fromName( 'IN' ) );
        self::assertSame( RecordClass::CH, RecordClass::fromName( 'ch' ) );
        self::assertSame( RecordClass::HS, RecordClass::fromName( 'Hs' ) );
        self::assertSame( RecordClass::NONE, RecordClass::fromName( 'none' ) );
        self::assertSame( RecordClass::ANY, RecordClass::fromName( 'ANY' ) );
        self::assertSame( RecordClass::IN, RecordClass::fromName( 'CLASS1' ) );
    }


    public function testIdToNameForInvalid() : void {
        self::expectException( RecordClassException::class );
        RecordClass::idToName( -1 );
    }


    public function testIdToNameForValid() : void {
        self::assertSame( 'IN', RecordClass::idToName( 1 ) );
        self::assertSame( 'CH', RecordClass::idToName( 3 ) );
        self::assertSame( 'HS', RecordClass::idToName( 4 ) );
        self::assertSame( 'NONE', RecordClass::idToName( 254 ) );
        self::assertSame( 'ANY', RecordClass::idToName( 255 ) );
        self::assertSame( 'CLASS999', RecordClass::idToName( 999 ) );
    }


    public function testIs() : void {
        self::assertTrue( RecordClass::IN->is( RecordClass::IN ) );
        self::assertFalse( RecordClass::IN->is( RecordClass::CH ) );
        self::assertTrue( RecordClass::IN->is( 1 ) );
        self::assertFalse( RecordClass::IN->is( 3 ) );
        self::assertTrue( RecordClass::IN->is( 'IN' ) );
        self::assertFalse( RecordClass::IN->is( 'CH' ) );

        $rr = new ResourceRecord( [], 'A', 'IN', 3600, '1.2.3.4' );
        self::assertTrue( $rr->getClass()->is( RecordClass::IN ) );
        self::assertFalse( $rr->getClass()->is( RecordClass::CH ) );
        self::assertTrue( RecordClass::IN->is( $rr ) );
        self::assertFalse( RecordClass::CH->is( $rr ) );
    }


    public function testIsKnownId() : void {
        self::assertTrue( RecordClass::isKnownId( 1 ) );
        self::assertFalse( RecordClass::isKnownId( 999 ) );
    }


    public function testIsValidName() : void {
        self::assertTrue( RecordClass::isValidName( 'IN' ) );
        self::assertTrue( RecordClass::isValidName( 'CH' ) );
        self::assertTrue( RecordClass::isValidName( 'HS' ) );
        self::assertFalse( RecordClass::isValidName( 'INVALID' ) );
    }


    public function testNameToIdForInvalid() : void {
        self::expectException( RecordClassException::class );
        RecordClass::nameToId( 'FOO' );
    }


    public function testNameToIdForValid() : void {
        self::assertSame( 1, RecordClass::nameToId( 'IN' ) );
    }


    public function testNormalizeForInvalidInt() : void {
        self::expectException( RecordClassException::class );
        RecordClass::normalize( 999 );
    }


    public function testNormalizeForInvalidString() : void {
        self::expectException( RecordClassException::class );
        RecordClass::normalize( 'FOO' );
    }


    public function testNormalizeForValid() : void {
        self::assertSame( RecordClass::IN, RecordClass::normalize( 1 ) );
        self::assertSame( RecordClass::IN, RecordClass::normalize( 'IN' ) );
        self::assertSame( RecordClass::IN, RecordClass::normalize( RecordClass::IN ) );
    }


    public function testToBinary() : void {
        self::assertSame( OK::pack( 'n', 1 ), RecordClass::IN->toBinary() );
    }


    public function testTryConsume() : void {
        $data = new Buffer( OK::pack( 'n', 1 ) . 'rest' );
        self::assertSame( RecordClass::IN, RecordClass::tryConsume( $data ) );
        self::assertSame( 2, $data->tell() );
    }


    public function testTryConsumeForInsufficientData() : void {
        $data = new Buffer( 'a' );
        self::expectException( OutOfBoundsException::class );
        RecordClass::tryConsume( $data );
    }


    public function testTryConsumeForInvalidValue() : void {
        $data = new Buffer( 'nope' );
        self::assertNull( RecordClass::tryConsume( $data ) );
        # It still ate the two bytes
        self::assertSame( 2, $data->tell() );
    }


    public function testTryFromBinaryForInvalidLength() : void {
        self::expectException( RecordClassException::class );
        RecordClass::tryFromBinary( 'a' );
    }


    public function testTryFromBinaryForInvalidValue() : void {
        $data = pack( 'n', 999 );
        self::assertNull( RecordClass::tryFromBinary( $data ) );
    }


    public function testTryFromBinaryForValid() : void {
        $data = OK::pack( 'n', 1 );
        self::assertSame( RecordClass::IN, RecordClass::tryFromBinary( $data ) );
    }


    public function testTryFromName() : void {
        self::assertSame( RecordClass::IN, RecordClass::tryFromName( 'IN' ) );
        self::assertNull( RecordClass::tryFromName( 'FOO' ) );
    }


    public function testTryIdToName() : void {
        self::assertSame( 'IN', RecordClass::tryIdToName( 1 ) );
        self::assertNull( RecordClass::tryIdToName( 999 ) );
    }


    public function testTryNameToId() : void {
        self::assertSame( 1, RecordClass::tryNameToId( 'IN' ) );
        self::assertNull( RecordClass::tryNameToId( 'FOO' ) );
    }


}
