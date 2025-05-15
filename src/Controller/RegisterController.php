<?php

namespace App\Controller;

use App\Dto\UserRegisterDto;
use App\Exception\BillingUnavailableException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Service\BillingClient;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\User;


class RegisterController extends AbstractController
{
    public function __construct(
        private readonly BillingClient              $billingClient,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly BillingAuthenticator       $billingAuthenticator,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        $user = new UserRegisterDto();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);
        $error = null;

        if($form->isSubmitted() && $form->isValid()) {
            try {
                $responseBilling = $this->billingClient->register([
                    'email' => $user->email,
                    'password' => $user->password,
                ]);

                if(isset($responseBilling['access_token'])) {
                    $userNew = new User();

                    $userNew->setEmail($responseBilling['user']['email'])
                        ->setRoles($responseBilling['user']['roles'])
                        ->setApiToken($responseBilling['access_token'])
                        ->setRefreshToken($responseBilling['refresh_token']);

                    $passport = $this->billingAuthenticator->createPassportFromUser($userNew);

                    $this->userAuthenticator->authenticateUser(
                        $userNew,
                        $this->billingAuthenticator,
                        $request
                    );
                    return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
                }

                if ($responseBilling['message'] === 'User with this email already exists') {
                    $error = $responseBilling['message'];
                }
            } catch (BillingUnavailableException $e) {
                $error = 'Сервис временно недоступен. Попробуйте зарегистироваться позже.';
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
            'user' => $user,
            'error' => $error,
        ]);
    }
}