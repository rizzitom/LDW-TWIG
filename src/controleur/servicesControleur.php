<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServicesController extends AbstractController
{
    #[Route('/services', name: 'app_services')]
    public function index(): Response
    {
        $services = [
            [
                'title' => 'Développement Web',
                'description' => 'Création de sites web modernes et performants adaptés à vos besoins.',
                'icon' => 'fa-code',
                'link' => '#developpement-web',
            ],
            [
                'title' => 'Montage Informatique',
                'description' => 'Assemblage de PC personnalisés pour gaming, bureautique ou création.',
                'icon' => 'fa-desktop',
                'link' => '#montage-informatique',
            ],
            [
                'title' => 'Dépannage Informatique',
                'description' => 'Réparation et optimisation de vos équipements informatiques.',
                'icon' => 'fa-tools',
                'link' => '#depannage-informatique',
            ],
        ];

        return $this->render('services', [
            'services' => $services,
        ]);
    }
}
