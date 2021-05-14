<?php
namespace App\Controller;

// ...
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="root", methods={"GET"})
     */
    public function main(Request $request)
    {

        return $this->json([
            'content' => 'OK',
        ]);
    }
}