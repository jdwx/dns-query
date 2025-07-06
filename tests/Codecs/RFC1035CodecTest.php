<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\ReadBuffer;
use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\RDataValue;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RFC1035Codec::class )]
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
        $codec = new RFC1035Codec();
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
        $codec = new RFC1035Codec();
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
        $codec = new RFC1035Codec();
        self::expectException( RecordException::class );
        $codec->decodeMessage( $buffer );
    }


    public function testDecodeForNoData() : void {
        $codec = new RFC1035Codec();
        $buffer = new ReadBuffer( '' );
        self::assertNull( $codec->decodeMessage( $buffer ) );
    }


    public function testDecodeQuestion() : void {
        $stQuestion = "\x03bar\03baz\x00\x03foo\xC0\x00\x12\x34\x56\x78qux";
        $buffer = new ReadBuffer( $stQuestion );
        $buffer->seek( 9 );
        $codec = new RFC1035Codec();
        $question = $codec->decodeQuestion( $buffer );
        self::assertSame( [ 'foo', 'bar', 'baz' ], $question->getName() );
        self::assertSame( 0x1234, $question->typeValue() );
        self::assertSame( 0x5678, $question->classValue() );
        self::assertSame( 'qux', $buffer->consume( 3 ) );

    }


    public function testDecodeRData() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x01\x02\x03\x04\x05\x06" );
        $buffer->seek( 3 );
        $uEndOfRData = $buffer->length();
        $buffer->append( 'Qux' );
        $rDataMap = [
            'foo' => RDataType::CharacterString,
            'bar' => RDataType::UINT16,
            'baz' => RDataType::IPv4Address,
        ];
        $codec = new RFC1035Codec();
        $r = $codec->decodeRData( $rDataMap, $buffer, $uEndOfRData );
        assert( $r instanceof RData );

        self::assertSame( 'Bar', $r[ 'foo' ] );
        self::assertSame( 0x0102, $r[ 'bar' ] );
        self::assertSame( '3.4.5.6', $r[ 'baz' ] );

        self::assertCount( 3, $r );

        self::assertSame( $uEndOfRData, $buffer->tell() );

    }


    public function testDecodeRDataCharacterStringList() : void {
        $buffer = new ReadBuffer( "\x03Foo\x03Bar\x03Baz\x03Qux\x04Quux" );
        $buffer->seek( 4 );
        $uEndOfRData = $buffer->append( "\x05Corge" );
        $codec = new RFC1035Codec();
        $r = $codec->decodeRDataCharacterStringList( $buffer, $uEndOfRData );
        self::assertSame( [ 'Bar', 'Baz', 'Qux', 'Quux' ], $r );
        self::assertSame( $uEndOfRData, $buffer->tell() );
    }


    public function testDecodeRDataHexBinary() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x03\x04\x05\x06" );
        $buffer->seek( 3 );
        $uEndOfRData = $buffer->append( 'Bar' );
        self::assertSame( '010203040506', RFC1035Codec::decodeRDataHexBinary( $buffer, $uEndOfRData ) );
    }


    public function testDecodeRDataOption() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x09BarBazQux" );
        $buffer->seek( 3 );
        $uEndOfRData = $buffer->append( 'Quux' );
        $option = RFC1035Codec::decodeRDataOption( $buffer );
        self::assertSame( 'BarBazQux', $option->data );
        self::assertSame( 0x102, $option->code );
        self::assertSame( $uEndOfRData, $buffer->tell() );
    }


    public function testDecodeRDataOptionList() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux" );
        $uEndOfRData = $buffer->append( 'Quux' );
        $buffer->seek( 3 );
        $options = RFC1035Codec::decodeRDataOptionList( $buffer, $uEndOfRData );

        self::assertSame( 0x102, $options[ 0 ]->code );
        self::assertSame( 'Bar', $options[ 0 ]->data );

        self::assertSame( 0x123, $options[ 1 ]->code );
        self::assertSame( 'Baz', $options[ 1 ]->data );

        self::assertSame( 0x234, $options[ 2 ]->code );
        self::assertSame( 'Qux', $options[ 2 ]->data );

        self::assertCount( 3, $options );
    }


    public function testDecodeRDataValueForCharacterString() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar" );
        $uEndOfRDataValue = $buffer->append( "\x03Baz\x04Qux" );
        $buffer->seek( 3 );
        $uEndOfRData = $buffer->length();
        $codec = new RFC1035Codec();
        $r = $codec->decodeRDataValue( RDataType::CharacterString, $buffer, $uEndOfRData );
        self::assertSame( 'Bar', $r );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForDomainName() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x03Baz\x00" );
        $buffer->seek( 3 );
        $uEndOfRDataValue1 = $buffer->append( "\x03Qux\xC0\x07" );
        $uEndOfRDataValue2 = $buffer->length();
        $uEndOfRData = $buffer->append( 'QuuxCorge' );
        $codec = new RFC1035Codec();
        $value = $codec->decodeRDataValue( RDataType::DomainName, $buffer, $uEndOfRData );
        self::assertSame( [ 'Bar', 'Baz' ], $value );
        self::assertSame( $uEndOfRDataValue1, $buffer->tell() );

        $value = $codec->decodeRDataValue( RDataType::DomainName, $buffer, $uEndOfRData );
        self::assertSame( [ 'Qux', 'Baz' ], $value );
        self::assertSame( $uEndOfRDataValue2, $buffer->tell() );
    }


    public function testDecodeRDataValueForIPv4() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x03\x04" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );
        $uEndOfRData = $buffer->length();

        $codec = new RFC1035Codec();
        $rdv = $codec->decodeRDataValue( RDataType::IPv4Address, $buffer, $uEndOfRData );
        self::assertSame( '1.2.3.4', $rdv );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForIPv6() : void {
        $buffer = new ReadBuffer( "Foo\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );
        $uEndOfRData = $buffer->length();

        $codec = new RFC1035Codec();
        $rdv = $codec->decodeRDataValue( RDataType::IPv6Address, $buffer, $uEndOfRData );
        self::assertSame( '2001:db8::1', $rdv );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForOption() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x09BarBazQux" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Quux' );
        $uEndOfRData = $buffer->length();

        $codec = new RFC1035Codec();
        $option = $codec->decodeRDataValue( RDataType::Option, $buffer, $uEndOfRData );
        assert( $option instanceof Option );
        self::assertSame( 0x102, $option->code );
        self::assertSame( 'BarBazQux', $option->data );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForOptionList() : void {
        $buffer = new ReadBuffer( "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux" );
        $uEndOfRData = $buffer->append( 'Quux' );
        $buffer->seek( 3 );

        $codec = new RFC1035Codec();
        $optionList = $codec->decodeRDataValue( RDataType::OptionList, $buffer, $uEndOfRData );
        assert( is_array( $optionList ) );
        self::assertCount( 3, $optionList );

        self::assertSame( 0x102, $optionList[ 0 ]->code );
        self::assertSame( 'Bar', $optionList[ 0 ]->data );

        self::assertSame( 0x123, $optionList[ 1 ]->code );
        self::assertSame( 'Baz', $optionList[ 1 ]->data );

        self::assertSame( 0x234, $optionList[ 2 ]->code );
        self::assertSame( 'Qux', $optionList[ 2 ]->data );

        self::assertSame( $uEndOfRData, $buffer->tell() );
    }


    public function testDecodeRDataValueForUINT16() : void {
        $buffer = new ReadBuffer( "Foo\x12\x34" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );
        $uEndOfRData = $buffer->length();

        $codec = new RFC1035Codec();
        $u = $codec->decodeRDataValue( RDataType::UINT16, $buffer, $uEndOfRData );
        self::assertSame( 0x1234, $u );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeRDataValueForUINT32() : void {
        $buffer = new ReadBuffer( "Foo\x12\x34\x56\x78" );
        $buffer->seek( 3 );
        $uEndOfRDataValue = $buffer->append( 'Bar' );
        $uEndOfRData = $buffer->length();

        $codec = new RFC1035Codec();
        $u = $codec->decodeRDataValue( RDataType::UINT32, $buffer, $uEndOfRData );
        self::assertSame( 0x12345678, $u );
        self::assertSame( $uEndOfRDataValue, $buffer->tell() );
    }


    public function testDecodeResourceRecord() : void {
        $buffer = new ReadBuffer( "Foo\x03Bar\x03Baz\x00\x00\x01\x00\x01\x01\x23\x45\x67\x00\x04\x01\x02\x03\x04" );
        $buffer->seek( 3 );
        $uEndOfRR = $buffer->append( 'Qux' );

        $codec = new RFC1035Codec();
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
        $codec = new RFC1035Codec();
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


    public function testEncodeMessage() : void {
        $codec = new RFC1035Codec();

        // Create a request message using the factory method
        $msg = Message::request( new Question( 'test.example.com', 'A', 'IN' ) );

        // Update header properties
        $msg->header()->setId( 0x1234 );
        $msg->header()->setOpCode( OpCode::QUERY );
        $msg->header()->setRD( RD::RECURSION_DESIRED );

        // Add an answer record
        $msg->addAnswer( new ResourceRecord(
            'test.example.com',
            'A',
            'IN',
            0x1234,
            new RData( 'A', [ 'address' => '1.2.3.4' ] )
        ) );

        $msg->addAuthority( new ResourceRecord(
            'test.example.com',
            'NS',
            'IN',
            0x2345,
            new RData( 'NS', [ 'nsdname' => [ 'ns', 'example', 'com' ] ] )
        ) );

        $msg->addAdditional( new ResourceRecord(
            'ns.example.com',
            'A',
            'IN',
            0x3456,
            new RData( 'A', [ 'address' => '2.3.4.5' ] )
        ) );

        $wri = new WriteBuffer();
        $codec->encodeMessage( $wri, $msg );
        // echo HexDump::dump( strval( $wri ) );

        // Header
        self::assertSame( "\x12\x34", $wri->shift( 2 ) ); // ID
        self::assertSame( "\x01\x00", $wri->shift( 2 ) ); // Flags (RD=1)
        self::assertSame( HexDump::escape( "\x00\x01" ), HexDump::escape( $wri->shift( 2 ) ) ); // Question Count
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Answer Count
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Authority Count
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Additional Count

        // Question
        self::assertSame(
            HexDump::escape( "\x04test\x07example\x03com\x00" ),
            HexDump::escape( $wri->shift( 18 ) )
        ); // Question Name
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Type A
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Class IN

        // Answer
        self::assertSame( HexDump::escape( "\xc0\x0c" ), HexDump::escape( $wri->shift( 2 ) ) ); // Name (uncompressed)
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Type A
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Class IN
        self::assertSame( "\x00\x00\x12\x34", $wri->shift( 4 ) ); // TTL
        self::assertSame( "\x00\x04", $wri->shift( 2 ) ); // RDLength
        self::assertSame( "\x01\x02\x03\x04", $wri->shift( 4 ) ); // IP address

        // Authority
        self::assertSame( HexDump::escape( "\xc0\x0c" ), HexDump::escape( $wri->shift( 2 ) ) ); // Name (uncompressed)
        self::assertSame( "\x00\x02", $wri->shift( 2 ) ); // Type NS
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Class IN
        self::assertSame( "\x00\x00\x23\x45", $wri->shift( 4 ) ); // TTL
        self::assertSame( HexDump::escape( "\x00\x05" ), HexDump::escape( $wri->shift( 2 ) ) ); // RDLength
        self::assertSame( HexDump::escape( "\x02ns\xc0\x11" ), HexDump::escape( $wri->shift( 5 ) ) ); // NSDNAME

        // Additional
        self::assertSame( HexDump::x( "\xc0\x3e" ), HexDump::x( $wri->shift( 2 ) ) ); // Name
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Type A
        self::assertSame( "\x00\x01", $wri->shift( 2 ) ); // Class IN
        self::assertSame( HexDump::escape( "\x00\x00\x34\x56" ), HexDump::escape( $wri->shift( 4 ) ) ); // TTL
        self::assertSame( "\x00\x04", $wri->shift( 2 ) ); // RDLength
        self::assertSame( "\x02\x03\x04\x05", $wri->shift( 4 ) ); // IP address

        self::assertSame( '', $wri->end() );

    }


    public function testEncodeRData() : void {
        $rDataMap = [
            'foo' => RDataType::CharacterString,
            'bar' => RDataType::UINT16,
            'baz' => RDataType::IPv4Address,
        ];
        $rDataValues = [
            'foo' => 'Test',
            'bar' => 0x1234,
            'baz' => '1.2.3.4',
        ];
        $rData = new RData( $rDataMap, $rDataValues );
        $wri = new WriteBuffer();
        $enc = new RFC1035Codec( [], 10 );
        $enc->encodeRData( $wri, $rData );
        self::assertSame( HexDump::escape( "\x00\x0b\x04Test\x12\x34\x01\x02\x03\x04" ), HexDump::escape( $wri->end() ) );
        self::assertSame( 23, $enc->getOffset() ); // 10 + 5 + 2 + 4
    }


    public function testEncodeRDataCharacterStringList() : void {
        $strings = [ 'Foo', 'Bar', 'Baz', 'Quux' ];
        $enc = new RFC1035Codec();
        $st = $enc->encodeRDataValueCharacterStringList( $strings );
        self::assertSame( "\x03Foo\x03Bar\x03Baz\x04Quux", $st );
    }


    public function testEncodeRDataForMissingData() : void {
        $rDataMap = [
            'foo' => RDataType::UINT16,
        ];
        $rDataValues = [ 'foo' => 0x1234 ];
        $rData = new RData( $rDataMap, $rDataValues );
        $rData->rDataMap[ 'bar' ] = RDataType::CharacterString;
        $enc = new RFC1035Codec();
        $wri = new WriteBuffer();
        self::expectException( RecordDataException::class );
        $enc->encodeRData( $wri, $rData );
    }


    public function testEncodeRDataForOpaque() : void {
        $stData = 'FooBarBazQux';
        $rData = new OpaqueRData( bin2hex( $stData ) );
        $enc = new RFC1035Codec();
        $wri = new WriteBuffer();
        $enc->encodeRData( $wri, $rData );
        self::assertSame( HexDump::escape( "\x00\x0c{$stData}" ), HexDump::escape( $wri->end() ) );
    }


    public function testEncodeRDataHexBinary() : void {
        $enc = new RFC1035Codec();
        $st = $enc->encodeRDataValueHexBinary( '010203040506' );
        self::assertSame( "\x01\x02\x03\x04\x05\x06", $st );
        self::expectException( RecordDataException::class );
        $enc->encodeRDataValueHexBinary( 'Invalid hex' );
    }


    public function testEncodeRDataOption() : void {
        $option = new Option( 0x123, 'Test data' );
        $enc = new RFC1035Codec();
        $st = $enc->encodeRDataValueOption( $option );
        self::assertSame( "\x01\x23\x00\x09Test data", $st );
    }


    public function testEncodeRDataOptionList() : void {
        $options = [
            new Option( 0x123, 'Test data' ),
            new Option( 0x456, 'More data' ),
        ];
        $enc = new RFC1035Codec();
        $st = $enc->encodeRDataValueOptionList( $options );
        self::assertSame( "\x01\x23\x00\x09Test data\x04\x56\x00\x09More data", $st );
    }


    public function testEncodeRDataValueForCharacterString() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::CharacterString, 'Test string' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x0bTest string", $wri->end() );
    }


    public function testEncodeRDataValueForCharacterStringList() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::CharacterStringList, [ 'Foo', 'Bar', 'Baz' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x03Foo\x03Bar\x03Baz", $wri->end() );
    }


    public function testEncodeRDataValueForDomainName() : void {
        $enc = new RFC1035Codec( [], 3 );
        self::assertSame( 3, $enc->getOffset() );

        $rdv = new RDataValue( RDataType::DomainName, [ 'example', 'com' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x07example\x03com\x00", $wri->end() );
        self::assertSame( 16, $enc->getOffset() );

        $rdv = new RDataValue( RDataType::DomainName, [ 'sub', 'domain', 'example', 'com' ] );
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( HexDump::escape( "\x03sub\x06domain\xc0\x03" ), HexDump::escape( $wri->end() ) );

        $rLabelMap = $enc->getLabelMap();
        self::assertSame( 3, array_values( $rLabelMap )[ 0 ] ); // Offset for 'example.com'
        self::assertSame( 11, array_values( $rLabelMap )[ 1 ] ); // Offset for 'com'
        self::assertSame( 16, array_values( $rLabelMap )[ 2 ] ); // Offset for 'sub.domain.example.com'
        self::assertSame( 20, array_values( $rLabelMap )[ 3 ] ); // Offset for 'domain.example.com'
        self::assertCount( 4, $rLabelMap );

    }


    public function testEncodeRDataValueForIPv4() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::IPv4Address, '1.2.3.4' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x01\x02\x03\x04", $wri->end() );
    }


    public function testEncodeRDataValueForIPv6() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::IPv6Address, '2001:db8::1' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", $wri->end() );
    }


    public function testEncodeRDataValueForOption() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::Option, new Option( 0x123, 'Test data' ) );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x01\x23\x00\x09Test data", $wri->end() );
        self::assertSame( 13, $enc->getOffset() );
    }


    public function testEncodeRDataValueForOptionList() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::OptionList, [
            new Option( 0x123, 'Test data' ),
            new Option( 0x456, 'More data' ),
        ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x01\x23\x00\x09Test data\x04\x56\x00\x09More data", $wri->end() );
        self::assertSame( 26, $enc->getOffset() );
    }


    public function testEncodeRDataValueForUINT16() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::UINT16, 0x1234 );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x12\x34", $wri->end() );
    }


    public function testEncodeRDataValueForUINT32() : void {
        $enc = new RFC1035Codec();
        $rdv = new RDataValue( RDataType::UINT32, 0x12345678 );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x12\x34\x56\x78", $wri->end() );
    }


    public function testEncodeResourceRecord() : void {
        $rData = new RData( 'A', [ 'address' => '1.2.3.4' ] );
        $rr = new ResourceRecord(
            [ 'example', 'com' ],
            'A',
            'IN',
            300,
            $rData
        );
        $enc = new RFC1035Codec( [], 12 );
        $wri = new WriteBuffer();
        $enc->encodeResourceRecord( $wri, $rr );

        // Name: example.com
        self::assertStringStartsWith( "\x07example\x03com\x00", $wri->shift( 13 ) );

        // Type: A (1)
        self::assertStringStartsWith( "\x00\x01", $wri->shift( 2 ) );

        // Class: IN (1)
        self::assertStringStartsWith( "\x00\x01", $wri->shift( 2 ) );

        // TTL: 300
        self::assertStringStartsWith( "\x00\x00\x01\x2c", $wri->shift( 4 ) );

        // RDLength: 4
        self::assertStringStartsWith( "\x00\x04", $wri->shift( 2 ) );

        // RData: 1.2.3.4
        self::assertSame( "\x01\x02\x03\x04", $wri->end() );

        // Check label map - keys are hashes, so we check count and values
        $rLabelMap = $enc->getLabelMap();
        self::assertCount( 2, $rLabelMap );
        $offsets = array_values( $rLabelMap );
        self::assertContains( 12, $offsets ); // Offset for 'example.com'
        self::assertContains( 20, $offsets ); // Offset for 'com'
    }


}
