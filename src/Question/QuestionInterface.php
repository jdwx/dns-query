<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


interface QuestionInterface extends \Stringable {


    public function class() : string;


    public function classValue() : int;


    public function getClass() : RecordClass;


    public function getName() : array;


    public function getType() : RecordType;


    public function name() : string;


    public function setClass( int|string|RecordClass $class ) : void;


    public function setName( array|string $name ) : void;


    public function setType( int|string|RecordType $type ) : void;


    public function type() : string;


    public function typeValue() : int;


}