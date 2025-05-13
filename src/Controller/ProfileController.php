<?php

namespace App\Controller;

use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly BillingClient $billingClient,
    ) {}

    #[Route(path: '/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request): Response
    {
        /** @var  $user User */
        $user = $this->getUser();

        $token = $user->getApiToken();

        $userData = $this->billingClient->userCurrent($token);

        return $this->render('profile/profile.html.twig', [
            'user' => $userData,
        ]);

    }
}
