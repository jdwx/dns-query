<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Legacy\Resolver;


require_once '../vendor/autoload.php';


try {

    # This is the native interface to the resolver, which takes slightly
    # different input format than the compatibility interfaces (specifically,
    # the string "MX" instead of the integer constant DNS_MX to look up a
    # domain's MX record) and returns much more information about the DNS
    # response received.

    # By default, it will learn what nameserver(s) to use from your system's /etc/resolv.conf file.
    $rsv = new Resolver();
    $out = $rsv->query( 'google.com', 'MX' );
    var_dump( $out );

    # A custom resolver can be used.
    $rsv = new Resolver( '1.1.1.1' );
    $out = $rsv->query( 'google.com', 'MX' );
    echo $out, "\n";

    # Or a list of resolvers.
    $rsv = new Resolver([ '1.1.1.1', '1.0.0.1' ]);
    $out = $rsv->query( 'google.com', 'MX' );
    var_dump( $out );

    # You can also specify a custom resolv.conf file if you prefer.  Both the nameservers and the
    # default domain will be used.
    $rsv = new Resolver( i_resolvConf: 'cloudflare-resolv.conf' );
    $out = $rsv->query( 'www' );  # "A" is the default query type.
    var_dump( $out );

} catch ( Exception $ex ) {
    echo $ex->getMessage();
}

