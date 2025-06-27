<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\DomainName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( DomainName::class )]
final class DomainNameTest extends TestCase {


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
