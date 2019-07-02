<?php
class AMPRequest
{
    public static function setCORSHeader($AMPCacheSubdomain, $optExposeHeaders = array())
    {        
        $unauthorized = 'Unauthorized Request';
        $origin = '';
        $allowedSourceOrigin = 'https://' . $_SERVER['HTTP_HOST'];
        $allowedOrigins = array(
            $allowedSourceOrigin,
            "https://$AMPCacheSubdomain.cdn.ampproject.org",
            "$allowedSourceOrigin.amp.cloudflare.com",
            "https://cdn.ampproject.org"
        );
        $sourceOrigin = urlencode( self::getRequestQuery('__amp_source_origin') );

        if( self::getRequestHeader('AMP-Same-Origin') == 'true' )
        {
            $origin = $sourceOrigin;
        }
        elseif( in_array( self::getRequestHeader('Origin'), $allowedOrigins)
            && $sourceOrigin == $allowedSourceOrigin)
        {
            $origin = self::getRequestHeader('Origin');
        }
        else
        {
            http_response_code(401);
            echo "{'message':'$unauthorized'";
            throw new Exception($unauthorized);
        }

        $origin = urldecode( $origin );
        $sourceOrigin = urldecode( $sourceOrigin );

        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin'
            . join(', ', $optExposeHeaders) );
        header("AMP-Access-Control-Allow-Source-Origin: $sourceOrigin");
    }

    private static function getRequestQuery($query)
    {
        if( isset( $_GET[$query] ) )
            return $_GET[$query];

        return '';
    }

    private static function getRequestHeader($header)
    {
        $requestHeaders
            = function_exists('getallheaders')
            ? getallheaders()
            : self::getAllHeadersPolyfill();

        foreach ($requestHeaders as $key => $value)
            if( self::isInRequestHeaders($header, $key) )
                return $value;                    

        return ''; 
    }

    private static function getAllHeadersPolyfill()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
            if (substr($name, 0, 5) == 'HTTP_')
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            
        return $headers; 
    }

    private static function isInRequestHeaders($searchHeader, $requestHeader)
    {
        return preg_match("/$searchHeader/i", $requestHeader) ? true : false;
    }

    private static function logData($data)
    {
        file_put_contents(__DIR__ . "/data.log", "$data\n", FILE_APPEND | LOCK_EX);
    }
}
?>