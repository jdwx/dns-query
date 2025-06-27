<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( ResourceRecord::class )]
final class ResourceRecordTest extends TestCase {


    public function testConstruct() : void {
        $rr = new ResourceRecord( 'test.example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.123' ] );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
    }


    public function testFromString() : void {
        $rr = ResourceRecord::fromString( 'test.example.com. 3600 IN A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );

        $rr = ResourceRecord::fromString( 'test.example.com. "3600" "In" a 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertSame( 3600, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );

        $rr = ResourceRecord::fromString( 'test.example.com A 192.0.2.123' );
        self::assertSame( 'test.example.com', $rr->name() );
        self::assertGreaterThan( 0, $rr->ttl() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.123', $rr->getRDataValue( 'address' )->value );
    }


    public function testFromStringForBadClass() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 FOO A 1.2.3.4' );
    }


    public function testFromStringForBadRData() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN A 1.2.3' );
    }


    public function testFromStringForBadTTLHuge() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 100000000000000 IN A' );
    }


    public function testFromStringForBadTTLNegative() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. -20 IN A 1.2.3.4' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForBadType() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN FOO 1.2.3.4' );
    }


    public function testFromStringForDefaultClassIN() : void {
        $rr = ResourceRecord::fromString( 'example.com. A 1.2.3.4' );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForDefaultClassINWithTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 A 1.2.3.4' );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertSame( 3600, $rr->ttl() );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForDefaultTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. IN A 1.2.3.4' );
        # I'm not saying this is right or wrong, but it is the current behavior.
        self::assertSame( 86400, $rr->ttl() );
    }


    public function testFromStringForEmpty() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( '' );
    }


    public function testFromStringForFirstClass() : void {
        $rr = ResourceRecord::fromString( 'example.com. IN 12345 A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl() );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForFirstTTL() : void {
        $rr = ResourceRecord::fromString( 'example.com. 12345 IN A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl() );
        self::assertTrue( $rr->isClass( 'IN' ) );
        self::assertTrue( $rr->isType( 'A' ) );
    }


    public function testFromStringForNoTypeOrValue() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN' );
    }


    public function testFromStringForNoTypeWithTTL() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN 1.2.3.4' );
    }


    public function testFromStringForNoTypeWithoutTTL() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. IN 1.2.3.4' );
    }


    public function testFromStringForQuotedName() : void {
        $rr = ResourceRecord::fromString( '"Sure, why not?" 3600 IN A 1.2.3.4' );
        # Still making up my mind about this behavior.
        self::assertSame( '"sure, why not?"', $rr->name() );
    }


    public function testFromStringForQuotesEmpty() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "" "This is a test" ""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '', 'This is a test', '' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesMixed() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar" baz' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesMixed2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT foo "bar baz" qux' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'foo', 'bar baz', 'qux' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesUnclosed() : void {
        self::expectException( Exception::class );
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForQuotesWithEmbeddedNewline() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\nan embedded newline.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\nan embedded newline." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEmbeddedNull() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\0an embedded null.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\0an embedded null." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEmbeddedTab() : void {
        $rr = ResourceRecord::fromString( "example.com. 3600 IN TXT \"This is a test with\ta tab.\"" );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ "This is a test with\ta tab." ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedBackslash() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test with an \\ escaped backslash"' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test with an \\ escaped backslash' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuote() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with an escaped quote."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with an escaped quote.' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuote2() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "\\"escaped quote\\""' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ '"escaped quote"' ], $rr[ 'text' ] );
    }


    public function testFromStringForQuotesWithEscapedQuotes() : void {
        $rr = ResourceRecord::fromString( 'example.com. 3600 IN TXT "This is a test \\"with escaped quotes\\"."' );
        self::assertTrue( $rr->isType( 'TXT' ) );
        self::assertSame( [ 'This is a test "with escaped quotes".' ], $rr[ 'text' ] );
    }


    public function testFromStringForTotallyInvalid() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'invalid' );
    }


    public function testFromStringForTwoClasses() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. IN IN A 1.2.3.4' );
    }


    public function testFromStringForUnimplementedType() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN UNIMPLEMENTED 1.2.3.4' );
    }


    public function testFromStringForWrongRRClass() : void {
        self::expectException( Exception::class );
        ResourceRecord::fromString( 'example.com. 3600 IN SAY_WHAT 1.2.3.4' );
    }


}
