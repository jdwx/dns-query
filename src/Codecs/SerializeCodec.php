<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class SerializeCodec implements CodecInterface {


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {
        if ( ! $i_buffer->readyCheck() ) {
            return null;
        }

        $uLength = $i_buffer->consumeUINT32();
        $st = $i_buffer->consume( $uLength );
        return unserialize( $st, [ 'allowed_classes' => true ] );
    }


    public function encodeHeader( WriteBufferInterface $i_buffer, HeaderInterface $i_hdr ) : void {
        $i_buffer->append( serialize( $i_hdr ) );
    }


    public function encodeMessage( WriteBufferInterface $i_buffer, MessageInterface $i_msg ) : void {
        $st = serialize( $i_msg );
        $i_buffer->append( Binary::packUINT32( strlen( $st ) ), $st );
    }


    public function encodeQuestion( WriteBufferInterface $i_buffer, QuestionInterface $i_question ) : void {
        $i_buffer->append( serialize( $i_question ) );
    }


    public function encodeRData( WriteBufferInterface $i_buffer, RDataInterface $i_rData ) : void {
        $i_buffer->append( serialize( $i_rData ) );
    }


    public function encodeRDataValue( WriteBufferInterface $i_buffer, RDataValueInterface $i_rDataValue ) : void {
        $i_buffer->append( serialize( $i_rDataValue ) );
    }


    public function encodeResourceRecord( WriteBufferInterface $i_buffer, ResourceRecordInterface $i_rr ) : void {
        $i_buffer->append( serialize( $i_rr ) );
    }


}
