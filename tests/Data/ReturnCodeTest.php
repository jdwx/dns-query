<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\ReturnCodeException;
use JDWX\DNSQuery\Message\Header;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( ReturnCode::class )]
final class ReturnCodeTest extends TestCase {


    public function testDecode() : void {
        self::assertStringContainsString( 'completed successfully', ReturnCode::NOERROR->decode() );
        self::assertStringContainsString( 'ZZZ', ReturnCode::ZZZ_TEST_ONLY_DO_NOT_USE->decode() );
    }


    public function testFromExtended() : void {
        $header = new Header();
        self::assertSame( ReturnCode::NOERROR, ReturnCode::fromExtended( $header, 0 ) );
        self::assertSame( ReturnCode::BADSIG, ReturnCode::fromExtended( $header, 0x01000000 ) );
        $header->setRCode( ReturnCode::FORMERR );
        self::assertSame( ReturnCode::FORMERR, ReturnCode::fromExtended( $header, 0 ) );
        self::assertSame( ReturnCode::BADKEY, ReturnCode::fromExtended( $header, 0x1000000 ) );
    }


    public function testFromFlagWord() : void {
        self::assertSame( ReturnCode::NOERROR, ReturnCode::fromFlagWord( 0 ) );
        self::assertSame( ReturnCode::FORMERR, ReturnCode::fromFlagWord( 1 ) );
        self::expectException( ReturnCodeException::class );
        ReturnCode::fromFlagWord( 9999 );
    }


    public function testFromName() : void {
        self::assertSame( ReturnCode::NOERROR, ReturnCode::fromName( 'NOERROR' ) );
        self::assertSame( ReturnCode::SERVFAIL, ReturnCode::fromName( 'ServFail' ) );
        self::assertSame( ReturnCode::NXDOMAIN, ReturnCode::fromName( 'nxdomain' ) );
        self::expectException( ReturnCodeException::class );
        ReturnCode::fromName( 'Foo' );
    }


    public function testNormalize() : void {
        self::assertSame( ReturnCode::NOERROR, ReturnCode::normalize( 'NOERROR' ) );
        self::assertSame( ReturnCode::NOERROR, ReturnCode::normalize( 0 ) );
        self::assertSame( ReturnCode::NOERROR, ReturnCode::normalize( ReturnCode::NOERROR ) );
        self::expectException( ReturnCodeException::class );
        ReturnCode::normalize( 'Foo' );
    }


    public function testToFlagTTL() : void {
        self::assertSame( 0, ReturnCode::NOERROR->toFlagTTL() );
        self::assertSame( 0, ReturnCode::SERVFAIL->toFlagTTL() );
        self::assertSame( 0x1000000, ReturnCode::BADKEY->toFlagTTL() );
        self::assertSame( 0x1000000, ReturnCode::BADTIME->toFlagTTL() );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0, ReturnCode::NOERROR->toFlagWord() );
        self::assertSame( 2, ReturnCode::SERVFAIL->toFlagWord() );
        self::assertSame( 3, ReturnCode::NXDOMAIN->toFlagWord() );
    }


    public function testTryFromName() : void {
        self::assertSame( ReturnCode::NOERROR, ReturnCode::tryFromName( 'NOERROR', true ) );
        self::assertSame( ReturnCode::SERVFAIL, ReturnCode::tryFromName( 'ServFail' ) );
        self::assertSame( ReturnCode::NXDOMAIN, ReturnCode::tryFromName( 'nxdomain' ) );
        self::assertSame( ReturnCode::BADVERS, ReturnCode::tryFromName( 'BADVERS' ) );
        self::assertSame( ReturnCode::BADVERS, ReturnCode::tryFromName( 'BADVERS' ) );
        self::assertNull( ReturnCode::tryFromName( 'Foo' ) );
    }


}
