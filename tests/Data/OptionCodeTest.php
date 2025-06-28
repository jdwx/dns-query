<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\OptionCode;
use PHPUnit\Framework\TestCase;


class OptionCodeTest extends TestCase {


    public function testFromName() : void {
        self::assertSame( OptionCode::CHAIN, OptionCode::fromName( 'CHAIN' ) );

        self::expectException( InvalidArgumentException::class );
        OptionCode::fromName( "That's not an option!" );
    }


    public function testNormalize() : void {
        self::assertSame( OptionCode::CHAIN, OptionCode::normalize( OptionCode::CHAIN->value ) );
        self::assertSame( OptionCode::CHAIN, OptionCode::normalize( OptionCode::CHAIN ) );
        self::assertSame( OptionCode::CHAIN, OptionCode::normalize( 'CHAIN' ) );
    }


    public function testNormalizeForInvalidInt() : void {
        self::expectException( InvalidArgumentException::class );
        OptionCode::normalize( -9999 );
    }


    public function testNormalizeForInvalidString() : void {
        self::expectException( InvalidArgumentException::class );
        OptionCode::normalize( "That's not an option!" );
    }


    public function testTryFromName() : void {
        self::assertSame( OptionCode::CHAIN, OptionCode::tryFromName( 'CHAIN', true ) );
        self::assertNull( OptionCode::tryFromName( "That's not an option!" ) );
    }


}