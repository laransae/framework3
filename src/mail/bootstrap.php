<?php

use Laravel\IoC;
use Laravel\Config;
use Laravel\Mail\Mailer;

// Register a mailer in the IoC container
IoC::singleton( 'mailer', function() {
        $swiftMailer = IoC::resolve( 'swift.mailer' );
        $mailer = new Mailer( $swiftMailer );

        $from = Config::get( 'mail.from' );

        // If a "from" address is set, we will set it on the mailer so that all mail
        // messages sent by the applications will utilize the same "from" address
        // on each one, which makes the developer's life a lot more convenient.
        if ( is_array( $from ) and isset( $from['address'] ) ) {
            $mailer->alwaysFrom( $from['address'], $from['name'] );
        }

        // toggle email debugging mode
        $pretend = Config::get( 'mail.pretend', false );
        $mailer->pretend( $pretend );

        return $mailer;
    } );


// Register a swift mailer in the IoC container
IoC::register( 'swift.mailer', function() {
        $transport = IoC::resolve( 'swift.mailer.transport' );

        return Swift_Mailer::newInstance( $transport );
    } );

// Register a transporter in the IoC container
IoC::register( 'swift.mailer.transport', function() {
        extract( Config::get( 'mail' ) );
        // $host = Config::get('mail.host');
        // $port = Config::get('mail.port');
        // $encryption = Config::get('mail.encryption');
        // $username = Config::get('mail.username');
        // $password = Config::get('mail.password');

        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SMS, or a custom server
        // a developer has available. We will just pass this configured host.
        $transport = Swift_SmtpTransport::newInstance( $host, $port );

        if ( isset( $encryption ) ) {
            $transport->setEncryption( $encryption );
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
        if ( isset( $username ) ) {
            $transport->setUsername( $username );

            $transport->setPassword( $password );
        }

        return $transport;
    } );
