<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Codecs;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\RFC1035Encoder;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\RDataValue;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RFC1035Encoder::class )]
class RFC1035EncoderTest extends TestCase {


    public function testEncodeMessage() : void {
        $codec = new RFC1035Encoder();

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
        $enc = new RFC1035Encoder( [], 10 );
        $enc->encodeRData( $wri, $rData );
        self::assertSame( HexDump::escape( "\x00\x0b\x04Test\x12\x34\x01\x02\x03\x04" ), HexDump::escape( $wri->end() ) );
        self::assertSame( 23, $enc->getOffset() ); // 10 + 5 + 2 + 4
    }


    public function testEncodeRDataCharacterStringList() : void {
        $strings = [ 'Foo', 'Bar', 'Baz', 'Quux' ];
        $enc = new RFC1035Encoder();
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
        $enc = new RFC1035Encoder();
        $wri = new WriteBuffer();
        self::expectException( RecordDataException::class );
        $enc->encodeRData( $wri, $rData );
    }


    public function testEncodeRDataForOpaque() : void {
        $stData = 'FooBarBazQux';
        $rData = new OpaqueRData( bin2hex( $stData ) );
        $enc = new RFC1035Encoder();
        $wri = new WriteBuffer();
        $enc->encodeRData( $wri, $rData );
        self::assertSame( HexDump::escape( "\x00\x0c{$stData}" ), HexDump::escape( $wri->end() ) );
    }


    public function testEncodeRDataHexBinary() : void {
        $enc = new RFC1035Encoder();
        $st = $enc->encodeRDataValueHexBinary( '010203040506' );
        self::assertSame( "\x01\x02\x03\x04\x05\x06", $st );
        self::expectException( RecordDataException::class );
        $enc->encodeRDataValueHexBinary( 'Invalid hex' );
    }


    public function testEncodeRDataOption() : void {
        $option = new Option( 0x123, 'Test data' );
        $enc = new RFC1035Encoder();
        $st = $enc->encodeRDataValueOption( $option );
        self::assertSame( "\x01\x23\x00\x09Test data", $st );
    }


    public function testEncodeRDataOptionList() : void {
        $options = [
            new Option( 0x123, 'Test data' ),
            new Option( 0x456, 'More data' ),
        ];
        $enc = new RFC1035Encoder();
        $st = $enc->encodeRDataValueOptionList( $options );
        self::assertSame( "\x01\x23\x00\x09Test data\x04\x56\x00\x09More data", $st );
    }


    public function testEncodeRDataValueDomainNameUncompressed() : void {
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::DomainNameUncompressed, [ 'example', 'com' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x07example\x03com\x00", $wri->end() );

    }


    public function testEncodeRDataValueForCharacterString() : void {
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::CharacterString, 'Test string' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x0bTest string", $wri->end() );
    }


    public function testEncodeRDataValueForCharacterStringList() : void {
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::CharacterStringList, [ 'Foo', 'Bar', 'Baz' ] );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x03Foo\x03Bar\x03Baz", $wri->end() );
    }


    public function testEncodeRDataValueForDomainName() : void {
        $enc = new RFC1035Encoder( [], 3 );
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
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::IPv4Address, '1.2.3.4' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x01\x02\x03\x04", $wri->end() );
    }


    public function testEncodeRDataValueForIPv6() : void {
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::IPv6Address, '2001:db8::1' );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", $wri->end() );
    }


    public function testEncodeRDataValueForOption() : void {
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::Option, new Option( 0x123, 'Test data' ) );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x01\x23\x00\x09Test data", $wri->end() );
        self::assertSame( 13, $enc->getOffset() );
    }


    public function testEncodeRDataValueForOptionList() : void {
        $enc = new RFC1035Encoder();
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
        $enc = new RFC1035Encoder();
        $rdv = new RDataValue( RDataType::UINT16, 0x1234 );
        $wri = new WriteBuffer();
        $enc->encodeRDataValue( $wri, $rdv );
        self::assertSame( "\x12\x34", $wri->end() );
    }


    public function testEncodeRDataValueForUINT32() : void {
        $enc = new RFC1035Encoder();
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
        $enc = new RFC1035Encoder( [], 12 );
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
