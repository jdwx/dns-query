<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use JDWX\DNSQuery\Transport\Buffer;
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
 *
 * Except where not possible (e.g., OPT records have no string representation), the test should look like this:
 * - Construct a ResourceRecord with the type.
 * - Use RRTypesTest::roundTripArray() to convert it to an array, check the result, and convert it back.
 * - Use RRTypesTest::roundTripBinary() to convert it to a binary representation and convert it back.
 * - Use RRTypesTest::roundTripString() to convert it to a string representation, check the result, and convert it back.
 * - Check that the type and values are (still) as expected.
 */
#[CoversClass( RDataMaps::class )]
class RRTypesTest extends TestCase {


    public function testA() : void {
        $rr = new ResourceRecord( 'example.com', 'A', 'IN', 3600,
            [ 'address' => '192.0.2.1' ] );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( '192.0.2.1', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testAAAA() : void {
        $rr = new ResourceRecord( 'example.com', 'AAAA', 'IN', 3600,
            [ 'address' => '2001:db8::1' ] );
        self::assertSame( 'AAAA', $rr->type() );
        self::assertSame( '2001:db8::1', $rr->tryGetRDataValue( 'address' ) );
    }


    public function testAFSDB() : void {
        $rr = new ResourceRecord( 'example.com', 'AFSDB', 'IN', 3600, [
            'subtype' => 1,
            'hostname' => [ 'afs', 'example', 'com' ],
        ] );
        self::assertSame( 'AFSDB', $rr->type() );
        self::assertSame( 1, $rr->tryGetRDataValue( 'subtype' ) );
        self::assertSame( [ 'afs', 'example', 'com' ], $rr->tryGetRDataValue( 'hostname' ) );
    }


    public function testALIAS() : void {
        $rr = new ResourceRecord( 'example.com', 'ALIAS', 'IN', 3600,
            [ 'alias' => [ 'target', 'example', 'com' ] ] );
        self::assertSame( 'ALIAS', $rr->type() );
        self::assertSame( [ 'target', 'example', 'com' ], $rr->tryGetRDataValue( 'alias' ) );
    }


    public function testCAA() : void {
        $rr = new ResourceRecord( 'example.com', 'CAA', 'IN', 3600, [
            'flags' => 0,
            'tag' => 'issue',
            'value' => 'letsencrypt.org',
        ] );
        self::assertSame( 'CAA', $rr->type() );
        self::assertSame( 0, $rr->tryGetRDataValue( 'flags' ) );
        self::assertSame( 'issue', $rr->tryGetRDataValue( 'tag' ) );
        self::assertSame( 'letsencrypt.org', $rr->tryGetRDataValue( 'value' ) );
    }


    public function testCNAME() : void {
        $rr = new ResourceRecord( 'www.example.com', 'CNAME', 'IN', 3600,
            [ 'cname' => [ 'canonical', 'example', 'com' ] ] );
        self::assertSame( 'CNAME', $rr->type() );
        self::assertSame( [ 'canonical', 'example', 'com' ], $rr->tryGetRDataValue( 'cname' ) );
    }


    public function testDNAME() : void {
        $rr = new ResourceRecord( 'example.com', 'DNAME', 'IN', 3600,
            [ 'dname' => [ 'target', 'example', 'net' ] ] );
        self::assertSame( 'DNAME', $rr->type() );
        self::assertSame( [ 'target', 'example', 'net' ], $rr->tryGetRDataValue( 'dname' ) );
    }


    public function testHINFO() : void {
        $rr = new ResourceRecord( 'example.com', 'HINFO', 'IN', 3600, [
            'cpu' => 'Intel',
            'os' => 'Linux',
        ] );
        self::assertSame( 'HINFO', $rr->type() );
        self::assertSame( 'Intel', $rr->tryGetRDataValue( 'cpu' ) );
        self::assertSame( 'Linux', $rr->tryGetRDataValue( 'os' ) );
    }


    public function testISDN() : void {
        $rr = new ResourceRecord( 'example.com', 'ISDN', 'IN', 3600, [
            'isdnAddress' => '150862028003217',
            'sa' => '004',
        ] );
        self::assertSame( 'ISDN', $rr->type() );
        self::assertSame( '150862028003217', $rr->tryGetRDataValue( 'isdnAddress' ) );
        self::assertSame( '004', $rr->tryGetRDataValue( 'sa' ) );
    }


    public function testKX() : void {
        $rr = new ResourceRecord( 'example.com', 'KX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => 'kdc.example.com',
        ] );
        self::assertSame( 'KX', $rr->type() );
        self::assertSame( 10, $rr->tryGetRDataValue( 'preference' ) );
        self::assertSame( 'kdc.example.com', $rr->tryGetRDataValue( 'exchange' ) );
    }


    public function testMX() : void {
        $rr = new ResourceRecord( 'example.com', 'MX', 'IN', 3600, [
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $rr = $this->roundTripArray( $rr, [
            'name' => [ 'example', 'com' ],
            'type' => 'MX',
            'class' => 'IN',
            'ttl' => 3600,
            'preference' => 10,
            'exchange' => [ 'mail', 'example', 'com' ],
        ] );
        $rr = $this->roundTripBinary( $rr );
        $rr = $this->roundTripString( $rr, 'example.com 3600 IN MX 10 mail.example.com' );
        self::assertSame( [ 'example', 'com' ], $rr->getName() );
        self::assertSame( 3600, $rr->getTTL() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'MX', $rr->type() );
        self::assertSame( 10, $rr->tryGetRDataValue( 'preference' ) );
        self::assertSame( [ 'mail', 'example', 'com' ], $rr->tryGetRDataValue( 'exchange' ) );
    }


    public function testNS() : void {
        $rr = new ResourceRecord( 'example.com', 'NS', 'IN', 3600,
            [ 'nsdname' => [ 'ns1', 'example', 'com' ] ] );
        self::assertSame( 'NS', $rr->type() );
        self::assertSame( [ 'ns1', 'example', 'com' ], $rr->tryGetRDataValue( 'nsdname' ) );
    }


    public function testPTR() : void {
        $rr = new ResourceRecord( '1.2.0.192.in-addr.arpa', 'PTR', 'IN', 3600,
            [ 'ptrdname' => [ 'example', 'com' ] ] );
        self::assertSame( 'PTR', $rr->type() );
        self::assertSame( [ 'example', 'com' ], $rr->tryGetRDataValue( 'ptrdname' ) );
    }


    public function testPX() : void {
        $rr = new ResourceRecord( 'example.com', 'PX', 'IN', 3600, [
            'preference' => 10,
            'map822' => [ 'mail', 'example', 'com' ],
            'mapX400' => [ 'x400', 'example', 'com' ],
        ] );
        self::assertSame( 'PX', $rr->type() );
        self::assertSame( 10, $rr->tryGetRDataValue( 'preference' ) );
        self::assertSame( [ 'mail', 'example', 'com' ], $rr->tryGetRDataValue( 'map822' ) );
        self::assertSame( [ 'x400', 'example', 'com' ], $rr->tryGetRDataValue( 'mapX400' ) );
    }


    public function testRP() : void {
        $rr = new ResourceRecord( 'example.com', 'RP', 'IN', 3600, [
            'mboxDName' => [ 'admin', 'example', 'com' ],
            'txtDName' => [ 'contact', 'example', 'com' ],
        ] );
        self::assertSame( 'RP', $rr->type() );
        self::assertSame( [ 'admin', 'example', 'com' ], $rr->tryGetRDataValue( 'mboxDName' ) );
        self::assertSame( [ 'contact', 'example', 'com' ], $rr->tryGetRDataValue( 'txtDName' ) );
    }


    public function testRT() : void {
        $rr = new ResourceRecord( 'example.com', 'RT', 'IN', 3600, [
            'preference' => 10,
            'intermediateHost' => [ 'router', 'example', 'com' ],
        ] );
        self::assertSame( 'RT', $rr->type() );
        self::assertSame( 10, $rr->tryGetRDataValue( 'preference' ) );
        self::assertSame( [ 'router', 'example', 'com' ], $rr->tryGetRDataValue( 'intermediateHost' ) );
    }


    public function testSOA() : void {
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


    public function testSPF() : void {
        $rr = new ResourceRecord( 'example.com', 'SPF', 'IN', 3600,
            [ 'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ] ] );
        $rr = $this->roundTripArray( $rr, [
            'name' => [ 'example', 'com' ],
            'type' => 'SPF',
            'class' => 'IN',
            'ttl' => 3600,
            'text' => [ 'v=spf1', 'include:_spf.example.com', '~all' ],
        ] );
        $rr = $this->roundTripBinary( $rr );
        $rr = $this->roundTripString( $rr, 'example.com 3600 IN SPF v=spf1 include:_spf.example.com ~all' );
        self::assertSame( [ 'example', 'com' ], $rr->getName() );
        self::assertSame( 3600, $rr->getTTL() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'SPF', $rr->type() );
        self::assertSame( [ 'v=spf1', 'include:_spf.example.com', '~all' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testTXT() : void {
        $rr = new ResourceRecord( 'example.com', 'TXT', 'IN', 3600,
            [ 'text' => [ 'foo bar', 'baz qux', 'quux' ] ] );
        $rr = $this->roundTripArray( $rr, [
            'name' => [ 'example', 'com' ],
            'type' => 'TXT',
            'class' => 'IN',
            'ttl' => 3600,
            'text' => [ 'foo bar', 'baz qux', 'quux' ],
        ] );
        $rr = $this->roundTripBinary( $rr );
        $rr = $this->roundTripString( $rr, 'example.com 3600 IN TXT "foo bar" "baz qux" quux' );
        self::assertSame( [ 'example', 'com' ], $rr->getName() );
        self::assertSame( 3600, $rr->getTTL() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 'TXT', $rr->type() );
        self::assertSame( [ 'foo bar', 'baz qux', 'quux' ], $rr->tryGetRDataValue( 'text' ) );
    }


    public function testX25() : void {
        $rr = new ResourceRecord( 'example.com', 'X25', 'IN', 3600,
            [ 'psdnAddress' => '311061700956' ] );
        self::assertSame( 'X25', $rr->type() );
        self::assertSame( '311061700956', $rr->tryGetRDataValue( 'psdnAddress' ) );
    }


    private function roundTripArray( ResourceRecordInterface $rr, ?array $i_nrCheck = null ) : ResourceRecordInterface {
        $array = $rr->toArray( true );
        if ( is_array( $i_nrCheck ) ) {
            self::assertSame( $i_nrCheck, $array );
        }
        return ResourceRecord::fromArray( $array );
    }


    private function roundTripBinary( ResourceRecordInterface $rr ) : ResourceRecordInterface {
        $rLabelMap = [];
        $uOffset = 0;
        $binary = RFC1035Codec::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        $buffer = new Buffer( $binary );
        return RFC1035Codec::decodeResourceRecord( $buffer );
    }


    private function roundTripString( ResourceRecordInterface $rr, ?string $i_nstCheck = null ) : ResourceRecordInterface {
        $string = (string) $rr;
        if ( is_string( $i_nstCheck ) ) {
            self::assertSame( $i_nstCheck, $string );
        }
        return ResourceRecord::fromString( $string );
    }


}
