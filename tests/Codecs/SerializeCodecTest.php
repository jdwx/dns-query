<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\ReadBuffer;
use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\SerializeDecoder;
use JDWX\DNSQuery\Codecs\SerializeEncoder;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( SerializeDecoder::class )]
#[CoversClass( SerializeEncoder::class )]
final class SerializeCodecTest extends TestCase {


    public function testCodec() : void {
        $dec = new SerializeDecoder();
        $enc = new SerializeEncoder();
        $wri = new WriteBuffer();

        $msg = Message::request( 'example.com', 'A' );
        $enc->encodeMessage( $wri, $msg );
        $buffer = new ReadBuffer( $wri->end() );
        $msg2 = $dec->decodeMessage( $buffer );
        self::assertSame( strval( $msg ), strval( $msg2 ) );
    }


    public function testDecodeForNoData() : void {
        $codec = new SerializeDecoder();
        $buffer = new ReadBuffer( '' );
        self::assertNull( $codec->decodeMessage( $buffer ) );
    }


    public function testHeader() : void {
        $dec = new SerializeDecoder();
        $enc = new SerializeEncoder();
        $wri = new WriteBuffer();

        $hdr = new Header( null, 1, 2, 3, 4 );
        $enc->encodeHeader( $wri, $hdr );
        $buffer = new ReadBuffer( $wri->end() );
        $hdr2 = $dec->decodeHeader( $buffer );
        self::assertSame( strval( $hdr ), strval( $hdr2 ) );
    }


}