<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


interface DecoderInterface {


    public function decodeHeader( ReadBufferInterface $i_buffer ) : ?HeaderInterface;


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface;


    public function decodeQuestion( ReadBufferInterface $i_buffer ) : ?QuestionInterface;


    /** @param array<string, RDataType> $i_rDataMap */
    public function decodeRData( ReadBufferInterface $i_buffer, array $i_rDataMap ) : ?RDataInterface;


    public function decodeRDataValue( ReadBufferInterface $i_buffer, RDataType $i_rdt ) : mixed;


    public function decodeResourceRecord( ReadBufferInterface $i_buffer ) : ?ResourceRecordInterface;


}