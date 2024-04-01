<?php

namespace app\Controllers;

abstract class CoreController
{
    /**
     * Méthode qui est appelée dès l'instanciation
     */
    public function __construct()
    {
        // Nom de la route actuelle
        global $match;

        // S'il existe des acl pour la page en cours
        if (isset($match['target']['acl'])) {
            // La variable $acl contient un tableau avec les rôles autorisés pour la route actuelle
            $acl = $match['target']['acl'];

            // Est-ce que le rôle de l'utilisateur lui autorise à voir la page ?
            $this->checkAuthorization($acl);
        }

        $currentRoute = $match['name'];

        // CSRF en POST
        $routesCSRF = [
            'user-create',
            'product-create',
            'category-create',
            'category-home-update'
        ];

        // 1. Génère un token aléatoire s'il n'existe pas encore
        if (! isset($_SESSION['tokenCSRF'])) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['tokenCSRF'] = $token;
        }

        // 2. Est-ce que la route actuelle doit être contrôlée ?
        if (in_array($currentRoute, $routesCSRF)) {
            // Oui : on vérifie si le token csrf soumis correspond au token en session

            // Si le token n'existe pas dans le formulaire on met une chaîne de caractères vide
            $postToken = isset($_POST['tokenCSRF']) ? $_POST['tokenCSRF'] : '';

            if ($postToken != $_SESSION['tokenCSRF']) {
                // Token différents : ce n'est pas la même personne qui a envoyé le formulaire !
                header('HTTP/1.0 403 Forbidden');
                $this->show('error/err403');
                exit();
            }
        }
    }

    /**
     * Méthode permettant d'afficher du code HTML en se basant sur les views
     *
     * @param string $viewName Nom du fichier de vue
     * @param array $viewData Tableau des données à transmettre aux vues
     * @return void
     */
    protected function show(string $viewName, $viewData = [])
    {
        // On globalise $router car on ne sait pas faire mieux pour l'instant
        global $router;

        // Comme $viewData est déclarée comme paramètre de la méthode show()
        // les vues y ont accès
        // ici une valeur dont on a besoin sur TOUTES les vues
        // donc on la définit dans show()
        $viewData['currentPage'] = $viewName;

        // définir l'url absolue pour nos assets
        $viewData['assetsBaseUri'] = $_SERVER['BASE_URI'] . 'public/assets/';
        // définir l'url absolue pour la racine du site
        // /!\ != racine projet, ici on parle du répertoire public/
        $viewData['baseUri'] = $_SERVER['BASE_URI'];

        // On veut désormais accéder aux données de $viewData, mais sans accéder au tableau
        // La fonction extract permet de créer une variable pour chaque élément du tableau passé en argument
        extract($viewData);
        // => la variable $currentPage existe désormais, et sa valeur est $viewName
        // => la variable $assetsBaseUri existe désormais, et sa valeur est $_SERVER['BASE_URI'] . '/assets/'
        // => la variable $baseUri existe désormais, et sa valeur est $_SERVER['BASE_URI']
        // => il en va de même pour chaque élément du tableau

        // $viewData est disponible dans chaque fichier de vue
        require_once __DIR__ . '/../Views/header.tpl.php';
        require_once __DIR__ . '/../Views/' . $viewName . '.tpl.php';
        require_once __DIR__ . '/../Views/footer.tpl.php';
    }

    /**
     * Permet de vérifier si l'utilisateur connecté a le droit de voir la page
     *
     * @param array $roles Les rôles qui seront autorisés pour cette page
     * @return bool
     */
    protected function checkAuthorization( $roles = [])
    {
        // 1. Récupérer l'utilisateur connecté (et par extension son rôle)
        if (isset($_SESSION['userId'])) {
            // Si l'utilisateur est connecté, on récupère ses informations
            $appUser = $_SESSION['appUser'];

            // 2. En fonction des rôles autorisés ($roles), on affiche ou non la page
            // $roles => un tableau avec les rôles autorisés pour cette page
            // $appUser->getRole() => le rôle de l'utilisateur

            // Est-ce que le rôle de l'utilisateur se trouve dans la liste des rôles autorisés ?
            // => est-ce que la $user->getRole() est contenu dans le tableau $roles ?
            if (in_array($appUser->getRole(), $roles)) {
                // Utilisateur est autorisé !
                return true;
            } else {
                // Utilisateur non autorisé !
                // => on affiche une page 403
                header('HTTP/1.0 403 Forbidden');
                $this->show("error/err403");

                // On arrête le script
                exit();
            }
        } else {
            // Si l'utilisaur n'est pas connecté on le renvoie sur la page de connexion
            header('Location: /login');
            exit();
        }
    }
}