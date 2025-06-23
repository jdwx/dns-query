<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\ZBits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( ZBits::class )]
final class ZBitsTest extends TestCase {


    public function testFromFlagWord() : void {
        self::assertSame( 0, ZBits::fromFlagWord( 0x0000 )->bits );
        self::assertSame( 0, ZBits::fromFlagWord( 0x008F )->bits );
        self::assertSame( 4, ZBits::fromFlagWord( 0x00CF )->bits );
        self::assertSame( 7, ZBits::fromFlagWord( 0x00FF )->bits );
    }


    public function testToFlagWord() : void {
        $z = new ZBits( 0 );
        self::assertSame( 0, $z->toFlagWord() );
        $z = new ZBits( 1 );
        self::assertSame( 0x0010, $z->toFlagWord() );
        $z = new ZBits( 4 );
        self::assertSame( 0x0040, $z->toFlagWord() );
    }


    public function testToString() : void {
        $z = new ZBits( 0 );
        self::assertSame( '0', (string) $z );
        $z = new ZBits( 1 );
        self::assertSame( '1', (string) $z );
        $z = new ZBits( 4 );
        self::assertSame( '4', (string) $z );
    }


}
