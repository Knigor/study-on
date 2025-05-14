<?php

namespace App\Tests;

use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\BillingClient;

abstract class AbstractTest extends WebTestCase
{
    /** @var AbstractBrowser */
    protected static $client;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    protected static $container;

    /**
     * @param AbstractBrowser|null $newClient
     *
     * @return AbstractBrowser|null
     */
    protected static function getClient(?AbstractBrowser $newClient = null): ?AbstractBrowser
    {
        if (null === static::$client || $newClient) {
            static::$client = static::createClient();
            static::$container = static::$client->getContainer(); // Инициализация контейнера
        }

        // core is loaded (for tests without calling of getClient(true))
        static::$client->getKernel()->boot();

        return static::$client;
    }

    /**
     * Получить EntityManager.
     *
     * @return EntityManagerInterface
     */
    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::$container->get('doctrine')->getManager(); // Получение EntityManager через контейнер
    }

    protected function setUp(): void
    {
        static::getClient(); // Инициализация клиента
        $this->loadFixtures($this->getFixtures()); // Загрузка фикстур
    }

    final protected function tearDown(): void
    {
        parent::tearDown();
        static::$client = null; // Очистка клиента
    }

    /**
     * Получить массив фикстур для загрузки.
     *
     * @return array
     */
    protected function getFixtures(): array
    {
        return [];
    }

    /**
     * Загрузка фикстур в базу данных.
     *
     * @param array $fixtures
     */
    protected function loadFixtures(array $fixtures = []): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!\is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(static::$container); // Установка контейнера в фикстуры
            }

            $loader->addFixture($fixture);
        }

        $em = static::getEntityManager(); // Получаем EntityManager
        $purger = new ORMPurger($em); // Очистка данных
        $executor = new ORMExecutor($em, $purger); // Выполнение фикстур
        $executor->execute($loader->getFixtures());
    }

    protected function replaceServiceBillingClient($ex = false): ?AbstractBrowser
    {
        $client = static::getClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock('', ex: $ex),
        );

        return $client;
    }



    public function assertResponseOk(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isOk', $message, $type);
    }

    public function assertResponseRedirect(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    public function assertResponseNotFound(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    public function assertResponseForbidden(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isForbidden', $message, $type);
    }

    public function assertResponseCode(int $expectedCode, ?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }

    private function failOnResponseStatusCheck(
        Response $response = null,
                 $func = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && self::$client) {
            $response = self::$client->getResponse();
        }

        try {
            if (\is_int($func)) {
                $this->assertEquals($func, $response->getStatusCode());
            } else {
                $this->assertTrue($response->{$func}());
            }

            return;
        } catch (\Exception $e) {
            // nothing to do
        }

        $err = $this->guessErrorMessageFromResponse($response, $type);
        if ($message) {
            $message = rtrim($message, '.') . ". ";
        }

        if (is_int($func)) {
            $template = "Failed asserting Response status code %s equals %s.";
        } else {
            $template = "Failed asserting that Response[%s] %s.";
            $func = preg_replace('#([a-z])([A-Z])#', '$1 $2', $func);
        }

        $message .= sprintf($template, $response->getStatusCode(), $func, $err);

        $max_length = 100;
        if (mb_strlen($err, 'utf-8') < $max_length) {
            $message .= " " . $this->makeErrorOneLine($err);
        } else {
            $message .= " " . $this->makeErrorOneLine(mb_substr($err, 0, $max_length, 'utf-8') . '...');
            $message .= "\n\n" . $err;
        }

        $this->fail($message);
    }

    /**
     * Метод для получения сообщения об ошибке из ответа.
     *
     * @param Response $response
     * @param string $type
     * @return string
     */
    private function guessErrorMessageFromResponse(Response $response, string $type = 'text/html'): string
    {
        $content = $response->getContent();
        if ('text/html' === $type) {
            // Вытаскиваем текст ошибки из HTML-страницы
            if (preg_match('/<title>(.*?)<\/title>/', $content, $matches)) {
                return $matches[1];
            }
        }

        return 'Unknown error'; // По умолчанию возвращаем неизвестную ошибку
    }

    private function makeErrorOneLine($text)
    {
        return preg_replace('#[\n\r]+#', ' ', $text);
    }
}
