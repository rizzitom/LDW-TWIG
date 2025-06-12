<?php

namespace App\Controleur;

use Twig\Environment;

abstract class AbstractControleur
{
    protected Environment $twig;
    protected $db;
    public function __construct(Environment $twig, $db)
    {
        $this->twig = $twig;
        $this->db = $db;
    }

    protected function afficherVue(string $template, array $data = []): void
    {
        if (!str_ends_with($template, '.twig')) {
            $template .= '.twig';
        }
        
        try {
            echo $this->twig->render($template, $data);
        } catch (\Twig\Error\LoaderError $e) {
            error_log("Twig LoaderError: " . $e->getMessage());
            echo "Une erreur est survenue lors de l'affichage de la page. Veuillez réessayer plus tard.";
        } catch (\Twig\Error\RuntimeError $e) {
            error_log("Twig RuntimeError: " . $e->getMessage());
            echo "Une erreur est survenue lors de l'affichage de la page. Veuillez réessayer plus tard.";
        } catch (\Twig\Error\SyntaxError $e) {
            error_log("Twig SyntaxError: " . $e->getMessage());
            echo "Une erreur est survenue lors de l'affichage de la page. Veuillez réessayer plus tard.";
        }
    }

    protected function rediriger(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    // Helper to get a value from GET or return a default
    protected function getParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    // Helper to get a value from POST or return a default
    protected function postParam(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}
