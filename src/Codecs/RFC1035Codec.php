<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\OptRecord;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord;
use JDWX\DNSQuery\ResourceRecordInterface;


class RFC1035Codec implements CodecInterface {


    /**
     * @param array<string, RDataType> $i_rDataMap
     * @return array<string, RDataValue>
     */
    public static function decodeRData( array $i_rDataMap, string $i_packet, int &$io_offset,
                                        int   $i_uEndOfRData ) : array {
        $rData = [];

        /** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
        foreach ( $i_rDataMap as $stName => $rDataType ) {
            $rData[ $stName ] = self::decodeRDataValue( $rDataType, $i_packet, $io_offset, $i_uEndOfRData );
        }
        assert( $io_offset === $i_uEndOfRData,
            "Offset after reading RData ({$io_offset}) does not equal end of RData ({$i_uEndOfRData})."
        );
        return $rData;
    }


    /** @return list<string> */
    public static function decodeRDataCharacterStringList( string $i_stPacket, int &$io_uOffset,
                                                           int    $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $io_uOffset < $i_uEndOfRData ) {
            $rOut[] = Binary::consumeLabel( $i_stPacket, $io_uOffset );
        }
        return $rOut;
    }


    public static function decodeRDataOption( string $i_stOption, int &$io_uOffset ) : Option {
        $uCode = Binary::consumeUINT16( $i_stOption, $io_uOffset );
        $uLength = Binary::consumeUINT16( $i_stOption, $io_uOffset );
        $data = Binary::consume( $i_stOption, $io_uOffset, $uLength );
        return new Option( $uCode, $data );
    }


    /** @return list<Option> */
    public static function decodeRDataOptionList( string $i_stList, int &$io_uOffset,
                                                  int    $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $io_uOffset < $i_uEndOfRData ) {
            $rOut[] = self::decodeRDataOption( $i_stList, $io_uOffset );
        }
        return $rOut;
    }


    public static function decodeRDataValue( RDataType $i_rdt, string $i_packet, int &$io_offset,
                                             int       $i_uEndOfRData ) : RDataValue {
        $data = match ( $i_rdt ) {
            RDataType::CharacterString => Binary::consumeLabel( $i_packet, $io_offset ),
            RDataType::CharacterStringList => self::decodeRDataCharacterStringList( $i_packet, $io_offset, $i_uEndOfRData ),
            RDataType::DomainName => Binary::consumeNameArray( $i_packet, $io_offset ),
            RDataType::IPv4Address => Binary::consumeIPv4( $i_packet, $io_offset ),
            RDataType::IPv6Address => Binary::consumeIPv6( $i_packet, $io_offset ),
            RDataType::Option => self::decodeRDataOption( $i_packet, $io_offset ),
            RDataType::OptionList => self::decodeRDataOptionList( $i_packet, $io_offset, $i_uEndOfRData ),
            RDataType::UINT16 => Binary::consumeUINT16( $i_packet, $io_offset ),
            RDataType::UINT32 => Binary::consumeUINT32( $i_packet, $io_offset ),
        };
        return new RDataValue( $i_rdt, $data );
    }


    public static function decodeResourceRecord( string $i_packet, int &$io_offset ) : ResourceRecordInterface {
        $r = [
            'name' => Binary::consumeName( $i_packet, $io_offset ),
            'type' => RecordType::consume( $i_packet, $io_offset ),
            'class' => Binary::consumeUINT16( $i_packet, $io_offset ),
            'ttl' => Binary::consumeUINT32( $i_packet, $io_offset ),
        ];
        $uRDLength = Binary::consumeUINT16( $i_packet, $io_offset );
        $uRDOffset = $io_offset;
        $rData = self::decodeRData( RDataMaps::map( $r[ 'type' ] ), $i_packet, $io_offset, $io_offset + $uRDLength );
        assert( $io_offset - $uRDOffset === $uRDLength );
        $r[ 'rdata' ] = $rData;

        if ( $r[ 'type' ] === RecordType::OPT ) {
            return OptRecord::fromArray( $r );
        }
        return ResourceRecord::fromArray( $r );
    }


    /**
     * @param array<string, RDataType> $i_rDataMap
     * @param array<string, int> $io_rLabelMap
     * @param array<string, RDataValue> $i_rData
     */
    public static function encodeRData( array $i_rDataMap, array &$io_rLabelMap, int &$io_offset,
                                        array $i_rData ) : string {
        $stRData = '';
        foreach ( $i_rDataMap as $stName => $rDataType ) {
            if ( ! isset( $i_rData[ $stName ] ) ) {
                throw new RecordException( "Missing RData '{$stName}'" );
            }
            $st = self::encodeRDataValue( $i_rData[ $stName ], $io_rLabelMap, $io_offset );
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
    public static function encodeRDataValue( RDataValue $i_rdv, array &$io_rLabelMap, int $i_uOffset ) : string {
        return match ( $i_rdv->type ) {
            RDataType::CharacterString => Binary::packLabel( $i_rdv->value ),
            RDataType::CharacterStringList => self::encodeRDataCharacterStringList( $i_rdv->value ),
            RDataType::DomainName => Binary::packLabels( $i_rdv->value, $io_rLabelMap, $i_uOffset ),
            RDataType::IPv4Address => Binary::packIPv4( $i_rdv->value ),
            RDataType::IPv6Address => Binary::packIPv6( $i_rdv->value ),
            RDataType::Option => self::encodeRDataOption( $i_rdv->value ),
            RDataType::OptionList => self::encodeRDataOptionList( $i_rdv->value ),
            RDataType::UINT16 => Binary::packUINT16( $i_rdv->value ),
            RDataType::UINT32 => Binary::packUINT32( $i_rdv->value ),
        };
    }


    /** @param array<string, int> $io_rLabelMap */
    public static function encodeResourceRecord( ResourceRecordInterface $i_rr, array &$io_rLabelMap,
                                                 int                     &$io_offset ) : string {
        $uType = $i_rr->getType()->value;
        $stOut = Binary::packLabels( $i_rr->getName(), $io_rLabelMap, $io_offset )
            . Binary::packUINT16( $uType )
            . Binary::packUINT16( $i_rr->classValue() )
            . Binary::packUINT32( $i_rr->getTTL() );
        $io_offset += strlen( $stOut ) + 2; // +2 for the RDLength that will be added later

        $rMap = RDataMaps::map( $uType );
        $stRData = self::encodeRData( $rMap, $io_rLabelMap, $io_offset, $i_rr->getRData() );
        $stOut .= Binary::packUINT16( strlen( $stRData ) );
        $stOut .= $stRData;

        return $stOut;
    }


    public function decode( string $i_packet ) : Message {
        $msg = new Message();

        $uOffset = 0;
        $msg->id = Binary::consumeUINT16( $i_packet, $uOffset );
        $msg->setFlagWord( Binary::consumeUINT16( $i_packet, $uOffset ) );

        $qCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $aCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $auCount = Binary::consumeUINT16( $i_packet, $uOffset );
        $adCount = Binary::consumeUINT16( $i_packet, $uOffset );

        for ( $ii = 0 ; $ii < $qCount ; ++$ii ) {
            $msg->question[] = Question::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $aCount ; ++$ii ) {
            $msg->answer[] = self::decodeResourceRecord( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $auCount ; ++$ii ) {
            $msg->authority[] = self::decodeResourceRecord( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $adCount ; ++$ii ) {
            $rr = self::decodeResourceRecord( $i_packet, $uOffset );
            if ( $rr->isType( 'OPT' ) ) {
                $msg->opt[] = $rr;
            } else {
                $msg->additional[] = $rr;
            }
        }
        return $msg;
    }


    public function encode( Message $i_msg ) : string {
        $st = Binary::packUINT16( $i_msg->id )
            . Binary::packUINT16( $i_msg->getFlagWord() )
            . Binary::packUINT16( count( $i_msg->question ) )
            . Binary::packUINT16( count( $i_msg->answer ) )
            . Binary::packUINT16( count( $i_msg->authority ) )
            . Binary::packUINT16( count( $i_msg->additional ) + count( $i_msg->opt ) );

        $rLabelMap = [];
        $uOffset = strlen( $st );

        foreach ( $i_msg->question as $q ) {
            $stQ = $q->toBinary( $rLabelMap, strlen( $st ) );
            $st .= $stQ;
            $uOffset += strlen( $stQ );
        }

        foreach ( $i_msg->answer as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->authority as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->additional as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->opt as $rr ) {
            $st .= self::encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        return $st;
    }


}
