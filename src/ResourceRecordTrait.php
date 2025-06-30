<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\Strict\TypeIs;
use LogicException;


trait ResourceRecordTrait {


    use RequireResourceRecord;


    public function class() : string {
        return $this->getClass()->name;
    }


    public function name() : string {
        return DomainName::format( $this->getName() );
    }


    public function offsetExists( mixed $offset ) : bool {
        return $this->hasRDataValue( TypeIs::string( $offset ) );
    }


    public function offsetGet( mixed $offset ) : mixed {
        return $this->getRDataValueEx( TypeIs::string( $offset ) )->value;
    }


    public function offsetSet( mixed $offset, mixed $value ) : void {
        $this->setRDataValue( TypeIs::string( $offset ), $value );
    }


    public function offsetUnset( mixed $offset ) : void {
        throw new LogicException( 'Cannot unset RData values in a resource record.' );
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