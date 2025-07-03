<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RA;
use JDWX\DNSQuery\Exceptions\FlagException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RA::class )]
final class RATest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::fromBool( false ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::fromBool( true ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::fromFlagWord( 0x0000 ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::fromFlagWord( 0x0080 ) );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::fromFlagWord( 0x007F ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::fromFlagWord( 0x00FF ) );
    }


    public function testFromName() : void {
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::fromName( 'nora' ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::fromName( 'ra' ) );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::fromName( 'Recursion_Not_Available' ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::fromName( 'Recursion_Available' ) );
        self::expectException( FlagException::class );
        RA::fromName( 'Invalid_Name' );
    }


    public function testNormalize() : void {
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::normalize( false ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::normalize( true ) );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::normalize( 0 ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::normalize( 1 ) );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::normalize( 'nora' ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::normalize( 'ra' ) );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, RA::normalize( 'Recursion_Not_Available' ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::normalize( 'Recursion_Available' ) );
        self::assertSame( RA::RECURSION_AVAILABLE, RA::normalize( RA::RECURSION_AVAILABLE ) );
    }


    public function testToBool() : void {
        self::assertFalse( RA::RECURSION_NOT_AVAILABLE->toBool() );
        self::assertTrue( RA::RECURSION_AVAILABLE->toBool() );
    }


    public function testToFlag() : void {
        self::assertSame( '', RA::RECURSION_NOT_AVAILABLE->toFlag() );
        self::assertSame( 'ra ', RA::RECURSION_AVAILABLE->toFlag() );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, RA::RECURSION_NOT_AVAILABLE->toFlagWord() );
        self::assertSame( 128, RA::RECURSION_AVAILABLE->toFlagWord() );
    }


}
