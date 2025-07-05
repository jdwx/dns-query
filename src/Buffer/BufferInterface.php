<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Buffer;


interface BufferInterface {


    public function atEnd() : bool;


    public function consume( ?int $i_nuLength ) : string;


    public function consumeHexBinary() : string;


    public function consumeIPv4() : string;


    public function consumeIPv6() : string;


    public function consumeLabel() : string;


    /**
     * Be a little careful with this function. It is theoretically legal for a name to
     * contain dots that are part of a label, not label separators. This function
     * will lose that distinction. However, in practice, this is unlikely to be a problem.
     */
    public function consumeName() : string;


    /** @return list<string> */
    public function consumeNameArray() : array;


    public function consumeNameLabel() : string;


    public function consumeUINT16() : int;


    public function consumeUINT32() : int;


    public function consumeUINT8() : int;


    public function getData() : string;


    public function length() : int;


    public function readyCheck() : bool;


    public function seek( int $i_uOffset, int $i_iWhence = SEEK_SET ) : void;


    public function sub( int $i_uLength, int $i_iWhence = SEEK_CUR, ?int $i_uOffset = null ) : BufferInterface;


    public function tell() : int;


}