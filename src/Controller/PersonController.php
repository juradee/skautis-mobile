<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webwings\SkautisBundle\Api\PersonApi;
use Webwings\SkautisBundle\Api\UnitApi;
use Webwings\SkautisBundle\Security\SkautisUser;

#[IsGranted('ROLE_USER')]
final class PersonController extends AbstractController
{
    #[Route('/osoby', name: 'app_person_index', methods: ['GET'])]
    public function index(Request $request, PersonApi $personApi, UnitApi $unitApi): Response
    {
        /** @var SkautisUser $user */
        $user = $this->getUser();

        $unitId = $request->query->getInt('jednotka') ?: $user->getUnitId();

        if (null === $unitId) {
            return $this->render('person/index.html.twig', ['people' => [], 'unit' => null, 'units' => []]);
        }

        // The unit picker offers the role's own unit plus everything directly under it.
        $rootId = $user->getUnitId() ?? $unitId;
        $root = $unitApi->detail($rootId);

        return $this->render('person/index.html.twig', [
            'people' => $personApi->listByUnit($unitId),
            'unit' => $rootId === $unitId ? $root : $unitApi->detail($unitId),
            'units' => array_values(array_filter([$root, ...$unitApi->children($rootId)])),
            'current_unit_id' => $unitId,
        ]);
    }

    #[Route('/osoby/{id<\d+>}', name: 'app_person_detail', methods: ['GET'])]
    public function detail(int $id, PersonApi $personApi): Response
    {
        $person = $personApi->detail($id);

        if (null === $person) {
            throw $this->createNotFoundException('Osoba nebyla nalezena.');
        }

        return $this->render('person/detail.html.twig', ['person' => $person]);
    }
}
