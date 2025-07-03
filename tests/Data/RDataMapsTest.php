<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use PHPUnit\Framework\TestCase;


class RDataMapsTest extends TestCase {


    public function testMap() : void {
        $r = RDataMaps::map( 'A' );
        self::assertSame( [ 'address' => RDataType::IPv4Address ], $r );

        self::expectException( RecordException::class );
        RDataMaps::map( RecordType::ZZZ_TEST_ONLY_DO_NOT_USE );
    }


    public function testTryMap() : void {
        $r = RDataMaps::tryMap( 'A' );
        self::assertSame( [ 'address' => RDataType::IPv4Address ], $r );

        $r = RDataMaps::tryMap( RecordType::AAAA );
        self::assertSame( [ 'address' => RDataType::IPv6Address ], $r );

        $r = RDataMaps::tryMap( RecordType::ZZZ_TEST_ONLY_DO_NOT_USE );
        self::assertNull( $r );

        self::assertNull( RDataMaps::tryMap( 'Not_a_Record_Type' ) );
    }


}