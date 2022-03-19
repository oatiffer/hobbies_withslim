<?php

use League\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Lib\Database;

require_once __DIR__ . '/vendor/autoload.php';

$container = new Container();
$container->add('db', Database::class);

// Application creation
AppFactory::setContainer($container);
$app = AppFactory::create();

try {
    $twig = Twig::create('views', ['cache' => false]);
} catch (\Twig\Error\LoaderError $e) {
}

$app->add(TwigMiddleware::create($app, $twig));
$app->add(new MethodOverrideMiddleware());

// End application creation

// Route get root
$app->get('/', function (Request $request, Response $response, $args) {
    $response = $response->withStatus(302);

    return $response->withHeader('Location', '/users');
});

// Route get all users
$app->get('/users', function (Request $request, Response $response, $args) {
    $db = $this->get('db');
    $allUsers = $db->fetchAll();

    $view = Twig::fromRequest($request);

    return $view->render($response, 'users/index.html', ['allUsers' => $allUsers]);
});

// Route get create-user view
$app->get('/users/create', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();

    if (isset($params['errors'])) {
        $errors = $params['errors'];
    }

    $view = Twig::fromRequest($request);

    return $view->render($response, 'users/create.html', ['errors' => $errors]);
});


// Route post new user
$app->post('/users', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();

    $firstName = !empty($params['first_name']) ? $params['first_name'] : null;
    $lastName = !empty($params['last_name']) ? $params['last_name'] : null;
    $hobbies = !empty($params['hobbies']) ? $params['hobbies'] : null;

    $errors = null;

    if (!$firstName) {
        $errors['first_name'] = 'First name required';
    }

    if (!$lastName) {
        $errors['last_name'] = 'Last name required';
    }

    if (!$hobbies) {
        $errors['hobbies'] = 'Please select a hobbie';
    }

    if (isset($errors)) {
        $errorURL = '/users/create?' . http_build_query(['errors' => $errors]);

        $response = $response->withStatus(302);

        return $response->withHeader('Location', $errorURL);
    }

    $db = $this->get('db');
    $db->add([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'hobbies' => $hobbies
    ]);

    $response = $response->withStatus(302);

    return $response->withHeader('Location', '/users');
});

//Route get edit-user view
$app->get('/users/{id}/edit', function (Request $request, Response $response, $args) {
    $id = !empty($args['id']) ? $args['id'] : null;

    $db = $this->get('db');
    $user = $db->search($id);

    if (!$id || !$user) {
        $response = $response->withStatus(302);

        return $response->withHeader('Location', '/users');
    }

    $params = $request->getQueryParams();

    if (isset($params['errors'])) {
        $errors = $params['errors'];
    }

    $view = Twig::fromRequest($request);

    return $view->render($response, 'users/edit.html', [
        'id' => $id,
        'user' => $user,
        'errors' => $errors
    ]);
});

// Route edit user
$app->patch('/users/{id}', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();
    $id = $args['id'];

    $firstName = !empty($params['first_name']) ? $params['first_name'] : null;
    $lastName = !empty($params['last_name']) ? $params['last_name'] : null;
    $hobbies = !empty($params['hobbies']) ? $params['hobbies'] : null;

    $errors = null;

    if (!$firstName) {
        $errors['first_name'] = 'First name required';
    }

    if (!$lastName) {
        $errors['last_name'] = 'Last name required';
    }

    if (!$hobbies) {
        $errors['hobbies'] = 'Please select a hobbie';
    }

    if (isset($errors)) {
        $errorURL = "/users/$id/edit?" . http_build_query(['errors' => $errors]);

        $response = $response->withStatus(302);

        return $response->withHeader('Location', $errorURL);
    }

    $db = $this->get('db');
    $db->update(
        $id,
        [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'hobbies' => $hobbies
        ]
    );

    $response = $response->withStatus(302);

    return $response->withHeader('Location', '/users');
});

// Route delete user
$app->delete('/users/{id}', function (Request $request, Response $response, $args) {
    $id = !empty($args['id']) ? $args['id'] : null;

    $db = $this->get('db');
    $user = $db->search($id);

    if (!$id || !$user) {
        $response = $response->withStatus(302);

        return $response->withHeader('Location', '/users');
    }

    $db->delete($id);

    $response = $response->withStatus(302);

    return $response->withHeader('Location', '/users');
});


$app->run();