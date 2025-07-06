<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\DomainName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( DomainName::class )]
final class DomainNameTest extends TestCase {


    public function testFormat() : void {
        self::assertSame( 'example.com', DomainName::format( [ 'example', 'com' ] ) );
        self::assertSame( '"example.com".com', DomainName::format( [ 'example.com', 'com' ] ) );
        self::assertSame( '"example foo".com', DomainName::format( [ 'example foo', 'com' ] ) );
    }


    public function testNormalize() : void {

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( 'example.com.' )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( 'example', [ 'com' ] )
        );

        self::assertSame(
            [ 'example', 'net' ],
            DomainName::normalize( 'example.net.', [ 'com' ] )
        );

        self::assertSame(
            [ 'example', 'com', 'net' ],
            DomainName::normalize( [ 'example', 'com' ], [ 'net' ] )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( [ 'example', 'com', '' ], [ 'net' ] )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( '"example".com' )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( '"example".com.', [ 'net' ] )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( [ 'example', 'com' ] )
        );

        self::assertSame(
            [ 'example', 'com' ],
            DomainName::normalize( [ 'example', 'com', '' ], [ 'com' ] )
        );
    }


    public function testParseName() : void {
        self::assertSame(
            [ 'test', 'example', 'com' ],
            DomainName::parse( 'test.example.com.' )
        );
        self::assertSame(
            [ 'test', 'example', 'com' ],
            DomainName::parse( 'test', [ 'example', 'com' ] )
        );

        self::assertSame(
            [ 'test' ],
            DomainName::parse( 'test.', [ 'example', 'com' ] )
        );

        self::assertSame(
            [ 'test.example', 'com' ],
            DomainName::parse( '"test.example".com' )
        );

        self::assertSame(
            [ 'test.example', 'com' ],
            DomainName::parse( '"test.ex"ample.com' )
        );


    }


}
