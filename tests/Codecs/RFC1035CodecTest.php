<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\ReadBuffer;
use JDWX\DNSQuery\Codecs\RFC1035Decoder;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RFC1035Decoder::class )]
final class RFC1035CodecTest extends TestCase {


    public function testDecode() : void {
        /** @noinspection SpellCheckingInspection */
        $stPacketDump = <<<ZEND
        0x0000:  4500 00b6 690d 4000 3b11 371a 0101 0101  E...i.@.;.7.....
        0x0010:  0102 0304 0035 d4b0 00a2 dde5 de22 8180  ...e.5......."..
        0x0020:  0001 0004 0000 0001 0377 7777 0765 7861  .........www.exa
        0x0030:  6d70 6c65 0363 6f6d 0000 0100 01c0 0c00  mple.com........
        0x0040:  0500 0100 0000 cf00 2203 7777 7707 6578  ........".www.ex
        0x0050:  616d 706c 6506 636f 6d2d 7634 0965 6467  ample.com-v4.edg
        0x0060:  6573 7569 7465 036e 6574 00c0 2d00 0500  esuite.net..-...
        0x0070:  0100 0054 0300 1405 6131 3432 3204 6473  ...T....a1422.ds
        0x0080:  6372 0661 6b61 6d61 69c0 4ac0 5b00 0100  cr.akamai.J.[...
        0x0090:  0100 0000 1100 0417 283c 38c0 5b00 0100  ........(<8.[...
        0x00a0:  0100 0000 1100 0417 283c 2800 0029 04d0  ........(<(..)..
        0x00b0:  0000 0000 0000                           ......
        ZEND;
        $packet = HexDump::fromTcpDump( $stPacketDump );
        $packet = substr( $packet, 28 ); // Remove IP and UDP headers
        $buffer = new ReadBuffer( $packet );
        $codec = new RFC1035Decoder();
        $msg = $codec->decodeMessage( $buffer );

        self::assertInstanceOf( EDNSMessage::class, $msg );
        self::assertSame( 56866, $msg->header()->id() );
        self::assertSame( 'QUERY', $msg->header()->opcode() );

        self::assertCount( 1, $msg->getQuestion() );
        self::assertSame( 'www.example.com', $msg->getQuestion()[ 0 ]->name() );
        self::assertSame( 'A', $msg->getQuestion()[ 0 ]->type() );
        self::assertSame( 'IN', $msg->getQuestion()[ 0 ]->class() );

        self::assertCount( 4, $msg->getAnswer() );
        self::assertSame( 'www.example.com', $msg->getAnswer()[ 0 ]->name() );
        self::assertSame( 'CNAME', $msg->getAnswer()[ 0 ]->type() );
        self::assertSame( 'IN', $msg->getAnswer()[ 0 ]->class() );
        self::assertSame( 207, $msg->getAnswer()[ 0 ]->ttl() );
        self::assertSame(
            'www.example.com-v4.edgesuite.net',
            join( '.', $msg->getAnswer()[ 0 ]->tryGetRDataValue( 'cname' ) )
        );

        self::assertSame( 'www.example.com-v4.edgesuite.net', $msg->getAnswer()[ 1 ]->name() );
        self::assertSame( 'CNAME', $msg->getAnswer()[ 1 ]->type() );
        self::assertSame( 'IN', $msg->getAnswer()[ 1 ]->class() );
        self::assertSame( 21507, $msg->getAnswer()[ 1 ]->ttl() );
        self::assertSame(
            'a1422.dscr.akamai.net',
            join( '.', $msg->getAnswer()[ 1 ]->tryGetRDataValue( 'cname' ) )
        );

        self::assertSame( 'a1422.dscr.akamai.net', $msg->getAnswer()[ 2 ]->name() );
        self::assertSame( 'A', $msg->getAnswer()[ 2 ]->type() );
        self::assertSame( 'IN', $msg->getAnswer()[ 2 ]->class() );
        self::assertSame( 17, $msg->getAnswer()[ 2 ]->ttl() );
        self::assertSame( '23.40.60.56', $msg->getAnswer()[ 2 ]->tryGetRDataValue( 'address' ) );

        self::assertSame( 'a1422.dscr.akamai.net', $msg->getAnswer()[ 3 ]->name() );
        self::assertSame( 'A', $msg->getAnswer()[ 3 ]->type() );
        self::assertSame( 'IN', $msg->getAnswer()[ 3 ]->class() );
        self::assertSame( 17, $msg->getAnswer()[ 3 ]->ttl() );
        self::assertSame( '23.40.60.40', $msg->getAnswer()[ 3 ]->tryGetRDataValue( 'address' ) );

        self::assertCount( 0, $msg->getAuthority() );
        self::assertCount( 1, $msg->getAdditional() );
        self::assertSame( 1, $msg->header()->getARCount() );

        self::assertNull( $msg->additional( 0 ) );
        $opt = $msg->getAdditional()[ 0 ] ?? null;
        assert( $opt instanceof ResourceRecordInterface );
        $version = EDNSVersion::fromFlagTTL( $opt->getTTL() );
        self::assertSame( 0, $version->value );
        self::assertSame( 1232, $opt->classValue() );
        self::assertSame( [], $opt->tryGetRDataValue( 'options' ) );

    }


    public function testDecode2() : void {
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

        $buffer = new ReadBuffer( $stPacket );
        $codec = new RFC1035Decoder();
        $message = $codec->decodeMessage( $buffer );

        self::assertSame( 0x0102, $message->id() );
        self::assertSame( 0x0304, $message->header()->flagWordValue() );

        self::assertCount( 1, $message->getQuestion() );
        self::assertSame( 1, $message->header()->getQDCount() );
        self::assertSame( [ 'bar', 'baz' ], $message->question()->getName() );
        self::assertSame( 0x1234, $message->question()->typeValue() );
        self::assertSame( 0x5678, $message->question()->classValue() );

        self::assertCount( 1, $message->getAnswer() );
        self::assertSame( 1, $message->header()->getANCount() );
        self::assertSame( [ 'bar', 'baz' ], $message->answer( 0 )->getName() );
        self::assertSame( 0x1234, $message->answer( 0 )->typeValue() );
        self::assertSame( 0x5678, $message->answer( 0 )->classValue() );
        self::assertSame( 0x2030405, $message->answer( 0 )->ttl() );
        self::assertSame( "\x03\x04\x05\x06", $message->answer( 0 )->tryGetRDataValue( 'rdata' ) );

        self::assertCount( 1, $message->getAuthority() );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $message->authority( 0 )->getName() );
        self::assertSame( 0x2345, $message->authority( 0 )->typeValue() );
        self::assertSame( 0x6789, $message->authority( 0 )->classValue() );
        self::assertSame( 0x12131415, $message->authority( 0 )->getTTL() );
        self::assertSame( "\x43\x21", $message->authority( 0 )->tryGetRDataValue( 'rdata' ) );

        self::assertCount( 1, $message->getAdditional() );
        self::assertSame( [ 'qux', 'bar', 'baz' ], $message->additional( 0 )->getName() );
        self::assertSame( 0x3456, $message->additional( 0 )->typeValue() );
        self::assertSame( 0x789a, $message->additional( 0 )->classValue() );
        self::assertSame( 0x11121314, $message->additional( 0 )->getTTL() );
        self::assertSame( "\x01\x02\x03", $message->additional( 0 )->tryGetRDataValue( 'rdata' ) );

    }


    public function testDecodeForMultipleOPT() : void {
        $stPacketDump = <<<ZEND
        0x0010:  de22 8180 0000 0000 0000 0002 0000 2900 
        0x0020:  0100 0000 1200 0700 0100 0346 6f6f 0000
        0x0030:  2900 0100 0000 3400 0700 0200 0342 6172
        ZEND;
        $packet = HexDump::fromTcpDump( $stPacketDump );
        $buffer = new ReadBuffer( $packet );
        $codec = new RFC1035Decoder();
        self::expectException( RecordException::class );
        $codec->decodeMessage( $buffer );
    }


    public function testDecodeForNoData() : void {
        $codec = new RFC1035Decoder();
        $buffer = new ReadBuffer( '' );
        self::assertNull( $codec->decodeMessage( $buffer ) );
    }


    public function testDecodeQuestion() : void {
        $stQuestion = "\x03bar\03baz\x00\x03foo\xC0\x00\x12\x34\x56\x78qux";
        $buffer = new ReadBuffer( $stQuestion );
        $buffer->seek( 9 );
        $codec = new RFC1035Decoder();
        $question = $codec->decodeQuestion( $buffer );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $question->getName() );
        self::assertSame( 0x1234, $question->typeValue() );
        self::assertSame( 0x5678, $question->classValue() );
        self::assertSame( 'qux', $buffer->consume( 3 ) );

    }


    public function testDecodeRData() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x01\x02\x03\x04\x05\x06" );
        $buffer->seek( 3 );
        $rDataMap = [
            'foo' => RDataType::CharacterString,
            'bar' => RDataType::UINT16,
            'baz' => RDataType::IPv4Address,
        ];
        $codec = new RFC1035Decoder();
        $r = $codec->decodeRData( $buffer, $rDataMap );
        assert( $r instanceof RData );

        self::assertSame( 'Bar', $r[ 'foo' ] );
        self::assertSame( 0x0102, $r[ 'bar' ] );
        self::assertSame( '3.4.5.6', $r[ 'baz' ] );

        self::assertCount( 3, $r );

        self::assertTrue( $buffer->atEnd() );

    }


    public function testDecodeRDataCharacterStringList() : void {
        $buffer = new ReadBuffer( "\x03Foo\x03Bar\x03Baz\x03Qux\x04Quux" );
        $buffer->seek( 4 );
        $codec = new RFC1035Decoder();
        $r = $codec->decodeRDataCharacterStringList( $buffer );
        self::assertSame( [ 'Bar', 'Baz', 'Qux', 'Quux' ], $r );
        self::assertTrue( $buffer->atEnd() );
    }


    public function testDecodeRDataHexBinary() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x03\x04\x05\x06", 3 );
        self::assertSame( '010203040506', RFC1035Decoder::decodeRDataHexBinary( $buffer ) );
    }


    public function testDecodeRDataOption() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x09BarBazQux" );
        $buffer->seek( 3 );
        $uEndOfRData = $buffer->append( 'Quux' );
        $option = RFC1035Decoder::decodeRDataOption( $buffer );
        self::assertSame( 'BarBazQux', $option->data );
        self::assertSame( 0x102, $option->code );
        self::assertSame( $uEndOfRData, $buffer->tell() );
    }


    public function testDecodeRDataOptionList() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux" );
        $buffer->seek( 3 );
        $options = RFC1035Decoder::decodeRDataOptionList( $buffer );

        self::assertSame( 0x102, $options[ 0 ]->code );
        self::assertSame( 'Bar', $options[ 0 ]->data );

        self::assertSame( 0x123, $options[ 1 ]->code );
        self::assertSame( 'Baz', $options[ 1 ]->data );

        self::assertSame( 0x234, $options[ 2 ]->code );
        self::assertSame( 'Qux', $options[ 2 ]->data );

        self::assertCount( 3, $options );
    }


    public function testDecodeRDataValueForCharacterString() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x03Baz", 3 );
        $codec = new RFC1035Decoder();
        $r = $codec->decodeRDataValue( $buffer, RDataType::CharacterString );
        self::assertSame( 'Bar', $r );
        self::assertSame( "\x03Baz", $buffer->consume( null ) );
    }


    public function testDecodeRDataValueForDomainName() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x03Baz\x00" );
        $buffer->seek( 3 );
        $uEndOfRDataValue1 = $buffer->append( "\x03Qux\xC0\x07" );
        $uEndOfRDataValue2 = $buffer->length();

        $codec = new RFC1035Decoder();
        $sub = $buffer->consumeSub( $uEndOfRDataValue1 - $buffer->tell() );
        $value = $codec->decodeRDataValue( $sub, RDataType::DomainName );
        self::assertSame( [ 'Bar', 'Baz' ], $value );
        self::assertSame( $uEndOfRDataValue1, $buffer->tell() );

        $sub = $buffer->consumeSub( $uEndOfRDataValue2 - $buffer->tell() );
        $value = $codec->decodeRDataValue( $sub, RDataType::DomainName );
        self::assertSame( [ 'Qux', 'Baz' ], $value );
        self::assertSame( $uEndOfRDataValue2, $buffer->tell() );
    }


    public function testDecodeRDataValueForIPv4() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x03\x04", 3 );
        $codec = new RFC1035Decoder();
        $rdv = $codec->decodeRDataValue( $buffer, RDataType::IPv4Address );
        self::assertSame( '1.2.3.4', $rdv );
        self::assertTrue( $buffer->atEnd() );
    }


    public function testDecodeRDataValueForIPv6() : void {
        $buffer = new ReadBuffer( "Foo\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );

        $codec = new RFC1035Decoder();
        $rdv = $codec->decodeRDataValue( $buffer, RDataType::IPv6Address );
        self::assertSame( '2001:db8::1', $rdv );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForOption() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x09BarBazQux", 3 );
        $codec = new RFC1035Decoder();
        $option = $codec->decodeRDataValue( $buffer, RDataType::Option );
        assert( $option instanceof Option );
        self::assertSame( 0x102, $option->code );
        self::assertSame( 'BarBazQux', $option->data );
        self::assertTrue( $buffer->atEnd() );
    }


    public function testDecodeRDataValueForOptionList() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux", 3 );
        $buffer->seek( 3 );

        $codec = new RFC1035Decoder();
        $optionList = $codec->decodeRDataValue( $buffer, RDataType::OptionList );
        assert( is_array( $optionList ) );
        self::assertCount( 3, $optionList );

        self::assertSame( 0x102, $optionList[ 0 ]->code );
        self::assertSame( 'Bar', $optionList[ 0 ]->data );

        self::assertSame( 0x123, $optionList[ 1 ]->code );
        self::assertSame( 'Baz', $optionList[ 1 ]->data );

        self::assertSame( 0x234, $optionList[ 2 ]->code );
        self::assertSame( 'Qux', $optionList[ 2 ]->data );

        self::assertTrue( $buffer->atEnd() );
    }


    public function testDecodeRDataValueForUINT16() : void {
        $buffer = new ReadBuffer( "Foo\x12\x34Bar", 3 );
        $codec = new RFC1035Decoder();
        $u = $codec->decodeRDataValue( $buffer, RDataType::UINT16 );
        self::assertSame( 0x1234, $u );
        self::assertSame( 'Bar', $buffer->consume( 3 ) );
    }


    public function testDecodeRDataValueForUINT32() : void {
        $buffer = new ReadBuffer( "Foo\x12\x34\x56\x78" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );

        $codec = new RFC1035Decoder();
        $u = $codec->decodeRDataValue( $buffer, RDataType::UINT32 );
        self::assertSame( 0x12345678, $u );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeResourceRecord() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x03Baz\x00\x00\x01\x00\x01\x01\x23\x45\x67\x00\x04\x01\x02\x03\x04" );
        $buffer->seek( 3 );
        $uEndOfRR = $buffer->append( 'Qux' );

        $codec = new RFC1035Decoder();
        $rr = $codec->decodeResourceRecord( $buffer );

        self::assertSame( [ 'bar', 'baz' ], $rr->getName() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 0x1234567, $rr->ttl() );
        self::assertSame( '1.2.3.4', $rr->tryGetRDataValue( 'address' ) );
        self::assertSame( $uEndOfRR, $buffer->tell() );
        self::assertSame( 'Qux', $buffer->consume( 3 ) );
    }


    public function testDecodeResourceRecordForOPT() : void {
        // OPT record: name=root, type=OPT(41), payload size=1232, flags=0, RCODE=0
        $buffer = new ReadBuffer(
            "\x00" // Root domain name
            . "\x00\x29" // Type OPT (41)
            . "\x04\xd0" // UDP payload size 1232
            . "\x00" // Extended RCODE
            . "\x00" // Version
            . "\x00\x00" // Flags
            . "\x00\x0c" // RDLength (12 bytes)
            . "\x00\x0a" // Option code 10 (COOKIE)
            . "\x00\x08" // Option length 8
            . "\x01\x02\x03\x04\x05\x06\x07\x08" // Cookie data
        );
        $codec = new RFC1035Decoder();
        $rr = $codec->decodeResourceRecord( $buffer );

        assert( $rr instanceof ResourceRecord );

        self::assertSame( [], $rr->getName() ); // Root domain
        self::assertSame( 'OPT', $rr->type() );
        self::assertSame( 1232, $rr->classValue() );
        $version = EDNSVersion::fromFlagTTL( $rr->getTTL() );
        self::assertSame( 0, $version->value );

        $options = $rr->tryGetRDataValue( 'options' );
        self::assertCount( 1, $options );
        self::assertSame( 10, $options[ 0 ]->code ); // COOKIE
        self::assertSame( "\x01\x02\x03\x04\x05\x06\x07\x08", $options[ 0 ]->data );
    }


}
