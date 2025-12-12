<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MergeApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidocs:merge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge model-specific API documentation into the main api-docs.json file!';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiDocsPath = storage_path('api-docs/api-docs.json');
        $apiDocs = json_decode(file_get_contents($apiDocsPath), true);

        $modelFiles = glob(storage_path('api-docs/models/*.json'));
        $apiDocs['openapi'] = "3.0.0";
        $apiDocs['info'] = [
            'title'       => 'Backend Application Api',
            'version'     => '1.0',
            'description' => 'On every index request using per_page=$number as a parameter will add a pagination override. If the parameter is not present in the request the results will be still paginated with 25 per page. If the request needs to return multiple relationships the query parameter should be like: ?include=relationship1,relationship2. On every request that is sent you can use the include, filter and sorting query parameters. All of these query parameters are defined in a models index or get request.',

        ];
        $apiDocs['paths'] = [];

        foreach ($modelFiles as $file) {
            $modelJson = json_decode(file_get_contents($file), true);
            $baseName = basename($file, '.json');

            $this->mergeDefinitions($modelJson, $baseName);
            $this->mergeParameters($modelJson, $baseName);

            // Merge model paths into api-docs paths
            foreach ($modelJson as $path => $methods) {
                foreach ($methods as $method => $details) {
                    $apiDocs['paths'][$path][$method] = $details;
                }
            }
        }

        file_put_contents($apiDocsPath, json_encode($apiDocs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('API documentation has been merged successfully.');
    }

    private function mergeDefinitions(&$modelJson, $baseName)
    {
        $definitionsPath = storage_path("api-docs/models/definitions/{$baseName}.json");
        if (file_exists($definitionsPath)) {
            foreach ($modelJson as $path => &$methods) {
                foreach ($methods as $method => &$details) {
                    $definitionsContent = json_decode(file_get_contents($definitionsPath), true);
                    $code = match (true) {
                        isset($details['responses']['200']) => 200,
                        isset($details['responses']['201']) => 201,
                        isset($details['responses']['204']) => 204,
                        default => null,
                    };

                    $responses = &$details['responses']["{$code}"];
                    $definitionsContent['content']['application/json']['schema']['type'] = 'object';

                    if ($code == 200 || $code == 201) {
                        $responseData = [
                            'meta' => [
                                'type'       => 'object',
                                'properties' => [
                                    'code'    => [
                                        'type'    => 'integer',
                                        'example' => $code
                                    ],
                                    'message' => [
                                        'type'    => 'string',
                                        'example' => 'Success'
                                    ]
                                ]
                            ],
                            'data' => [
                                'type'  => 'array',
                                'items' => $definitionsContent['content']['application/json']['schema']
                            ]
                        ];

                        if(isset($details['responses']["{$code}"]['do_not_generate_definition'])){
                            unset($responseData['data']);
                        }

                        $definitionsContent['content']['application/json']['schema']['properties'] = $responseData;
                    } elseif ($code == 204) {
                        $definitionsContent['content']['application/json']['schema']['properties'] = [
                            'meta' => [
                                'type'       => 'object',
                                'properties' => [
                                    'code'    => [
                                        'type'    => 'integer',
                                        'example' => $code
                                    ],
                                    'message' => [
                                        'type'    => 'string',
                                        'example' => 'No Content'
                                    ]
                                ]
                            ]
                        ];
                    }

                    if (is_array($responses) && isset($responses['description'])) {
                        $responses = array_merge(
                            ['description' => $responses['description']],
                            $definitionsContent,
                            $responses
                        );
                    }
                }
            }
        }
    }

    private function mergeParameters(&$modelJson, $baseName)
    {
        foreach ($modelJson as $path => &$methods) {
            foreach ($methods as $method => &$details) {
                $paramFileName = "{$baseName}-{$this->getMethodSuffix($baseName, $path, $method)}.json";
                $paramFilePath = storage_path("api-docs/models/parameters/{$paramFileName}");

                if (file_exists($paramFilePath)) {
                    $paramsContent = json_decode(file_get_contents($paramFilePath), true);
                    if (isset($paramsContent['parameters'])) {
                        $details['parameters'] = $paramsContent['parameters'];
                    }
                }
            }
        }
    }

    private function getMethodSuffix($baseName, $path, $method)
    {
        $suffixes = [
            "{$baseName}" => [
                "/api/{$baseName}" => ['get' => 'index', 'post' => 'store'],
                "/api/{$baseName}/{id}" => ['get' => 'show', 'put' => 'update', 'delete' => 'delete']
            ]
        ];

        return $suffixes[$baseName][$path][$method] ?? '';
    }
}
