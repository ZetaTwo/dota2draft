<?php

// load Tonic
require_once '../../../../shared/tonic/src/Tonic/Autoloader.php';

$config = array(
    'load' => array('./resources/*.php'), // load example resources
    #'mount' => array('Dota2Draft' => '/development/dota2draft'), // mount in example resources at URL /nexus
    #'cache' => new Tonic\MetadataCacheFile('/tmp/tonic.cache') // use the metadata cache
    #'cache' => new Tonic\MetadataCacheAPC // use the metadata cache
);

$app = new Tonic\Application($config);

#echo $app; die;

$request = new Tonic\Request();

#echo $request; die;

try {

    $resource = $app->getResource($request);

    #echo $resource; die;

    $response = $resource->exec();

} catch (Tonic\NotFoundException $e) {
    $response = new Tonic\Response(404, $e->getMessage());

} catch (Tonic\UnauthorizedException $e) {
    $response = new Tonic\Response(401, $e->getMessage());
    $response->wwwAuthenticate = 'Basic realm="My Realm"';

} catch (Tonic\Exception $e) {
    $response = new Tonic\Response($e->getCode(), $e->getMessage());
}

#echo $response;

$response->output();
