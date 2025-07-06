<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Codecs\PresentationEncoder;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\Strict\TypeIs;


class RData extends AbstractRData {


    /** @var array<string, RDataType> */
    public array $rDataMap;

    /** @var array<string, mixed> */
    public array $rDataValues = [];


    /**
     * @param array<string, RDataType>|int|string|RecordType|ResourceRecordInterface $i_rDataMap
     * @param array<string, mixed> $i_rDataValues
     *
     */
    public function __construct( array|int|string|RecordType|ResourceRecordInterface $i_rDataMap, array|string $i_rDataValues ) {
        $this->rDataMap = RDataMaps::map( $i_rDataMap );
        foreach ( $this->rDataMap as $stName => $rdt ) {
            /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
            assert( $rdt instanceof RDataType );
            if ( ! isset( $i_rDataValues[ $stName ] ) ) {
                throw new RecordDataException( "Missing RData value for {$stName} in record" );
            }
            $this->rDataValues[ $stName ] = $i_rDataValues[ $stName ];
        }
    }


    /**
     * @param array<string, RDataType> $i_rDataMap
     * @param list<string> $i_rParsedStrings
     */
    public static function fromParsedString( array $i_rDataMap, array $i_rParsedStrings ) : self {
        $rData = [];
        foreach ( $i_rDataMap as $stName => $rdt ) {
            if ( empty( $i_rParsedStrings ) ) {
                throw new RecordDataException( "Missing RData value for {$stName} in record" );
            }
            $rData[ $stName ] = $rdt->consume( $i_rParsedStrings );
        }
        if ( ! empty( $i_rParsedStrings ) ) {
            throw new RecordDataException( 'Extra data found in record: ' . implode( ' ', $i_rParsedStrings ) );
        }
        return new self( $i_rDataMap, $rData );
    }


    /**
     * @param array<string, RDataType>|int|string|RecordType|ResourceRecordInterface $i_map
     * @param array<string, mixed>|string|RDataInterface $i_value
     * @return RDataInterface
     */
    public static function normalize( array|int|string|RecordType|ResourceRecordInterface $i_map,
                                      array|string|RDataInterface                         $i_value ) : RDataInterface {
        if ( $i_value instanceof RDataInterface ) {
            return $i_value;
        }
        $i_map = RDataMaps::tryMap( $i_map );
        if ( ! is_array( $i_map ) ) {
            if ( is_string( $i_value ) ) {
                return new OpaqueRData( $i_value );
            }
            throw new RecordDataException(
                'Missing RData map'
            );
        }
        if ( is_array( $i_value ) ) {
            return new self( $i_map, $i_value );
        }
        $i_value = ResourceRecord::splitString( $i_value );
        return self::fromParsedString( $i_map, $i_value );
    }


    public function __toString() : string {
        $wri = new WriteBuffer();
        $enc = new PresentationEncoder();
        $enc->encodeRData( $wri, $this );
        return $wri->end();
    }


    public function getValue( string $i_stKey ) : mixed {
        if ( ! $this->hasValue( $i_stKey ) ) {
            throw new RecordDataException( "Invalid RData key: \"{$i_stKey}\"" );
        }
        return $this->rDataValues[ $i_stKey ];
    }


    public function hasValue( string $i_stKey ) : bool {
        return isset( $this->rDataMap[ $i_stKey ] );
    }


    /** @return array<string, RDataType> */
    public function map() : array {
        return $this->rDataMap;
    }


    /**
     * @param string $offset
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetGet( mixed $offset ) : mixed {
        return $this->rDataValues[ TypeIs::string( $offset ) ] ?? null;
    }


    /**
     * @param string $offset
     * @param mixed $value
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetSet( mixed $offset, mixed $value ) : void {
        $this->setValue( TypeIs::string( $offset ), $value );
    }


    public function setValue( string $i_stKey, mixed $i_value ) : void {
        if ( ! $this->hasValue( $i_stKey ) ) {
            throw new RecordDataException( "Invalid RData key: \"{$i_stKey}\"" );
        }
        $this->rDataValues[ $i_stKey ] = $i_value;
    }


    /** @return array<string, mixed> */
    public function toArray() : array {
        return $this->rDataValues;
    }


    /**
     * @return \Generator<string, RDataValueInterface>
     */
    public function values() : \Generator {
        foreach ( $this->validKeys() as $stKey ) {
            if ( ! isset( $this->rDataValues[ $stKey ] ) ) {
                throw new RecordDataException( "Missing RData value for {$stKey} in record" );
            }
            yield $stKey => new RDataValue( $this->rDataMap[ $stKey ], $this->rDataValues[ $stKey ] );
        }
    }


    protected function validKeys() : array {
        return array_keys( $this->rDataMap );
    }


}
