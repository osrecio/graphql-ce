<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver\Field;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use GraphQL\Language\AST\FieldNode;

/**
 * Calculate max depth of fields for query.
 */
class DepthResolver
{
    /**
     * @param ResolveInfo $info
     * @return int
     */
    public function getMaxDepth(ResolveInfo $info)
    {
        $fields = [];
        foreach ($info->fieldNodes as $fieldNode) {
            $fields[$fieldNode->name->value] = $this->calculateMaxDepth($fieldNode);
        }

        return !empty($fields) ? max($fields) - 1 : 0;
    }

    /**
     * Calculate max depth for field.
     *
     * @param FieldNode $fieldNode
     * @return int
     */
    private function calculateMaxDepth(FieldNode $fieldNode)
    {
        $depth = 0;
        if (!empty($fieldNode->selectionSet)) {
            $depth++;
            foreach ($fieldNode->selectionSet->selections as $selectionNode) {
                if ($selectionNode instanceof FieldNode) {
                    $depth += $this->calculateMaxDepth($selectionNode);
                }
            }
        }

        return $depth;
    }
}
