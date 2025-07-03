<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Message;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( EDNSMessage::class )]
final class EDNSMessageTest extends TestCase {


    public function testAddOption() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );

        self::assertEmpty( $msg->getOptions() );

        $msg->addOption( new Option( 10, 'cookie-data' ) );
        self::assertCount( 1, $msg->getOptions() );
        self::assertSame( 10, $msg->getOptions()[ 0 ]->code );
        self::assertSame( 'cookie-data', $msg->getOptions()[ 0 ]->data );

        $msg->addOption( OptionCode::ERROR, 'error-data' );
        self::assertCount( 2, $msg->getOptions() );
        self::assertSame( 15, $msg->getOptions()[ 1 ]->code );
        self::assertSame( 'error-data', $msg->getOptions()[ 1 ]->data );
    }


    public function testAddOptionMissingData() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );

        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Option data is missing.' );
        $msg->addOption( OptionCode::COOKIE );
    }


    public function testConstructDefault() : void {
        $header = Header::request();
        $msg = new EDNSMessage( $header );

        self::assertSame( EDNSMessage::DEFAULT_PAYLOAD_SIZE, $msg->getPayloadSize() );
        self::assertSame( 0, $msg->getVersion()->value );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, $msg->getDo() );
        self::assertEmpty( $msg->getOptions() );
    }


    public function testConstructWithParameters() : void {
        $header = Header::request();
        $question = [ new Question( 'example.com', 'A' ) ];
        $options = [ new Option( 10, 'test-data' ) ];

        $msg = new EDNSMessage(
            $header,
            $question,
            [],
            [],
            [],
            8192,
            1,
            true,
            $options
        );

        self::assertSame( 8192, $msg->getPayloadSize() );
        self::assertSame( 1, $msg->getVersion()->value );
        self::assertSame( DOK::DNSSEC_OK, $msg->getDo() );
        self::assertCount( 1, $msg->getOptions() );
        self::assertSame( 10, $msg->getOptions()[ 0 ]->code );
        self::assertSame( 'test-data', $msg->getOptions()[ 0 ]->data );
    }


    public function testConversionFromMessage() : void {
        // Test the conversion method on Message class
        $msg = Message::request( 'example.com', 'A' );
        $edns = EDNSMessage::fromMessage( $msg );

        self::assertSame( EDNSMessage::DEFAULT_PAYLOAD_SIZE, $edns->getPayloadSize() );
        self::assertCount( 1, $edns->getQuestion() );
        self::assertSame( 'example.com', $edns->question()->name() );
    }


    public function testEdnsRequest() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com', 'A', 'IN', 1232, true );

        self::assertInstanceOf( EDNSMessage::class, $msg );
        self::assertCount( 1, $msg->getQuestion() );
        self::assertSame( 'example.com', $msg->question()->name() );
        self::assertSame( 'A', $msg->question()->type() );
        self::assertSame( 'IN', $msg->question()->class() );
        self::assertSame( 1232, $msg->getPayloadSize() );
        self::assertSame( DOK::DNSSEC_OK, $msg->getDo() );
    }


    public function testEdnsResponse() : void {
        $request = EDNSMessage::ednsRequest( 'example.com', 'A' );
        $response = EDNSMessage::ednsResponse( $request );

        self::assertInstanceOf( EDNSMessage::class, $response );
        self::assertCount( 1, $response->getQuestion() );
        self::assertSame( 'example.com', $response->question()->name() );
        self::assertSame( EDNSMessage::DEFAULT_PAYLOAD_SIZE, $response->getPayloadSize() );
        self::assertSame( 0, $response->getVersion()->value );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, $response->getDo() );
    }


    public function testEdnsResponseFromRegularMessage() : void {
        $request = Message::request( 'example.com', 'A' );
        $response = EDNSMessage::ednsResponse( $request, payloadSize: 1232 );

        self::assertInstanceOf( EDNSMessage::class, $response );
        self::assertSame( 1232, $response->getPayloadSize() );
    }


    public function testFromMessage() : void {
        $msg = Message::request( 'example.com', 'A' );
        $edns = EDNSMessage::fromMessage( $msg );

        self::assertInstanceOf( EDNSMessage::class, $edns );
        self::assertCount( 1, $edns->getQuestion() );
        self::assertSame( 'example.com', $edns->question()->name() );
        self::assertSame( EDNSMessage::DEFAULT_PAYLOAD_SIZE, $edns->getPayloadSize() );
    }


    public function testFromMessageWithOpt() : void {
        $msg = Message::request( 'example.com', 'A' );
        $opt = new ResourceRecord( '', RecordType::OPT, class: 1232, uTTL: 0x8000 ); // "DO" bit set
        $msg->addAdditional( $opt );

        $edns = EDNSMessage::fromMessage( $msg );

        self::assertSame( 1232, $edns->getPayloadSize() );
        self::assertSame( 0, $edns->getVersion()->value );
        self::assertSame( DOK::DNSSEC_OK, $edns->getDo() );
    }


    public function testSetDnssecOK() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );

        $msg->setDo( true );
        self::assertSame( DOK::DNSSEC_OK, $msg->getDo() );

        $msg->setDo( false );
        self::assertSame( DOK::DNSSEC_NOT_SUPPORTED, $msg->getDo() );

        $msg->setDo( DOK::DNSSEC_OK );
        self::assertSame( DOK::DNSSEC_OK, $msg->getDo() );
    }


    public function testSetPayloadSize() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );
        $msg->setPayloadSize( 512 );
        self::assertSame( 512, $msg->getPayloadSize() );
    }


    public function testSetPayloadSizeInvalid() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );

        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must be a non-negative integer.' );
        $msg->setPayloadSize( -1 );
    }


    public function testSetPayloadSizeTooLarge() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );

        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must not exceed 65535.' );
        $msg->setPayloadSize( 65536 );
    }


    public function testSetVersion() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );
        $msg->setVersion( 1 );
        self::assertSame( 1, $msg->getVersion()->value );

        $msg->setVersion( EDNSVersion::from( 2 ) );
        self::assertSame( 2, $msg->getVersion()->value );
    }


    public function testToOptResourceRecord() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com', payloadSize: 1232 );
        $msg->setVersion( 1 );
        $msg->setDo( true );
        $msg->addOption( new Option( 10, 'test' ) );

        $opt = $msg->toOptResourceRecord();

        self::assertInstanceOf( ResourceRecord::class, $opt );
        self::assertSame( [], $opt->getName() );
        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 1232, $opt->classValue() );
        $version = EDNSVersion::fromFlagTTL( $opt->ttl() );
        self::assertSame( 1, $version->value );
        $do = DOK::fromFlagTTL( $opt->ttl() );
        self::assertSame( DOK::DNSSEC_OK, $do );
        $options = $opt->getRDataValue( 'options' );
        self::assertCount( 1, $options );
        self::assertSame( 10, $options[ 0 ]->code );
        self::assertSame( 'test', $options[ 0 ]->data );
    }


    public function testToString() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com', 'A', payloadSize: 1232 );
        $msg->setDo( true );
        $msg->addOption( new Option( 10, 'test' ) );

        $str = (string) $msg;

        self::assertStringContainsString( '; EDNS:', $str );
        self::assertStringContainsString( 'version: 0', $str );
        self::assertStringContainsString( 'flags: do;', $str );
        self::assertStringContainsString( 'payload: 1232', $str );
        self::assertStringContainsString( ';; Options:', $str );
        self::assertStringContainsString( ';;   Code 10:', $str );
    }


    public function testTryOption() : void {
        $msg = EDNSMessage::ednsRequest( 'example.com' );
        $msg->addOption( new Option( 10, 'test' ) );

        $option = $msg->tryOption( 0 );
        self::assertInstanceOf( Option::class, $option );
        self::assertSame( 10, $option->code );
        self::assertSame( 'test', $option->data );

        self::assertNull( $msg->tryOption( 1 ) );
    }


}