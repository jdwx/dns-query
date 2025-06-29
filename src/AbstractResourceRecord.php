<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\Strict\TypeIs;
use LogicException;
use OutOfBoundsException;


abstract class AbstractResourceRecord implements ResourceRecordInterface {


    /** @var array<string, RDataType> */
    protected array $rDataMap;


    /**
     * @param array<string, RDataType>|RecordType $rDataMap
     * @param array<string, mixed> $rData
     */
    public function __construct( array|RecordType $rDataMap, protected array $rData = [] ) {
        if ( $rDataMap instanceof RecordType ) {
            $rDataMap = RDataMaps::map( $rDataMap );
        }
        $this->rDataMap = $rDataMap;

        foreach ( array_keys( $this->rDataMap ) as $stKey ) {
            if ( ! isset( $rData[ $stKey ] ) ) {
                throw new InvalidArgumentException( "Missing required RData key: {$stKey}" );
            }
            $this->setRDataValueAlreadyChecked( $stKey, $rData[ $stKey ] );
        }
    }


    public function class() : string {
        return $this->getClass()->name;
    }


    /** @return array<string, RDataValue> */
    public function getRData() : array {
        return $this->rData;
    }


    public function getRDataValueEx( string $stKey ) : RDataValue {
        $rdv = $this->getRDataValue( $stKey );
        if ( $rdv instanceof RDataValue ) {
            return $rdv;
        }
        throw new OutOfBoundsException( "RData key not found: {$stKey}" );
    }


    public function hasRDataValue( string $i_stName ) : bool {
        return isset( $this->rDataMap[ $i_stName ] );
    }


    public function isClass( int|string|RecordClass $i_class ) : bool {
        return $this->getClass()->is( $i_class );
    }


    public function isType( int|string|RecordType $i_type ) : bool {
        return $this->getType()->is( $i_type );
    }


    public function name() : string {
        return DomainName::format( $this->getName() );
    }


    public function offsetExists( mixed $offset ) : bool {
        return isset( $this->rData[ $offset ] );
    }


    public function offsetGet( mixed $offset ) : mixed {
        return $this->getRDataValueEx( TypeIs::string( $offset ) )->value;
    }


    public function offsetSet( mixed $offset, mixed $value ) : void {
        $this->setRDataValue( TypeIs::string( $offset ), $value );
    }


    public function offsetUnset( mixed $offset ) : void {
        throw new LogicException( 'Cannot unset RData values in a ResourceRecord.' );
    }


    public function setRDataValue( string $i_stName, mixed $i_value ) : void {
        if ( ! $this->hasRDataValue( $i_stName ) ) {
            throw new InvalidArgumentException( "Invalid RData key: {$i_stName}" );
        }
        if ( ! $i_value instanceof RDataValue ) {
            $i_value = new RDataValue( $this->rDataMap[ $i_stName ], $i_value );
        }
        $this->rData[ $i_stName ] = $i_value;
    }


    public function ttl() : int {
        return $this->getTTL();
    }


    public function type() : string {
        return $this->getType()->name;
    }


    protected function setRDataValueAlreadyChecked( string $i_stName, mixed $i_value ) : void {
        if ( ! $i_value instanceof RDataValue ) {
            $i_value = new RDataValue( $this->rDataMap[ $i_stName ], $i_value );
        } elseif ( $i_value->type !== $this->rDataMap[ $i_stName ] ) {
            throw new InvalidArgumentException(
                "RData type mismatch for {$i_stName}: wanted {$this->rDataMap[$i_stName]->name}, got {$i_value->type->name}"
            );
        }
        $this->rData[ $i_stName ] = $i_value;
    }


}
