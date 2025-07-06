<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


interface EncoderInterface {


    public function encodeHeader( WriteBufferInterface $i_buffer, HeaderInterface $i_hdr ) : void;


    public function encodeMessage( WriteBufferInterface $i_buffer, MessageInterface $i_msg ) : void;


    public function encodeQuestion( WriteBufferInterface $i_buffer, QuestionInterface $i_question ) : void;


    public function encodeRData( WriteBufferInterface $i_buffer, RDataInterface $i_rData ) : void;


    public function encodeRDataValue( WriteBufferInterface $i_buffer, RDataValueInterface $i_rDataValue ) : void;


    public function encodeResourceRecord( WriteBufferInterface $i_buffer, ResourceRecordInterface $i_rr ) : void;


}