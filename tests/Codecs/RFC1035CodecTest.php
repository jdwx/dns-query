<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\OptRecord;
use JDWX\DNSQuery\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RFC1035Codec::class )]
final class RFC1035CodecTest extends TestCase {


    public function testDecode() : void {
        $codec = new RFC1035Codec();
        $msg = new Message();
        $msg->id = 0x1234;
        $msg->opcode = OpCode::QUERY;
        $msg->question[] = new Question( 'foo', 'A', 'IN' );
        $msg->answer[] = ResourceRecord::fromString( 'foo.bar 3600 IN A 192.0.2.1' );
        $msg->authority[] = ResourceRecord::fromString( 'baz.qux 7200 IN NS quux.corge' );
        $msg->additional[] = ResourceRecord::fromString( 'grault.garply 10800 IN A 192.0.2.2' );
        $msg->opt[] = new OptRecord();
        $st = $codec->encode( $msg );
        $msg2 = $codec->decode( $st );
        self::assertSame( $msg->id, $msg2->id );
        self::assertSame( $msg->qr, $msg2->qr );
        self::assertSame( $msg->opcode, $msg2->opcode );
        self::assertSame( $msg->aa, $msg2->aa );
        self::assertSame( $msg->tc, $msg2->tc );
        self::assertSame( $msg->rd, $msg2->rd );
        self::assertSame( $msg->ra, $msg2->ra );
        self::assertSame( $msg->z->bits, $msg2->z->bits );
        self::assertCount( 1, $msg2->question );
        self::assertSame( 'foo', $msg2->question[ 0 ]->stName );
        self::assertSame( 'A', $msg2->question[ 0 ]->type->name );
        self::assertSame( 'IN', $msg2->question[ 0 ]->class->name );

        self::assertCount( 1, $msg2->answer );
        self::assertSame( [ 'foo', 'bar' ], $msg2->answer[ 0 ]->getName() );
        self::assertSame( 'A', $msg2->answer[ 0 ]->type() );
        self::assertSame( 'IN', $msg2->answer[ 0 ]->class() );
        self::assertSame( 3600, $msg2->answer[ 0 ]->ttl() );

        self::assertCount( 1, $msg2->authority );
        self::assertSame( [ 'baz', 'qux' ], $msg2->authority[ 0 ]->getName() );
        self::assertSame( 'NS', $msg2->authority[ 0 ]->type() );
        self::assertSame( 'IN', $msg2->authority[ 0 ]->class() );
        self::assertSame( 7200, $msg2->authority[ 0 ]->ttl() );

        self::assertCount( 1, $msg2->additional );
        self::assertSame( [ 'grault', 'garply' ], $msg2->additional[ 0 ]->getName() );
        self::assertSame( 'A', $msg2->additional[ 0 ]->type() );
        self::assertSame( 'IN', $msg2->additional[ 0 ]->class() );
        self::assertSame( 10800, $msg2->additional[ 0 ]->ttl() );

        self::assertCount( 1, $msg2->opt );
        self::assertSame( [ 'test', 'opt' ], $msg2->opt[ 0 ]->getName() );
        self::assertSame( 'OPT', $msg2->opt[ 0 ]->type() );
        self::assertSame( 'IN', $msg2->opt[ 0 ]->class() );
        self::assertSame( 32768, $msg2->opt[ 0 ]->ttl() );


    }


    public function testEncodeForRequest() : void {
        $codec = new RFC1035Codec();
        $msg = new Message();
        $msg->id = 0x1234;
        $msg->opcode = OpCode::QUERY;
        $msg->question[] = new Question( 'test', 'A', 'IN' );
        $st = $codec->encode( $msg );
        self::assertStringStartsWith( "\x12\x34", $st ); # ID
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x01\x00", $st ); # Flags
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); # Question Count
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x00\x00\x00\x00\x00", $st );
        $st = substr( $st, 6 );

        self::assertStringStartsWith( "\x04test\x00", $st ); # Question Name
        $st = substr( $st, 6 );
        self::assertStringStartsWith( "\x00\x01", $st ); # Type A
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); # Class IN
        $st = substr( $st, 2 );
        self::assertSame( '', $st ); # End of message
    }


}
