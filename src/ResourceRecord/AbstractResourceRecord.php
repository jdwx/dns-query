<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\DomainName;


abstract class AbstractResourceRecord implements ResourceRecordInterface {


    public function class() : string {
        return $this->getClass()->name;
    }


    public function getRDataValue( string $i_stKey ) : mixed {
        return $this->getRData()->toArray()[ $i_stKey ];
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
