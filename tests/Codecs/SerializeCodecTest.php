<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Codecs\SerializeCodec;
use JDWX\DNSQuery\Message\Message;
use PHPUnit\Framework\TestCase;


class SerializeCodecTest extends TestCase {


    public function testCodec() : void {
        $codec = new SerializeCodec();
        $msg = Message::request( 'example.com', 'A' );
        $msg2 = $codec->decode( $codec->encode( $msg ) );
        self::assertSame( strval( $msg ), strval( $msg2 ) );
    }


}