<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Resolver\Field\DepthResolver;

/**
 * Resolve fields for query.
 */
class FieldResolver
{
    /**
     * @var DepthResolver
     */
    private $depthResolver;

    /**
     * @param DepthResolver $depthResolver
     */
    public function __construct(
        DepthResolver $depthResolver
    ) {
        $this->depthResolver = $depthResolver;
    }

    /**
     * Get fields.
     *
     * @param ResolveInfo $info
     * @return array
     */
    public function getFields(ResolveInfo $info)
    {
        $maxDepth = $this->depthResolver->getMaxDepth($info);
        $attributes = $info->getFieldSelection($maxDepth);

        return $this->makeFlat($attributes);
    }

    /**
     * Make a flat array from multi-dimensions.
     *
     * @param array $attributes
     * @return array
     */
    private function makeFlat(array $attributes) {
        $result = [[]];
        foreach ($attributes as $key => $attribute) {
            $result[] = [$key];
            if (is_array($attribute)) {
                $result[] = $this->makeFlat($attribute);
            }
        }
        $result = array_merge(...$result);

        return $result;
    }
}
