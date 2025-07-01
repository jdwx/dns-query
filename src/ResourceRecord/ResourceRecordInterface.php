<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use ArrayAccess;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\RDataValue;


/**
 * @extends ArrayAccess<string, mixed>
 * @suppress PhanAccessWrongInheritanceCategoryInternal
 */
interface ResourceRecordInterface extends \Stringable, ArrayAccess {


    /** @param array<string, mixed> $i_data */
    public static function fromArray( array $i_data ) : self;


    public static function fromString( string $i_string ) : self;


    public function class() : string;


    public function classValue() : int;


    public function getClass() : RecordClass;


    /** @return list<string> */
    public function getName() : array;


    /** @return array<string, RDataValue> */
    public function getRData() : array;


    public function getRDataValue( string $stKey ) : ?RDataValue;


    public function getRDataValueEx( string $stKey ) : RDataValue;


    public function getTTL() : int;


    public function getType() : RecordType;


    public function hasRDataValue( string $i_stName ) : bool;


    public function isClass( int|string|RecordClass $i_class ) : bool;


    public function isType( int|string|RecordType $i_type ) : bool;


    public function name() : string;


    public function setRDataValue( string $i_stName, mixed $i_value ) : void;


    /** @return array<string, mixed> */
    public function toArray( bool $i_bNameAsArray = false ) : array;


    public function ttl() : int;


    public function type() : string;


    public function typeValue() : int;


}