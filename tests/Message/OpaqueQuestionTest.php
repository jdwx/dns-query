<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Message;


use JDWX\DNSQuery\Question\OpaqueQuestion;
use JDWX\DNSQuery\Transport\Buffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OpaqueQuestion::class )]
final class OpaqueQuestionTest extends TestCase {


    public function testFromBuffer() : void {
        $stQuestion = "\x03bar\03baz\x00\x03foo\xC0\x00\x12\x34\x56\x78qux";
        $buffer = new Buffer( $stQuestion );
        $buffer->seek( 9 );
        $question = OpaqueQuestion::fromBuffer( $buffer );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $question->rName );
        self::assertSame( 0x1234, $question->uType );
        self::assertSame( 0x5678, $question->uClass );
        self::assertSame( 'qux', $buffer->consume( 3 ) );
    }


}
