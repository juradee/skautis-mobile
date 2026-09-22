<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webwings\SkautisBundle\Api\UserApi;
use Webwings\SkautisBundle\Exception\SkautisException;
use Webwings\SkautisBundle\Security\SkautisAuthenticator;
use Webwings\SkautisBundle\Security\SkautisUser;
use Webwings\SkautisBundle\Security\SkautisUserFactory;

#[IsGranted('ROLE_USER')]
final class RoleController extends AbstractController
{
    #[Route('/role', name: 'app_role_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var SkautisUser $user */
        $user = $this->getUser();

        return $this->render('role/index.html.twig', [
            'roles' => $user->getAvailableRoles(),
            'current' => $user->getCurrentRole(),
        ]);
    }

    /**
     * Switching a role changes what skautIS lets us read, so it happens in
     * skautIS first and only then in our session.
     */
    #[Route('/role/{id<\d+>}', name: 'app_role_switch', methods: ['POST'])]
    public function switch(
        int $id,
        Request $request,
        UserApi $userApi,
        SkautisUserFactory $userFactory,
        Security $security,
    ): Response {
        /** @var SkautisUser $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('switch-role', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Neplatný CSRF token.');
        }

        try {
            $unitId = $userApi->switchRole($id);
            $security->login($userFactory->create($user->getSkautisToken(), $id, $unitId), SkautisAuthenticator::class);
        } catch (SkautisException $e) {
            $this->addFlash('error', 'Roli se nepodařilo přepnout: '.$e->getMessage());

            return $this->redirectToRoute('app_role_index');
        }

        $this->addFlash('success', 'Role byla přepnuta.');

        return $this->redirectToRoute('app_dashboard');
    }
}
