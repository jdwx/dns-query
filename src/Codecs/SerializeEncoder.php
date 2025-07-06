<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class SerializeEncoder implements EncoderInterface {


    public function encodeHeader( WriteBufferInterface $i_buffer, HeaderInterface $i_hdr ) : void {
        $this->encodeObject( $i_buffer, $i_hdr );
    }


    public function encodeMessage( WriteBufferInterface $i_buffer, MessageInterface $i_msg ) : void {
        $this->encodeObject( $i_buffer, $i_msg );
    }


    public function encodeQuestion( WriteBufferInterface $i_buffer, QuestionInterface $i_question ) : void {
        $this->encodeObject( $i_buffer, $i_question );
    }


    public function encodeRData( WriteBufferInterface $i_buffer, RDataInterface $i_rData ) : void {
        $this->encodeObject( $i_buffer, $i_rData );
    }


    public function encodeRDataValue( WriteBufferInterface $i_buffer, RDataValueInterface $i_rDataValue ) : void {
        $this->encodeObject( $i_buffer, $i_rDataValue );
    }


    public function encodeResourceRecord( WriteBufferInterface $i_buffer, ResourceRecordInterface $i_rr ) : void {
        $this->encodeObject( $i_buffer, $i_rr );
    }


    protected function encodeObject( WriteBufferInterface $i_buffer, object $i_object ) : void {
        $st = serialize( $i_object );
        $i_buffer->append( Binary::packUINT32( strlen( $st ) ), $st );
    }


}
