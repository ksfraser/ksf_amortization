<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\TokenExtractor;

class TokenExtractorTest extends TestCase
{
    /**
     * Test extract from Authorization header
     */
    public function testExtractFromAuthorizationHeader(): void
    {
        $headers = ['Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'];
        $token = TokenExtractor::fromAuthorizationHeader($headers);
        $this->assertEquals('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $token);
    }

    /**
     * Test extract from Authorization header is case-insensitive
     */
    public function testExtractFromAuthorizationHeaderCaseInsensitive(): void
    {
        $headers = ['authorization' => 'Bearer token123'];
        $token = TokenExtractor::fromAuthorizationHeader($headers);
        $this->assertEquals('token123', $token);
    }

    /**
     * Test extract fails with missing Authorization header
     */
    public function testExtractFromAuthorizationHeaderMissing(): void
    {
        $token = TokenExtractor::fromAuthorizationHeader([]);
        $this->assertNull($token);
    }

    /**
     * Test extract from query parameter
     */
    public function testExtractFromQueryParameter(): void
    {
        $query = ['access_token' => 'query-token-123'];
        $token = TokenExtractor::fromQueryParameter($query);
        $this->assertEquals('query-token-123', $token);
    }

    /**
     * Test extract from POST body
     */
    public function testExtractFromPostBody(): void
    {
        $body = ['access_token' => 'post-token-456'];
        $token = TokenExtractor::fromPostBody($body);
        $this->assertEquals('post-token-456', $token);
    }

    /**
     * Test extract from cookie
     */
    public function testExtractFromCookie(): void
    {
        $cookies = ['access_token' => 'cookie-token-789'];
        $token = TokenExtractor::fromCookie($cookies);
        $this->assertEquals('cookie-token-789', $token);
    }

    /**
     * Test extract with priority order
     */
    public function testExtractWithPriority(): void
    {
        $headers = ['Authorization' => 'Bearer header-token'];
        $query = ['access_token' => 'query-token'];
        $body = ['access_token' => 'body-token'];
        $cookies = ['access_token' => 'cookie-token'];

        // Header has priority
        $token = TokenExtractor::extract($headers, $query, $body, $cookies);
        $this->assertEquals('header-token', $token);
    }

    /**
     * Test extract prioritizes header over query
     */
    public function testExtractPriorityQueryOverBody(): void
    {
        $query = ['access_token' => 'query-token'];
        $token = TokenExtractor::extract([], $query);
        $this->assertEquals('query-token', $token);
    }

    /**
     * Test isValidFormat with valid token
     */
    public function testIsValidFormatValid(): void
    {
        $this->assertTrue(TokenExtractor::isValidFormat('token123'));
        $this->assertTrue(TokenExtractor::isValidFormat('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4Jf-w_g6OR2j-3NyMLqKjLhP8iWlVMr0Ew'));
    }

    /**
     * Test isValidFormat with invalid token
     */
    public function testIsValidFormatInvalid(): void
    {
        $this->assertFalse(TokenExtractor::isValidFormat(''));
        $this->assertFalse(TokenExtractor::isValidFormat(str_repeat('a', 10001))); // Too long
    }

    /**
     * Test isJwtFormat with JWT token
     */
    public function testIsJwtFormatTrue(): void
    {
        $jwtToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4Jf-w_g6OR2j-3NyMLqKjLhP8iWlVMr0Ew';
        $this->assertTrue(TokenExtractor::isJwtFormat($jwtToken));
    }

    /**
     * Test isJwtFormat with opaque token
     */
    public function testIsJwtFormatFalse(): void
    {
        $this->assertFalse(TokenExtractor::isJwtFormat('opaque-token-123'));
    }

    /**
     * Test sanitize removes whitespace
     */
    public function testSanitizeRemovesWhitespace(): void
    {
        $result = TokenExtractor::sanitize('  token123  ');
        $this->assertEquals('token123', $result);
    }

    /**
     * Test getTokenType extracts Bearer
     */
    public function testGetTokenTypeBearer(): void
    {
        $headers = ['Authorization' => 'Bearer token123'];
        $type = TokenExtractor::getTokenType($headers);
        $this->assertEquals('bearer', $type);
    }

    /**
     * Test getTokenType extracts Basic
     */
    public function testGetTokenTypeBasic(): void
    {
        $headers = ['Authorization' => 'Basic dXNlcjpwYXNz'];
        $type = TokenExtractor::getTokenType($headers);
        $this->assertEquals('basic', $type);
    }

    /**
     * Test hasBearerToken  
     */
    public function testHasBearerToken(): void
    {
        $headers = ['Authorization' => 'Bearer token123'];
        $this->assertTrue(TokenExtractor::hasBearerToken($headers));
    }

    /**
     * Test hasBearerToken returns false for Basic auth
     */
    public function testHasBearerTokenFalse(): void
    {
        $headers = ['Authorization' => 'Basic dXNlcjpwYXNz'];
        $this->assertFalse(TokenExtractor::hasBearerToken($headers));
    }
}
