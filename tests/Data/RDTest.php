<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Exceptions\FlagException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RD::class )]
final class RDTest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::fromBool( false ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::fromBool( true ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::fromFlagWord( 0x0000 ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::fromFlagWord( 0x0100 ) );
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::fromFlagWord( 0xFEFF ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::fromFlagWord( 0xFFFF ) );
    }


    public function testFromName() : void {
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::fromName( 'nord' ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::fromName( 'rd' ) );
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::fromName( 'Recursion_Not_Desired' ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::fromName( 'Recursion_Desired' ) );
        self::expectException( FlagException::class );
        RD::fromName( 'Invalid_Name' );
    }


    public function testNormalize() : void {
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::normalize( false ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::normalize( true ) );
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::normalize( 0 ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::normalize( 1 ) );
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::normalize( 'nord' ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::normalize( 'rd' ) );
        self::assertSame( RD::RECURSION_NOT_DESIRED, RD::normalize( 'Recursion_Not_Desired' ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::normalize( 'Recursion_Desired' ) );
        self::assertSame( RD::RECURSION_DESIRED, RD::normalize( RD::RECURSION_DESIRED ) );
    }


    public function testToBool() : void {
        self::assertFalse( RD::RECURSION_NOT_DESIRED->toBool() );
        self::assertTrue( RD::RECURSION_DESIRED->toBool() );
    }


    public function testToFlag() : void {
        self::assertSame( '', RD::RECURSION_NOT_DESIRED->toFlag() );
        self::assertSame( 'rd ', RD::RECURSION_DESIRED->toFlag() );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, RD::RECURSION_NOT_DESIRED->toFlagWord() );
        self::assertSame( 256, RD::RECURSION_DESIRED->toFlagWord() );
    }


}
