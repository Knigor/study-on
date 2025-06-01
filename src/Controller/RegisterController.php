<?php

namespace App\Controller;

use App\Dto\UserRegisterDto;
use App\Exception\BillingUnavailableException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Service\BillingClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\User;



class RegisterController extends AbstractController
{

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        BillingClient $billingClient,
        UserAuthenticatorInterface $userAuthenticator,
        BillingAuthenticator $billingAuthenticator,
        UrlGeneratorInterface $urlGenerator,
    ): Response
    {
        // Проверка авторизованного пользователя
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Получаем данные из формы
                $username = $form->get('email')->getData();
                $password = $form->get('password')->getData();

                // Регистрируем пользователя в биллинге
                $response = $billingClient->register($username, $password);

                // Создаем объект User
                $user = new User();
                $user->setApiToken($response['access_token'])
                    ->setRefreshToken($response['refresh_token'])
                    ->fromApiToken();

                // Получаем дополнительные данные о пользователе
                $billingUser = $billingClient->getCurrentUser($user->getApiToken());
                $user->setBalance($billingUser->getBalance());

                // Аутентифицируем пользователя
                return $userAuthenticator->authenticateUser(
                    $user,
                    $billingAuthenticator,
                    $request
                );

            } catch (\Exception $e) {
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'error' => null
        ]);
    }
}