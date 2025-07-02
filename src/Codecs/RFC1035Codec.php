<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\OpaqueQuestion;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\OptResourceRecord;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use JDWX\DNSQuery\Transport\BufferInterface;


class RFC1035Codec implements CodecInterface {


    public static function decodeHeader( BufferInterface $i_buffer ) : HeaderInterface {
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


    public static function decodeQuestion( BufferInterface $i_buffer ) : QuestionInterface {
        $rName = $i_buffer->consumeNameArray();
        $uType = $i_buffer->consumeUINT16();
        $uClass = $i_buffer->consumeUINT16();
        return new OpaqueQuestion( $rName, $uType, $uClass );
    }


    /**
     * @param array<string, RDataType> $i_rDataMap
     * @param BufferInterface $i_buffer
     * @param int $i_uEndOfRData
     * @return RDataInterface
     */
    public static function decodeRData( array $i_rDataMap, BufferInterface $i_buffer, int $i_uEndOfRData ) : RDataInterface {
        $rData = [];

        /** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
        foreach ( $i_rDataMap as $stName => $rDataType ) {
            $rData[ $stName ] = self::decodeRDataValue( $rDataType, $i_buffer, $i_uEndOfRData );
        }
        $uOffset = $i_buffer->tell();
        assert( $uOffset === $i_uEndOfRData,
            "Offset after reading RData ({$uOffset}) does not equal end of RData ({$i_uEndOfRData})."
        );
        return new RData( $i_rDataMap, $rData );
    }


    /** @return list<string> */
    public static function decodeRDataCharacterStringList( BufferInterface $i_buffer, int $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $i_buffer->tell() < $i_uEndOfRData ) {
            $rOut[] = $i_buffer->consumeLabel();
        }
        return $rOut;
    }


    public static function decodeRDataOption( BufferInterface $i_buffer ) : Option {
        $uCode = $i_buffer->consumeUINT16();
        $uLength = $i_buffer->consumeUINT16();
        $data = $i_buffer->consume( $uLength );
        return new Option( $uCode, $data );
    }


    /** @return list<Option> */
    public static function decodeRDataOptionList( BufferInterface $i_buffer, int $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $i_buffer->tell() < $i_uEndOfRData ) {
            $rOut[] = self::decodeRDataOption( $i_buffer );
        }
        return $rOut;
    }


    public static function decodeRDataValue( RDataType $i_rdt, BufferInterface $i_buffer,
                                             int       $i_uEndOfRData ) : mixed {
        return match ( $i_rdt ) {
            RDataType::CharacterString => $i_buffer->consumeLabel(),
            RDataType::CharacterStringList => self::decodeRDataCharacterStringList( $i_buffer, $i_uEndOfRData ),
            RDataType::DomainName => $i_buffer->consumeNameArray(),
            RDataType::IPv4Address => $i_buffer->consumeIPv4(),
            RDataType::IPv6Address => $i_buffer->consumeIPv6(),
            RDataType::Option => self::decodeRDataOption( $i_buffer ),
            RDataType::OptionList => self::decodeRDataOptionList( $i_buffer, $i_uEndOfRData ),
            RDataType::UINT16 => $i_buffer->consumeUINT16(),
            RDataType::UINT32 => $i_buffer->consumeUINT32(),
        };
    }


    public static function decodeResourceRecord( BufferInterface $i_buffer ) : ResourceRecordInterface {
        $r = [
            'name' => $i_buffer->consumeName(),
            'type' => $i_buffer->consumeUINT16(),
            'class' => $i_buffer->consumeUINT16(),
            'ttl' => $i_buffer->consumeUINT32(),
        ];
        $uRDLength = $i_buffer->consumeUINT16();
        $uRDOffset = $i_buffer->tell();

        $map = RDataMaps::tryMap( $r[ 'type' ] );
        if ( is_array( $map ) ) {
            $rData = self::decodeRData( RDataMaps::map( $r[ 'type' ] ), $i_buffer, $uRDOffset + $uRDLength );
        } else {
            $stData = $i_buffer->consume( $uRDLength );
            $rData = new OpaqueRData( $stData );
        }

        assert( $i_buffer->tell() - $uRDOffset === $uRDLength );
        $r[ 'rdata' ] = $rData;

        if ( RecordType::OPT->is( $r[ 'type' ] ) ) {
            return OptResourceRecord::fromArray( $r );
        }
        return ResourceRecord::fromArray( $r );
    }


    public static function encodeHeader( HeaderInterface $i_header ) : string {
        return Binary::packUINT16( $i_header->id() )
            . Binary::packUINT16( $i_header->flagWord()->value() )
            . Binary::packUINT16( $i_header->getQDCount() )
            . Binary::packUINT16( $i_header->getANCount() )
            . Binary::packUINT16( $i_header->getNSCount() )
            . Binary::packUINT16( $i_header->getARCount() );
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function encodeQuestion( QuestionInterface $i_question, array &$io_rLabelMap, int &$io_uOffset ) : string {
        $stName = Binary::packLabels( $i_question->getName(), $io_rLabelMap, $io_uOffset );
        $stType = Binary::packUINT16( $i_question->typeValue() );
        $stClass = Binary::packUINT16( $i_question->classValue() );
        $st = $stName . $stType . $stClass;
        $io_uOffset += strlen( $st );
        return $st;
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function encodeRData( RDataInterface $i_rData, array &$io_rLabelMap, int &$io_offset ) : string {
        if ( $i_rData instanceof OpaqueRData ) {
            $io_offset += strlen( $i_rData->stData );
            return $i_rData->stData;
        }
        assert( $i_rData instanceof RData );
        $rMap = $i_rData->map();
        $stRData = '';
        foreach ( $rMap as $stName => $rDataType ) {
            if ( ! isset( $i_rData[ $stName ] ) ) {
                throw new RecordException( "Missing RData '{$stName}'" );
            }
            $st = self::encodeRDataValue( $rDataType, $i_rData[ $stName ], $io_rLabelMap, $io_offset );
            $stRData .= $st;
            $io_offset += strlen( $st );
        }

        return $stRData;
    }


    /** @param list<string> $i_rStrings */
    public static function encodeRDataCharacterStringList( array $i_rStrings ) : string {
        $stOut = '';
        foreach ( $i_rStrings as $stString ) {
            $stOut .= Binary::packLabel( $stString );
        }
        return $stOut;
    }


    public static function encodeRDataOption( Option $i_option ) : string {
        $stOut = Binary::packUINT16( $i_option->code );
        $stOut .= Binary::packUINT16( strlen( $i_option->data ) );
        $stOut .= $i_option->data;
        return $stOut;
    }


    /** @param list<Option> $i_rOptions */
    public static function encodeRDataOptionList( array $i_rOptions ) : string {
        $stOut = '';
        foreach ( $i_rOptions as $option ) {
            $stOut .= self::encodeRDataOption( $option );
        }
        return $stOut;
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function encodeRDataValue( RDataType $i_rdt, mixed $i_value, array &$io_rLabelMap,
                                             int       $i_uOffset ) : string {
        return match ( $i_rdt ) {
            RDataType::CharacterString => Binary::packLabel( $i_value ),
            RDataType::CharacterStringList => self::encodeRDataCharacterStringList( $i_value ),
            RDataType::DomainName => Binary::packLabels( $i_value, $io_rLabelMap, $i_uOffset ),
            RDataType::IPv4Address => Binary::packIPv4( $i_value ),
            RDataType::IPv6Address => Binary::packIPv6( $i_value ),
            RDataType::Option => self::encodeRDataOption( $i_value ),
            RDataType::OptionList => self::encodeRDataOptionList( $i_value ),
            RDataType::UINT16 => Binary::packUINT16( $i_value ),
            RDataType::UINT32 => Binary::packUINT32( $i_value ),
        };
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function encodeResourceRecord( ResourceRecordInterface $i_rr, array &$io_rLabelMap,
                                                 int                     &$io_offset ) : string {
        $stOut = Binary::packLabels( $i_rr->getName(), $io_rLabelMap, $io_offset )
            . Binary::packUINT16( $i_rr->typeValue() )
            . Binary::packUINT16( $i_rr->classValue() )
            . Binary::packUINT32( $i_rr->getTTL() );
        $io_offset += strlen( $stOut ) + 2; // +2 for the RDLength that will be added later

        $rData = $i_rr->getRData();
        $stRData = self::encodeRData( $rData, $io_rLabelMap, $io_offset );

        $stOut .= Binary::packUINT16( strlen( $stRData ) );
        $stOut .= $stRData;

        return $stOut;
    }


    public function decode( BufferInterface $i_buffer ) : ?MessageInterface {

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
            if ( $rr instanceof OptResourceRecord ) {
                if ( $opt !== null ) {
                    throw new RecordException( 'Multiple OPT records found in message.' );
                }
                $opt = $rr;
            } else {
                $additional[] = $rr;
            }
        }
        return new Message(
            $header,
            $question,
            $answer,
            $authority,
            $additional,
            $opt
        );
    }


    public function encode( MessageInterface $i_msg ) : string {
        $st = self::encodeHeader( $i_msg->header() );

        $rLabelMap = [];
        $uOffset = strlen( $st );

        foreach ( $i_msg->getQuestion() as $q ) {
            $st .= self::encodeQuestion( $q, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->getAnswer() as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->getAuthority() as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->getAdditional() as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        $opt = $i_msg->opt();
        if ( $opt instanceof ResourceRecordInterface ) {
            $st .= self::encodeResourceRecord( $opt, $rLabelMap, $uOffset );
        }

        return $st;
    }


}
