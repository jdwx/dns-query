<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use ArrayAccess;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\Strict\TypeIs;


/** @implements ArrayAccess<string, mixed> */
class RData implements RDataInterface, ArrayAccess {


    /**
     * @param array<string, RDataType> $rDataMap
     * @param array<string, mixed> $rDataValues
     *
     */
    public function __construct( public array $rDataMap, public array $rDataValues ) {}


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
            throw new \LogicException( "RData key \"{$i_stKey}\" does not exist in the record." );
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
    public function offsetExists( mixed $offset ) : bool {
        return $this->hasValue( TypeIs::string( $offset ) );
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


    public function offsetUnset( mixed $offset ) : void {
        throw new \LogicException( 'Cannot unset RData values in a resource record.' );
    }


    public function setValue( string $i_stKey, mixed $i_value ) : void {
        if ( ! $this->hasValue( $i_stKey ) ) {
            throw new \LogicException( "RData key \"{$i_stKey}\" does not exist in the record." );
        }
        $this->rDataValues[ $i_stKey ] = $i_value;
    }


    /** @return array<string, mixed> */
    public function toArray() : array {
        return $this->rDataValues;
    }


}
