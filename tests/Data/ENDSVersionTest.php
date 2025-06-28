<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\EDNSVersion;
use PHPUnit\Framework\TestCase;


class ENDSVersionTest extends TestCase {


    public function testConstruct() : void {
        self::assertInstanceOf( EDNSVersion::class, new EDNSVersion( 0 ) );
        self::assertInstanceOf( EDNSVersion::class, new EDNSVersion( 255 ) );

        self::expectException( InvalidArgumentException::class );
        new EDNSVersion( -1 );

    }


    public function testConstructForTooBigValue() : void {
        self::expectException( InvalidArgumentException::class );
        new EDNSVersion( 256 );
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