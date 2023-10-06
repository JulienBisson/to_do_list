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

    // #[Route('/{id}', name: 'task_show', methods: ['GET'])]

    // public function show(): Response
    // {
    //     $task = $this->entityManager->getRepository(Task::class)->findById();
    //     return $this->render('tasks/show.html.twig', [
    //         'task' => $task,
    //     ]);
    // }
    
    // #[Route('/task/new', name: 'task_new', methods: ['GET', 'POST'])]
    // public function newTask(Request $request): Response {
    //     $task = new Tasks();
    //     $form = $this->createForm(TaskType::class, $task);
    //     $form->handleRequest($request); 

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager = $this->entityManager;
    //         $entityManager->persist($task);
    //         $entityManager->flush();
    
    //         return $this->redirectToRoute('tasks');
    //     }
    
    //     return $this->render('tasks/new.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }

}
