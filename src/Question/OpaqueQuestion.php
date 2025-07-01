<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\Transport\BufferInterface;


class OpaqueQuestion extends AbstractQuestion {


    public function __construct( array $name, private int $uType, private int $uClass ) {
        parent::__construct( $name );
    }


    public static function fromBuffer( BufferInterface $i_buffer ) : self {
        $rName = $i_buffer->consumeNameArray();
        $uType = $i_buffer->consumeUINT16();
        $uClass = $i_buffer->consumeUINT16();
        return new self( $rName, $uType, $uClass );
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


    public function setClass( int|string|RecordClass $class ) : void {
        $this->uClass = RecordClass::normalize( $class )->value;
    }


    public function setType( int|string|RecordType $type ) : void {
        $this->uType = RecordType::normalize( $type )->value;
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->uType;
    }


}
