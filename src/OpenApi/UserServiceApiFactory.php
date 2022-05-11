<?php
namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\RequestBody;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;

class UserServiceApiFactory implements OpenApiFactoryInterface
{
    private $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        foreach ($openApi->getPaths()->getPaths() as $key => $path) {
            if($path->getGet() && $path->getGet()->getSummary() == 'services'){

                $openApi->getPaths()->addPath($key, $path->withGet(null));
            }
        }

        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ]);

       
        $schemas = $openApi->getComponents()->getSchemas();
        // dd($schemas);
         $schemas['UserServices'] =  new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'services' => [
                    'type' => 'object',
                    'example' => [
                            "/api/services/1",
                            "/api/services/2",
                        
                    ]
                ]
            ]
        ]);

        $schemas['Services'] =  new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'services' => [
                    'type' => 'array',
                    'readOnly' => true,
                ]
            ]
        ]);
        
        $pathItem = new PathItem(
            post: new Operation(
                operationId: 'postServices',
                tags: ['User'],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/UserServices',
                            ]
                        ]
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'Add services to user',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/User-write.User',
                                ]
                            ]
                        ]
                    ]
                ]
            )
     );

        

    

    $openApi->getPaths()->addPath('/api/post_services', $pathItem);
    return $openApi;

    }
}