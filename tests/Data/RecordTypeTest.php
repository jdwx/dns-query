<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\ALL;
use JDWX\DNSQuery\RR\ANY;
use JDWX\DNSQuery\RR\DS;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RecordTypeTest::class )]
final class RecordTypeTest extends TestCase {


    public function testClassNameToId() : void {
        self::assertSame( RecordType::A->value, RecordType::classNameToId( A::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::classNameToId( ALL::class ) );
        self::assertSame( RecordType::ANY->value, RecordType::classNameToId( ANY::class ) );
        self::assertSame( RecordType::DS->value, RecordType::classNameToId( DS::class ) );
        self::assertSame( RecordType::MX->value, RecordType::classNameToId( MX::class ) );
        self::expectException( RecordTypeException::class );
        RecordType::classNameToId( 'Foo' );
    }


    public function testClassNameToName() : void {
        self::assertSame( 'A', RecordType::classNameToName( A::class ) );
        self::assertSame( 'ANY', RecordType::classNameToName( ALL::class ) );
        self::assertSame( 'ANY', RecordType::classNameToName( ANY::class ) );
        self::assertSame( 'DS', RecordType::classNameToName( DS::class ) );
        self::assertSame( 'MX', RecordType::classNameToName( MX::class ) );
        self::expectException( RecordTypeException::class );
        RecordType::classNameToName( $this::class );
    }


    public function testFromName() : void {
        self::assertSame( RecordType::A, RecordType::tryFromName( 'A' ) );
        self::assertSame( RecordType::CNAME, RecordType::tryFromName( 'CnAmE' ) );
        self::assertSame( RecordType::MX, RecordType::tryFromName( 'Mx' ) );
        self::assertSame( RecordType::TXT, RecordType::tryFromName( 'txt' ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromName( 'ANY' ) );
        self::assertSame( RecordType::ANY, RecordType::tryFromName( '*' ) );
        self::assertNull( RecordType::tryFromName( 'FOO' ) );
    }


}
