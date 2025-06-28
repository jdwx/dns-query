<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\OptRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OptRecord::class )]
final class OptRecordTest extends TestCase {


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
            new \JDWX\DNSQuery\Option( 10, 'cookie-data' ),
            new \JDWX\DNSQuery\Option( 15, 'error-data' )
        ];
        
        $opt = new OptRecord(
            \JDWX\DNSQuery\Data\ReturnCode::NOERROR,
            \JDWX\DNSQuery\Data\DOK::DNSSEC_OK,
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
            new \JDWX\DNSQuery\Option( 10, 'test-data' )
        ];
        $rdataValue = new \JDWX\DNSQuery\RDataValue( 
            \JDWX\DNSQuery\Data\RDataType::OptionList, 
            $options 
        );
        
        $opt = new OptRecord(
            \JDWX\DNSQuery\Data\ReturnCode::NOERROR,
            \JDWX\DNSQuery\Data\DOK::DNSSEC_NOT_SUPPORTED,
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
            'ttl' => 0
        ];
        
        $opt = OptRecord::fromArray( $data );
        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 1232, $opt->payloadSize() );
        self::assertSame( 0, $opt->version() );
        self::assertSame( [], $opt[ 'options' ] );
    }


    public function testFromArrayWithOptions() : void {
        $options = [
            new \JDWX\DNSQuery\Option( 10, 'cookie' ),
            new \JDWX\DNSQuery\Option( 15, 'error' )
        ];
        
        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
            'options' => $options
        ];
        
        $opt = OptRecord::fromArray( $data );
        self::assertCount( 2, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'cookie', $opt[ 'options' ][ 0 ]->data );
    }


    public function testFromArrayWithNestedRData() : void {
        $options = [
            new \JDWX\DNSQuery\Option( 10, 'nested-data' )
        ];
        
        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
            'rdata' => [
                'options' => $options
            ]
        ];
        
        $opt = OptRecord::fromArray( $data );
        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'nested-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testGetPayloadSize() : void {
        $opt = new OptRecord( uPayloadSize: 2048 );
        self::assertSame( 2048, $opt->getPayloadSize() );
        self::assertSame( 2048, $opt->payloadSize() );
    }


    public function testSetPayloadSize() : void {
        $opt = new OptRecord();
        $opt->setPayloadSize( 8192 );
        self::assertSame( 8192, $opt->payloadSize() );
    }


    public function testSetPayloadSizeInvalidNegative() : void {
        $opt = new OptRecord();
        self::expectException( \InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must be a non-negative integer.' );
        $opt->setPayloadSize( -1 );
    }


    public function testSetPayloadSizeInvalidTooLarge() : void {
        $opt = new OptRecord();
        self::expectException( \InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must not exceed 65535.' );
        $opt->setPayloadSize( 65536 );
    }


    public function testGetVersion() : void {
        $opt = new OptRecord( version: 2 );
        self::assertInstanceOf( \JDWX\DNSQuery\Data\EDNSVersion::class, $opt->getVersion() );
        self::assertSame( 2, $opt->version() );
    }


    public function testClassValue() : void {
        $opt = new OptRecord( uPayloadSize: 1232 );
        self::assertSame( 1232, $opt->classValue() );
    }


    public function testGetClassThrowsException() : void {
        $opt = new OptRecord();
        self::expectException( \LogicException::class );
        self::expectExceptionMessage( 'OPT records do not have a class.' );
        $opt->getClass();
    }


    public function testAddOption() : void {
        $opt = new OptRecord();
        self::assertCount( 0, $opt[ 'options' ] );
        
        $option = new \JDWX\DNSQuery\Option( 10, 'test-data' );
        $opt->addOption( $option );
        
        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code );
        self::assertSame( 'test-data', $opt[ 'options' ][ 0 ]->data );
    }


    public function testAddOptionWithCodeAndData() : void {
        $opt = new OptRecord();
        $opt->addOption( \JDWX\DNSQuery\Data\OptionCode::COOKIE, 'cookie-value' );
        
        self::assertCount( 1, $opt[ 'options' ] );
        self::assertSame( 10, $opt[ 'options' ][ 0 ]->code ); // COOKIE = 10
        self::assertSame( 'cookie-value', $opt[ 'options' ][ 0 ]->data );
    }


    public function testAddOptionMissingData() : void {
        $opt = new OptRecord();
        self::expectException( \InvalidArgumentException::class );
        self::expectExceptionMessage( 'Option data is missing.' );
        $opt->addOption( \JDWX\DNSQuery\Data\OptionCode::COOKIE );
    }


    public function testGetRData() : void {
        $opt = new OptRecord();
        $rData = $opt->getRData();
        
        self::assertArrayHasKey( 'rCode', $rData );
        self::assertArrayHasKey( 'do', $rData );
        self::assertArrayHasKey( 'options', $rData );
        self::assertArrayHasKey( 'version', $rData );
        
        self::assertInstanceOf( \JDWX\DNSQuery\Data\ReturnCode::class, $rData[ 'rCode' ] );
        self::assertInstanceOf( \JDWX\DNSQuery\Data\DOK::class, $rData[ 'do' ] );
        self::assertIsArray( $rData[ 'options' ] );
        self::assertInstanceOf( \JDWX\DNSQuery\Data\EDNSVersion::class, $rData[ 'version' ] );
    }


    public function testGetRDataValue() : void {
        $opt = new OptRecord();
        $rdataValue = $opt->getRDataValue( 'options' );
        
        self::assertInstanceOf( \JDWX\DNSQuery\RDataValue::class, $rdataValue );
        self::assertSame( \JDWX\DNSQuery\Data\RDataType::OptionList, $rdataValue->type );
        self::assertIsArray( $rdataValue->value );
    }


    public function testGetRDataValueInvalidKey() : void {
        $opt = new OptRecord();
        self::assertNull( $opt->getRDataValue( 'invalid' ) );
    }


    public function testGetTTL() : void {
        $opt = new OptRecord(
            \JDWX\DNSQuery\Data\ReturnCode::SERVFAIL,
            \JDWX\DNSQuery\Data\DOK::DNSSEC_OK,
            4096,
            1
        );
        
        // TTL is constructed from rCode, DO bit, and version
        $ttl = $opt->getTTL();
        self::assertIsInt( $ttl );
        self::assertSame( $ttl, $opt->ttl() );
    }


    public function testGetType() : void {
        $opt = new OptRecord();
        self::assertSame( \JDWX\DNSQuery\Data\RecordType::OPT, $opt->getType() );
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
        self::expectException( \LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be rendered to a string.' );
        (string) $opt;
    }


    public function testFromStringThrowsException() : void {
        self::expectException( \LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be created from a string.' );
        OptRecord::fromString( 'test' );
    }


    public function testArrayAccess() : void {
        $options = [
            new \JDWX\DNSQuery\Option( 10, 'test-data' )
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


}
