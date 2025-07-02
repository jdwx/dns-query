<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\ResourceRecord;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\ResourceRecord\OptResourceRecord;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( OptResourceRecord::class )]
final class OptRecordTest extends TestCase {


    public function testAddOption() : void {
        $opt = new OptResourceRecord();
        self::assertCount( 0, $opt->tryGetRDataValue( 'options' ) );

        $option = new Option( 10, 'test-data' );
        $opt->addOption( $option );

        self::assertCount( 1, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code );
        self::assertSame( 'test-data', $opt->option( 0 )->data );
    }


    public function testAddOptionMissingData() : void {
        $opt = new OptResourceRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Option data is missing.' );
        $opt->addOption( OptionCode::COOKIE );
    }


    public function testAddOptionWithCodeAndData() : void {
        $opt = new OptResourceRecord();
        $opt->addOption( OptionCode::COOKIE, 'cookie-value' );

        self::assertCount( 1, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code ); // COOKIE = 10
        self::assertSame( 'cookie-value', $opt->option( 0 )->data );
    }


    public function testArrayAccess() : void {
        $opt = new OptResourceRecord( rData: new Option( 10, 'test-data' ) );

        // Test exists
        self::assertTrue( isset( $opt->getRData()[ 'options' ] ) );
        self::assertFalse( isset( $opt->getRData()[ 'nonexistent' ] ) );

        // Test get
        self::assertCount( 1, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code );
        self::assertSame( 'test-data', $opt->option( 0 )->data );
    }


    public function testClassValue() : void {
        $opt = new OptResourceRecord( class: 1232 );
        self::assertSame( 1232, $opt->classValue() );
    }


    public function testConstructDefault() : void {
        $opt = new OptResourceRecord();

        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( '', $opt->name() );
        self::assertSame( [], $opt->getName() );
        self::assertSame( 4096, $opt->payloadSize() );
        self::assertSame( 0, $opt->version() );
        self::assertSame( [], $opt->options() );
    }


    public function testConstructWithParameters() : void {
        $options = [
            new Option( 10, 'cookie-data' ),
            new Option( 15, 'error-data' ),
        ];

        $opt = new OptResourceRecord(
            [],
            RecordType::OPT,
            12345,
            65536,
            $options
        );

        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 12345, $opt->payloadSize() );
        self::assertSame( 1, $opt->version() );
        self::assertCount( 2, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code );
        self::assertSame( 'cookie-data', $opt->option( 0 )->data );

        self::assertSame( 15, $opt->option( 1 )->code );
        self::assertSame( 'error-data', $opt->option( 1 )->data );
    }


    public function testFromArrayBasic() : void {
        $data = [
            'name' => [],
            'type' => 'OPT',
            'class' => 1232,
            'ttl' => 0,
            'rdata' => [
                'options' => [],
            ],
        ];

        $opt = OptResourceRecord::fromArray( $data );
        self::assertSame( 'OPT', $opt->type() );
        self::assertSame( 1232, $opt->payloadSize() );
        self::assertSame( 0, $opt->version() );
        self::assertSame( [], $opt->options() );
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

        $opt = OptResourceRecord::fromArray( $data );
        self::assertCount( 1, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code );
        self::assertSame( 'nested-data', $opt->option( 0 )->data );
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

        $opt = OptResourceRecord::fromArray( $data );
        self::assertCount( 2, $opt->options() );
        self::assertSame( 10, $opt->option( 0 )->code );
        self::assertSame( 'cookie', $opt->option( 0 )->data );

        self::assertSame( 15, $opt->option( 1 )->code );
        self::assertSame( 'error', $opt->option( 1 )->data );
    }


    public function testFromStringThrowsException() : void {
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be created from a string.' );
        OptResourceRecord::fromString( 'test' );
    }


    public function testGetClassThrowsException() : void {
        $opt = new OptResourceRecord();
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records do not have a class.' );
        $opt->getClass();
    }


    public function testGetPayloadSize() : void {
        $opt = new OptResourceRecord( class: 2048 );
        self::assertSame( 2048, $opt->getPayloadSize() );
        self::assertSame( 2048, $opt->payloadSize() );
    }


    public function testGetRData() : void {
        $opt = new OptResourceRecord();
        $rData = $opt->getRData();

        self::assertArrayHasKey( 'options', $rData );

        self::assertIsArray( $rData[ 'options' ] );
    }


    public function testGetRDataValue() : void {
        $opt = new OptResourceRecord();
        $options = $opt->tryGetRDataValue( 'options' );
        self::assertIsArray( $options );
    }


    public function testGetRDataValueInvalidKey() : void {
        $opt = new OptResourceRecord();
        self::assertNull( $opt->tryGetRDataValue( 'invalid' ) );
    }


    public function testGetTTL() : void {
        $opt = new OptResourceRecord( uTTL: 12345 );
        self::assertSame( 12345, $opt->getTTL() );
        self::assertSame( 12345, $opt->ttl() );
    }


    public function testGetType() : void {
        $opt = new OptResourceRecord();
        self::assertSame( RecordType::OPT, $opt->getType() );
    }


    public function testGetVersion() : void {
        $opt = new OptResourceRecord();
        $opt->setVersion( 2 );
        self::assertInstanceOf( EDNSVersion::class, $opt->getVersion() );
        self::assertEquals( EDNSVersion::from( 2 ), $opt->getVersion() );
        self::assertSame( 2, $opt->version() );
    }


    public function testSetPayloadSize() : void {
        $opt = new OptResourceRecord();
        $opt->setPayloadSize( 8192 );
        self::assertSame( 8192, $opt->payloadSize() );
    }


    public function testSetPayloadSizeInvalidNegative() : void {
        $opt = new OptResourceRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must be a non-negative integer.' );
        $opt->setPayloadSize( -1 );
    }


    public function testSetPayloadSizeInvalidTooLarge() : void {
        $opt = new OptResourceRecord();
        self::expectException( InvalidArgumentException::class );
        self::expectExceptionMessage( 'Payload size must not exceed 65535.' );
        $opt->setPayloadSize( 65536 );
    }


    public function testToArray() : void {
        $opt = new OptResourceRecord( class: 1232 );
        $array = $opt->toArray();

        self::assertArrayHasKey( 'name', $array );
        self::assertArrayHasKey( 'type', $array );
        self::assertArrayHasKey( 'ttl', $array );
        self::assertArrayHasKey( 'do', $array );
        self::assertArrayHasKey( 'version', $array );
        self::assertArrayHasKey( 'payloadSize', $array );

        self::assertSame( '', $array[ 'name' ] );
        self::assertSame( 'OPT', $array[ 'type' ] );
        self::assertSame( 1232, $array[ 'payloadSize' ] );
    }


    public function testToArrayWithNameAsArray() : void {
        $opt = new OptResourceRecord();
        $array = $opt->toArray( true );

        self::assertSame( [], $array[ 'name' ] );
    }


    public function testToStringThrowsException() : void {
        $opt = new OptResourceRecord();
        self::expectException( LogicException::class );
        self::expectExceptionMessage( 'OPT records cannot be rendered to a string.' );
        $x = (string) $opt;
        unset( $x );
    }


}
