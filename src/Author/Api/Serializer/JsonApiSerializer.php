<?php

namespace GisClient\Author\Api\Serializer;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\PagedResultDto;

class JsonApiSerializer
{
    /**
     * @var DtoHydrator
     */
    private $dtoHydrator;

    /**
     * @var DtoSerializer
     */
    private $dtoSerializer;

    public function __construct(
        ?DtoHydrator $dtoHydrator = null,
        ?DtoSerializer $dtoSerializer = null
    ) {
        $this->dtoHydrator = $dtoHydrator ?: new DtoHydrator();
        $this->dtoSerializer = $dtoSerializer ?: new DtoSerializer();
    }

    /**
     * @return JsonApiDto
     */
    public function deserializeRequestBody($content, $expectedType)
    {
        return $this->dtoHydrator->hydrateDocument((string) $content, (string) $expectedType);
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeResource(JsonApiDto $resource)
    {
        return [
            'data' => $this->dtoSerializer->serialize($resource),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeCollection(PagedResultDto $collection)
    {
        $data = [];
        foreach ($collection->getItems() as $item) {
            $data[] = $this->dtoSerializer->serialize($item);
        }

        return [
            'data' => $data,
            'meta' => [
                'total' => $collection->getTotal(),
                'limit' => $collection->getLimit(),
                'offset' => $collection->getOffset(),
            ],
        ];
    }
}
