<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class SearchTaskController extends AbstractController
{

#[Route(path:"/search", name:"task_search")]
  public function searchTask(Request $request)
  {
    $defaultData = ['message' => 'Type your message here'];
    $searchForm = $this->createFormBuilder($defaultData)

        ->getForm();

    $searchForm->handleRequest($request);
    if ($searchForm->isSubmitted() && $searchForm->isValid()) {
        // data is an array with "name", "email", and "message" keys
        $data = $searchForm->getData();
    }

    return $this->render('task/search.html.twig', [
        'search_form' => $searchForm->createView(),
    ]);
  }
}