<?php

namespace SpNoPicture\Bundle\StoreFrontBundle;

use Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class ListProductServiceDecorator implements ListProductServiceInterface
{
    /**
     * @var ListProductServiceInterface
     */
    private $coreService;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var array
     */
    private $pluginConfig;

    /**
     * ListProductServiceDecorator constructor.
     *
     * @param ListProductServiceInterface $coreService
     * @param MediaService                $mediaService
     * @param array                       $pluginConfig
     */
    public function __construct(
        ListProductServiceInterface $coreService,
        MediaService $mediaService,
        array $pluginConfig
    ) {
        $this->coreService = $coreService;
        $this->mediaService = $mediaService;
        $this->pluginConfig = $pluginConfig;
    }

    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        $products = $this->coreService->getList($numbers, $context);

        /** @var Struct\Media $noPicture */
        $noPicture = $this->mediaService->get($this->pluginConfig['noPictureId'], $context);
        if (!$noPicture instanceof Struct\Media) {
            return $products;
        }

        foreach ($products as $product) {
            if (!$product->getCover() instanceof Struct\Media) {
                $product->setCover($noPicture);
            }
        }

        return $products;
    }

    /**
     * @param string                         $number
     * @param Struct\ProductContextInterface $context
     *
     * @return mixed|Struct\ListProduct
     */
    public function get($number, Struct\ProductContextInterface $context)
    {
        $products = $this->getList([$number], $context);

        return array_shift($products);
    }
}
