<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


interface QuestionInterface extends \Stringable {


    public function class() : string;


    public function classValue() : int;


    public function getClass() : RecordClass;


    /** @return list<string> */
    public function getName() : array;


    public function getType() : RecordType;


    public function name() : string;


    public function setClass( int|string|RecordClass $i_class ) : void;


    /** @param list<string>|string $i_name */
    public function setName( array|string $i_name ) : void;


    public function setType( int|string|RecordType $i_type ) : void;


    public function type() : string;


    public function typeValue() : int;


}