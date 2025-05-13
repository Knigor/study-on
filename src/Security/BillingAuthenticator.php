<?php
namespace App\Security;

use App\Service\BillingClient;
use App\Exception\BillingUnavailableException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly BillingClient $billingClient)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $password = $request->getPayload()->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new SelfValidatingPassport(
            new UserBadge(implode(' ',[$email, $password]), function(string $credentials): ?UserInterface {
                $credentials = explode(' ', $credentials);
                $username = $credentials[0];
                $password = $credentials[1];
                try {
                    $responseBilling = $this->billingClient->auth([
                        'email' => $username,
                        'password' => $password,
                    ]);
                } catch (BillingUnavailableException $exception) {
                    throw new CustomUserMessageAuthenticationException('Сервис времменно не доступен. Попробуйте авторизоваться позднее.');
                }


                $user = new User();
                if (isset($responseBilling['access_token']) && $responseBilling['access_token'] !== '') {
                    $user->setEmail($responseBilling['user']['email'])
                        ->setApiToken($responseBilling['access_token'])
                        ->setRoles($responseBilling['user']['roles']);
                } else {
                    throw new CustomUserMessageAuthenticationException($responseBilling['message'] ?? 'Unknown error');
                }
              //  dd($user);
                return $user;
            }),
            [
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

    public function createPassportFromUser(UserInterface $user): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user),
            [
                new RememberMeBadge(),
            ]
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
