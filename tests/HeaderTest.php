<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\Header;
use PHPUnit\Framework\TestCase;


class HeaderTest extends TestCase {


    /**
     * @throws Exception
     */
    public function testHeader() {
        $hdr = $this->makeTestHeader();
        $pack = $hdr->pack();

        $hdr2 = new Header( $pack );

        static::assertEquals( $hdr, $hdr2 );
    }


    public function testHeaderToString() {
        $hdr = $this->makeTestHeader();
        $str = (string) $hdr;
        /** @noinspection SpellCheckingInspection */
        $strExpected = ";; ->>HEADER<<- opcode: OPCODE10, status: NOTZONE, id: 12345\n;; flags: qr tc ra ad; QUERY: 23456, ANSWER: 45678, AUTHORITY: 56789, ADDITIONAL: 6789\n";
        static::assertEquals( $strExpected, $str );
    }


    public function testHeaderToString2() {
        $hdr = $this->makeTestHeader( aa: true, rd: true, z: true, cd: true );
        $str = (string) $hdr;
        /** @noinspection SpellCheckingInspection */
        $strExpected = ";; ->>HEADER<<- opcode: OPCODE10, status: NOTZONE, id: 12345\n;; flags: qr aa tc rd ra z ad cd; QUERY: 23456, ANSWER: 45678, AUTHORITY: 56789, ADDITIONAL: 6789\n";
        static::assertEquals( $strExpected, $str );
    }


    public function testHeaderShort() {
        $this->expectException( Exception::class );
        new Header( "TooShort" );
    }


    public function makeTestHeader( bool $aa = false, bool $rd = false, bool $z = false, bool $cd = false ) : Header {
        $hdr = new Header();
        $hdr->id = 12345;
        $hdr->qdCount = 23456;
        $hdr->anCount = 45678;
        $hdr->nsCount = 56789;
        $hdr->arCount = 6789;
        $hdr->qr = 1;
        $hdr->opcode = 10;
        $hdr->aa = $aa ? 1 : 0;
        $hdr->tc = 1;
        $hdr->rd = $rd ? 1 : 0;
        $hdr->ra = 1;
        $hdr->z = $z ? 1 : 0;
        $hdr->ad = 1;
        $hdr->cd = $cd ? 1 : 0;
        $hdr->rCode = 10;
        return $hdr;
    }


}