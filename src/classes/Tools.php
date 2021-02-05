<?php
namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException as Exception;
use Psr\Http\Message\RequestInterface as Request;
use GuzzleHttp\Pool;
use Monolog\Logger;

class Tools {

    public function __construct(Client $client, Logger $logger){
        $this->client = $client;
        $this->logger = $logger;
    }
    
    function conseguirAlbumes($album) {
        //Obtengo el acces token
        $access_token = $this->autentificar();
        $this->logger->addInfo('Se obtuvo la access token');
        // Obtengo una lista de todos los artistas matcheables con ese nombre
        $id = $this->obtenerIdArtista($album, $access_token);
        $this->logger->addInfo('Se obtuvo el id del artista especifico');
        //Busco los albumes de ese artista (Solo Albumes, y solo disponibles en el mercado de Argentina)
        $arrayDes = $this->findAlbumes($id, $access_token);
        $this->logger->addInfo('Se obtuvo la lista de albums');
        return $arrayDes;
    }

    function autentificar(){
        $auth = $this->client->post('https://accounts.spotify.com/api/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => '1202313a89ba4cc390d095749a0750cc',
                'client_secret' => '392b74b35e0644cd9a2d27c16255f199',]
        ]);

        $access_token = json_decode($auth->getBody(), true)['access_token'];

        return $access_token;
    }

    function obtenerIdArtista($album, $access_token) {
        $artist = $this->client->get('https://api.spotify.com/v1/search', [
            'query' => ['q' => $album, 'type' => 'artist', 'limit' => 1, 'offset' => 0],         
            'headers' => ["Content-Type: application/json", 'Authorization' => "Bearer {$access_token}"]
        ])->getBody()->getContents();

        if(empty(json_decode($artist, true)["artists"]["items"])) {
            $this->logger->addError("No se encontraron bandas con ese nombre");
            throw new \Exception("No se encontraron bandas con ese nombre", 204);
        } else {
            $id = json_decode($artist, true)["artists"]["items"][0]["id"];
        }
        
        return $id;
    }

    function findAlbumes($id, $access_token){
        $album_list = $this->client->get('https://api.spotify.com/v1/artists/'.$id.'/albums', [
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

    function obtenerRestoDeAlbumes($offset, $n, $id, $access_token) {
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
