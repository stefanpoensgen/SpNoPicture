<?php

namespace SpNoPicture\Bundle\StoreFrontBundle;

use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService;
use Shopware\Components\Plugin\CachedConfigReader;

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
     * @var string
     */
    private $pluginName;

    /**
     * @var CachedConfigReader
     */
    private $config;

    /**
     * ListProductServiceDecorator constructor.
     * @param ListProductServiceInterface $coreService
     * @param MediaService $mediaService
     * @param CachedConfigReader $config
     * @param $pluginName
     */
    public function __construct(
        ListProductServiceInterface $coreService,
        MediaService $mediaService,
        CachedConfigReader $config,
        $pluginName
    ) {
        $this->coreService = $coreService;
        $this->mediaService = $mediaService;
        $this->pluginName = $pluginName;
        $this->config = $config->getByPluginName($pluginName);
    }


    /**
     * {@inheritdoc}
     */
    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        $products = $this->coreService->getList($numbers, $context);
        $noPicture = $this->mediaService->get($this->config['mediaID'], $context);

        foreach ($products as $product) {
            if ($product->getCover() === null) {
                $product->setCover($noPicture);
            }
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function get($number, Struct\ProductContextInterface $context)
    {
        $products = $this->getList([$number], $context);

        return array_shift($products);
    }
}
