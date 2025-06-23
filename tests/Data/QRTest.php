<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\QR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( QR::class )]
final class QRTest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( QR::QUERY, QR::fromBool( false ) );
        self::assertSame( QR::RESPONSE, QR::fromBool( true ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( QR::QUERY, QR::fromFlagWord( 0x0000 ) );
        self::assertSame( QR::RESPONSE, QR::fromFlagWord( 0x8000 ) );
        self::assertSame( QR::QUERY, QR::fromFlagWord( 0x7FFF ) );
        self::assertSame( QR::RESPONSE, QR::fromFlagWord( 0xFFFF ) );
    }


    public function testFromName() : void {
        self::assertSame( QR::QUERY, QR::fromName( 'QUERY' ) );
        self::assertSame( QR::RESPONSE, QR::fromName( 'Response' ) );
        self::expectException( \InvalidArgumentException::class );
        QR::fromName( 'FOO' );
    }


    public function testIdToName() : void {
        self::assertSame( 'QUERY', QR::idToName( 0 ) );
        self::assertSame( 'RESPONSE', QR::idToName( 1 ) );
        self::expectException( \InvalidArgumentException::class );
        QR::idToName( 2 );
    }


    public function testNameToId() : void {
        self::assertSame( 0, QR::nameToId( 'QUERY' ) );
        self::assertSame( 1, QR::nameToId( 'Response' ) );
        self::expectException( \InvalidArgumentException::class );
        QR::nameToId( 'FOO' );
    }


    public function testNormalize() : void {
        self::assertSame( QR::QUERY, QR::normalize( false ) );
        self::assertSame( QR::RESPONSE, QR::normalize( true ) );
        self::assertSame( QR::QUERY, QR::normalize( 0 ) );
        self::assertSame( QR::QUERY, QR::normalize( 'QUERY' ) );
        self::assertSame( QR::QUERY, QR::normalize( QR::QUERY ) );
        self::assertSame( QR::RESPONSE, QR::normalize( QR::RESPONSE ) );
    }


    public function testNormalizeInvalidInt() : void {
        self::expectException( \ValueError::class );
        QR::normalize( 2 );
    }


    public function testNormalizeInvalidString() : void {
        self::expectException( \InvalidArgumentException::class );
        QR::normalize( 'FOO' );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, QR::QUERY->toFlagWord() );
        self::assertSame( 0x8000, QR::RESPONSE->toFlagWord() );
    }


    public function testTryFromName() : void {
        self::assertSame( QR::QUERY, QR::tryFromName( 'QUERY' ) );
        self::assertSame( QR::RESPONSE, QR::tryFromName( 'Response' ) );
        self::assertNull( QR::tryFromName( 'FOO' ) );
    }


}
