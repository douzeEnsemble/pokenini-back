<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

#[Route('/user-info')]
class UserInfoController extends AbstractController
{
    public function __construct(
    ) {}

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        /** @var ?User $user */
        $user = $this->getUser();

        if (null === $user) {
            throw new BadCredentialsException();
        }

        return new JsonResponse(
            [
                'id' => $user->getId(),
                'provider' => $user->getProvider(),
                'roles' => $user->getRoles(),
                'profile' => $user->getProfile(),
            ],
            Response::HTTP_OK
        );
    }
}
