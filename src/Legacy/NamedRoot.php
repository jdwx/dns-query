<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\RR\AAAA;
use JDWX\DNSQuery\Legacy\RR\RR;
use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\NS;


/** This class allows querying a named.root file for IP information about root name servers. */
class NamedRoot {


    /** The name of the named.root file. */
    public const string DEFAULT_NAMED_ROOT_FILE = __DIR__ . '/../data/named.root';


    /** @var array<string, array<string, mixed>> */
    protected array $records = [];


    /** Construct the object, optionally using a custom file.
     * @throws Exception
     */
    public function __construct( ?string $i_namedRootPath = null ) {
        if ( is_null( $i_namedRootPath ) ) {
            $i_namedRootPath = self::DEFAULT_NAMED_ROOT_FILE;
        }
        foreach ( file( $i_namedRootPath ) as $line ) {
            $line = strtolower( trim( $line ) );
            if ( '' === $line ) {
                continue;
            }
            if ( ';' === $line[ 0 ] ) {
                continue;
            }
            $rr = RR::fromString( $line );
            if ( ! array_key_exists( $rr->name, $this->records ) ) {
                $this->records[ $rr->name ] = [];
            }
            if ( ! array_key_exists( $rr->type, $this->records[ $rr->name ] ) ) {
                $this->records[ $rr->name ][ $rr->type ] = [];
            }
            $this->records[ $rr->name ][ $rr->type ][] = $rr;
        }
    }


    /**
     * @return list<string> A list of root name server IP addresses.
     */
    public function listAddresses( bool $i_useIPv4 = true, bool $i_useIPv6 = false ) : array {
        $out = [];
        foreach ( $this->listNameServers() as $nameServer ) {
            if ( $i_useIPv4 ) {
                foreach ( $this->records[ $nameServer ][ 'A' ] as $rr ) {
                    assert( $rr instanceof A );
                    $out[] = $rr->address;
                }
            }
            if ( $i_useIPv6 ) {
                foreach ( $this->records[ $nameServer ][ 'AAAA' ] as $rr ) {
                    assert( $rr instanceof AAAA );
                    $out[] = $rr->address;
                }
            }
        }
        return $out;
    }


    /** Get a list of the root name server names.
     *
     * This should return a.root-servers.net through m.root-servers.net unless
     * the Internet changes quite dramatically.
     *
     * @return string[] The list of name servers.
     */
    public function listNameServers() : array {
        $out = [];
        foreach ( $this->records[ '' ][ 'NS' ] as $record ) {
            assert( $record instanceof NS );
            $out[] = $record->nsdName;
        }
        return $out;
    }


}