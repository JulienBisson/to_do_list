<?php

namespace App\Controller;

use App\Entity\Tasks;
use App\Repository\TasksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class SearchTaskController extends AbstractController
{
    private $entityManager;
    private $user;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request, TasksRepository $tasksRepository, Security $security): Response
    {
        $user = $security->getUser();
        $query = $request->query->get('searchTask');


        if (!$query) {
            return $this->render('task/search.html.twig', [
                'searchTask' => '',
            ]);
        }

        $tasks = $this->findBySearchQuery($query);
        
        if ($security->isGranted('ROLE_SUPER_ADMIN')) {
            // Requête pour le super admin
            $tasksAdmin = $this->findBySearchQuery($query);
        } else {
            // Requête pour les utilisateurs normaux (excluant les tâches du super admin)
            $tasksUsers = $this->findBySearchQuery($query);
        }
        
        return $this->render('task/index.html.twig', [
            'tasksAdmin' => $tasksAdmin ?? [],
            'tasksUsers' => $tasksUsers ?? [],
        ]);
    }

    public function findBySearchQuery($query)
{
    $query = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
        ->andWhere('t.title LIKE :query OR t.content LIKE :query')
        ->setParameter('query', '%' . $query . '%')
        ->getQuery();

    return $query->getResult();
}
}