<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use App\Tools;

$app->get('/api/v1/albums', function (Request $request, Response $response,array $args) {
    $this->logger->addInfo('Se accedio al modulo /api/v1/albums');

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
        $client = new Client();
        $tools = new App\Tools($client, $this->logger);

        $arrayDes = $tools->conseguirAlbumes($album);

        function ordenarAlbumes($array) {
            $a["name"] = $array["name"];
            $a["released"] = $array["release_date"];
            $a["tracks"] = $array["total_tracks"];
            $a["cover"] = $array["images"];
    
            return $a;
        }
    
        $albumes = array_map("ordenarAlbumes", $arrayDes);

        $code = empty($albumes) ? 204 : 200;
        
        return $response->withJson($albumes, $code);
        
    } catch (RequestException $e) {
        $this->logger->addError('Error'. Psr7\Message::toString($e->getResponse()));
        $err["request"] = Psr7\Message::toString($e->getRequest());
        if ($e->hasResponse()) {
            $err["response"] = Psr7\Message::toString($e->getResponse());
            return $response->withJson($err, $e->getResponse()->getStatusCode());
        }
    }

});

$app->run();
