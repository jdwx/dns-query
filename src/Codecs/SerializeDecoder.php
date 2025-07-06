<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class SerializeDecoder implements DecoderInterface {


    public function decodeHeader( ReadBufferInterface $i_buffer ) : ?HeaderInterface {
        return $this->decodeObject( $i_buffer, HeaderInterface::class );
    }


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {
        return $this->decodeObject( $i_buffer, MessageInterface::class );
    }


    public function decodeQuestion( ReadBufferInterface $i_buffer ) : ?QuestionInterface {
        return $this->decodeObject( $i_buffer, QuestionInterface::class );
    }


    /** @param array<string, RDataType> $i_rDataMap */
    public function decodeRData( ReadBufferInterface $i_buffer, array $i_rDataMap ) : ?RDataInterface {
        return $this->decodeObject( $i_buffer, RDataInterface::class );
    }


    public function decodeRDataValue( ReadBufferInterface $i_buffer, RDataType $i_rdt ) : ?RDataValueInterface {
        return $this->decodeObject( $i_buffer, RDataValueInterface::class );
    }


    public function decodeResourceRecord( ReadBufferInterface $i_buffer ) : ?ResourceRecordInterface {
        return $this->decodeObject( $i_buffer, ResourceRecordInterface::class );
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


}
