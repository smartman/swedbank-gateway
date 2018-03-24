<?php

return [
    'production_mode'     => false,
    'private_key'         => base_path() . "/certs/itgarage.p12",
    'private_key_pass'    => "itgarage",
    'gateway_certificate' => base_path() . "/certs/sandbox_authentication_v2.cer",
    'agreement_id'        => "5182",
    'gateway_ca'          => base_path() . "/certs/sgwCA.cer",
    'client_cert'         => base_path() . "/certs/itgarage.pem",
    'client_ssl_key'      => base_path() . "/certs/itgarage.key",
    'config_path'         => config_path('jdigidoc.cfg'),

    'payee_name'    => "IT Garage OÜ",
    'payee_account' => "EE372200221066353884",

    //Accepted codes:
    //DEBT (debtor) Not valid for SEPA
    //SHAR (shared)
    //SLEV (service level)
    //If missing or not valid code, will be treated as SHAR
    'charge_bearer' => "SLEV",

];