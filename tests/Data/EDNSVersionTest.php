<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Exceptions\FlagException;
use PHPUnit\Framework\TestCase;


class EDNSVersionTest extends TestCase {


    public function testConstruct() : void {
        self::assertInstanceOf( EDNSVersion::class, new EDNSVersion( 0 ) );
        self::assertInstanceOf( EDNSVersion::class, new EDNSVersion( 255 ) );

        self::expectException( FlagException::class );
        $x = new EDNSVersion( -1 );
        unset( $x );
    }


    public function testConstructForTooBigValue() : void {
        self::expectException( FlagException::class );
        $x = new EDNSVersion( 256 );
        unset( $x );
    }


    public function testFrom() : void {
        self::assertInstanceOf( EDNSVersion::class, EDNSVersion::from( 0 ) );
        self::assertInstanceOf( EDNSVersion::class, EDNSVersion::from( 255 ) );
        self::assertSame( 0, EDNSVersion::from( 0 )->value );
        self::assertSame( 255, EDNSVersion::from( 255 )->value );
        self::expectException( FlagException::class );
        EDNSVersion::from( -1 );
    }


    public function testFromFlagTTL() : void {
        self::assertSame( 0, EDNSVersion::fromFlagTTL( 0 )->value );
        self::assertSame( 1, EDNSVersion::fromFlagTTL( 0x10000 )->value ); // 1 << 16
        self::assertSame( 18, EDNSVersion::fromFlagTTL( 0x120000 )->value ); // 18 << 16
    }


    public function testNormalize() : void {
        $version = new EDNSVersion( 0 );
        self::assertSame( 0, EDNSVersion::normalize( 0 )->value );
        self::assertSame( 0, EDNSVersion::normalize( $version )->value );
        self::assertSame( 123, EDNSVersion::normalize( 123 )->value );
    }


    public function testToFlagTTL() : void {
        self::assertSame( 0, ( new EDNSVersion( 0 ) )->toFlagTTL() );
        self::assertSame( 0x10000, ( new EDNSVersion( 1 ) )->toFlagTTL() ); // 1 << 16
        self::assertSame( 0x120000, ( new EDNSVersion( 18 ) )->toFlagTTL() ); // 18 << 16
    }


}