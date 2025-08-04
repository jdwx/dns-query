<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\Packet\Packet;
use JDWX\DNSQuery\Tests\Utility\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;


require_once __DIR__ . '/Utility/TestCase.php';


#[CoversClass( Packet::class )]
final class PacketTest extends TestCase {


    public function testCompress() : void {
        $packet = new Packet();
        $offset = 0;
        $st = $packet->compress( 'example.com.', $offset );
        self::assertSame( 13, $offset );
        self::assertSame( "\x07example\x03com\x00", $st );

        $st = $packet->compress( '.', $offset );
        self::assertSame( 15, $offset );
        self::assertSameHex( "\xc0\x0c", $st );

        $st = $packet->compress( '', $offset );
        self::assertSame( 17, $offset );
        self::assertSameHex( "\xc0\x0c", $st );
    }


}
