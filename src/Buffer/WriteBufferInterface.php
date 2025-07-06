<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Buffer;


use Stringable;


interface WriteBufferInterface extends Stringable {


    public function append( int|string ...$i_rData ) : int;


    public function clear() : void;


    public function end() : string;


    public function length() : int;


    public function set( int $i_uOffset, int|string $i_istData ) : void;


    public function shift( int $i_uLength ) : string;


}