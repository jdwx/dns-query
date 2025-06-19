<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\RR\RR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;


/**
 * NOTE: This is in addition to the existing tests in the LegacyParserTest class.
 */
#[CoversClass( RR::class )]
final class RRTest extends TestCase {


    public function testFromStringForBadClass() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 FOO A 1.2.3.4' );
    }


    public function testFromStringForBadRData() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN A 1.2.3' );
    }


    public function testFromStringForBadTTLHuge() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 100000000000000 IN A' );
    }


    public function testFromStringForBadTTLNegative() : void {
        self::expectException( Exception::class );
        $rr = RR::fromString( 'example.com. -20 IN A 1.2.3.4' );
        var_dump( $rr ); // This line should not be reached.
    }


    public function testFromStringForBadType() : void {
        self::expectException( Exception::class );
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
        self::expectException( Exception::class );
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
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN' );
    }


    public function testFromStringForNoTypeWithTTL() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN 1.2.3.4' );
    }


    public function testFromStringForNoTypeWithoutTTL() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. IN 1.2.3.4' );
    }


    public function testFromStringForTotallyInvalid() : void {
        self::expectException( Exception::class );
        RR::fromString( 'invalid' );
    }


    public function testFromStringForTwoClasses() : void {
        self::expectException( Exception::class );
        RR::fromString( 'example.com. IN IN A 1.2.3.4' );
    }


    public function testFromStringForUnimplementedType() : void {
        Lookups::$rrTypesByName[ 'UNIMPLEMENTED' ] = 9999; // Simulate an unimplemented type.
        Lookups::$rrTypesIdToClass[ 9999 ] = 'Not even a little bit.';
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN UNIMPLEMENTED 1.2.3.4' );
    }


    public function testFromStringForWrongRRClass() : void {
        Lookups::$rrTypesByName[ 'SAY_WHAT' ] = 9998; // Simulate a breathtakingly incompetent developer.
        Lookups::$rrTypesIdToClass[ 9998 ] = stdClass::class;
        self::expectException( Exception::class );
        RR::fromString( 'example.com. 3600 IN SAY_WHAT 1.2.3.4' );
    }


}
