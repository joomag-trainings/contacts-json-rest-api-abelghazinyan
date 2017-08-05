<?php

    namespace Controller;

    use Slim\Http\Request;
    use Slim\Http\Response;

    class Contact extends AbstractController
    {
        /**
         * @var \PDO
         */
        private $connection;

        public function __construct($container)
        {
            parent::__construct($container);
            $this->connection = $this->container->get('connection');

        }

        public function getContacts(Request $request, Response $response, $args)
        {

            $page = $request->getParam('page');
            $size = $request->getParam('size');

            if ($page == null || $size == null) {
                return $response->withStatus(400);
            } elseif (($page < 1) || (size < 0)) {
                return $response->withStatus(400);
            }

            $start = ($page - 1) * $size;
            $limit = $size;

            $statement=$this->connection->prepare("SELECT * FROM contacts LIMIT {$limit} OFFSET {$start}");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $statement=$this->connection->prepare("SELECT COUNT(*) FROM contacts");
            $statement->execute();
            $count = $statement->fetch(\PDO::FETCH_ASSOC);

            $count = $count['COUNT(*)'];

            $output['data'] = $res;

            if ($page != 1) {
                $prev = $page - 1;
                $paging['previous'] = "/contacts?page={$prev}&size={$size}";
            }
            if ($start + $limit < $count) {
                $next = $page + 1;
                $paging['next'] = "/contacts?page={$next}&size={$size}";
            }

            $output['paging'] = $paging;

            if (!empty($output)) {
                $response = $response->withJson($output ,200);
            } else {
                $response = $response->withStatus(204);
            }

            return $response;
        }

        public function getContactById(Request $request, Response $response, $args)
        {
            $id = $args['id'];
            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement=$this->connection->prepare("SELECT * FROM contacts WHERE id='{$id}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            if (!empty($res)) {
                $response = $response->withJson($res,200);
            } else {
                $response = $response->withStatus(204);
            }

            return $response;
        }

        public function postContact(Request $request, Response $response, $args)
        {
            $json =  $request->getBody()->getContents();
            $array = json_decode($json, true);

            $statement = $this->connection->prepare(
                "INSERT INTO contacts (id, fname, lname, email, star) VALUES (NULL, :fname, :lname, :email, :star)"
            );

            if (!isset($array['fname']) || !isset($array['lname']) || !isset($array['email']) || !isset($array['star'])) {
                return $response->withStatus(400);
            }

            if ((strlen($array['fname']) > 50) || (strlen($array['lname']) > 50) || (strlen($array['email']) > 50)) {
                return $response->withStatus(400);
            }

            $fname = $array['fname'];
            $lname = $array['lname'];
            $email = $array['email'];
            $star = $array['star'];
            if ($star != 0 && $star !=1) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }


            $statement->bindParam('fname',$fname);
            $statement->bindParam('lname',$lname);
            $statement->bindParam('email',$email);
            $statement->bindParam('star', $star);

                $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $id = $this->connection->lastInsertId();

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            $response = $response->withJson($res,201);

            return $response;
        }

        public function putContactById(Request $request, Response $response, $args)
        {
            $id = $args['id'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
               return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $json =  $request->getBody()->getContents();
            $array = json_decode($json, true);

            if (!isset($array['fname']) || !isset($array['lname']) || !isset($array['email']) || !isset($array['star'])) {
                return $response->withStatus(400);
            }

            if ((strlen($array['fname']) > 50) || (strlen($array['lname']) > 50) || (strlen($array['email']) > 50)) {
                return $response->withStatus(400);
            }

            $fname = $array['fname'];
            $lname = $array['lname'];
            $email = $array['email'];
            $star = $array['star'];
            if ($star != 0 && $star !=1) {
                return $response->withJson(['message' => 'star can be only 1 or 0'], 400);
            }

            $statement = $this->connection->prepare(
                "UPDATE contacts SET fname='{$fname}', lname='{$lname}', email='{$email}', star='{$star}' WHERE id='{$id}'"
            );

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            $response = $response->withJson($res,200);

            return $response;
        }

        public function patchContactById(Request $request, Response $response, $args)
        {
            $id = $args['id'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);

            }

            $json =  $request->getBody()->getContents();
            $array = json_decode($json, true);

            $query = "UPDATE contacts SET ";

            if (!isset($array['fname']) && !isset($array['lname']) && !isset($array['email']) && !isset($array['star'])) {
                return $response->withStatus(400);
            }

            if ((strlen($array['fname']) > 50) || (strlen($array['lname']) > 50) || (strlen($array['email']) > 50)) {
                return $response->withStatus(400);
            }

            $period = false;
            if (isset($array['fname'])) {
                $fname = $array['fname'];
                $query .= "fname='{$fname}'";
                $period = true;
            }

            if (isset($array['lname'])) {
                $lname = $array['lname'];
                if ($period) {
                    $query .= ",";
                }
                $period = true;
                $query .= "lname='{$lname}'";
            }

            if (isset($array['email'])) {
                if ($period) {
                    $query .= ",";
                }
                $period = true;
                $email = $array['email'];
                $query .= "email='{$email}'";
            }

            if (isset($array['star'])) {
                $star = $array['star'];
                if ($star != 0 && $star !=1) {
                    return $response->withJson(['message' => 'star can be only 1 or 0'], 400);
                }
                if ($period) {
                    $query .= ",";
                }
                $query .= "star='{$star}'";
            }

            $query .= "WHERE id='{$id}'";

            $statement = $this->connection->prepare($query);

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            $response = $response->withJson($res,200);

            return $response;
        }

        public function deleteContactById(Request $request, Response $response, $args)
        {

            $id = $args['id'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement=$this->connection->prepare("DELETE FROM contacts where id='{$id}'");
            $res = $statement->execute();

            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $response = $response->withStatus(204);

            return $response;
        }

        public function starContactById(Request $request, Response $response, $args){

            $id = $args['id'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }


            $statement = $this->connection->prepare(
                "UPDATE contacts SET star='1' WHERE id='{$id}'"
            );

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $response = $response->withStatus(200);

            return $response;
        }

        public function UnStarContactById(Request $request, Response $response, $args){

            $id = $args['id'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }


            $statement = $this->connection->prepare(
                "UPDATE contacts SET star='0' WHERE id='{$id}'"
            );

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $response = $response->withStatus(200);

            return $response;
        }

    }