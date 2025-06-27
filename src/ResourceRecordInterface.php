<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


interface ResourceRecordInterface extends \Stringable {


    /** @param array<string, mixed> $i_data */
    public static function fromArray( array $i_data ) : self;


    public static function fromString( string $i_string ) : self;


    public function class() : string;


    public function getClass() : RecordClass;


    /** @return list<string> */
    public function getName() : array;


    /** @return array<string, RDataValue> */
    public function getRData() : array;


    public function getTTL() : int;


    public function getType() : RecordType;


    public function isClass( int|string|RecordClass $i_class ) : bool;


    public function isType( int|string|RecordType $i_type ) : bool;


    public function name() : string;


    /** @return array<string, mixed> */
    public function toArray() : array;


    public function type() : string;


}