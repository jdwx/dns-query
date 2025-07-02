<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\Exceptions\RecordDataException;


abstract class AbstractResourceRecord implements ResourceRecordInterface {


    public function class() : string {
        return $this->getClass()->name;
    }


    public function getRDataValue( string $i_stKey ) : mixed {
        $rData = $this->getRData();
        if ( ! isset( $rData[ $i_stKey ] ) ) {
            throw new RecordDataException( "Invalid RData key: \"{$i_stKey}\"" );
        }
        return $rData[ $i_stKey ];
    }


    public function hasRDataValue( string $i_stKey ) : bool {
        return isset( $this->getRData()->toArray()[ $i_stKey ] );
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


    public function setRDataValue( string $i_stKey, mixed $i_value ) : void {
        $this->getRData()[ $i_stKey ] = $i_value;
    }


    public function tryGetRDataValue( string $i_stKey ) : mixed {
        return $this->getRData()->toArray()[ $i_stKey ] ?? null;
    }


    public function ttl() : int {
        return $this->getTTL();
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->getType()->value;
    }


}
