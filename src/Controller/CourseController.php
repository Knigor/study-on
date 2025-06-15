<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Exception\NotEnoughBalanceException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
final class CourseController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
    ){
    }

    /**
     * @throws BillingUnavailableException
     * @throws \DateMalformedStringException
     */
    #[Route(name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $billingCourses = $this->billingClient->coursesList();

        $mergedCoursesInfo = $courseRepository->findAllWithBilling($billingCourses);


        $user = $this->getUser();

        // Ставим флаги доступен или не доступен курс
        foreach ($mergedCoursesInfo as $i => $iValue) {
            if ($user) {
                $isAvailable = $this->billingClient->isCourseAvailable(
                    $user->getApiToken(),
                    $iValue['code']
                );
                $mergedCoursesInfo[$i]['is_available'] = true ? $isAvailable : false;
            } else {
                $mergedCoursesInfo[$i]['is_available'] = false;
            }
        }
        return $this->render('course/index.html.twig', [
            'courses' => $mergedCoursesInfo
        ]);
    }


    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET', 'POST'])]
    #[IsGranted("ROLE_USER")]
    public function payCourse(Course $course, Request $request): Response
    {
        $user = $this->getUser();

        if ($user) {
            try {
                $success = $this->billingClient
                    ->payCourse(
                        $user->getApiToken(),
                        $course->getCharacterCode()
                    )
                ;
                $flashType = 'success';
                $message = 'Course successfully paid!';
            } catch (BillingUnavailableException) {
                $flashType = 'danger';
                $message = 'Service is temporarily unavailable. Try again later.';
            } catch (NotEnoughBalanceException) {
                $flashType = 'danger';
                $message = 'Not enough money for payment.';
            } finally {
                $this->addFlash($flashType, $message);
                return $this->redirectToRoute(
                    'app_course_show',
                    ['id' => $course->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        } else {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }
    }





    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws BillingUnavailableException
     */
    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $billingCourse = $this->billingClient->courseInfoByCode($course->getCharacterCode());

        $user = $this->getUser();

        if ($user) {
            $isCourseAvailable = $this->billingClient
                ->isCourseAvailable(
                    $user->getApiToken(),
                    $course->getCharacterCode()
                )
            ;
        } else {
            $isCourseAvailable = false;
        }

        if ($isCourseAvailable && $billingCourse['type'] === 'rent') {
            $expires_at = $isCourseAvailable;   // Для rent возвращается дата
        } else {
            $expires_at = null;
        }

        if ($isCourseAvailable) {
            $isEnoughBalance = true;    // Заглушка на всякий случай
        } else {
            $isEnoughBalance = $this->billingClient->isEnoughBalance(
                $user->getApiToken(),
                $course->getCharacterCode()
            );
        }


        $lessons = $course->getLessons();
        return $this->render('course/show.html.twig', [
            'course' => $course,
            'lessons' => $lessons,
            'course_type' => $billingCourse['type'],
            'course_price' => $billingCourse['price'] ?? 0.00,
            'is_course_available' => $isCourseAvailable,
            'expires_at' => $expires_at,
            'is_enough_balance' => $isEnoughBalance
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
