<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\RR\OPT;
use JDWX\DNSQuery\RR\RR;


class IpTransportCodec implements TransportCodecInterface {


    public function decode( string $i_packet ) : Message {
        $msg = new Message();

        $msg->id = Binary::unpack16BitInt( $i_packet );
        $msg->setFlagWord( Binary::unpack16BitInt( $i_packet, 2 ) );

        $qCount = Binary::unpack16BitInt( $i_packet, 4 );
        $aCount = Binary::unpack16BitInt( $i_packet, 6 );
        $auCount = Binary::unpack16BitInt( $i_packet, 8 );
        $adCount = Binary::unpack16BitInt( $i_packet, 10 );

        $uOffset = 12;

        for ( $ii = 0 ; $ii < $qCount ; ++$ii ) {
            $msg->question[] = Question::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $aCount ; ++$ii ) {
            $msg->answer[] = RR::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $auCount ; ++$ii ) {
            $msg->authority[] = RR::fromBinary( $i_packet, $uOffset );
        }

        for ( $ii = 0 ; $ii < $adCount ; ++$ii ) {
            $rr = RR::fromBinary( $i_packet, $uOffset );
            if ( $rr instanceof OPT ) {
                $msg->opt[] = $rr;
            } else {
                $msg->additional[] = $rr;
            }
        }
        return $msg;
    }


    public function encode( Message $i_msg ) : string {
        $st = Binary::pack16BitInt( $i_msg->id )
            . Binary::pack16BitInt( $i_msg->getFlagWord() )
            . Binary::pack16BitInt( count( $i_msg->question ) )
            . Binary::pack16BitInt( count( $i_msg->answer ) )
            . Binary::pack16BitInt( count( $i_msg->authority ) )
            . Binary::pack16BitInt( count( $i_msg->additional ) + count( $i_msg->opt ) );

        $rLabelMap = [];

        foreach ( $i_msg->question as $q ) {
            $st .= $q->toBinary( $rLabelMap, strlen( $st ) );
        }

        foreach ( $i_msg->answer as $rr ) {
            $st .= $rr->toBinary( $rLabelMap, strlen( $st ) );
        }

        return 'request';
    }


}
