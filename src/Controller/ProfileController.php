<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use App\Repository\CourseRepository;
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

    /**
     * @throws BillingUnavailableException
     */
    #[Route(path: '/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        $billingUser = $this->billingClient
            ->getCurrentUser(
                $user->getApiToken()
            );

        return $this->render('profile/profile.html.twig', [
            'user' => [
                'username' => $billingUser->getUserIdentifier(),
                'roles' => $billingUser->getRoles(),
                'balance' => $billingUser->getBalance(),
            ]
        ]);

    }
}
