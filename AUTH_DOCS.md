# CuraSync Authentication Guide for Kotlin / Spring Boot Services

This document is the integration contract for any non-Laravel backend (starting
with the planned Kotlin/Spring Boot service) that needs to authenticate users.

The Laravel API is the **token issuer**. Other services are **OAuth2 resource
servers**: they only verify the access token. They never create users, issue
tokens, or refresh tokens.

See `AUTH_MIGRATION.md` for the Laravel-side implementation plan.

## 1. Trust model

```text
Frontend / mobile
      |
      | 1. login / register / refresh
      v
Laravel auth API  --- issues RS256 JWT access token + opaque refresh token
      |
      | 2. API call with  Authorization: Bearer <access_token>
      v
Kotlin Spring Boot service  --- verifies JWT using Laravel's public key/JWKS
```

- Laravel holds the **private key**. Never share it with the Kotlin service.
- The Kotlin service holds only the **public key** via JWKS.
- Access tokens are self-contained; no call back to Laravel is required to
  validate a token.
- Refresh tokens are opaque and are handled by Laravel only. The frontend calls
  Laravel to refresh; Spring Boot never sees a refresh token.

## 2. Token contract

Access token algorithm: **RS256**.

JWT header:

```json
{
  "alg": "RS256",
  "typ": "at+jwt",
  "kid": "<key-id>"
}
```

JWT payload:

```json
{
  "iss": "https://api.curasync.example",
  "aud": "curasync-api",
  "sub": "42",
  "jti": "2a6f0f7e-...",
  "token_use": "access",
  "role": "doctor",
  "ver": 1,
  "iat": 1762500000,
  "nbf": 1762500000,
  "exp": 1762500900
}
```

| Claim | Type | Meaning |
| --- | --- | --- |
| `iss` | string | Issuer URL. Must exactly match the configured issuer (no trailing slash). |
| `aud` | string | Audience. Must be `curasync-api` (or the deployed value). |
| `sub` | string | Laravel user ID. Parse to `Long` when needed. |
| `jti` | string | Unique token ID. Useful for tracing/blacklisting. |
| `token_use` | string | Always `access`. Reject anything else. |
| `role` | string | `patient`, `doctor`, or `admin`. |
| `ver` | number | Matches `users.token_version`. Laravel rejects stale values. |
| `iat` / `nbf` / `exp` | number | Unix timestamps. Access tokens live ~15 minutes. |

Do not introduce new required claims without updating this document and the
Laravel issuer.

## 3. Laravel auth endpoints

Base URL example: `https://api.curasync.example/api/v1`.

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| `POST` | `/auth/login` | none | Email/password login |
| `POST` | `/auth/register/patient` | none | Patient registration |
| `POST` | `/auth/register/doctor` | none | Doctor registration |
| `POST` | `/auth/refresh` | refresh token body | Rotate refresh token, issue new pair |
| `POST` | `/auth/logout` | Bearer access token | Revoke the supplied refresh token |
| `GET` | `/me` | Bearer access token | Current user profile |
| `POST` | `/auth/google` | none | Future Google sign-in (not implemented yet) |

Login request:

```json
{
  "email": "jane@example.com",
  "password": "secret-password"
}
```

Successful login/registration response (same envelope as the rest of the API):

```json
{
  "data": {
    "user": {
      "id": 42,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "phone_number": null,
      "profile_url": null
    },
    "role": "patient",
    "profile": null,
    "access_token": "<jwt>",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_token": "<opaque-token>"
  },
  "errors": [],
  "message": "",
  "status": 200
}
```

Refresh request:

```json
{
  "refresh_token": "<opaque-token>"
}
```

Refresh response: same token fields as login (`access_token`, `token_type`,
`expires_in`, `refresh_token`). The old refresh token is invalid after a
successful rotation.

Logout request:

```json
{
  "refresh_token": "<opaque-token>"
}
```

Send the access token as `Authorization: Bearer <access_token>`.

Unauthenticated response:

```json
{
  "data": [],
  "errors": { "auth": ["Unauthenticated."] },
  "message": "Unauthenticated.",
  "status": 401
}
```

## 4. Public key discovery

Laravel exposes:

- `GET /.well-known/jwks.json` - public signing keys.
- `GET /.well-known/openid-configuration` - optional OIDC discovery document, if
  enabled.

JWKS shape:

```json
{
  "keys": [
    {
      "kty": "RSA",
      "use": "sig",
      "alg": "RS256",
      "kid": "<key-id>",
      "n": "<base64url modulus>",
      "e": "AQAB"
    }
  ]
}
```

Spring caches the JWKS and refreshes it when it sees an unknown `kid`. Always
keep the `kid` header in the token.

## 5. Spring Boot setup

Tested configuration target: Spring Boot 3.x, Kotlin, Java 17+.

### 5.1 Dependencies

```kotlin
dependencies {
    implementation("org.springframework.boot:spring-boot-starter-web")
    implementation("org.springframework.boot:spring-boot-starter-security")
    implementation("org.springframework.boot:spring-boot-starter-oauth2-resource-server")

    testImplementation("org.springframework.boot:spring-boot-starter-test")
    testImplementation("org.springframework.security:spring-security-test")
}
```

`spring-boot-starter-oauth2-resource-server` brings in
`spring-security-oauth2-jose`, which includes Nimbus JOSE + JWT.

### 5.2 Configuration

`application.yml`:

```yaml
server:
  port: 8081

auth:
  issuer: ${AUTH_ISSUER:http://localhost:8000}
  audience: ${AUTH_AUDIENCE:curasync-api}
  jwks-uri: ${AUTH_JWKS_URI:http://localhost:8000/.well-known/jwks.json}
```

`JWT_ISSUER` on the Laravel side must match `AUTH_ISSUER` exactly. Do not add a
trailing slash.

### 5.3 Security configuration (Kotlin)

```kotlin
package com.curasync.auth

import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.http.SessionCreationPolicy
import org.springframework.security.oauth2.core.DelegatingOAuth2TokenValidator
import org.springframework.security.oauth2.core.OAuth2Error
import org.springframework.security.oauth2.core.OAuth2TokenValidator
import org.springframework.security.oauth2.core.OAuth2TokenValidatorResult
import org.springframework.security.oauth2.jose.jws.SignatureAlgorithm
import org.springframework.security.oauth2.jwt.Jwt
import org.springframework.security.oauth2.jwt.JwtDecoder
import org.springframework.security.oauth2.jwt.JwtValidators
import org.springframework.security.oauth2.jwt.NimbusJwtDecoder
import org.springframework.security.oauth2.server.resource.authentication.JwtAuthenticationConverter
import org.springframework.security.oauth2.server.resource.authentication.JwtGrantedAuthoritiesConverter
import org.springframework.security.web.SecurityFilterChain

@Configuration
@EnableMethodSecurity
class SecurityConfig(
    @Value("\${auth.issuer}") private val issuer: String,
    @Value("\${auth.audience}") private val audience: String,
    @Value("\${auth.jwks-uri}") private val jwksUri: String,
) {

    @Bean
    fun securityFilterChain(http: HttpSecurity): SecurityFilterChain {
        http
            .csrf { it.disable() }
            .sessionManagement { it.sessionCreationPolicy(SessionCreationPolicy.STATELESS) }
            .authorizeHttpRequests { auth ->
                auth.requestMatchers("/actuator/health", "/public/**").permitAll()
                auth.anyRequest().authenticated()
            }
            .oauth2ResourceServer { oauth ->
                oauth.jwt { jwt ->
                    jwt.jwtAuthenticationConverter(jwtAuthenticationConverter())
                }
            }

        return http.build()
    }

    @Bean
    fun jwtDecoder(): JwtDecoder {
        val decoder = NimbusJwtDecoder
            .withJwkSetUri(jwksUri)
            .jwsAlgorithm(SignatureAlgorithm.RS256)
            .build()

        decoder.setJwtValidator(
            JwtValidators.createDefaultWithIssuer(issuer)
                .and(audienceValidator(audience))
                .and(accessTokenValidator())
        )

        return decoder
    }

    private fun audienceValidator(audience: String) = OAuth2TokenValidator<Jwt> { jwt ->
        if (jwt.audience.contains(audience)) {
            OAuth2TokenValidatorResult.success()
        } else {
            OAuth2TokenValidatorResult.failure(
                OAuth2Error("invalid_token", "Required audience '$audience' is missing", null)
            )
        }
    }

    private fun accessTokenValidator() = OAuth2TokenValidator<Jwt> { jwt ->
        if (jwt.getClaimAsString("token_use") == "access") {
            OAuth2TokenValidatorResult.success()
        } else {
            OAuth2TokenValidatorResult.failure(
                OAuth2Error("invalid_token", "Token is not an access token", null)
            )
        }
    }

    private fun jwtAuthenticationConverter(): JwtAuthenticationConverter {
        val authorities = JwtGrantedAuthoritiesConverter().apply {
            setAuthoritiesClaimName("role")
            setAuthorityPrefix("ROLE_")
        }

        return JwtAuthenticationConverter().apply {
            setJwtGrantedAuthoritiesConverter(authorities)
        }
    }
}
```

Notes:

- `JwtValidators.createDefaultWithIssuer(issuer)` validates `iss`, `exp`, and
  `nbf` (with the default 60-second clock skew).
- The audience validator is required; Spring does not validate `aud` by default.
- The `token_use` validator rejects anything that is not an access token.
- `.jwsAlgorithm(SignatureAlgorithm.RS256)` prevents algorithm confusion.
- Do not use `issuer-uri` alone unless Laravel also exposes a valid
  `/.well-known/openid-configuration`; otherwise use `jwk-set-uri` as above.
- If you prefer Boot auto-configuration, set
  `spring.security.oauth2.resourceserver.jwt.jwk-set-uri` and
  `spring.security.oauth2.resourceserver.jwt.audiences`, but keep explicit issuer
  validation in a custom decoder if you need it.

### 5.4 Using the authenticated user

`role` becomes `ROLE_PATIENT`, `ROLE_DOCTOR`, or `ROLE_ADMIN`.

```kotlin
package com.curasync.appointments

import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.oauth2.server.resource.authentication.JwtAuthenticationToken
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

@RestController
@RequestMapping("/api/appointments")
class AppointmentController {

    @GetMapping("/me")
    fun me(authentication: JwtAuthenticationToken): Map<String, Any?> {
        val jwt = authentication.token
        return mapOf(
            "userId" to jwt.subject.toLong(),
            "role" to jwt.getClaimAsString("role"),
            "jti" to jwt.id,
        )
    }

    @PreAuthorize("hasRole('DOCTOR')")
    @GetMapping("/doctor-only")
    fun doctorOnly(): String = "ok"

    @PreAuthorize("hasAnyRole('PATIENT', 'DOCTOR')")
    @GetMapping("/shared")
    fun shared(): String = "ok"
}
```

Equivalently, use `@AuthenticationPrincipal jwt: Jwt` if you only need claims.

### 5.5 Matching the Laravel error envelope (optional)

Spring returns its own 401/403 body by default. If clients need the same
`{data, errors, message, status}` shape everywhere, add:

```kotlin
package com.curasync.auth

import com.fasterxml.jackson.databind.ObjectMapper
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.security.core.AuthenticationException
import org.springframework.security.web.AuthenticationEntryPoint
import org.springframework.stereotype.Component

@Component
class JsonAuthenticationEntryPoint(
    private val objectMapper: ObjectMapper,
) : AuthenticationEntryPoint {
    override fun commence(
        request: HttpServletRequest,
        response: HttpServletResponse,
        authException: AuthenticationException,
    ) {
        response.status = HttpServletResponse.SC_UNAUTHORIZED
        response.contentType = "application/json"
        objectMapper.writeValue(
            response.writer,
            mapOf(
                "data" to emptyList<Any>(),
                "errors" to mapOf("auth" to listOf("Unauthenticated.")),
                "message" to "Unauthenticated.",
                "status" to 401,
            ),
        )
    }
}
```

Wire it with:

```kotlin
.exceptionHandling { ex ->
    ex.authenticationEntryPoint(jsonAuthenticationEntryPoint)
}
```

Add an equivalent `AccessDeniedHandler` for 403 if needed.

## 6. Token lifecycle

1. The frontend calls Laravel `POST /api/v1/auth/login` (or a register endpoint)
   and stores `access_token` and `refresh_token` securely.
2. The frontend sends `Authorization: Bearer <access_token>` to Laravel and to
   the Kotlin service.
3. The Kotlin service validates the token locally. It does not call Laravel.
4. When the access token expires, the frontend calls Laravel
   `POST /api/v1/auth/refresh` with the refresh token and retries the request.
5. On logout, the frontend calls Laravel `POST /api/v1/auth/logout`.

The Kotlin service never refreshes tokens. If it receives an expired token, it
returns 401; the frontend handles the refresh.

## 7. Key rotation

1. Generate a new RSA keypair in Laravel.
2. Publish both public keys in JWKS, each with a distinct `kid`.
3. Start signing new access tokens with the new `kid`.
4. Wait at least the access token TTL plus clock skew.
5. Remove the old public key from JWKS.

Spring's remote JWKS cache refreshes when it encounters an unknown `kid`, so no
service restart or config change is needed.

## 8. Local development

Laravel:

```bash
php artisan serve
# issuer:    http://localhost:8000
# JWKS:      http://localhost:8000/.well-known/jwks.json
# audience:  curasync-api
```

Kotlin service:

```bash
AUTH_ISSUER=http://localhost:8000 \
AUTH_AUDIENCE=curasync-api \
AUTH_JWKS_URI=http://localhost:8000/.well-known/jwks.json \
./gradlew bootRun
```

Use plain HTTP only locally. Production must use HTTPS.

## 9. Testing

### 9.1 Controller tests without real tokens

```kotlin
import org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.jwt
import org.springframework.test.web.servlet.get

@Test
fun `doctor can access doctor-only endpoint`() {
    mockMvc.get("/api/appointments/doctor-only") {
        with(jwt().jwt { it.subject("42").claim("role", "doctor") })
    }.andExpect {
        status { isOk() }
    }
}
```

### 9.2 Contract test with a real Laravel-issued token

Keep this test in CI to catch claim/issuer/audience drift:

1. Generate a token from Laravel (or a fixture signed with a test key).
2. Make a request to a protected endpoint with that token.
3. Assert 200 for a valid token and 401 for a token signed with the wrong key.

If you use a test keypair, the public key must be served by the test JWKS
endpoint or configured directly in the decoder.

## 10. Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| 401 `Signed JWT rejected: no matching key(s)` | `kid` missing/mismatched, stale JWKS, or wrong key published |
| 401 `Jwt expired` | Access token expired; refresh it |
| 401 `Jwt issuer is not trusted` | `iss` mismatch; check trailing slashes and `JWT_ISSUER` |
| 401 `Required audience is missing` | `aud` mismatch; check `JWT_AUDIENCE` / `AUTH_AUDIENCE` |
| 401 `Token is not an access token` | `token_use` claim missing or not `access` |
| 403 with a valid token | Role authority mismatch; check `ROLE_` prefix and `hasRole('DOCTOR')` |
| 401 `Another algorithm expected` | Token is not RS256, or decoder algorithm config is wrong |
| Intermittent 401 right after deployment | Clock skew or old public key removed too early |
| `sub` parse error | `sub` is a string; use `jwt.subject.toLong()` |

## 11. Security rules for the Kotlin service

- Validate `alg`, `iss`, `aud`, `exp`, `nbf`, and `token_use` on every request.
- Never disable signature verification or audience validation.
- Never accept tokens from query parameters or cookies; use the
  `Authorization: Bearer` header only.
- Never log `Authorization` headers, access tokens, or refresh tokens.
- Keep the service stateless: no server-side sessions for bearer APIs.
- Do not share Laravel's private key with the Kotlin service.
- If the service calls Laravel, forward the caller's access token; do not mint
  your own tokens.
- For browser clients, configure CORS for the Spring service. Bearer tokens are
  not cookies, so `allowCredentials` is not required.

## 12. Google sign-in (future, no Kotlin changes)

When Google sign-in is added:

1. The frontend obtains a Google ID token via Google Identity Services.
2. The frontend posts it to Laravel `POST /api/v1/auth/google`.
3. Laravel verifies the Google ID token and returns the same Laravel access and
   refresh tokens documented above.
4. The Kotlin service continues to verify only Laravel-issued JWTs. Nothing in
   this document changes.

## 13. Change management

If any of the following changes, update this document and notify the Kotlin
service owners before deployment:

- issuer or audience values
- claim names or types (`sub`, `role`, `ver`, `token_use`)
- signing algorithm or key format
- JWKS URL
- access token TTL
- role names
