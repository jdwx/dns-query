<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class RFC1035Decoder implements DecoderInterface {


    public static function decodeRDataHexBinary( ReadBufferInterface $i_buffer ) : string {
        return bin2hex( $i_buffer->consume( null ) );
    }


    public static function decodeRDataOption( ReadBufferInterface $i_buffer ) : Option {
        $uCode = $i_buffer->consumeUINT16();
        $uLength = $i_buffer->consumeUINT16();
        $data = $i_buffer->consume( $uLength );
        return new Option( $uCode, $data );
    }


    /** @return list<Option> */
    public static function decodeRDataOptionList( ReadBufferInterface $i_buffer ) : array {
        $rOut = [];
        while ( ! $i_buffer->atEnd() ) {
            $rOut[] = self::decodeRDataOption( $i_buffer );
        }
        return $rOut;
    }


    public function decodeHeader( ReadBufferInterface $i_buffer ) : HeaderInterface {
        $uId = $i_buffer->consumeUINT16();
        $uFlagWord = $i_buffer->consumeUINT16();
        $qCount = $i_buffer->consumeUINT16();
        $aCount = $i_buffer->consumeUINT16();
        $auCount = $i_buffer->consumeUINT16();
        $adCount = $i_buffer->consumeUINT16();
        return new Header(
            $uId,
            $uFlagWord,
            $qCount,
            $aCount,
            $auCount,
            $adCount
        );
    }


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {

        if ( ! $i_buffer->readyCheck() ) {
            return null;
        }

        $header = self::decodeHeader( $i_buffer );
        $question = [];
        $answer = [];
        $authority = [];
        $additional = [];
        $opt = null;

        for ( $ii = 0 ; $ii < $header->getQDCount() ; ++$ii ) {
            $question[] = self::decodeQuestion( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getANCount() ; ++$ii ) {
            $answer[] = self::decodeResourceRecord( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getNSCount() ; ++$ii ) {
            $authority[] = self::decodeResourceRecord( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getARCount() ; ++$ii ) {
            $rr = self::decodeResourceRecord( $i_buffer );
            if ( RecordType::OPT->is( $rr->typeValue() ) ) {
                if ( $opt !== null ) {
                    throw new RecordException( 'Multiple OPT records found in message.' );
                }
                $opt = $rr;
            }
            $additional[] = $rr;
        }

        // If we found an OPT record, create an EDNSMessage
        if ( $opt instanceof ResourceRecordInterface ) {
            return EDNSMessage::fromOptRecord(
                $header,
                $question,
                $answer,
                $authority,
                $additional,
                $opt
            );
        }

        // Otherwise create a regular Message
        return new Message(
            $header,
            $question,
            $answer,
            $authority,
            $additional
        );
    }


    public function decodeQuestion( ReadBufferInterface $i_buffer ) : QuestionInterface {
        $rName = $i_buffer->consumeNameArray();
        $uType = $i_buffer->consumeUINT16();
        $uClass = $i_buffer->consumeUINT16();
        return new Question( $rName, $uType, $uClass );
    }


    /**
     * @param ReadBufferInterface $i_buffer
     * @param array<string, RDataType> $i_rDataMap
     * @return RDataInterface
     */
    public function decodeRData( ReadBufferInterface $i_buffer, array $i_rDataMap ) : RDataInterface {
        $rData = [];

        /** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
        foreach ( $i_rDataMap as $stName => $rDataType ) {
            $rData[ $stName ] = self::decodeRDataValue( $i_buffer, $rDataType );
        }
        assert( $i_buffer->atEnd(), 'RData did not consume all data.' );
        return new RData( $i_rDataMap, $rData );
    }


    /** @return list<string> */
    public function decodeRDataCharacterStringList( ReadBufferInterface $i_buffer ) : array {
        $rOut = [];
        while ( ! $i_buffer->atEnd() ) {
            $rOut[] = $i_buffer->consumeLabel();
        }
        return $rOut;
    }


    public function decodeRDataValue( ReadBufferInterface $i_buffer, RDataType $i_rdt ) : mixed {
        return match ( $i_rdt ) {
            RDataType::CharacterString => $i_buffer->consumeLabel(),
            RDataType::CharacterStringList => $this->decodeRDataCharacterStringList( $i_buffer ),
            RDataType::DomainName => $i_buffer->consumeNameArray(),
            RDataType::HexBinary => self::decodeRDataHexBinary( $i_buffer ),
            RDataType::IPv4Address => $i_buffer->consumeIPv4(),
            RDataType::IPv6Address => $i_buffer->consumeIPv6(),
            RDataType::Option => self::decodeRDataOption( $i_buffer ),
            RDataType::OptionList => self::decodeRDataOptionList( $i_buffer ),
            RDataType::UINT8 => $i_buffer->consumeUINT8(),
            RDataType::UINT16 => $i_buffer->consumeUINT16(),
            RDataType::UINT32 => $i_buffer->consumeUINT32(),
        };
    }


    public function decodeResourceRecord( ReadBufferInterface $i_buffer ) : ResourceRecordInterface {
        $r = [
            'name' => $i_buffer->consumeName(),
            'type' => $i_buffer->consumeUINT16(),
            'class' => $i_buffer->consumeUINT16(),
            'ttl' => $i_buffer->consumeUINT32(),
        ];
        $uRDLength = $i_buffer->consumeUINT16();
        $uRDOffset = $i_buffer->tell();

        $sub = $i_buffer->consumeSub( $uRDLength );
        $map = RDataMaps::tryMap( $r[ 'type' ] );
        if ( $map === null ) {
            $rData = new OpaqueRData( $sub->consume( null ) );
        } else {
            $rData = $this->decodeRData( $sub, $map );
        }

        assert( $i_buffer->tell() - $uRDOffset === $uRDLength );
        $r[ 'rdata' ] = $rData;

        return ResourceRecord::fromArray( $r );
    }


}
