<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Message;


use JDWX\DNSQuery\Message\OpaqueMessage;
use JDWX\DNSQuery\Transport\Buffer;
use PHPUnit\Framework\TestCase;


class OpaqueMessageTest extends TestCase {


    public function testFromBuffer() : void {
        $stPacket =
            "\x01\x02" # ID
            . "\03\x04" # Flags
            . "\x00\x01" # QDCount
            . "\x00\x01" # ANCount
            . "\x00\x01" # NSCount
            . "\x00\x01"; # ARCount

        # Question Section
        $stPacket .=
            "\x03bar\03baz\x00" # Question Name
            . "\x12\x34" # Type
            . "\x56\x78"; # Class

        # Answer Section
        $stPacket .=
            "\xc0\x0c" # Pointer to Question Name
            . "\x12\x34" # Answer Type
            . "\x56\x78" # Answer Class
            . "\x02\x03\x04\x05" # TTL
            . "\x00\x04" # RDLENGTH
            . "\x03\x04\x05\x06"; # RDATA

        # Authority Section
        $stPacket .=
            "\x03foo\xc0\x0c" # Pointer to Question Name
            . "\x23\x45" # Authority Type
            . "\x67\x89" # Authority Class
            . "\x12\x13\x14\x15" # TTL
            . "\x00\x02" # RDLENGTH
            . "\x43\x21"; # RDATA

        # Additional Section
        $stPacket .=
            "\x03qux\xc0\x0c" # Pointer to Question Name
            . "\x34\x56" # Additional Type
            . "\x78\x9a" # Additional Class
            . "\x11\x12\x13\x14" # TTL
            . "\x00\x03" # RDLENGTH
            . "\x01\x02\x03"; # RDATA

        $buffer = new Buffer( $stPacket );
        $message = OpaqueMessage::fromBuffer( $buffer );

        self::assertSame( 0x0102, $message->uID );
        self::assertSame( 0x0304, $message->uFlags );

        self::assertCount( 1, $message->rQuestion );
        self::assertSame( [ 'bar', 'baz' ], $message->rQuestion[ 0 ]->rName );
        self::assertSame( 0x1234, $message->rQuestion[ 0 ]->uType );
        self::assertSame( 0x5678, $message->rQuestion[ 0 ]->uClass );

        self::assertCount( 1, $message->rAnswer );
        self::assertSame( [ 'bar', 'baz' ], $message->rAnswer[ 0 ]->rName );
        self::assertSame( 0x1234, $message->rAnswer[ 0 ]->uType );
        self::assertSame( 0x5678, $message->rAnswer[ 0 ]->uClass );
        self::assertSame( 0x2030405, $message->rAnswer[ 0 ]->uTTL );
        self::assertSame( "\x03\x04\x05\x06", $message->rAnswer[ 0 ]->stData );

        self::assertCount( 1, $message->rAuthority );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $message->rAuthority[ 0 ]->rName );
        self::assertSame( 0x2345, $message->rAuthority[ 0 ]->uType );
        self::assertSame( 0x6789, $message->rAuthority[ 0 ]->uClass );
        self::assertSame( 0x12131415, $message->rAuthority[ 0 ]->uTTL );
        self::assertSame( "\x43\x21", $message->rAuthority[ 0 ]->stData );

        self::assertCount( 1, $message->rAdditional );
        self::assertSame( [ 'qux', 'bar', 'baz' ], $message->rAdditional[ 0 ]->rName );
        self::assertSame( 0x3456, $message->rAdditional[ 0 ]->uType );
        self::assertSame( 0x789a, $message->rAdditional[ 0 ]->uClass );
        self::assertSame( 0x11121314, $message->rAdditional[ 0 ]->uTTL );
        self::assertSame( "\x01\x02\x03", $message->rAdditional[ 0 ]->stData );

    }


}
