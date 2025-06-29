<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\OptRecord;
use JDWX\DNSQuery\RDataValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OptRecord::class )]
final class OptRecordTest extends TestCase {


    public function testAddOption() : void {
        $opt = new OptRecord();
        self::assertCount( 0, $opt[ 'options' ] );

        $option = new Option( 10, 'test-data' );
        $opt->addOption( $option );

        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'test-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testAddOptionMissingData() : void {
        $opt = new OptRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Option data is missing.' );
        $opt->addOption( OptionCode::COOKIE );
    }


    public function testAddOptionWithCodeAndData() : void {
        $opt = new OptRecord();
        $opt->addOption( OptionCode::COOKIE, 'cookie-value' );

        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code ); // COOKIE = 10
        self::assertSame( 'cookie-value', $opt[ 'options' ][ 0 ]->data );
    }


    public function testArrayAccess() : void {
        $options = [
            new Option( 10, 'test-data' ),
        ];
        $opt = new OptRecord( options: $options );

        // Test exists
        self::assertTrue( isset( $opt[ 'options' ] ) );
        self::assertFalse( isset( $opt[ 'nonexistent' ] ) );

        // Test get
        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'test-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testClassValue() : void {
        $opt = new OptRecord( uPayloadSize: 1232 );
        self::assertSame( 1232, $opt->classValue() );
    }


    public function testConstructDefault() : void {
        $opt = new OptRecord();

        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( '', $opt->name() );
        self::assertSame( [], $opt->getName() );
        self::assertSame( 4096, $opt->payloadSize() );
        self::assertSame( 0, $opt->version() );
        self::assertSame( [], $opt[ 'options' ] );
    }


    public function testConstructWithParameters() : void {
        $options = [
            new Option( 10, 'cookie-data' ),
            new Option( 15, 'error-data' ),
        ];

        $opt = new OptRecord(
            ReturnCode::NOERROR,
            DOK::DNSSEC_OK,
            1232,
            1,
            $options
        );

        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 1232, $opt->payloadSize() );
        self::assertSame( 1, $opt->version() );
        self::assertCount( 2, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'cookie-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testConstructWithRDataValue() : void {
        $options = [
            new Option( 10, 'test-data' ),
        ];
        $rdataValue = new RDataValue(
            RDataType::OptionList,
            $options
        );

        $opt = new OptRecord(
            ReturnCode::NOERROR,
            DOK::DNSSEC_NOT_SUPPORTED,
            4096,
            0,
            $rdataValue
        );

        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'test-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testFromArrayBasic() : void {
        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
        ];

        $opt = OptRecord::fromArray( $data );
        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 1232, $opt->payloadSize() );
        self::assertSame( 0, $opt->version() );
        self::assertSame( [], $opt[ 'options' ] );
    }


    public function testFromArrayWithNestedRData() : void {
        $options = [
            new Option( 10, 'nested-data' ),
        ];

        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
            'rdata' => [
                'options' => $options,
            ],
        ];

        $opt = OptRecord::fromArray( $data );
        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'nested-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testFromArrayWithOptions() : void {
        $options = [
            new Option( 10, 'cookie' ),
            new Option( 15, 'error' ),
        ];

        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
            'options' => $options,
        ];

        $opt = OptRecord::fromArray( $data );
        self::assertCount( 2, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'cookie', $opt[ 'options' ][ 0 ]->data );
    }


    public function testFromStringThrowsException() : void {
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be created from a string.' );
        OptRecord::fromString( 'test' );
    }


    public function testGetClassThrowsException() : void {
        $opt = new OptRecord();
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records do not have a class.' );
        $opt->getClass();
    }


    public function testGetPayloadSize() : void {
        $opt = new OptRecord( uPayloadSize: 2048 );
        self::assertSame( 2048, $opt->getPayloadSize() );
        self::assertSame( 2048, $opt->payloadSize() );
    }


    public function testGetRData() : void {
        $opt = new OptRecord();
        $rData = $opt->getRData();

        self::assertArrayHasKey( 'options', $rData );

        self::assertIsArray( $rData[ 'options' ]->value );
    }


    public function testGetRDataValue() : void {
        $opt = new OptRecord();
        $rdataValue = $opt->getRDataValue( 'options' );

        self::assertInstanceOf( RDataValue::class, $rdataValue );
        self::assertSame( RDataType::OptionList, $rdataValue->type );
        self::assertIsArray( $rdataValue->value );
    }


    public function testGetRDataValueInvalidKey() : void {
        $opt = new OptRecord();
        self::assertNull( $opt->getRDataValue( 'invalid' ) );
    }


    public function testGetTTL() : void {
        $opt = new OptRecord(
            ReturnCode::SERVFAIL,
            DOK::DNSSEC_OK,
            4096,
            1
        );

        // TTL is constructed from rCode, the "DO" bit, and version
        $ttl = $opt->getTTL();
        self::assertSame( $ttl, $opt->ttl() );
    }


    public function testGetType() : void {
        $opt = new OptRecord();
        self::assertSame( RecordType::OPT, $opt->getType() );
    }


    public function testGetVersion() : void {
        $opt = new OptRecord( version: 2 );
        self::assertInstanceOf( EDNSVersion::class, $opt->getVersion() );
        self::assertSame( 2, $opt->version() );
    }


    public function testSetPayloadSize() : void {
        $opt = new OptRecord();
        $opt->setPayloadSize( 8192 );
        self::assertSame( 8192, $opt->payloadSize() );
    }


    public function testSetPayloadSizeInvalidNegative() : void {
        $opt = new OptRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must be a non-negative integer.' );
        $opt->setPayloadSize( -1 );
    }


    public function testSetPayloadSizeInvalidTooLarge() : void {
        $opt = new OptRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must not exceed 65535.' );
        $opt->setPayloadSize( 65536 );
    }


    public function testToArray() : void {
        $opt = new OptRecord( uPayloadSize: 1232 );
        $array = $opt->toArray();

        self::assertArrayHasKey( 'name', $array );
        self::assertArrayHasKey( 'type', $array );
        self::assertArrayHasKey( 'class', $array );
        self::assertArrayHasKey( 'ttl', $array );
        self::assertArrayHasKey( 'do', $array );
        self::assertArrayHasKey( 'version', $array );
        self::assertArrayHasKey( 'payloadSize', $array );

        self::assertSame( '', $array[ 'name' ] );
        self::assertSame( 'OPT', $array[ 'type' ] );
        self::assertSame( 1232, $array[ 'class' ] );
        self::assertSame( 1232, $array[ 'payloadSize' ] );
    }


    public function testToArrayWithNameAsArray() : void {
        $opt = new OptRecord();
        $array = $opt->toArray( true );

        self::assertSame( [], $array[ 'name' ] );
    }


    public function testToStringThrowsException() : void {
        $opt = new OptRecord();
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be rendered to a string.' );
        $x = (string) $opt;
        unset( $x );
    }


}
