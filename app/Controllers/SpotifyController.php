<?php
namespace App\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException as Exception;
use Monolog\Logger;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class SpotifyController {

    public function __construct(Client $client, Logger $logger){
        $this->client = $client;
        $this->logger = $logger;
    }
    
    public function get(Request $request, Response $response, $args = []) {

        if(isset($request->getQueryParams()['q'])) {
            if(!empty($request->getQueryParams()['q'])){
                $album = $request->getQueryParams()['q'];
            } else {
                $this->logger->addError('No se mando ningun nombre');
                return $response->withJson("No se mando ninguna nombre", 400);
            }
        } else {
            $this->logger->addError('No se mando ningun parametro');
            return $response->withJson("No se mando ninguna parametro", 400);
        }

        try {
            //Obtengo el acces token
            $access_token = $this->autentificar();    
            $this->logger->addInfo('Se obtuvo la access token');
            // Obtengo una lista de todos los artistas matcheables con ese nombre
            $id = $this->obtenerIdArtista($album, $access_token);
            $this->logger->addInfo('Se obtuvo el id del artista especifico');
            //Busco los albumes de ese artista (Solo Albumes, y solo disponibles en el mercado de Argentina)
            $arrayDes = $this->findAlbumes($id, $access_token);
            $this->logger->addInfo('Se obtuvo la lista de albums');
            //Ordeno los albumes
            $albumes = array_map(function ($array) {
                                        $a["name"] = $array["name"];
                                        $a["released"] = $array["release_date"];
                                        $a["tracks"] = $array["total_tracks"];
                                        $a["cover"] = $array["images"];

                                        return $a;
                                }, $arrayDes);
    
            $code = empty($albumes) ? 204 : 200;
        
            return $response->withJson($albumes, $code);

        } catch (Exception $e) {
            $this->logger->addError('Error'. Psr7\Message::toString($e->getResponse()));
            $err["request"] = Psr7\Message::toString($e->getRequest());
            if ($e->hasResponse()) {
                $err["response"] = Psr7\Message::toString($e->getResponse());
                return $res->withJson($err, $e->getResponse()->getStatusCode());
            }
        }

    }

    private function autentificar(){
        $auth = $this->client->post($_ENV['URL_AUTH'], [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['CLIENT_ID'],
                'client_secret' => $_ENV['CLIENT_SECRET'],
            ]
        ]);

        $access_token = json_decode($auth->getBody(), true)['access_token'];

        return $access_token;
    }

    private function obtenerIdArtista($album, $access_token) {
        $artist = $this->client->get($_ENV['URL_SEARCH'], [
            'query' => ['q' => $album, 'type' => 'artist', 'limit' => 1, 'offset' => 0],         
            'headers' => ["Content-Type: application/json", 'Authorization' => "Bearer {$access_token}"]
        ])->getBody()->getContents();

        if(empty(json_decode($artist, true)["artists"]["items"])) {
            $this->logger->addError("No se encontraron bandas con ese nombre");
        } else {
            $id = json_decode($artist, true)["artists"]["items"][0]["id"];
        }
        
        return $id;
    }

    private function findAlbumes($id, $access_token){
        $album_list = $this->client->get($_ENV['URL_ARTIST'].$id.'/albums', [
            'query' => ['include_groups'=> 'album,single,compilation', 'market' => 'AR','limit' => 50, 'offset' => 0],         
            'headers' => ["Content-Type: application/json", 'Authorization' => "Bearer {$access_token}"]
        ]);

        $arrayDes = json_decode($album_list->getBody()->getContents(), true);
        //Consigo el resto de albumes
        
        if($arrayDes["total"] >= 50) {
            $cantAlbumesFaltantes = $arrayDes["total"] - 50;
            $nDeInteraciones = is_int($cantAlbumesFaltantes/50) ? $cantAlbumesFaltantes/50 : intval($cantAlbumesFaltantes/50) + 1;
            $offset = 50;
            $albumes = $this->obtenerRestoDeAlbumes($offset, $nDeInteraciones, $id, $access_token);
            $todos = array_merge($arrayDes["items"], $albumes);

            return $todos;
        }

        return $arrayDes["items"];
    }

    private function obtenerRestoDeAlbumes($offset, $n, $id, $access_token) {
        for($i = 1; $i <= $n; $i++ ) {
            $requests[] = $this->client->get('https://api.spotify.com/v1/artists/'.$id.'/albums', [
                'query' => ['include_groups'=> 'album,single,compilation', 'market' => 'AR','limit' => 50, 'offset' => $offset],         
                'headers' => ["Content-Type: application/json", 'Authorization' => "Bearer {$access_token}"]
            ]);
            $offset = $offset + 50;
        }
        foreach ($requests as $response) {
            $albumes[] = json_decode($response->getBody()->getContents(), true)["items"];
        }

        $total = array_merge([], ...$albumes);

        return $total;

    }

}
