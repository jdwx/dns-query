<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\ReturnCodeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( ReturnCode::class )]
final class ReturnCodeTest extends TestCase {


    public function testDecode() : void {
        self::assertStringContainsString( 'completed successfully', ReturnCode::NOERROR->decode() );
        self::assertStringContainsString( 'ZZZ', ReturnCode::ZZZ_TEST_ONLY_DO_NOT_USE->decode() );
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


    public function testTryFromName() : void {
        self::assertSame( ReturnCode::NOERROR, ReturnCode::tryFromName( 'NOERROR' ) );
        self::assertSame( ReturnCode::SERVFAIL, ReturnCode::tryFromName( 'ServFail' ) );
        self::assertSame( ReturnCode::NXDOMAIN, ReturnCode::tryFromName( 'nxdomain' ) );
        self::assertSame( ReturnCode::BADVERS, ReturnCode::tryFromName( 'BADVERS' ) );
        self::assertNull( ReturnCode::tryFromName( 'Foo' ) );
    }


}
