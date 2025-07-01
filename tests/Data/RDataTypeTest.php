<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RDataType::class )]
final class RDataTypeTest extends TestCase {


    public function testConsume() : void {
        $args = [ 'example.com', 'bar', 'baz' ];
        $rDataValue = RDataType::DomainName->consume( $args );
        self::assertSame( [ 'example', 'com' ], $rDataValue->value );
        self::assertSame( [ 'bar', 'baz' ], $args );

        $rDataValue = RDataType::CharacterStringList->consume( $args );
        self::assertSame( [ 'bar', 'baz' ], $rDataValue->value );
        self::assertSame( [], $args );
    }


    public function testFormat() : void {
        self::assertSame( '1.2.3.4', RDataType::IPv4Address->format( '1.2.3.4' ) );
        self::assertSame( 'example', RDataType::CharacterString->format( 'example' ) );
        self::assertSame( '"foo bar"', RDataType::CharacterString->format( 'foo bar' ) );
        self::assertSame( 'foo "bar baz"', RDataType::CharacterStringList->format( [ 'foo', 'bar baz' ] ) );
    }


    public function testNormalize() : void {
        self::assertSame( RDataType::DomainName, RDataType::normalize( 0 ) );
        self::assertSame( RDataType::DomainName, RDataType::normalize( RDataType::DomainName ) );
    }


    public function testParse() : void {
        self::assertSame( [ 'foo.bar', 'baz' ], RDataType::DomainName->parse( '"foo.bar".baz' ) );
        self::assertSame( '1.2.3.4', RDataType::IPv4Address->parse( '1.2.3.4' ) );
        self::assertSame( '2001:db8::ff00:42:8329', RDataType::IPv6Address->parse( '2001:db8::ff00:42:8329' ) );
        self::assertSame( 'foo', RDataType::CharacterString->parse( 'foo' ) );
        self::assertSame( 12345, RDataType::UINT16->parse( '12345' ) );
        self::assertSame( 1234567890, RDataType::UINT32->parse( '1234567890' ) );
    }


    public function testParseForInvalidCharacterStringList() : void {
        self::expectException( LogicException::class );
        self::assertSame( [ 'foo', 'bar baz' ], RDataType::CharacterStringList->parse( '"foo".bar baz' ) );

    }


    public function testParseForInvalidIPv4() : void {
        self::expectException( RecordDataException::class );
        RDataType::IPv4Address->parse( '999.999.999.999' );
    }


    public function testParseForInvalidIPv6() : void {
        self::expectException( RecordDataException::class );
        RDataType::IPv6Address->parse( 'invalid-ipv6' );
    }


    public function testParseForInvalidUINT16() : void {
        self::expectException( RecordDataException::class );
        RDataType::UINT16->parse( '65536' ); // UINT16 max is 65535
    }


    public function testParseForInvalidUINT32() : void {
        self::expectException( RecordDataException::class );
        RDataType::UINT32->parse( '-1' );
    }


}
