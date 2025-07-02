<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use Stringable;


interface ResourceRecordInterface extends Stringable {


    public function class() : string;


    public function classValue() : int;


    public function getClass() : RecordClass;


    /** @return list<string> */
    public function getName() : array;


    public function getRData() : RDataInterface;

    public function getTTL() : int;

    public function getType() : RecordType;

    public function isClass( int|string|RecordClass $i_class ) : bool;

    public function isType( int|string|RecordType $i_type ) : bool;

    public function name() : string;

    public function setClass( int|string|RecordClass $i_class ) : void;

    /** @param list<string>|string $i_name */
    public function setName( array|string $i_name ) : void;

    public function setRData( string|RDataInterface $i_rData ) : void;

    public function setTTL( int $i_uTTL ) : void;

    public function setType( int|string|RecordType $i_type ) : void;

    /** @return array<string, mixed> */
    public function toArray( bool $i_bNameAsArray = false ) : array;

    public function tryGetRDataValue( string $i_stKey ) : mixed;

    public function ttl() : int;


    public function type() : string;


    public function typeValue() : int;


}