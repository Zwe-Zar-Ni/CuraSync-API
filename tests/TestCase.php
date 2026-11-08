<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureJwtKeys();
    }

    private function ensureJwtKeys(): void
    {
        $privatePath = storage_path('jwt/private.pem');
        $publicPath = storage_path('jwt/public.pem');

        if (is_file($privatePath) && is_file($publicPath)) {
            return;
        }

        if (! is_dir(dirname($privatePath))) {
            mkdir(dirname($privatePath), 0755, true);
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privatePem);
        $details = openssl_pkey_get_details($resource);

        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $details['key']);
    }
}
