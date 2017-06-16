<?php

/*
********************************************************************************
* CONFIG.PHP                                                                   *
********************************************************************************
*/

namespace models;

use exception;
use Lcobucci\JWT\Parser; 
use Lcobucci\JWT\Builder; 
use Lcobucci\JWT\Signer\Hmac\Sha256;

class jwt
{
    const DEBUG_PHRASE = FALSE;
    
    // Default token lasts a half hour
    const DEFAULT_DURATION = 1800;

    public $now;
    public $secret;
    public $signer;

    //**************************************************************************
    
    static function init($secret=FALSE)
    {
        $self = new static();
        $self->now = time();
        // Default secret to app url path
        $secretDef = $secret ? $secret : config::get('site', 'uri');
        $self->secret = self::DEBUG_PHRASE ? self::DEBUG_PHRASE : $secretDef;
        $self->signer = new Sha256();
        return $self;
    }

    //**************************************************************************

    function token($params)
    {
        $builder = new Builder();
        
        $duration = getDefault($params['duration'], self::DEFAULT_DURATION);

        // Configures the issuer (iss claim)
        return $builder->setIssuer(NULL)
            // Configures the time that the token was issue (iat claim)
            ->setIssuedAt($this->now) 
            // Configures the expiration time of the token (nbf claim)
            ->setExpiration($this->now + $duration) 
            // Configures a new claims
            ->set('jti', $params['password'])
            ->set('name', $params['fullName'])
            // creates a signature using second param as key
            ->sign($this->signer, $this->secret) 
            // Retrieves the generated token        
            ->getToken()
            ->__toString();
    }

    //**************************************************************************

    static function noAuth()
    {
        return [
            'name' => 'Unauthorized',
            'message' => 'Invalid token',
            'code' => 0,
            'status' => 401,
        ];
    }

    //**************************************************************************

    static function headerToken()
    {
        $headers = apache_request_headers();
        $auth = getDefault($headers['Authorization']);
        if (! $auth) {
            return FALSE;
        }

        $explode = explode(' ', $auth);
        return [
            'string' => end($explode),
            'original' => $auth,
            'header' => ['Authorization: '.$auth],
        ];
    }

    //**************************************************************************

    function validityResponse()
    {
        $token = self::headerToken();
        if (! $token['string']) {
            return self::noAuth();
        }

        $parser = new Parser();
        
        // Parses from a string
        try {
            $tokenObj = $parser->parse((string) $token['string']);
            $valid = $tokenObj->verify($this->signer, $this->secret);
        } catch (exception $e) {
            return self::noAuth();
        }
        
        if (! $tokenObj->hasClaim('exp') 
        ||  $tokenObj->getClaim('exp') < $this->now
        ) {
            return [
                'name' => 'Unauthorized',
                'message' => 'Token Has Expire',
                'code' => 403,
                'status' => 401,
            ];
        }

        if ($valid) {
            return [
                'data' => [
                    'name' => $tokenObj->getClaim('name'),
                    'user_pwd' => $tokenObj->getClaim('jti'),
                ]
            ];
        } 
        
        return self::noAuth();
    }

    //**************************************************************************

    static function noValid()
    {
        return [
            'name' => 'Unauthorized',
            'message' => 'Invalid Username or Password',
            'code' => 403,
            'status' => 401,
        ];
    }
}


