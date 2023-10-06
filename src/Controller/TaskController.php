<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tasks;
use App\Entity\Task;
use App\Form\TaskType;


class TaskController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'task_index', methods: ['GET'])]
    public function index(): Response
    {
        $tasks = $this->entityManager->getRepository(Tasks::class)->findAll();

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}', name: 'task_show', methods: ['GET'])]

    public function show(int $id): Response
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }
    
    #[Route('/task/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function newTask(Request $request): Response {
        $task = new Tasks();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request); 
        
        if ($form->isSubmitted() && $form->isValid()) {
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
            $this->entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'task_delete', methods: ['GET', 'POST'])]

    public function deleteTask(Request $request, int $id): Response {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);
    
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('task_index');
    }

    #[Route('/delete/all', name: 'task_delete_all', methods: ['GET', 'POST'])]

    public function deleteAllTasks(): Response {
        $tasks = $this->entityManager->getRepository(Tasks::class)->findAll();

        foreach ($tasks as $task) {
            $this->entityManager->remove($task);
        }
        
        $this->entityManager->flush();
    
        return $this->redirectToRoute('task_index');

    }
}