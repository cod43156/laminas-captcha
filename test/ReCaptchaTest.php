<?php

namespace LaminasTest\Captcha;

use Laminas\Captcha\ReCaptcha;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Client\Adapter\Socket;
use Laminas\ReCaptcha\ReCaptcha as ReCaptchaService;
use PHPUnit\Framework\TestCase;

use function getenv;

/**
 * @group      Laminas_Captcha
 */
class ReCaptchaTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        if (! getenv('TESTS_LAMINAS_CAPTCHA_RECAPTCHA_SUPPORT')) {
            $this->markTestSkipped('Enable TESTS_LAMINAS_CAPTCHA_RECAPTCHA_SUPPORT to test Recaptcha');
        }
    }

    public function testConstructorShouldSetOptions(): void
    {
        $options = [
            'secret_key'       => 'secretKey',
            'site_key'         => 'siteKey',
            'size'             => 'a',
            'theme'            => 'b',
            'type'             => 'c',
            'tabindex'         => 'd',
            'callback'         => 'e',
            'expired-callback' => 'f',
            'hl'               => 'g',
            'noscript'         => 'h',
        ];
        $captcha = new ReCaptcha($options);
        $service = $captcha->getService();

        // have params been stored correctly?
        $test    = $service->getParams();
        $compare = ['noscript' => $options['noscript']];
        foreach ($compare as $key => $value) {
            $this->assertArrayHasKey($key, $test);
            $this->assertSame($value, $test[$key]);
        }

        // have options been stored correctly?
        $test    = $service->getOptions();
        $compare = [
            'size'             => $options['size'],
            'theme'            => $options['theme'],
            'type'             => $options['type'],
            'tabindex'         => $options['tabindex'],
            'callback'         => $options['callback'],
            'expired-callback' => $options['expired-callback'],
            'hl'               => $options['hl'],
        ];
        $this->assertEquals($compare, $test);
    }

    public function testShouldAllowSpecifyingServiceObject(): void
    {
        $captcha = new ReCaptcha();
        $try     = new ReCaptchaService();
        $this->assertNotSame($captcha->getService(), $try);
        $captcha->setService($try);
        $this->assertSame($captcha->getService(), $try);
    }

    public function testSetAndGetSiteAndSecretKeys(): void
    {
        $captcha   = new ReCaptcha();
        $siteKey   = 'siteKey';
        $secretKey = 'secretKey';
        $captcha->setSiteKey($siteKey)
                ->setSecretKey($secretKey);

        $this->assertSame($siteKey, $captcha->getSiteKey());
        $this->assertSame($secretKey, $captcha->getSecretKey());

        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    public function testSetAndGetSiteAndSecretKeysViaBCMethods(): void
    {
        $captcha   = new ReCaptcha();
        $siteKey   = 'siteKey';
        $secretKey = 'secretKey';
        $captcha->setPubKey($siteKey)
                ->setPrivKey($secretKey);

        $this->assertSame($siteKey, $captcha->getPubKey());
        $this->assertSame($secretKey, $captcha->getPrivKey());

        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    public function testSetAndGetRecaptchaServiceSiteAndSecretKeysFromOptions(): void
    {
        $siteKey   = 'siteKey';
        $secretKey = 'secretKey';
        $options   = [
            'site_key'   => $siteKey,
            'secret_key' => $secretKey,
        ];
        $captcha   = new ReCaptcha($options);
        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    public function testSetAndGetRecaptchaServiceSiteAndSecretKeysFromOptionsWithBCNames(): void
    {
        $siteKey   = 'siteKey';
        $secretKey = 'secretKey';
        $options   = [
            'pubKey'  => $siteKey,
            'privKey' => $secretKey,
        ];
        $captcha   = new ReCaptcha($options);
        $this->assertSame($siteKey, $captcha->getService()->getSiteKey());
        $this->assertSame($secretKey, $captcha->getService()->getSecretKey());
    }

    /**
     * @group Laminas-7654
     */
    public function testConstructorShouldAllowSettingThemeOptionOnServiceObject(): void
    {
        $options = ['theme' => 'dark'];
        $captcha = new ReCaptcha($options);
        $this->assertEquals('dark', $captcha->getService()->getOption('theme'));
    }

    /**
     * @group Laminas-7654
     */
    public function testAllowsSettingThemeOptionOnServiceObject(): void
    {
        $captcha = new ReCaptcha();
        $captcha->setOption('theme', 'dark');
        $this->assertEquals('dark', $captcha->getService()->getOption('theme'));
    }

    public function testUsesReCaptchaHelper(): void
    {
        $captcha = new ReCaptcha();
        $this->assertEquals('captcha/recaptcha', $captcha->getHelperName());
    }

    public function testValidationForDifferentElementName(): void
    {
        $captcha = new ReCaptcha([
            'site_key'   => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_SITE_KEY'),
            'secret_key' => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_SECRET_KEY'),
        ]);
        $service = $captcha->getService();
        $service->setIp('127.0.0.1');
        $service->setHttpClient($this->getHttpClient());

        $response = getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_RESPONSE');
        $value    = 'g-recaptcha-response';
        $context  = ['g-recaptcha-response' => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_RESPONSE')];

        $this->assertTrue($captcha->isValid($value, $context));
    }

    public function testValidationForResponseElementName(): void
    {
        $captcha = new ReCaptcha([
            'site_key'   => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_SITE_KEY'),
            'secret_key' => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_SECRET_KEY'),
        ]);
        $service = $captcha->getService();
        $service->setIp('127.0.0.1');
        $service->setHttpClient($this->getHttpClient());

        $response = getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_RESPONSE');
        $value    = getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_RESPONSE');
        $context  = ['g-recaptcha-response' => getenv('TESTS_LAMINAS_SERVICE_RECAPTCHA_RESPONSE')];

        $this->assertTrue($captcha->isValid($value, $context));
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient()
    {
        $socket = new Socket();
        $socket->setOptions([
            'ssltransport' => 'tls',
        ]);
        return new HttpClient(null, [
            'adapter' => $socket,
        ]);
    }
}
