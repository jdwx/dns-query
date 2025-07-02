<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\AA;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AA::class )]
final class AATest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( AA::NON_AUTHORITATIVE, AA::fromBool( false ) );
        self::assertSame( AA::AUTHORITATIVE, AA::fromBool( true ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( AA::NON_AUTHORITATIVE, AA::fromFlagWord( 0x0000 ) );
        self::assertSame( AA::AUTHORITATIVE, AA::fromFlagWord( 0x0400 ) );
        self::assertSame( AA::NON_AUTHORITATIVE, AA::fromFlagWord( 0xFBFF ) );
        self::assertSame( AA::AUTHORITATIVE, AA::fromFlagWord( 0xFFFF ) );
    }


    public function testNormalize() : void {
        self::assertSame( AA::NON_AUTHORITATIVE, AA::normalize( false ) );
        self::assertSame( AA::AUTHORITATIVE, AA::normalize( true ) );
        self::assertSame( AA::NON_AUTHORITATIVE, AA::normalize( 0 ) );
        self::assertSame( AA::AUTHORITATIVE, AA::normalize( 1 ) );
        self::assertSame( AA::NON_AUTHORITATIVE, AA::normalize( 'noaa' ) );
        self::assertSame( AA::AUTHORITATIVE, AA::normalize( 'aa' ) );
    }


    public function testToBool() : void {
        self::assertFalse( AA::NON_AUTHORITATIVE->toBool() );
        self::assertTrue( AA::AUTHORITATIVE->toBool() );
    }


    public function testToFlag() : void {
        self::assertSame( '', AA::NON_AUTHORITATIVE->toFlag() );
        self::assertSame( 'aa ', AA::AUTHORITATIVE->toFlag() );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, AA::NON_AUTHORITATIVE->toFlagWord() );
        self::assertSame( 1024, AA::AUTHORITATIVE->toFlagWord() );
    }


}
