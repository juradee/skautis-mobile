<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webwings\SkautisBundle\Api\PersonApi;
use Webwings\SkautisBundle\Api\UnitApi;
use Webwings\SkautisBundle\Security\SkautisUser;

#[IsGranted('ROLE_USER')]
final class UnitController extends AbstractController
{
    #[Route('/jednotka', name: 'app_unit_current', methods: ['GET'])]
    public function current(UnitApi $unitApi, PersonApi $personApi): Response
    {
        /** @var SkautisUser $user */
        $user = $this->getUser();
        $unitId = $user->getUnitId();

        if (null === $unitId) {
            throw $this->createNotFoundException('Role nemá přiřazenou jednotku.');
        }

        return $this->detail($unitId, $unitApi, $personApi);
    }

    #[Route('/jednotka/{id<\d+>}', name: 'app_unit_detail', methods: ['GET'])]
    public function detail(int $id, UnitApi $unitApi, PersonApi $personApi): Response
    {
        $unit = $unitApi->detail($id);

        if (null === $unit) {
            throw $this->createNotFoundException('Jednotka nebyla nalezena.');
        }

        return $this->render('unit/detail.html.twig', [
            'unit' => $unit,
            'children' => $unitApi->children($id),
            'member_count' => \count($personApi->listByUnit($id)),
            'direct_member_count' => \count($personApi->listByUnit($id, onlyDirect: true)),
        ]);
    }
}
