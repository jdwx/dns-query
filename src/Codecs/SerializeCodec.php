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


    public function decodeHeader( ReadBufferInterface $i_buffer ) : ?HeaderInterface {
        return $this->decodeObject( $i_buffer, HeaderInterface::class );
    }


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {
        return $this->decodeObject( $i_buffer, MessageInterface::class );
    }


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


    protected function decodeObject( ReadBufferInterface $i_buffer, string $i_stClass ) : ?object {
        if ( ! $i_buffer->readyCheck() ) {
            return null;
        }

        $uLength = $i_buffer->consumeUINT32();
        if ( $uLength === 0 ) {
            return null;
        }

        $st = $i_buffer->consume( $uLength );
        $object = unserialize( $st, [ 'allowed_classes' => true ] );

        if ( ! is_a( $object, $i_stClass, true ) ) {
            throw new \UnexpectedValueException( "Decoded object is not of type {$i_stClass}." );
        }

        return $object;
    }


    protected function encodeObject( WriteBufferInterface $i_buffer, object $i_object ) : void {
        $st = serialize( $i_object );
        $i_buffer->append( Binary::packUINT32( strlen( $st ) ), $st );
    }


}
