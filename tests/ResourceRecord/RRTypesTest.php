<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/**
 * For each resource record type present in RDataMaps we should test:
 * - Constructing a record with that type directly.
 * - Constructing a record with that type using ResourceRecord::fromArray().
 * - Exporting the record to an array with $rr->toArray().
 * - Creating a string representation of the record with strval( $rr ).
 * - Parsing a string representation of the record with ResourceRecord::fromString().
 * - Creating a binary representation of the record with RFC1035Codec::encodeResourceRecord( $rr ).
 * - Parsing a binary representation of the record from a Buffer with RFC1035Codec::decodeResourceRecord( $buffer ).
 */
#[CoversClass( RDataMaps::class )]
class RRTypesTest extends TestCase {


    public function testAAAARecord() : void {
        $rr = new ResourceRecord( 'example.com', 'AAAA', 'IN', 3600,
            [ 'address' => '2001:db8::1' ] );
        self::assertSame( 'AAAA', $rr->type() );
        self::assertSame( '2001:db8::1', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testCNAMERecord() : void {
        $rr = new ResourceRecord( 'www.example.com', 'CNAME', 'IN', 3600,
            [ 'cname' => [ 'canonical', 'example', 'com' ] ] );
        self::assertSame( 'CNAME', $rr->type() );
        self::assertSame( [ 'canonical', 'example', 'com' ], $rr->tryGetRDataValue( 'cname' ) );
    }


    public function testMXForArray() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $array = $rr->toArray();

        self::assertSame( 'MX', $array[ 'type' ] );
        self::assertSame( 10, $array[ 'preference' ] );
        self::assertSame( [ 'mail', 'example', 'com' ], $array[ 'exchange' ] );
    }


    public function testMXForString() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $string = (string) $rr;
        self::assertStringContainsString( 'example.com', $string );
        self::assertStringContainsString( '3600', $string );
        self::assertStringContainsString( 'IN', $string );
        self::assertStringContainsString( 'MX', $string );
        self::assertStringContainsString( '10', $string );
        self::assertStringContainsString( 'mail.example.com', $string );
    }


    public function testMXRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        self::assertSame( 'MX', $rr->type() );
        self::assertSame( 10, $rr->tryGetRDataValue( 'preference' ) );
        self::assertSame( [ 'mail', 'example', 'com' ], $rr->tryGetRDataValue( 'exchange' ) );
    }


    public function testNSRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'NS', 'IN', 3600,
            [ 'nsdname' => [ 'ns1', 'example', 'com' ] ] );
        self::assertSame( 'NS', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr->tryGetRDataValue( 'nsdname' ) );
    }


    public function testPTRRecord() : void {
        $rr = new ResourceRecord( '1.2.0.192.in-addr.arpa', 'PTR', 'IN', 3600,
            [ 'ptrdname' => [ 'example', 'com' ] ] );
        self::assertSame( 'PTR', $rr->type() );
        self::assertSame( [ 'example', 'com' ], $rr->tryGetRDataValue( 'ptrdname' ) );
    }


    public function testSOARecord() : void {
        $rr = new ResourceRecord( 'example.com', 'SOA', 'IN', 3600, [
            'mname' => [ 'ns1', 'example', 'com' ],
            'rname' => [ 'admin', 'example', 'com' ],
            'serial' => 2023010101,
            'refresh' => 3600,
            'retry' => 1800,
            'expire' => 604800,
            'minimum' => 86400,
        ] );
        self::assertSame( 'SOA', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr->tryGetRDataValue( 'mname' ) );
        self::assertSame( [ 'admin', 'example', 'com' ], $rr->tryGetRDataValue( 'rname' ) );
        self::assertSame( 2023010101, $rr->tryGetRDataValue( 'serial' ) );
        self::assertSame( 3600, $rr->tryGetRDataValue( 'refresh' ) );
        self::assertSame( 1800, $rr->tryGetRDataValue( 'retry' ) );
        self::assertSame( 604800, $rr->tryGetRDataValue( 'expire' ) );
        self::assertSame( 86400, $rr->tryGetRDataValue( 'minimum' ) );
    }


    public function testTXTForString() : void {
        $rr = new ResourceRecord( 'example.com', 'TXT', 'IN', 3600,
            [ 'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ] ] );
        $string = (string) $rr;
        self::assertStringContainsString( 'example.com', $string );
        self::assertStringContainsString( '3600', $string );
        self::assertStringContainsString( 'IN', $string );
        self::assertStringContainsString( 'TXT', $string );
        self::assertStringContainsString( 'v=spf1', $string );
    }


    public function testTXTRecord() : void {
        $rr = new ResourceRecord( 'example.com', 'TXT', 'IN', 3600,
            [ 'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ] ] );
        self::assertSame( 'TXT', $rr->type() );
        self::assertSame( [ 'v=spf1', 'include:_spf.example.com', '~all' ], $rr->tryGetRDataValue( 'text' ) );
    }


}
