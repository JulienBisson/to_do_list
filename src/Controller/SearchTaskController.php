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
    // Recherche des tâches en fonction des critères de recherche et du rôle de l'utilisateur
public function search(Request $request, TasksRepository $tasksRepository, Security $security): Response
{
    // Obtient l'utilisateur actuellement connecté
    $user = $security->getUser();

    // Récupère la chaîne de recherche à partir de la requête
    $query = $request->query->get('searchTask');

    // Si la chaîne de recherche est vide, affiche la page de recherche sans résultats
    if (!$query) {
        return $this->render('task/search.html.twig', [
            'searchTask' => '',
        ]);
    }

    // Initialise la variable userId à null
    $userId = null;

    // Si l'utilisateur n'est pas un super admin, assigne l'ID de l'utilisateur à userId
    if (!$security->isGranted('ROLE_SUPER_ADMIN')) {
        $userId = $user->getId();
    }

    // Appelle la méthode pour rechercher les tâches pour les super admins
    $tasksAdmin = $this->findBySearchQuery($query); // Super Admin

    // Appelle la méthode pour rechercher les tâches pour les utilisateurs normaux
    $tasksUsers = $this->findBySearchQuery($query, $userId); // Normal User

    // Rend la vue avec les résultats de la recherche
    return $this->render('task/index.html.twig', [
        'tasksAdmin' => $tasksAdmin ?? [],
        'tasksUsers' => $tasksUsers ?? [],
    ]);
}

// Recherche les tâches en fonction de la chaîne de recherche et de l'ID de l'utilisateur
public function findBySearchQuery($query, $userId = null)
{
    // Crée une requête pour la recherche dans la table Tasks
    $queryBuilder = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
        ->andWhere('t.title LIKE :query OR t.content LIKE :query')
        ->setParameter('query', '%' . $query . '%');

    // Si l'ID de l'utilisateur est fourni, ajoute une condition pour filtrer par utilisateur
    if ($userId) {
        $queryBuilder->andWhere('t.user = :userId')->setParameter('userId', $userId);
    }

    // Exécute la requête et retourne les résultats
    return $queryBuilder->getQuery()->getResult();
}
}