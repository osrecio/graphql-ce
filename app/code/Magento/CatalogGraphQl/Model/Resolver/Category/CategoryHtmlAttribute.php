<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Helper\Output as OutputHelper;

/**
 * Resolve rendered content for category attributes where HTML content is allowed
 */
class CategoryHtmlAttribute implements ResolverInterface
{
    /**
     * @var OutputHelper
     */
    private $outputHelper;

    /**
     * @param OutputHelper $outputHelper
     */
    public function __construct(
        OutputHelper $outputHelper
    ) {
        $this->outputHelper = $outputHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $fieldName = $field->getName();
        $description = $value[$fieldName] ?? null;

        return $this->outputHelper->categoryAttribute(null, $description, $fieldName);
    }
}
