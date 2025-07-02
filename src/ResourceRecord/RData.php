<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\Strict\TypeIs;


class RData extends AbstractRData {


    /** @var array<string, RDataType> */
    public array $rDataMap;

    /** @var array<string, mixed> */
    public array $rDataValues = [];


    /**
     * @param array<string, RDataType>|int|string|RecordType $i_rDataMap
     * @param array<string, mixed> $i_rDataValues
     *
     */
    public function __construct( array|int|string|RecordType $i_rDataMap, array $i_rDataValues ) {
        if ( ! is_array( $i_rDataMap ) ) {
            $i_rDataMap = RDataMaps::map( $i_rDataMap );
        }
        $this->rDataMap = $i_rDataMap;
        foreach ( $this->rDataMap as $stName => $rdt ) {
            /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
            assert( $rdt instanceof RDataType );
            if ( ! isset( $i_rDataValues[ $stName ] ) ) {
                throw new RecordException( "Missing RData value for {$stName} in record" );
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
                throw new RecordException( "Missing RData value for {$stName} in record" );
            }
            $rData[ $stName ] = $rdt->consume( $i_rParsedStrings );
        }
        if ( ! empty( $i_rParsedStrings ) ) {
            throw new RecordException( 'Extra data found in record: ' . implode( ' ', $i_rParsedStrings ) );
        }
        return new self( $i_rDataMap, $rData );
    }


    public function __toString() : string {
        $st = '';
        foreach ( $this->rDataMap as $stName => $rdt ) {
            $st .= ' ' . $rdt->format( $this->rDataValues[ $stName ] );
        }
        return trim( $st );
    }


    public function getValue( string $i_stKey ) : mixed {
        if ( ! $this->hasValue( $i_stKey ) ) {
            throw new \LogicException( "Invalid RData key: \"{$i_stKey}\"" );
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


    protected function validKeys() : array {
        return array_keys( $this->rDataMap );
    }


}
