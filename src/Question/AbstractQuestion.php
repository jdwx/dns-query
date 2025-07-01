<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\DomainName;


abstract class AbstractQuestion implements QuestionInterface {


    private array $rName;


    public function __construct( array|string $name ) {
        $this->setName( $name );
    }


    public function __toString() : string {
        return $this->name() . ' ' . $this->class() . ' ' . $this->type();
    }


    public function class() : string {
        return $this->getClass()->name;
    }


    public function classValue() : int {
        return $this->getClass()->value;
    }


    public function getName() : array {
        return $this->rName;
    }


    public function name() : string {
        return DomainName::format( $this->getName() );
    }


    public function setName( array|string $name ) : void {
        $this->rName = DomainName::normalize( $name );
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->getType()->value;
    }


}
