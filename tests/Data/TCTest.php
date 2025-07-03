<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Exceptions\FlagException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TC::class )]
final class TCTest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( TC::NOT_TRUNCATED, TC::fromBool( false ) );
        self::assertSame( TC::TRUNCATED, TC::fromBool( true ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( TC::NOT_TRUNCATED, TC::fromFlagWord( 0x0000 ) );
        self::assertSame( TC::TRUNCATED, TC::fromFlagWord( 0x0200 ) );
        self::assertSame( TC::NOT_TRUNCATED, TC::fromFlagWord( 0xFDFF ) );
        self::assertSame( TC::TRUNCATED, TC::fromFlagWord( 0xFFFF ) );
    }


    public function testFromName() : void {
        self::assertSame( TC::NOT_TRUNCATED, TC::fromName( 'notc' ) );
        self::assertSame( TC::TRUNCATED, TC::fromName( 'tc' ) );
        self::assertSame( TC::NOT_TRUNCATED, TC::fromName( 'Not_Truncated' ) );
        self::assertSame( TC::TRUNCATED, TC::fromName( 'TRUNCATED' ) );
        self::expectException( FlagException::class );
        TC::fromName( 'Invalid_Name' );
    }


    public function testNormalize() : void {
        self::assertSame( TC::NOT_TRUNCATED, TC::normalize( false ) );
        self::assertSame( TC::TRUNCATED, TC::normalize( true ) );
        self::assertSame( TC::NOT_TRUNCATED, TC::normalize( 0 ) );
        self::assertSame( TC::TRUNCATED, TC::normalize( 1 ) );
        self::assertSame( TC::NOT_TRUNCATED, TC::normalize( 'notc' ) );
        self::assertSame( TC::TRUNCATED, TC::normalize( 'tc' ) );
        self::assertSame( TC::NOT_TRUNCATED, TC::normalize( 'Not_Truncated' ) );
        self::assertSame( TC::TRUNCATED, TC::normalize( 'TRUNCATED' ) );
        self::assertSame( TC::TRUNCATED, TC::normalize( TC::TRUNCATED ) );
    }


    public function testToBool() : void {
        self::assertFalse( TC::NOT_TRUNCATED->toBool() );
        self::assertTrue( TC::TRUNCATED->toBool() );
    }


    public function testToFlag() : void {
        self::assertSame( '', TC::NOT_TRUNCATED->toFlag() );
        self::assertSame( 'tc ', TC::TRUNCATED->toFlag() );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, TC::NOT_TRUNCATED->toFlagWord() );
        self::assertSame( 512, TC::TRUNCATED->toFlagWord() );
    }


}
