<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Exception\CustomUserMessageAuthenticationException;
use App\Exception\InvalidCredentialsException;
use App\Service\BillingClient;
use JsonException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient
    ) {
    }

    /**
     * @throws CustomUserMessageAuthenticationException
     * @throws JsonException
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        $credentials = json_encode([
            'username' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR);

        return new SelfValidatingPassport(
            new UserBadge($credentials, function ($credentials) use ($email, $password) {
                try {
                    $response = $this->billingClient
                        ->auth(
                            $email,
                            $password
                        );
                } catch (BillingUnavailableException $e) {
                    // CustomUserMessage эксептион прокидывает в AuthUtils чтобы вызвать twig
                   throw new CustomUserMessageAuthenticationException($e->getMessage());
                } catch (InvalidCredentialsException $e) {
                    throw new CustomUserMessageAuthenticationException($e->getMessage());
                }
                $user = new User();
                $user->setApiToken($response['access_token']);
                $user->fromApiToken();
                $user->setRefreshToken($response['refresh_token']);
                return $user;
            }),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}