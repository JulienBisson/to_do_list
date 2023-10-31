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

// Constructor for the class, initializes the entity manager
public function __construct(EntityManagerInterface $entityManager)
{
    $this->entityManager = $entityManager;
}

// Controller method for displaying tasks
#[Route('/', name: 'task_index', methods: ['GET'])]
public function index(Request $request, Security $security): Response
{
    // Get the current user from security context
    $user = $security->getUser();

    // Check if the user is a super admin
    if ($security->isGranted('ROLE_SUPER_ADMIN')) {
        // Query tasks for super admin
            // Query to retrieve tasks for super admin sorted by priority and creation date
            $tasksAdmin = $this->entityManager->getRepository(Tasks::class)->findBy(['user' => $user->getId()]);
        
            $tasksAdmin = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
            ->where('t.user = :superAdminId')
            ->setParameter('superAdminId', $user->getId())
            ->orderBy('
                CASE 
                    WHEN t.priority = \'hight\' THEN 0
                    WHEN t.priority = \'medium\' THEN 1
                    WHEN t.priority = \'low\' THEN 2
                    ELSE 3
                END', 'ASC') // Tri par priorité
            ->addOrderBy('t.created_at', 'ASC') // Tri par date de création
            ->getQuery()
            ->getResult();
        // Query tasks for normal users (excluding super admin tasks)
            // Query to retrieve tasks for normal users sorted by priority and creation date
            $tasksUsers = $this->entityManager->getRepository(Tasks::class)->createQueryBuilder('t')
            ->where('t.user != :superAdminId')
            ->setParameter('superAdminId', $user->getId())
            ->orderBy('
                CASE 
                WHEN t.priority = \'hight\' THEN 0
                WHEN t.priority = \'medium\' THEN 1
                WHEN t.priority = \'low\' THEN 2
                    ELSE 3
                END', 'ASC') // Tri par priorité
            ->addOrderBy('t.created_at', 'ASC') // Tri par date de création
            ->getQuery()
            ->getResult();

        } else {
        // Requête pour les utilisateurs normaux (excluant les tâches du super admin)
            // Query to retrieve tasks for normal users
            $tasksUsers = $this->entityManager->getRepository(Tasks::class)->findBy(['user' => $user->getId()]);
        }
    
        // Render the tasks view with the obtained task lists
        return $this->render('task/index.html.twig', [
            'tasksAdmin' => $tasksAdmin ?? [],
            'tasksUsers' => $tasksUsers ?? [],
        ]);
    }

   // Controller method for displaying a single task
    #[Route('/{id}', name: 'task_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        // Find the task by ID
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        // If the task is not found, throw a 404 exception
        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas');
        }

        // Render the task show view with the retrieved task
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }
    
    // Controller method for creating a new task
    #[Route('/task/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function newTask(Request $request, Security $security): Response
    {
        // Get the current user
        $user = $this->getUser();

        // Create a new task object
        $task = new Tasks();

        // Create a form for the task
        $form = $this->createForm(TaskType::class, $task);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            // If the user is not a super admin, set the task owner to the current user
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $task->setUser($user);
            }

            // Set the task creation timestamp
            $task->setCreatedAt(new \DateTimeImmutable('now'));

            // Persist and flush the task to the database
            $entityManager = $this->entityManager;
            $entityManager->persist($task);
            $entityManager->flush();

            // Redirect to the task index page
            return $this->redirectToRoute('task_index');
        }

        // Render the new task form view
        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    
    // Controller method for editing an existing task
    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        // Find the task by ID
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        // Create a form for editing the task
        $form = $this->createForm(TaskType::class, $task);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            // If the user is a super admin, allow changing the task owner
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $task->setUser($form->get('user')->getData());
            }

            // Set the task update timestamp
            $task->setUpdatedAt(new \DateTimeImmutable('now'));

            // Flush the changes to the database
            $this->entityManager->flush();

            // Redirect to the task index page
            return $this->redirectToRoute('task_index');
        }

        // Render the edit task form view
        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    // Controller method for deleting a task
    #[Route('/{id}/delete', name: 'task_delete', methods: ['GET', 'POST'])]
    public function deleteTask(Request $request, int $id, Security $security): Response
    {
        // Get the current user
        $user = $security->getUser();

        // Find the task by ID
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        // Check if the user is a super admin
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            // Remove the task for a super admin
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        } elseif ($task->getUser() !== $user) {
            // If the task owner is not the current user, deny access to delete
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de supprimer cette tâche.');
        }

        // Remove and flush the task for normal users
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        // Redirect to the task index page
        return $this->redirectToRoute('task_index');
    }

    // Controller method for deleting all tasks
    #[Route('/delete/all', name: 'task_delete_all', methods: ['GET', 'POST'])]
    public function deleteAllTasks(Security $security): Response
    {
        // Get the current user
        $user = $security->getUser();

        // Get all tasks from the repository
        $tasks = $this->entityManager->getRepository(Tasks::class)->findAll();

        // Check if the user is a super admin
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            // Remove all tasks for a super admin
            foreach ($tasks as $task) {
                $this->entityManager->remove($task);
            }
        } else {
            // Remove tasks for normal users, deny access if the task does not belong to the user
            foreach ($tasks as $task) {
                if ($task->getUser() !== $user) {
                    throw $this->createAccessDeniedException('Vous n\'avez pas la permission de supprimer cette tâche.');
                }
                $this->entityManager->remove($task);
            }
        }

        // Flush the changes to the database
        $this->entityManager->flush();

        // Redirect to the task index page
        return $this->redirectToRoute('task_index');
    }
}