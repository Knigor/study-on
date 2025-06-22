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
        private readonly CourseRepository $courseRepository
    ) {}

    #[Route(path: '/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service_unavailable = false;
        $billingUser = null;

        try {
            $billingUser = $this->billingClient->getCurrentUser($user->getApiToken());
        } catch (BillingUnavailableException $e) {
            $service_unavailable = true;
        }

        return $this->render('profile/profile.html.twig', [
            'user' => $billingUser ?? $user,
            'service_unavailable' => $service_unavailable
        ]);
    }

    #[Route('/profile/transactions', name: 'app_transactions_list')]
    #[IsGranted("ROLE_USER")]
    public function transactionsList(): Response
    {
        $user = $this->getUser();
        $transactions = [];
        $service_unavailable = false;

        try {
            $transactions = $this->billingClient->getUserTransactions($user->getApiToken());


            foreach ($transactions as &$transaction) {
                if ($transaction['type'] === "payment" && !empty($transaction['course_code'])) {
                    $paidCourse = $this->courseRepository->findOneBy([
                        'characterCode' => $transaction['course_code']
                    ]);

                    if ($paidCourse) {
                        $transaction['course_id'] = $paidCourse->getId();
                        $transaction['course_title'] = $paidCourse->getName();
                    }
                }
            }
            unset($transaction);



        } catch (BillingUnavailableException $e) {
            $service_unavailable = true;
        }

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactions,
            'service_unavailable' => $service_unavailable,
            'user' => $user
        ]);
    }
}