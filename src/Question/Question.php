<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;


class Question implements QuestionInterface {


    private int $uType;


    private int $uClass;


    /** @var list<string> */
    private array $rName;


    /** @param list<string>|string $i_name */
    public function __construct( array|string           $i_name, int|string|RecordType $i_type,
                                 int|string|RecordClass $i_class = RecordClass::IN ) {
        $this->setName( $i_name );
        $this->setType( $i_type );
        $this->setClass( $i_class );
    }


    public function __toString() : string {
        return $this->name() . ' ' . $this->class() . ' ' . $this->type();
    }


    public function class() : string {
        return $this->getClass()->name;
    }


    public function classValue() : int {
        return $this->uClass;
    }


    public function getClass() : RecordClass {
        return RecordClass::tryFrom( $this->uClass ) ??
            throw new RecordClassException( "Invalid record class: {$this->uClass}" );
    }


    /** @return list<string> */
    public function getName() : array {
        return $this->rName;
    }


    public function getType() : RecordType {
        return RecordType::tryFrom( $this->uType ) ??
            throw new RecordTypeException( "Invalid record type: {$this->uType}" );
    }


    public function name() : string {
        return DomainName::format( $this->getName() );
    }


    public function setClass( int|string|RecordClass $i_class ) : void {
        $this->uClass = RecordClass::anyToId( $i_class );
    }


    /**
     * @param list<string>|string $i_name
     */
    public function setName( array|string $i_name ) : void {
        $this->rName = DomainName::normalize( $i_name );
    }


    public function setType( int|string|RecordType $i_type ) : void {
        $this->uType = RecordType::anyToId( $i_type );
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->uType;
    }


}
