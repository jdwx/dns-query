<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord;


class RFC1035Codec implements CodecInterface {


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
            $msg->answer[] = $this->decodeResourceRecord( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $auCount ; ++$ii ) {
            $msg->authority[] = $this->decodeResourceRecord( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $adCount ; ++$ii ) {
            $rr = $this->decodeResourceRecord( $i_packet, $uOffset );
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
            $st .= $this->encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->authority as $rr ) {
            $st .= $this->encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->additional as $rr ) {
            $st .= $this->encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        foreach ( $i_msg->opt as $rr ) {
            $st .= $this->encodeResourceRecord( $rr, $rLabelMap, $uOffset );
        }

        return $st;
    }


    protected function decodeRDataValue( RDataType $i_rdt, string $i_packet, int &$io_offset ) : int|string {
        return match ( $i_rdt ) {
            RDataType::DomainName => Binary::consumeName( $i_packet, $io_offset ),
            RDataType::IPv4Address => Binary::consumeIPv4( $i_packet, $io_offset ),
            RDataType::IPv6Address => Binary::consumeIPv6( $i_packet, $io_offset ),
            RDataType::CharacterString => Binary::consumeLabel( $i_packet, $io_offset ),
            RDataType::UINT16 => Binary::consumeUINT16( $i_packet, $io_offset ),
            RDataType::UINT32 => Binary::consumeUINT32( $i_packet, $io_offset ),
            default => throw new RecordException( 'Unhandled RDataType: ' . $i_rdt->name ),
        };
    }


    protected function decodeResourceRecord( string $i_packet, int &$io_offset ) : ResourceRecord {
        $r = [
            'name' => Binary::consumeName( $i_packet, $io_offset ),
            'type' => RecordType::consume( $i_packet, $io_offset ),
            'class' => RecordClass::consume( $i_packet, $io_offset ),
            'ttl' => Binary::consumeUINT32( $i_packet, $io_offset ),
        ];
        $uRDLength = Binary::consumeUINT16( $i_packet, $io_offset );
        $uRDOffset = $io_offset;
        $rData = [];
        $rMap = RDataMaps::map( $r[ 'type' ] );

        foreach ( $rMap as $stName => $rDataType ) {
            if ( $rDataType === RDataType::CharacterStringList ) {
                $rData[ $stName ] = [];
                while ( $io_offset < strlen( $i_packet ) ) {
                    $rData[ $stName ][] = Binary::consumeLabel( $i_packet, $io_offset );
                }
                continue;
            }
            $rData[ $stName ] = $this->decodeRDataValue( $rDataType, $i_packet, $io_offset );
        }
        $r[ 'rdata' ] = $rData;
        assert( $io_offset - $uRDOffset === $uRDLength );

        return ResourceRecord::fromArray( $r );
    }


    /** @param array<string, int> $io_rLabelMap */
    protected function encodeRDataValue( RDataValue $i_rdv, array &$io_rLabelMap, int $i_uOffset ) : string {
        return match ( $i_rdv->type ) {
            RDataType::DomainName => Binary::packName( $i_rdv->value, $io_rLabelMap, $i_uOffset ),
            RDataType::IPv4Address => Binary::packIPv4( $i_rdv->value ),
            RDataType::IPv6Address => Binary::packIPv6( $i_rdv->value ),
            RDataType::CharacterString => Binary::packLabel( $i_rdv->value ),
            RDataType::UINT16 => Binary::packUINT16( 0 ),
            RDataType::UINT32 => Binary::packUINT32( 0 ),
            default => throw new RecordException( 'Unhandled RDataType: ' . $i_rdv->type->name ),
        };
    }


    /** @param array<string, int> $io_rLabelMap */
    protected function encodeResourceRecord( ResourceRecord $i_rr, array &$io_rLabelMap, int &$io_offset ) : string {
        $uType = $i_rr->getType()->value;
        $stOut = Binary::packLabels( $i_rr->getName(), $io_rLabelMap, $io_offset )
            . Binary::packUINT16( $uType )
            . Binary::packUINT16( $i_rr->getClass()->value )
            . Binary::packUINT32( $i_rr->getTTL() );
        $io_offset += strlen( $stOut ) + 2; // +2 for the RDLength that will be added later

        $rMap = RDataMaps::map( $uType );
        $stRData = '';
        foreach ( $rMap as $stName => $rDataType ) {
            if ( ! isset( $i_rr[ $stName ] ) ) {
                throw new RecordException( 'Unknown record type: ' . $i_rr->getType()->value );
            }
            $value = $i_rr->getRDataValueEx( $stName );
            if ( $rDataType === RDataType::CharacterStringList ) {
                $st = '';
                assert( is_array( $value->value ) );
                foreach ( $value->value as $stValue ) {
                    $st = Binary::packLabel( $stValue );
                }
            } else {
                $st = $this->encodeRDataValue( $value, $io_rLabelMap, $io_offset );
            }
            $stRData .= $st;
            $io_offset += strlen( $st );
        }

        $stOut .= Binary::packUINT16( strlen( $stRData ) );
        $stOut .= $stRData;

        return $stOut;
    }


}
