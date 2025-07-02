<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;


class OpaqueQuestion extends AbstractQuestion {


    /** @param list<string>|string $i_name */
    public function __construct( array|string $i_name, private int $uType, private int $uClass ) {
        parent::__construct( $i_name );
    }


    public function classValue() : int {
        return $this->uClass;
    }


    public function getClass() : RecordClass {
        return RecordClass::tryFrom( $this->uClass ) ??
            throw new RecordClassException( "Invalid record class: {$this->uClass}" );
    }


    public function getType() : RecordType {
        return RecordType::tryFrom( $this->uType ) ??
            throw new RecordTypeException( "Invalid record type: {$this->uType}" );
    }


    public function setClass( int|string|RecordClass $i_class ) : void {
        $this->uClass = RecordClass::normalize( $i_class )->value;
    }


    public function setType( int|string|RecordType $i_type ) : void {
        $this->uType = RecordType::normalize( $i_type )->value;
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->uType;
    }


}
