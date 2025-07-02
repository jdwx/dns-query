<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\DomainName;


abstract class AbstractQuestion implements QuestionInterface {


    /** @var list<string> */
    private array $rName;


    /** @param list<string>|string $i_name */
    public function __construct( array|string $i_name ) {
        $this->setName( $i_name );
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


    /** @return list<string> */
    public function getName() : array {
        return $this->rName;
    }


    public function name() : string {
        return DomainName::format( $this->getName() );
    }


    /**
     * @param list<string>|string $i_name
     */
    public function setName( array|string $i_name ) : void {
        $this->rName = DomainName::normalize( $i_name );
    }


    public function type() : string {
        return $this->getType()->name;
    }


    public function typeValue() : int {
        return $this->getType()->value;
    }


}
