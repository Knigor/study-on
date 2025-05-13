<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CustomAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private Environment $twig) {}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $html = $this->twig->render('bundles/TwigBundle/Exception/error403.html.twig', [
            'message' => 'У вас нет доступа к этой странице',
            'details' => $accessDeniedException->getMessage(),
            'status' => Response::HTTP_FORBIDDEN,
        ]);

        return new Response($html, Response::HTTP_FORBIDDEN);
    }
}
