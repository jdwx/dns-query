<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\ReadBuffer;
use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\SerializeCodec;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( SerializeCodec::class )]
final class SerializeCodecTest extends TestCase {


    public function testCodec() : void {
        $codec = new SerializeCodec();
        $msg = Message::request( 'example.com', 'A' );
        $wri = new WriteBuffer();
        $codec->encodeMessage( $wri, $msg );
        $buffer = new ReadBuffer( $wri->end() );
        $msg2 = $codec->decodeMessage( $buffer );
        self::assertSame( strval( $msg ), strval( $msg2 ) );
    }


    public function testDecodeForNoData() : void {
        $codec = new SerializeCodec();
        $buffer = new ReadBuffer( '' );
        self::assertNull( $codec->decodeMessage( $buffer ) );
    }


    public function testHeader() : void {
        $hdr = new Header( null, 1, 2, 3, 4 );
        $codec = new SerializeCodec();
        $wri = new WriteBuffer();
        $codec->encodeHeader( $wri, $hdr );
        $buffer = new ReadBuffer( $wri->end() );
        $hdr2 = $codec->decodeHeader( $buffer );
        self::assertSame( strval( $hdr ), strval( $hdr2 ) );
    }


}