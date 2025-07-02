<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\DOK;
use PHPUnit\Framework\TestCase;


class DOKTest extends TestCase {


    public function testFromBool() : void {
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::fromBool( false ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::fromBool( true ) );
    }


    public function testFromFlagTTL() : void {
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::fromFlagTTL( 0 ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::fromFlagTTL( 0x8000 ) );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::fromFlagTTL( 0x7FFF ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::fromFlagTTL( 0xFFFF ) );
    }


    public function testNormalize() : void {
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::normalize( false ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::normalize( true ) );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::normalize( 0 ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::normalize( 1 ) );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, DOK::normalize( DOK::DNSSEC_NOT_SUPPORTED ) );
        self::assertSame( DOK::DNSSEC_OK, DOK::normalize( DOK::DNSSEC_OK ) );
    }


    public function testToFlagTTL() : void {
        self::assertSame( 0, DOK::DNSSEC_NOT_SUPPORTED->toFlagTTL() );
        self::assertSame( 0x8000, DOK::DNSSEC_OK->toFlagTTL() );
    }


}