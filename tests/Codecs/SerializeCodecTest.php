<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\Buffer;
use JDWX\DNSQuery\Codecs\SerializeCodec;
use JDWX\DNSQuery\Message\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( SerializeCodec::class )]
final class SerializeCodecTest extends TestCase {


    public function testCodec() : void {
        $codec = new SerializeCodec();
        $msg = Message::request( 'example.com', 'A' );
        $st = $codec->encode( $msg );
        $buffer = new Buffer( $st );
        $msg2 = $codec->decode( $buffer );
        self::assertSame( strval( $msg ), strval( $msg2 ) );
    }


    public function testDecodeForNoData() : void {
        $codec = new SerializeCodec();
        $buffer = new Buffer( '' );
        self::assertNull( $codec->decode( $buffer ) );
    }


}