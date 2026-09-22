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
final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(UnitApi $unitApi, PersonApi $personApi): Response
    {
        /** @var SkautisUser $user */
        $user = $this->getUser();
        $unitId = $user->getUnitId();

        $unit = null !== $unitId ? $unitApi->detail($unitId) : null;
        $members = null !== $unitId ? $personApi->listByUnit($unitId) : [];

        return $this->render('dashboard/index.html.twig', [
            'unit' => $unit,
            'member_count' => \count($members),
            'child_units' => null !== $unitId ? $unitApi->children($unitId) : [],
            'birthdays' => $this->upcomingBirthdays($members),
        ]);
    }

    /**
     * Members with a birthday in the next 30 days, soonest first.
     *
     * @param list<\Webwings\SkautisBundle\Dto\Person> $members
     *
     * @return list<array{person: \Webwings\SkautisBundle\Dto\Person, date: \DateTimeImmutable, days: int}>
     */
    private function upcomingBirthdays(array $members): array
    {
        $today = new \DateTimeImmutable('today');
        $upcoming = [];

        foreach ($members as $person) {
            if (null === $person->birthday) {
                continue;
            }

            $next = $person->birthday->setDate(
                (int) $today->format('Y'),
                (int) $person->birthday->format('n'),
                (int) $person->birthday->format('j'),
            );

            if ($next < $today) {
                $next = $next->modify('+1 year');
            }

            $days = (int) $today->diff($next)->days;

            if ($days <= 30) {
                $upcoming[] = ['person' => $person, 'date' => $next, 'days' => $days];
            }
        }

        usort($upcoming, static fn (array $a, array $b): int => $a['days'] <=> $b['days']);

        return \array_slice($upcoming, 0, 5);
    }
}
