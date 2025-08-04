<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\RR;


use JDWX\DNSQuery\Packet\Packet;
use JDWX\DNSQuery\RR\MX;
use JDWX\DNSQuery\Tests\Utility\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;


require_once __DIR__ . '/../Utility/TestCase.php';


#[CoversClass( MX::class )]
final class MXTest extends TestCase {


    /** @noinspection SubStrUsedAsArrayAccessInspection */
    public function testRrGetForExplicitRoot() : void {
        $packet = new Packet();
        $packet->rdata = '';
        $mx = new MX();
        $mx->name = 'test.fr.';
        $mx->preference = 0;
        $mx->exchange = '.';
        $mx->ttl = 0x12345678;
        $st = $mx->get( $packet );
        self::assertSameHex( "\x04test", substr( $st, 0, 5 ) ); # Name "test"
        self::assertSameHex( "\x02fr", substr( $st, 5, 3 ) ); # Name "fr"
        self::assertSameHex( "\x00", substr( $st, 8, 1 ) ); # Null byte for end of name
        self::assertSameHex( "\x00\x0f", substr( $st, 9, 2 ) ); # Type MX
        self::assertSameHex( "\x00\x01", substr( $st, 11, 2 ) ); # Class IN
        self::assertSameHex( "\x12\x34\x56\x78", substr( $st, 13, 4 ) ); # TTL
        self::assertSameHex( "\x00\x04", substr( $st, 17, 2 ) ); # RDLENGTH
        self::assertSameHex( "\x00\x00", substr( $st, 19, 2 ) ); # Preference 0
        self::assertSameHex( "\xc0\x08", substr( $st, 21, 2 ) ); # Pointer to offset 8
        self::assertSame( 23, strlen( $st ) ); # Total length
    }


    /** @noinspection SubStrUsedAsArrayAccessInspection */
    public function testRrGetForImplicitRoot() : void {
        $packet = new Packet();
        $packet->rdata = '';
        $mx = new MX();
        $mx->name = 'test.fr.';
        $mx->preference = 0;
        $mx->exchange = '';
        $mx->ttl = 0x12345678;
        $st = $mx->get( $packet );
        self::assertSameHex( "\x04test", substr( $st, 0, 5 ) ); # Name "test"
        self::assertSameHex( "\x02fr", substr( $st, 5, 3 ) ); # Name "fr"
        self::assertSameHex( "\x00", substr( $st, 8, 1 ) ); # Null byte for end of name
        self::assertSameHex( "\x00\x0f", substr( $st, 9, 2 ) ); # Type MX
        self::assertSameHex( "\x00\x01", substr( $st, 11, 2 ) ); # Class IN
        self::assertSameHex( "\x12\x34\x56\x78", substr( $st, 13, 4 ) ); # TTL
        self::assertSameHex( "\x00\x04", substr( $st, 17, 2 ) ); # RDLENGTH
        self::assertSameHex( "\x00\x00", substr( $st, 19, 2 ) ); # Preference 0
        self::assertSameHex( "\xc0\x08", substr( $st, 21, 2 ) ); # Pointer to offset 8
        self::assertSame( 23, strlen( $st ) ); # Total length
    }


}
