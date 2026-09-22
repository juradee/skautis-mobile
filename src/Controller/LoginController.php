<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Webwings\SkautisBundle\Client\FixtureSkautisClient;
use Webwings\SkautisBundle\Security\SkautisUser;
use Webwings\SkautisBundle\SkautisConfig;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly SkautisConfig $config,
        #[Autowire('%webwings_skautis.mock%')]
        private readonly bool $mock,
    ) {
    }

    /**
     * The URL registered in skautIS as "URL po přihlášení". A GET shows the
     * entry screen; the POST that skautIS sends here is taken by the
     * authenticator before this controller ever runs.
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof SkautisUser) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('security/login.html.twig', [
            'skautis_login_url' => $this->config->loginUrl(),
            'configured' => $this->config->isConfigured(),
            'mock' => $this->mock,
            'mock_token' => FixtureSkautisClient::TOKEN,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Ends the skautIS session too, otherwise the next visit would log straight
     * back in. skautIS then redirects to the registered "URL po odhlášení".
     */
    #[Route('/odhlasit', name: 'app_logout_start', methods: ['GET', 'POST'])]
    public function startLogout(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof SkautisUser || $this->mock) {
            return $this->redirectToRoute('app_logout');
        }

        return $this->redirect($this->config->logoutUrl($user->getSkautisToken()));
    }

    /**
     * The URL registered in skautIS as "URL po odhlášení". The firewall's logout
     * listener clears our session here, so this body is never reached.
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('Odhlášení obsluhuje security firewall.');
    }

    #[Route('/odhlaseno', name: 'app_logged_out', methods: ['GET'])]
    public function loggedOut(): Response
    {
        return $this->render('security/logged_out.html.twig');
    }
}
