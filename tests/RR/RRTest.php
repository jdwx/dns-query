<?php /** @noinspection ForgottenDebugOutputInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\RR\RR;
use JDWX\DNSQuery\RR\TXT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;


/**
 * NOTE: This is in addition to the existing tests in the LegacyParserTest class.
 */
#[CoversClass( RR::class )]
final class RRTest extends TestCase {


    public function testFromStringForBadClass() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 FOO A 1.2.3.4' );
    }


    public function testFromStringForBadRData() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN A 1.2.3' );
    }


    public function testFromStringForBadTTLHuge() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 100000000000000 IN A' );
    }


    public function testFromStringForBadTTLNegative() : void {
        $this->expectException( Exception::class );
        $rr = RR::fromString( 'example.com. -20 IN A 1.2.3.4' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForBadType() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN FOO 1.2.3.4' );
    }


    public function testFromStringForDefaultClassIN() : void {
        $rr = RR::fromString( 'example.com. A 1.2.3.4' );
        self::assertSame( 'IN', $rr->class );
        self::assertSame( 'A', $rr->type );
    }


    public function testFromStringForDefaultClassINWithTTL() : void {
        $rr = RR::fromString( 'example.com. 3600 A 1.2.3.4' );
        self::assertSame( 'IN', $rr->class );
        self::assertSame( 3600, $rr->ttl );
        self::assertSame( 'A', $rr->type );
    }


    public function testFromStringForDefaultTTL() : void {
        $rr = RR::fromString( 'example.com. IN A 1.2.3.4' );
        # I'm not saying this is right or wrong, but it is the current behavior.
        self::assertSame( 86400, $rr->ttl );
    }


    public function testFromStringForEmpty() : void {
        $this->expectException( Exception::class );
        RR::fromString( '' );
    }


    public function testFromStringForFirstClass() : void {
        $rr = RR::fromString( 'example.com. IN 12345 A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl );
        self::assertSame( 'IN', $rr->class );
        self::assertSame( 'A', $rr->type );
    }


    public function testFromStringForFirstTTL() : void {
        $rr = RR::fromString( 'example.com. 12345 IN A 1.2.3.4' );
        self::assertSame( 12345, $rr->ttl );
        self::assertSame( 'IN', $rr->class );
        self::assertSame( 'A', $rr->type );
    }


    public function testFromStringForNoTypeOrValue() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN' );
    }


    public function testFromStringForNoTypeWithTTL() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN 1.2.3.4' );
    }


    public function testFromStringForNoTypeWithoutTTL() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. IN 1.2.3.4' );
    }


    public function testFromStringForQuotedName() : void {
        $rr = RR::fromString( '"Sure, why not?" 3600 IN A 1.2.3.4' );
        # Still making up my mind about this behavior.
        self::assertSame( '"sure, why not?"', $rr->name );
    }


    public function testFromStringForQuotesEmpty() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT "" "This is a test" ""' );
        assert( $rr instanceof TXT );
        self::assertSame( [ '', 'This is a test', '' ], $rr->text );
    }


    public function testFromStringForQuotesMixed() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT foo "bar" baz' );
        assert( $rr instanceof TXT );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $rr->text );
    }


    public function testFromStringForQuotesMixed2() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT foo "bar baz" qux' );
        assert( $rr instanceof TXT );
        self::assertSame( [ 'foo', 'bar baz', 'qux' ], $rr->text );
    }


    public function testFromStringForQuotesUnclosed() : void {
        $this->expectException( Exception::class );
        $rr = RR::fromString( 'example.com. 3600 IN TXT "This is a test' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForQuotesWithEmbeddedNewline() : void {
        $rr = RR::fromString( "example.com. 3600 IN TXT \"This is a test with\nan embedded newline.\"" );
        assert( $rr instanceof TXT );
        self::assertSame( [ "This is a test with\nan embedded newline." ], $rr->text );
    }


    public function testFromStringForQuotesWithEmbeddedNull() : void {
        $rr = RR::fromString( "example.com. 3600 IN TXT \"This is a test with\0an embedded null.\"" );
        assert( $rr instanceof TXT );
        self::assertSame( [ "This is a test with\0an embedded null." ], $rr->text );
    }


    public function testFromStringForQuotesWithEmbeddedTab() : void {
        $rr = RR::fromString( "example.com. 3600 IN TXT \"This is a test with\ta tab.\"" );
        assert( $rr instanceof TXT );
        self::assertSame( [ "This is a test with\ta tab." ], $rr->text );
    }


    public function testFromStringForQuotesWithEscapedBackslash() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT "This is a test with an \\ escaped backslash"' );
        assert( $rr instanceof TXT );
        self::assertSame( [ 'This is a test with an \\ escaped backslash' ], $rr->text );
    }


    public function testFromStringForQuotesWithEscapedQuote() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT "This is a test \\"with an escaped quote."' );
        assert( $rr instanceof TXT );
        self::assertSame( [ 'This is a test "with an escaped quote.' ], $rr->text );
    }


    public function testFromStringForQuotesWithEscapedQuote2() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT "\\"escaped quote\\""' );
        assert( $rr instanceof TXT );
        self::assertSame( [ '"escaped quote"' ], $rr->text );
    }


    public function testFromStringForQuotesWithEscapedQuotes() : void {
        $rr = RR::fromString( 'example.com. 3600 IN TXT "This is a test \\"with escaped quotes\\"."' );
        assert( $rr instanceof TXT );
        self::assertSame( [ 'This is a test "with escaped quotes".' ], $rr->text );
    }


    public function testFromStringForTotallyInvalid() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'invalid' );
    }


    public function testFromStringForTwoClasses() : void {
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. IN IN A 1.2.3.4' );
    }


    public function testFromStringForUnimplementedType() : void {
        Lookups::$rrTypesByName[ 'UNIMPLEMENTED' ] = 9999; // Simulate an unimplemented type.
        Lookups::$rrTypesIdToClass[ 9999 ] = 'Not even a little bit.';
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN UNIMPLEMENTED 1.2.3.4' );
    }


    public function testFromStringForWrongRRClass() : void {
        Lookups::$rrTypesByName[ 'SAY_WHAT' ] = 9998; // Simulate a breathtakingly incompetent developer.
        Lookups::$rrTypesIdToClass[ 9998 ] = stdClass::class;
        $this->expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN SAY_WHAT 1.2.3.4' );
    }


}
