<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\OpCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OpCode::class )]
class OpCodeTest extends TestCase {


    public function testFromFlagWord() : void {
        self::assertSame( OpCode::QUERY, OpCode::fromFlagWord( 0x0000 ) );
        self::assertSame( OpCode::IQUERY, OpCode::fromFlagWord( 0x0800 ) );
        self::assertSame( OpCode::STATUS, OpCode::fromFlagWord( 0x1000 ) );
        self::assertSame( OpCode::NOTIFY, OpCode::fromFlagWord( 0xA000 ) );
        self::assertSame( OpCode::UPDATE, OpCode::fromFlagWord( 0xA800 ) );
        self::assertSame( OpCode::DSO, OpCode::fromFlagWord( 0x3000 ) );
        self::assertSame( OpCode::QUERY, OpCode::fromFlagWord( 0x07FF ) );
        self::assertSame( OpCode::IQUERY, OpCode::fromFlagWord( 0x0FFF ) );
        self::assertSame( OpCode::STATUS, OpCode::fromFlagWord( 0x17FF ) );
        self::assertSame( OpCode::NOTIFY, OpCode::fromFlagWord( 0x27FF ) );
        self::assertSame( OpCode::UPDATE, OpCode::fromFlagWord( 0x2FFF ) );
        self::assertSame( OpCode::DSO, OpCode::fromFlagWord( 0x37FF ) );
        self::expectException( \InvalidArgumentException::class );
        OpCode::fromFlagWord( 0x4000 );
    }


    public function testFromName() : void {
        self::assertSame( OpCode::QUERY, OpCode::fromName( 'QUERY' ) );
        self::assertSame( OpCode::IQUERY, OpCode::fromName( 'IQuery' ) );
        self::assertSame( OpCode::STATUS, OpCode::fromName( 'status' ) );
        self::assertSame( OpCode::NOTIFY, OpCode::fromName( 'NoTiFy' ) );
        self::assertSame( OpCode::QUERY, OpCode::fromName( 'OPCODE0' ) );
        self::expectException( \InvalidArgumentException::class );
        OpCode::fromName( 'FOO' );
    }


    public function testIdToName() : void {
        self::assertSame( 'QUERY', OpCode::idToName( 0 ) );
        self::assertSame( 'IQUERY', OpCode::idToName( 1 ) );
        self::assertSame( 'STATUS', OpCode::idToName( 2 ) );
        self::assertSame( 'NOTIFY', OpCode::idToName( 4 ) );
        self::assertSame( 'UPDATE', OpCode::idToName( 5 ) );
        self::assertSame( 'DSO', OpCode::idToName( 6 ) );
        self::assertSame( 'OPCODE3', OpCode::idToName( 3 ) );
        self::assertSame( 'OPCODE15', OpCode::idToName( 15 ) );
        self::expectException( \InvalidArgumentException::class );
        OpCode::idToName( 16 );
    }


    public function testNameToId() : void {
        self::assertSame( 0, OpCode::nameToId( 'QUERY' ) );
        self::assertSame( 1, OpCode::nameToId( 'IQuery' ) );
        self::assertSame( 2, OpCode::nameToId( 'status' ) );
        self::assertSame( 3, OpCode::nameToId( 'OPCODE3' ) );
        self::assertSame( 4, OpCode::nameToId( 'NoTiFy' ) );
        self::assertSame( 5, OpCode::nameToId( 'UPDATE' ) );
        self::assertSame( 6, OpCode::nameToId( 'DSO' ) );
        self::expectException( \InvalidArgumentException::class );
        OpCode::nameToId( 'FOO' );
    }


    public function testNameToIdForOPCODEInvalid() : void {
        self::expectException( \InvalidArgumentException::class );
        OpCode::nameToId( 'OPCODEXYZ' );
    }


    public function testNameToIdForOPCODENegative() : void {
        self::expectException( \InvalidArgumentException::class );
        OpCode::nameToId( 'OPCODE-1' );
    }


    public function testNameToIdForOPCODEOutsideRange() : void {
        self::expectException( \InvalidArgumentException::class );
        OpCode::nameToId( 'OPCODE16' );
    }


    public function testNameToIdForOPCODEWayOutsideRange() : void {
        self::expectException( \InvalidArgumentException::class );
        OpCode::nameToId( 'OPCODE999' );
    }


    public function testNormalize() : void {
        self::assertSame( OpCode::QUERY, OpCode::normalize( 'QUERY' ) );
        self::assertSame( OpCode::QUERY, OpCode::normalize( 'OPCODE0' ) );
        self::assertSame( OpCode::QUERY, OpCode::normalize( 0 ) );
        self::assertSame( OpCode::QUERY, OpCode::normalize( OpCode::QUERY ) );
        self::expectException( \InvalidArgumentException::class );
        OpCode::normalize( 'FOO' );
    }


    public function testToFlagWord() : void {
        self::assertSame( 0x0000, OpCode::QUERY->toFlagWord() );
        self::assertSame( 0x0800, OpCode::IQUERY->toFlagWord() );
        self::assertSame( 0x1000, OpCode::STATUS->toFlagWord() );
        self::assertSame( 0x2000, OpCode::NOTIFY->toFlagWord() );
        self::assertSame( 0x2800, OpCode::UPDATE->toFlagWord() );
        self::assertSame( 0x3000, OpCode::DSO->toFlagWord() );
    }


    public function testTryFromName() : void {
        self::assertSame( OpCode::QUERY, OpCode::tryFromName( 'QUERY' ) );
        self::assertSame( OpCode::IQUERY, OpCode::tryFromName( 'IQuery' ) );
        self::assertSame( OpCode::STATUS, OpCode::tryFromName( 'status' ) );
        self::assertSame( OpCode::NOTIFY, OpCode::tryFromName( 'NoTiFy' ) );
        self::assertSame( OpCode::UPDATE, OpCode::tryFromName( 'UPDATE' ) );
        self::assertSame( OpCode::DSO, OpCode::tryFromName( 'DSO' ) );
        self::assertSame( OpCode::QUERY, OpCode::tryFromName( 'OPCODE0' ) );
        self::assertNull( OpCode::tryFromName( 'FOO' ) );
    }


}
