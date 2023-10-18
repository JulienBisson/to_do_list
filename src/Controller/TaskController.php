<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tasks;
use Symfony\Component\Security\Core\Security;
use App\Form\TaskType;
use Symfony\Component\Security\Core\User\UserInterface;


class TaskController extends AbstractController
{

    private $entityManager;
    private $user;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'task_index', methods: ['GET'])]
    public function index(UserInterface $user): Response
    {
        $user = $this->getUser();

        // Vérifie si l'utilisateur est un super admin
        if ($this->isGranted('ROLE_SUPER_ADMIN', $user)) {
            // Requête pour le super admin
            $tasksAdmin = $this->entityManager->getRepository(Tasks::class)->findBy(['user' => $user->getId()]);
            $tasksAdmin = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
            ->where('t.user = :superAdminId')
            ->setParameter('superAdminId', $user->getId())
            // ->groupBy('t.priority')
            ->orderBy('t.created_at','ASC')
            ->getQuery()
            ->getResult();
            $tasksUsers = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
            ->where('t.user != :superAdminId')
            ->setParameter('superAdminId', $user->getId())
            // ->groupBy('t.priority')
            ->orderBy('t.created_at','ASC')
            ->getQuery()
            ->getResult();
        } else {
            // Requête pour les utilisateurs normaux (excluant les tâches du super admin)
            $tasksAdmin = $this->entityManager->getRepository(Tasks::class)->findBy(['user' => $user->getId()]);
            $tasksUsers = [];
        }
    
        return $this->render('task/index.html.twig', [
            'tasksAdmin' => $tasksAdmin,
            'tasksUsers' => $tasksUsers,
        ]);
    }

    #[Route('/{id}', name: 'task_show', methods: ['GET'], requirements: ['id' => '\d+'])]

    public function show(int $id): Response
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas');
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }
    
    #[Route('/task/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function newTask(Request $request, Security $security): Response {
        $user = $this->getUser();
        $task = new Tasks();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request); 
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si l'utilisateur actuel est super admin
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                // Utilisateur normal peut ajouter seulement pour soi-même
                $task->setUser($user);
            }

            $task->setCreatedAt(new \DateTimeImmutable('now'));

            $entityManager = $this->entityManager;
            $entityManager->persist($task);
            $entityManager->flush();
            
            return $this->redirectToRoute('task_index');
        }
        
        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]

    public function edit(Request $request, int $id): Response {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id); 

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request); 

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                // Si oui, l'utilisateur peut changer le propriétaire de la tâche
                $task->setUser($form->get('user')->getData());
            }

            $task->setUpdatedAt(new \DateTimeImmutable('now'));
            
            $this->entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'task_delete', methods: ['GET', 'POST'])]

    public function deleteTask(Request $request, int $id, Security $security): Response {
        $user = $security->getUser();
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);
    
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {

            $this->entityManager->remove($task);
            $this->entityManager->flush();

        } elseif ($task->getUser() !== $user) {
            
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de supprimer cette tâche.');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('task_index');
    }

    #[Route('/delete/all', name: 'task_delete_all', methods: ['GET', 'POST'])]

    public function deleteAllTasks(Security $security): Response {
        $user = $security->getUser();
        $tasks = $this->entityManager->getRepository(Tasks::class)->findAll();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            foreach ($tasks as $task) {
                $this->entityManager->remove($task);
            }
        } else {
            foreach ($tasks as $task) {
                if ($task->getUser() !== $user) {
                    throw $this->createAccessDeniedException('Vous n\'avez pas la permission de supprimer cette tâche.');
                }
                $this->entityManager->remove($task);
            }
        }
        
        $this->entityManager->flush();
    
        return $this->redirectToRoute('task_index');

    }
}