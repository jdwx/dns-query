<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Legacy\Resolver;


require_once '../vendor/autoload.php';


try {

    # This is similar to the basic interface, but explicit instantiates the resolver object.
    # It's suitable for repeated queries.

    # By default, it will learn what nameserver(s) to use from your system's /etc/resolv.conf file.
    $rsv = new Resolver();
    $out = $rsv->compatQuery( 'google.com', DNS_MX );
    var_dump( $out );

    # A custom resolver can be used.
    $rsv = new Resolver( '1.1.1.1' );
    $out = $rsv->compatQuery( 'google.com', DNS_MX );
    var_dump( $out );

    # Or a list of resolvers.
    $rsv = new Resolver([ '1.1.1.1', '1.0.0.1' ]);
    $out = $rsv->compatQuery( 'google.com', DNS_MX );
    var_dump( $out );

    # You can also specify a custom resolv.conf file if you prefer.  Both the nameservers and the
    # default domain will be used.
    $rsv = new Resolver( i_resolvConf: 'cloudflare-resolv.conf' );
    $out = $rsv->compatQuery( 'www', DNS_A );
    var_dump( $out );

} catch ( Exception $ex ) {
    echo $ex->getMessage();
}

