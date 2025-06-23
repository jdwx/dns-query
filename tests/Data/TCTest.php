<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\TC;
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
