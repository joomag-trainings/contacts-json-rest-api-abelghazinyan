<?php

    use \Slim\Http\Request as Request;
    use \Slim\Http\Response as Response;
    use \Database\Connection as Connection;
    use 

    require "../vendor/autoload.php";
    require "../src/Database/Connection.php";


    $config = [
        'settings' => [
            'displayErrorDetails' => true,
            'addContentLengthHeader' => false,
        ],
    ];


    $c = new \Slim\Container($config);
    $app = new \Slim\App($c);

    $container = $app->getContainer();

    $connection = Connection::getInstance()->getConnection();

    $container['connection'] = function() use ($connection) {
        return $connection;
    };

    $app->group('/contacts',function () use ($app) {
            $app->get('', \Controller\Contact::class . ':getContacts');
            $app->post('', \Controller\Contact::class . ':postContact');
            $app->get('/{id}', \Controller\Contact::class . ':getContactById');
            $app->put('/{id}', \Controller\Contact::class . ':putContactById');
            $app->patch('/{id}', \Controller\Contact::class . ':patchContactById');
            $app->delete('/{id}', \Controller\Contact::class . ':deleteContactById');
            $app->delete('/{id}/star', \Controller\Contact::class . ':unStarContactById');
            $app->put('/{id}/star', \Controller\Contact::class . ':starContactById');
    });

    $app->group('/contacts/{id}/notes',function () use ($app) {
            $app->get('', \Controller\Note::class . ':getNotes');
            $app->post('', \Controller\Note::class . ':postNote');
            $app->get('/{nid}', \Controller\Note::class . ':getNoteById');
            $app->put('/{nid}', \Controller\Note::class . ':updateNoteById');
            $app->delete('/{nid}', \Controller\Note::class . ':deleteNoteById');
    });

    //JSON check
    $app->add(function (Request $request, Response $response, $next) {

        $response = $response->withHeader('Content-Type','application/json');

        $method = strtolower($request->getMethod());

        $contentTypeHeader = $request->getHeader('Content-Type');

        if (in_array(
            $method,
            ['post', 'put', 'patch']
        )) {
            if (empty($contentTypeHeader[0])
                || $contentTypeHeader[0] !== 'application/json') {
                $response = $response->withStatus(415);
                return $response;
            }
        }

        $response = $next($request,$response);

        return $response;
    });

    //Authentication
    $app->add(function (Request $request, Response $response, $next) {

        $authUser= $request->getHeader('PHP_AUTH_USER');
        $authPw= $request->getHeader('PHP_AUTH_PW');

        $users = [
            'abel' => '12345678',
            'tigran' => '87654321',
        ];

        if (key_exists($authUser[0], $users)) {
            if ($users[$authUser[0]] == $authPw[0]) {
                $response = $next($request,$response);

                return $response;
            } else {
                $response = $response->withStatus(401)->withHeader('WWW-Authenticate',
                    sprintf('Basic realm="%s"', 'Contact API'));
                return $response;
            }
        } else {
            $response = $response->withStatus(401)->withHeader('WWW-Authenticate',
                sprintf('Basic realm="%s"', 'Contact API'));
            return $response;
        }

    });
    $container['notFoundHandler'] = function ($c) {
        return function ($request, $response) use ($c) {

            $response = $c['response'];

            return $response->withJson(['code' => 404, 'message' => 'Not found'],404);
        };

    };

    $app->run();