<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\OptRecord;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord;
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
        $codec = new RFC1035Codec();
        $msg = $codec->decode( $packet );

        self::assertSame( 56866, $msg->id );
        self::assertSame( OpCode::QUERY, $msg->opcode );

        self::assertCount( 1, $msg->question );
        self::assertSame( 'www.example.com', $msg->question[ 0 ]->stName );
        self::assertSame( 'A', $msg->question[ 0 ]->type->name );
        self::assertSame( 'IN', $msg->question[ 0 ]->class->name );

        self::assertCount( 4, $msg->answer );
        self::assertSame( 'www.example.com', $msg->answer[ 0 ]->name() );
        self::assertSame( 'CNAME', $msg->answer[ 0 ]->type() );
        self::assertSame( 'IN', $msg->answer[ 0 ]->class() );
        self::assertSame( 207, $msg->answer[ 0 ]->ttl() );
        self::assertSame(
            'www.example.com-v4.edgesuite.net',
            join( '.', $msg->answer[ 0 ][ 'cname' ] )
        );

        self::assertSame( 'www.example.com-v4.edgesuite.net', $msg->answer[ 1 ]->name() );
        self::assertSame( 'CNAME', $msg->answer[ 1 ]->type() );
        self::assertSame( 'IN', $msg->answer[ 1 ]->class() );
        self::assertSame( 21507, $msg->answer[ 1 ]->ttl() );
        self::assertSame(
            'a1422.dscr.akamai.net',
            join( '.', $msg->answer[ 1 ][ 'cname' ] )
        );

        self::assertSame( 'a1422.dscr.akamai.net', $msg->answer[ 2 ]->name() );
        self::assertSame( 'A', $msg->answer[ 2 ]->type() );
        self::assertSame( 'IN', $msg->answer[ 2 ]->class() );
        self::assertSame( 17, $msg->answer[ 2 ]->ttl() );
        self::assertSame( '23.40.60.56', $msg->answer[ 2 ][ 'address' ] );

        self::assertSame( 'a1422.dscr.akamai.net', $msg->answer[ 3 ]->name() );
        self::assertSame( 'A', $msg->answer[ 3 ]->type() );
        self::assertSame( 'IN', $msg->answer[ 3 ]->class() );
        self::assertSame( 17, $msg->answer[ 3 ]->ttl() );
        self::assertSame( '23.40.60.40', $msg->answer[ 3 ][ 'address' ] );

        $opt = $msg->opt[ 0 ];
        assert( $opt instanceof OptRecord );
        self::assertSame( 0, $opt->version() );
        self::assertSame( 1232, $opt->payloadSize() );
        self::assertSame( [], $opt[ 'options' ] );

    }


    public function testDecodeRData() : void {
        $stData = "Foo\x03Bar\x01\x02\x03\x04\x05\x06";
        $uOffset = 3;
        $uEndOfRData = strlen( $stData );
        $stData .= 'Qux';
        $rDataMap = [
            'foo' => RDataType::CharacterString,
            'bar' => RDataType::UINT16,
            'baz' => RDataType::IPv4Address,
        ];
        $r = RFC1035Codec::decodeRData( $rDataMap, $stData, $uOffset, $uEndOfRData );

        self::assertSame( RDataType::CharacterString, $r[ 'foo' ]->type );
        self::assertSame( 'Bar', $r[ 'foo' ]->value );

        self::assertSame( RDataType::UINT16, $r[ 'bar' ]->type );
        self::assertSame( 0x0102, $r[ 'bar' ]->value );

        self::assertSame( RDataType::IPv4Address, $r[ 'baz' ]->type );
        self::assertSame( '3.4.5.6', $r[ 'baz' ]->value );

        self::assertCount( 3, $r );

        self::assertSame( $uEndOfRData, $uOffset );

    }


    public function testDecodeRDataCharacterStringList() : void {
        $stData = "\x03Foo\x03Bar\x03Baz\x03Qux\x04Quux";
        $uOffset = 4;
        $uEndOfRData = strlen( $stData );
        $stData .= "\x05Corge";
        $r = RFC1035Codec::decodeRDataCharacterStringList( $stData, $uOffset, $uEndOfRData );
        self::assertSame( [ 'Bar', 'Baz', 'Qux', 'Quux' ], $r );
        self::assertSame( $uEndOfRData, $uOffset );
    }


    public function testDecodeRDataOption() : void {
        $stData = "Foo\x01\x02\x00\x09BarBazQux";
        $uOffset = 3;
        $uEndOfRData = strlen( $stData );
        $stData .= 'Quux';
        $option = RFC1035Codec::decodeRDataOption( $stData, $uOffset );
        self::assertSame( 'BarBazQux', $option->data );
        self::assertSame( 0x102, $option->code );
        self::assertSame( $uEndOfRData, $uOffset );
    }


    public function testDecodeRDataOptionList() : void {
        $stData = "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux";
        $uEndOfRData = strlen( $stData );
        $uOffset = 3;
        $stData .= 'Quux';
        $options = RFC1035Codec::decodeRDataOptionList( $stData, $uOffset, $uEndOfRData );

        self::assertSame( 0x102, $options[ 0 ]->code );
        self::assertSame( 'Bar', $options[ 0 ]->data );

        self::assertSame( 0x123, $options[ 1 ]->code );
        self::assertSame( 'Baz', $options[ 1 ]->data );

        self::assertSame( 0x234, $options[ 2 ]->code );
        self::assertSame( 'Qux', $options[ 2 ]->data );

        self::assertCount( 3, $options );
    }


    public function testDecodeRDataValueForCharacterString() : void {
        $stData = "Foo\x03Bar";
        $uEndOfRDataValue = strlen( $stData );
        $stData .= "\x03Baz\x04Qux";
        $uOffset = 3;
        $uEndOfRData = strlen( $stData );
        $r = RFC1035Codec::decodeRDataValue( RDataType::CharacterString, $stData, $uOffset, $uEndOfRData );
        self::assertSame( 'Bar', $r->value );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeRDataValueForDomainName() : void {
        $st = "Foo\x03Bar\x03Baz\x00";
        $uOffset = 3;
        $uEndOfRDataValue1 = strlen( $st );
        $st .= "\x03Qux\xC0\x07";
        $uEndOfRDataValue2 = strlen( $st );
        $st .= 'QuuxCorge';
        $uEndOfRData = strlen( $st );
        $rdv = RFC1035Codec::decodeRDataValue( RDataType::DomainName, $st, $uOffset, $uEndOfRData );
        self::assertSame( [ 'Bar', 'Baz' ], $rdv->value );
        self::assertSame( $uEndOfRDataValue1, $uOffset );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::DomainName, $st, $uOffset, $uEndOfRData );
        self::assertSame( [ 'Qux', 'Baz' ], $rdv->value );
        self::assertSame( $uEndOfRDataValue2, $uOffset );
    }


    public function testDecodeRDataValueForIPv4() : void {
        $st = "Foo\x01\x02\x03\x04";
        $uOffset = 3;
        $uEndOfRDataValue = strlen( $st );
        $st .= 'Bar';
        $uEndOfRData = strlen( $st );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::IPv4Address, $st, $uOffset, $uEndOfRData );
        self::assertSame( '1.2.3.4', $rdv->value );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeRDataValueForIPv6() : void {
        $st = "Foo\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01";
        $uOffset = 3;
        $uEndOfRDataValue = strlen( $st );
        $st .= 'Bar';
        $uEndOfRData = strlen( $st );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::IPv6Address, $st, $uOffset, $uEndOfRData );
        self::assertSame( '2001:db8::1', $rdv->value );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeRDataValueForOption() : void {
        $st = "Foo\x01\x02\x00\x09BarBazQux";
        $uOffset = 3;
        $uEndOfRDataValue = strlen( $st );
        $st .= 'Quux';
        $uEndOfRData = strlen( $st );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::Option, $st, $uOffset, $uEndOfRData );
        self::assertSame( RDataType::Option, $rdv->type );
        assert( $rdv->value instanceof Option );
        self::assertSame( 0x102, $rdv->value->code );
        self::assertSame( 'BarBazQux', $rdv->value->data );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeRDataValueForOptionList() : void {
        $st = "Foo\x01\x02\x00\x03Bar\x01\x23\x00\x03Baz\x02\x34\x00\x03Qux";
        $uEndOfRData = strlen( $st );
        $uOffset = 3;
        $st .= 'Quux';

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::OptionList, $st, $uOffset, $uEndOfRData );
        self::assertSame( RDataType::OptionList, $rdv->type );
        assert( is_array( $rdv->value ) );
        self::assertCount( 3, $rdv->value );

        self::assertSame( 0x102, $rdv->value[ 0 ]->code );
        self::assertSame( 'Bar', $rdv->value[ 0 ]->data );

        self::assertSame( 0x123, $rdv->value[ 1 ]->code );
        self::assertSame( 'Baz', $rdv->value[ 1 ]->data );

        self::assertSame( 0x234, $rdv->value[ 2 ]->code );
        self::assertSame( 'Qux', $rdv->value[ 2 ]->data );

        self::assertSame( $uEndOfRData, $uOffset );
    }


    public function testDecodeRDataValueForUINT16() : void {
        $st = "Foo\x12\x34";
        $uOffset = 3;
        $uEndOfRDataValue = strlen( $st );
        $st .= 'Bar';
        $uEndOfRData = strlen( $st );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::UINT16, $st, $uOffset, $uEndOfRData );
        self::assertSame( 0x1234, $rdv->value );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeRDataValueForUINT32() : void {
        $st = "Foo\x12\x34\x56\x78";
        $uOffset = 3;
        $uEndOfRDataValue = strlen( $st );
        $st .= 'Bar';
        $uEndOfRData = strlen( $st );

        $rdv = RFC1035Codec::decodeRDataValue( RDataType::UINT32, $st, $uOffset, $uEndOfRData );
        self::assertSame( 0x12345678, $rdv->value );
        self::assertSame( $uEndOfRDataValue, $uOffset );
    }


    public function testDecodeResourceRecord() : void {
        $st = "Foo\x03Bar\x03Baz\x00\x00\x01\x00\x01\x01\x23\x45\x67\x00\x04\x01\x02\x03\x04";
        $uOffset = 3;
        $uEndOfRR = strlen( $st );
        $st .= 'Qux';
        $rr = RFC1035Codec::decodeResourceRecord( $st, $uOffset );

        self::assertSame( [ 'Bar', 'Baz' ], $rr->getName() );
        self::assertSame( 'A', $rr->type() );
        self::assertSame( 'IN', $rr->class() );
        self::assertSame( 0x1234567, $rr->ttl() );
        self::assertSame( '1.2.3.4', $rr[ 'address' ] );
        self::assertSame( $uEndOfRR, $uOffset );
    }


    public function testDecodeResourceRecordForOPT() : void {
        // OPT record: name=root, type=OPT(41), payload size=1232, flags=0, RCODE=0
        $st = "\x00" // Root domain name
            . "\x00\x29" // Type OPT (41)
            . "\x04\xd0" // UDP payload size 1232
            . "\x00" // Extended RCODE
            . "\x00" // Version
            . "\x00\x00" // Flags
            . "\x00\x0c" // RDLength (12 bytes)
            . "\x00\x0a" // Option code 10 (COOKIE)
            . "\x00\x08" // Option length 8
            . "\x01\x02\x03\x04\x05\x06\x07\x08"; // Cookie data

        $uOffset = 0;
        $rr = RFC1035Codec::decodeResourceRecord( $st, $uOffset );

        assert( $rr instanceof OptRecord );

        self::assertSame( [], $rr->getName() ); // Root domain
        self::assertSame( 'OPT', $rr->type() );
        self::assertSame( 1232, $rr->payloadSize() );
        self::assertSame( 0, $rr->version() );

        $options = $rr[ 'options' ];
        self::assertCount( 1, $options );
        self::assertSame( 10, $options[ 0 ]->code ); // COOKIE
        self::assertSame( "\x01\x02\x03\x04\x05\x06\x07\x08", $options[ 0 ]->data );
    }


    public function testEncode() : void {
        $codec = new RFC1035Codec();
        $msg = new Message();
        $msg->id = 0x1234;
        $msg->opcode = OpCode::QUERY;
        $msg->rd = RD::RECURSION_DESIRED;
        $msg->question[] = new Question( 'test', 'A', 'IN' );

        // Add an answer record
        $msg->answer[] = new ResourceRecord(
            'test',
            'A',
            'IN',
            300,
            [ 'address' => new RDataValue( RDataType::IPv4Address, '1.2.3.4' ) ]
        );

        $st = $codec->encode( $msg );

        // Header
        self::assertStringStartsWith( "\x12\x34", $st ); // ID
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x01\x00", $st ); // Flags (RD=1)
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Question Count
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Answer Count
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x00", $st ); // Authority Count
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x00", $st ); // Additional Count
        $st = substr( $st, 2 );

        // Question
        self::assertStringStartsWith( "\x04test\x00", $st ); // Question Name
        $st = substr( $st, 6 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Type A
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Class IN
        $st = substr( $st, 2 );

        // Answer
        self::assertStringStartsWith( "\x04test\x00", $st ); // Name (uncompressed)
        $st = substr( $st, 6 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Type A
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x01", $st ); // Class IN
        $st = substr( $st, 2 );
        self::assertStringStartsWith( "\x00\x00\x01\x2c", $st ); // TTL 300
        $st = substr( $st, 4 );
        self::assertStringStartsWith( "\x00\x04", $st ); // RDLength
        $st = substr( $st, 2 );
        self::assertSame( "\x01\x02\x03\x04", $st ); // IP address
    }


    public function testEncodeRData() : void {
        $rDataMap = [
            'foo' => RDataType::CharacterString,
            'bar' => RDataType::UINT16,
            'baz' => RDataType::IPv4Address,
        ];
        $rData = [
            'foo' => new RDataValue( RDataType::CharacterString, 'Test' ),
            'bar' => new RDataValue( RDataType::UINT16, 0x1234 ),
            'baz' => new RDataValue( RDataType::IPv4Address, '1.2.3.4' ),
        ];
        $rLabelMap = [];
        $uOffset = 10;
        $st = RFC1035Codec::encodeRData( $rDataMap, $rLabelMap, $uOffset, $rData );
        self::assertSame( "\x04Test\x12\x34\x01\x02\x03\x04", $st );
        self::assertSame( 21, $uOffset ); // 10 + 5 + 2 + 4
    }


    public function testEncodeRDataCharacterStringList() : void {
        $strings = [ 'Foo', 'Bar', 'Baz', 'Quux' ];
        $st = RFC1035Codec::encodeRDataCharacterStringList( $strings );
        self::assertSame( "\x03Foo\x03Bar\x03Baz\x04Quux", $st );
    }


    public function testEncodeRDataOption() : void {
        $option = new Option( 0x123, 'Test data' );
        $st = RFC1035Codec::encodeRDataOption( $option );
        self::assertSame( "\x01\x23\x00\x09Test data", $st );
    }


    public function testEncodeRDataOptionList() : void {
        $options = [
            new Option( 0x123, 'Test data' ),
            new Option( 0x456, 'More data' ),
        ];
        $st = RFC1035Codec::encodeRDataOptionList( $options );
        self::assertSame( "\x01\x23\x00\x09Test data\x04\x56\x00\x09More data", $st );
    }


    public function testEncodeRDataValueForCharacterString() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::CharacterString, 'Test string' );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x0bTest string", $st );
    }


    public function testEncodeRDataValueForCharacterStringList() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::CharacterStringList, [ 'Foo', 'Bar', 'Baz' ] );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x03Foo\x03Bar\x03Baz", $st );
    }


    public function testEncodeRDataValueForDomainName() : void {
        $rLabelMap = [];
        $uOffset = 3;
        $rdv = new RDataValue( RDataType::DomainName, [ 'example', 'com' ] );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        $uOffset += strlen( $st );
        self::assertSame( "\x07example\x03com\x00", $st );
        self::assertSame( 3, array_values( $rLabelMap )[ 0 ] ); // Offset for 'example'
        self::assertSame( 11, array_values( $rLabelMap )[ 1 ] ); // Offset for 'com'
        self::assertCount( 2, $rLabelMap );

        $rdv = new RDataValue( RDataType::DomainName, [ 'sub', 'domain', 'example', 'com' ] );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( HexDump::escape( "\x03sub\x06domain\xc0\x03" ), HexDump::escape( $st ) );
    }


    public function testEncodeRDataValueForIPv4() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::IPv4Address, '1.2.3.4' );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x01\x02\x03\x04", $st );
    }


    public function testEncodeRDataValueForIPv6() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::IPv6Address, '2001:db8::1' );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", $st );
    }


    public function testEncodeRDataValueForOption() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::Option, new Option( 0x123, 'Test data' ) );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x01\x23\x00\x09Test data", $st );
    }


    public function testEncodeRDataValueForOptionList() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::OptionList, [
            new Option( 0x123, 'Test data' ),
            new Option( 0x456, 'More data' ),
        ] );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x01\x23\x00\x09Test data\x04\x56\x00\x09More data", $st );
    }


    public function testEncodeRDataValueForUINT16() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::UINT16, 0x1234 );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x12\x34", $st );
    }


    public function testEncodeRDataValueForUINT32() : void {
        $rLabelMap = [];
        $uOffset = 0;
        $rdv = new RDataValue( RDataType::UINT32, 0x12345678 );
        $st = RFC1035Codec::encodeRDataValue( $rdv, $rLabelMap, $uOffset );
        self::assertSame( "\x12\x34\x56\x78", $st );
    }


    public function testEncodeResourceRecord() : void {
        $rr = new ResourceRecord(
            [ 'example', 'com' ],
            'A',
            'IN',
            300,
            [ 'address' => new RDataValue( RDataType::IPv4Address, '1.2.3.4' ) ]
        );
        $rLabelMap = [];
        $uOffset = 12;
        $st = RFC1035Codec::encodeResourceRecord( $rr, $rLabelMap, $uOffset );

        // Name: example.com
        self::assertStringStartsWith( "\x07example\x03com\x00", $st );
        $st = substr( $st, 13 );

        // Type: A (1)
        self::assertStringStartsWith( "\x00\x01", $st );
        $st = substr( $st, 2 );

        // Class: IN (1)
        self::assertStringStartsWith( "\x00\x01", $st );
        $st = substr( $st, 2 );

        // TTL: 300
        self::assertStringStartsWith( "\x00\x00\x01\x2c", $st );
        $st = substr( $st, 4 );

        // RDLength: 4
        self::assertStringStartsWith( "\x00\x04", $st );
        $st = substr( $st, 2 );

        // RData: 1.2.3.4
        self::assertSame( "\x01\x02\x03\x04", $st );

        // Check label map - keys are hashes, so we check count and values
        self::assertCount( 2, $rLabelMap );
        $offsets = array_values( $rLabelMap );
        self::assertContains( 12, $offsets ); // Offset for 'example.com'
        self::assertContains( 20, $offsets ); // Offset for 'com'
    }


}
