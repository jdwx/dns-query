<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\Message;


abstract class AbstractTimedClient extends AbstractClient {


    protected int $uDefaultTimeoutSeconds = 5;

    protected int $uDefaultTimeoutMicroSeconds = 0;

    /** @var array<int, Message> */
    private array $rLookAside = [];


    public function __construct( ?int $i_nuDefaultTimeoutSeconds = null, ?int $i_nuDefaultTimeoutMicroSeconds = null ) {
        if ( is_int( $i_nuDefaultTimeoutSeconds ) ) {
            $this->uDefaultTimeoutSeconds = $i_nuDefaultTimeoutSeconds;
        }
        if ( is_int( $i_nuDefaultTimeoutMicroSeconds ) ) {
            $this->uDefaultTimeoutMicroSeconds = $i_nuDefaultTimeoutMicroSeconds;
        }
    }


    public function receiveResponse( ?int $i_id = null, ?int $i_nuTimeoutSeconds = null,
                                     ?int $i_nuTimeoutMicroSeconds = null ) : ?Message {
        if ( is_int( $i_id ) && isset( $this->rLookAside[ $i_id ] ) ) {
            $msg = $this->rLookAside[ $i_id ];
            unset( $this->rLookAside[ $i_id ] );
            return $msg;
        }
        $fStartTime = microtime( true );
        $fEndTime = $fStartTime
            + ( $i_nuTimeoutSeconds ?? $this->uDefaultTimeoutSeconds )
            + ( ( $i_nuTimeoutMicroSeconds ?? $this->uDefaultTimeoutMicroSeconds ) / 1_000_000.0 );
        while ( true ) {
            $fNow = microtime( true );
            if ( $fNow >= $fEndTime ) {
                return null; // Timeout
            }
            $fRemainingTime = $fEndTime - $fNow;
            $uTimeoutSeconds = (int) $fRemainingTime;
            $uTimeoutMicroSeconds = (int) ( ( $fRemainingTime - $uTimeoutSeconds ) * 1_000_000.0 );
            $msg = $this->receiveAnyResponse( $uTimeoutSeconds, $uTimeoutMicroSeconds );
            if ( is_null( $msg ) ) {
                return null;
            }
            if ( ! is_int( $i_id ) || $msg->id === $i_id ) {
                return $msg;
            }
            $this->rLookAside[ $msg->id ] = $msg;
        }
    }


    abstract protected function receiveAnyResponse( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message;


}
