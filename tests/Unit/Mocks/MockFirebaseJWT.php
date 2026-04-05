<?php
namespace Tests\Unit\Mocks;

/**
 * Mock for Firebase\JWT\JWT
 * 
 * This is a temporary mock that simulates firebase/php-jwt functionality
 * for testing until the package can be properly installed
 */
class MockFirebaseJWT
{
    private static $algorithm = 'HS256';
    
    public static function encode(array $claims, $key, string $algorithm = 'HS256'): string
    {
        self::$algorithm = $algorithm;
        
        // Create JWT manually for testing
        $header = json_encode(['typ' => 'JWT', 'alg' => $algorithm]);
        $payload = json_encode($claims);
        
        $base64Header = self::base64urlEncode($header);
        $base64Payload = self::base64urlEncode($payload);
        
        $signatureinput = $base64Header . '.' . $base64Payload;
        $signature = self::sign($signatureinput, $key, $algorithm);
        
        return $signatureinput . '.' . $signature;
    }
    
    public static function decode(string $token, $key, array $allowedAlgorithms = ['HS256']): object
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Firebase\JWT\BeforeValidException('Invalid token format');
        }
        
        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        
        $payload = json_decode(self::base64urlDecode($encodedPayload), true);
        
        if (!is_array($payload)) {
            throw new \Firebase\JWT\BeforeValidException('Invalid payload');
        }
        
        return (object) $payload;
    }
    
    private static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64urlDecode(string $data): string
    {
        $data .= str_repeat('=', 4 - (strlen($data) % 4));
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    private static function sign(string $message, $key, string $algorithm): string
    {
        $signature = hash_hmac('sha256', $message, $key, true);
        return self::base64urlEncode($signature);
    }
}
