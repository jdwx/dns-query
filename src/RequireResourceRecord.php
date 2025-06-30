<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


/**
 * Because traits cannot implement or require interfaces, this trait can be used
 * from other traits that need to ensure that the class using them implements
 * ResourceRecordInterface.
 */
trait RequireResourceRecord {


    /**
     * @param array<string, mixed> $i_data
     */
    abstract public static function fromArray( array $i_data ) : ResourceRecordInterface;


    abstract public static function fromString( string $i_string ) : ResourceRecordInterface;


    abstract public function class() : string;


    abstract public function classValue() : int;


    abstract public function getClass() : RecordClass;


    /** @return list<string> */
    abstract public function getName() : array;


    /** @return array<string, RDataValue> */
    abstract public function getRData() : array;


    abstract public function getRDataValue( string $stKey ) : ?RDataValue;


    abstract public function getRDataValueEx( string $stKey ) : RDataValue;


    abstract public function getTTL() : int;


    abstract public function getType() : RecordType;


    abstract public function hasRDataValue( string $i_stName ) : bool;


    abstract public function isClass( int|string|RecordClass $i_class ) : bool;


    abstract public function isType( int|string|RecordType $i_type ) : bool;


    abstract public function name() : string;


    abstract public function setRDataValue( string $i_stName, mixed $i_value ) : void;


    /** @return array<string, mixed> */
    abstract public function toArray( bool $i_bNameAsArray = false ) : array;


    abstract public function ttl() : int;


    abstract public function type() : string;


    abstract public function typeValue() : int;


}
