<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\QR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( QR::class )]
final class QRTest extends TestCase {


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
        self::assertSame( QR::QUERY, QR::normalize( 0 ) );
        self::assertSame( QR::QUERY, QR::normalize( 'QUERY' ) );
        self::assertSame( QR::QUERY, QR::normalize( QR::QUERY ) );
        self::assertSame( QR::RESPONSE, QR::normalize( QR::RESPONSE ) );
        self::expectException( \ValueError::class );
        QR::normalize( 2 );
    }


    public function testTryFromName() : void {
        self::assertSame( QR::QUERY, QR::tryFromName( 'QUERY' ) );
        self::assertSame( QR::RESPONSE, QR::tryFromName( 'Response' ) );
        self::assertNull( QR::tryFromName( 'FOO' ) );
    }


}
