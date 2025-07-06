<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\PresentationEncoder;
use JDWX\DNSQuery\Data\FlagWord;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\ResourceRecord\RDataValue;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( PresentationEncoder::class )]
final class PresentationEncoderTest extends TestCase {


    public function testEncodeHeader() : void {
        $hdr = new Header( 12345, new FlagWord( i_rd: true ), 1, 2, 3, 4 );
        $enc = new PresentationEncoder();
        $wri = new WriteBuffer();
        $enc->encodeHeader( $wri, $hdr );
        $st = strval( $wri );
        self::assertStringContainsString( ';; Query', $st );
        self::assertStringContainsString( 'opcode: QUERY', $st );
        self::assertStringContainsString( 'status: NOERROR', $st );
        self::assertStringContainsString( 'id: 12345', $st );
        self::assertStringContainsString( 'flags: rd;', $st );
        self::assertStringContainsString( 'z: 0', $st );
    }


    public function testEncodeMessage() : void {
        $hdr = new Header( null, new FlagWord( i_rd: true ) );
        $msg = new Message( $hdr );
        $msg->addQuestion( new Question( 'example.com', 'A' ) );
        $msg->addAnswer( ResourceRecord::fromString( 'example.com 3600 IN A 1.2.3.4' ) );
        $msg->addAuthority( ResourceRecord::fromString( 'example.com 3600 IN NS ns.example.com' ) );
        $msg->addAuthority( ResourceRecord::fromString( 'example.com 3600 IN NS ns2.example.com' ) );
        $msg->addAdditional( ResourceRecord::fromString( 'ns.example.com 3600 IN A 2.3.4.5' ) );
        $msg->addAdditional( ResourceRecord::fromString( 'ns2.example.com 3600 IN A 2.3.4.6' ) );

        $enc = new PresentationEncoder();
        $wri = new WriteBuffer();
        $enc->encodeMessage( $wri, $msg );
        $st = strval( $wri );

        self::assertStringContainsString( ';; Query', $st );
        self::assertStringContainsString( ';; QUESTION SECTION', $st );
        self::assertStringContainsString( ';example.com. IN A', $st );
        self::assertStringContainsString( ';; ANSWER SECTION', $st );
        self::assertStringContainsString( 'example.com. 3600 IN A 1.2.3.4', $st );
        self::assertStringContainsString( ';; AUTHORITY SECTION', $st );
        self::assertStringContainsString( 'example.com. 3600 IN NS ns.example.com.', $st );
        self::assertStringContainsString( 'example.com. 3600 IN NS ns2.example.com.', $st );
        self::assertStringContainsString( ';; ADDITIONAL SECTION', $st );
        self::assertStringContainsString( 'ns.example.com. 3600 IN A 2.3.4.5', $st );
        self::assertStringContainsString( 'ns2.example.com. 3600 IN A 2.3.4.6', $st );
    }


    public function testEncodeMessageForEDNS() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com', 'A', payloadSize: 1232 );
        $msg->setDo( true );
        $msg->addOption( new Option( 10, 'test' ) );

        $enc = new PresentationEncoder();
        $wri = new WriteBuffer();
        $enc->encodeMessage( $wri, $msg );
        $st = strval( $wri );

        self::assertStringContainsString( '; EDNS:', $st );
        self::assertStringContainsString( 'version: 0', $st );
        self::assertStringContainsString( 'flags: do;', $st );
        self::assertStringContainsString( 'payload: 1232', $st );
        self::assertStringContainsString( ';; Options:', $st );
        self::assertStringContainsString( ';;   Code 10:', $st );
    }


    public function testEncodeQuestion() : void {
        $question = new Question( 'example.com', RecordType::MX->value, RecordClass::IN->value );
        self::assertSame( ';example.com. IN MX', (string) $question );
    }


    public function testEncodeQuestionForUnknownTypeAndClass() : void {
        $question = new Question( 'test.example.com', 12345, 65432 );
        self::assertSame( ';test.example.com. CLASS65432 TYPE12345', (string) $question );
    }


    public function testEncodeRDataValueForCharacterString() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::CharacterString, 'foo' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( 'foo', strval( $wri ) );
        $wri->clear();

        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::CharacterString, 'foo bar' );
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( '"foo bar"', strval( $wri ) );
    }


    public function testEncodeRDataValueForCharacterStringList() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::CharacterStringList, [ 'Foo', 'Bar Baz', 'Qux' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        $st = strval( $wri );
        self::assertStringContainsString( 'Foo "Bar Baz" Qux', $st );
    }


    public function testEncodeRDataValueForDomainName() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::DomainName, [ 'example', 'com' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( 'example.com.', $wri->end() );

        $rdv = new RDataValue( RDataType::DomainName, [ 'foo bar', 'baz' ] );
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( '"foo bar".baz.', $wri->end() );
    }


    public function testEncodeRDataValueForHexBinary() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::HexBinary, '466f6f' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( '466f6f', $wri->end() );
    }


    public function testEncodeRDataValueForIPv4() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::IPv4Address, '1.2.3.4' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( '1.2.3.4', $wri->end() );
    }


    public function testEncodeRDataValueForIPv6() : void {
        $enc = new PresentationEncoder();
        $rdv = new RDataValue( RDataType::IPv6Address, '2001:db8::1234' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( '2001:db8::1234', $wri->end() );
    }


    public function testEncodeRDataValueForOption() : void {
        $enc = new PresentationEncoder();
        $option = new Option( 1, 'Foo' );
        $st = $enc->encodeRDataValueOption( $option );
        self::assertStringContainsString( 'Code 1', $st );
        self::assertStringContainsString( '466f6f', $st );
    }


    public function testEncodeResourceRecord() : void {
        $enc = new PresentationEncoder();
        $rr = ResourceRecord::fromString( 'example.com 3600 IN AFSDB 1 afs.example.com' );
        $wri = new WriteBuffer();
        $enc->encodeResourceRecord( $wri, $rr );
        $st = strval( $wri );
        self::assertStringContainsString( 'example.com', $st );
        self::assertStringContainsString( '3600', $st );
        self::assertStringContainsString( 'afs.example.com', $st );
    }


}
