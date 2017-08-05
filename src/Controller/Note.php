<?php

    namespace Controller;

    use Slim\Http\Request;
    use Slim\Http\Response;

    class Note extends AbstractController
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


        public function getNotes(Request $request, Response $response, $args)
        {
            $id = $args['id'];

            $page = $request->getParam('page');
            $size = $request->getParam('size');

            if ($page == null || $size == null) {
                return $response->withStatus(400);
            } elseif (($page < 1) || (size < 0)) {
                return $response->withStatus(400);
            }

            $start = ($page - 1) * $size;
            $limit = $size;

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement=$this->connection->prepare("SELECT * FROM notes WHERE contact_id='{$id}' LIMIT {$limit} OFFSET {$start}");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $statement=$this->connection->prepare("SELECT COUNT(*) FROM notes");
            $statement->execute();
            $count = $statement->fetch(\PDO::FETCH_ASSOC);

            $count = $count['COUNT(*)'];

            $output['data'] = $res;

            if ($page != 1) {
                $prev = $page - 1;
                $paging['previous'] = "/notes?page={$prev}&size={$size}";
            }
            if ($start + $limit < $count) {
                $next = $page + 1;
                $paging['next'] = "/notes?page={$next}&size={$size}";
            }

            $output['paging'] = $paging;

            if (!empty($output)) {
                $response = $response->withJson($output ,200);
            } else {
                $response = $response->withStatus(204);
            }

            return $response;
        }

        public function postNote(Request $request, Response $response, $args)
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

            $statement = $this->connection->prepare(
                "INSERT INTO notes (id, contact_id, note) VALUES (NULL, :id, :note)"
            );

            if (!isset($array['note'])) {
                return $response->withStatus(400);
            }

            if ((strlen($array['note']) > 50)) {
                return $response->withStatus(400);
            }

            $note = $array['note'];

            $statement->bindParam('id',$id);
            $statement->bindParam('note',$note);

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $id = $this->connection->lastInsertId();

            $statement = $this->connection->prepare("SELECT * FROM notes where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            $response = $response->withJson($res,201);

            return $response;
        }

        public function getNoteById(Request $request, Response $response, $args)
        {

            $id = $args['id'];
            $nid = $args['nid'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement = $this->connection->prepare("SELECT * FROM notes where id= '{$nid}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid note ID'], 400);
            }

            $statement=$this->connection->prepare("SELECT * FROM notes WHERE id='{$nid}' AND contact_id='{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($res)) {
                $response = $response->withJson($res ,200);
            } else {
                $response = $response->withStatus(204);
            }

            return $response;
        }

        public function updateNoteById(Request $request, Response $response, $args)
        {

            $id = $args['id'];
            $nid = $args['nid'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement = $this->connection->prepare("SELECT * FROM notes where id= '{$nid}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid note ID'], 400);
            }

            $json =  $request->getBody()->getContents();
            $array = json_decode($json, true);

            if (!isset($array['note'])) {
                return $response->withStatus(400);
            }

            if ((strlen($array['note']) > 50)) {
                return $response->withStatus(400);
            }

            $note = $array['note'];

            $statement = $this->connection->prepare(
                "UPDATE notes SET note='{$note}' WHERE id='{$nid}' AND contact_id='{$id}'"
            );

            $res = $statement->execute();
            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $statement = $this->connection->prepare("SELECT * FROM notes where id= '{$nid}'");
            $statement->execute();
            $res = $statement->fetch(\PDO::FETCH_ASSOC);

            $response = $response->withJson($res,200);


            return $response;
        }

        public function deleteNoteById(Request $request, Response $response, $args)
        {

            $id = $args['id'];
            $nid = $args['nid'];

            $statement = $this->connection->prepare("SELECT * FROM contacts where id= '{$id}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid user ID'], 400);
            }

            $statement = $this->connection->prepare("SELECT * FROM notes where id= '{$nid}'");
            $statement->execute();
            $res = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($res)) {
                return $response->withJson(['message' => 'Invalid note ID'], 400);
            }

            $statement=$this->connection->prepare("DELETE FROM notes where id='{$nid}'");
            $res = $statement->execute();

            if ($res == false) {
                return $response = $response->withStatus(403);
            }

            $response = $response->withStatus(204);

            return $response;

        }

    }