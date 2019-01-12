<?php

namespace SpNoPicture\Bundle\StoreFrontBundle;

use Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Components\DependencyInjection\Container as DIContainer;
use Shopware\Components\Plugin\CachedConfigReader;

class ListProductServiceDecorator implements ListProductServiceInterface
{
    /**
     * @var DIContainer
     */
    private $container;

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
     *
     * @param DIContainer                 $container
     * @param ListProductServiceInterface $coreService
     * @param MediaService                $mediaService
     * @param CachedConfigReader          $config
     * @param $pluginName
     */
    public function __construct(
        DIContainer $container,
        ListProductServiceInterface $coreService,
        MediaService $mediaService,
        CachedConfigReader $config,
        $pluginName
    ) {
        $this->container = $container;
        $this->coreService = $coreService;
        $this->mediaService = $mediaService;
        $this->pluginName = $pluginName;
        $this->config = $config->getByPluginName($pluginName, $this->container->get('shop'));
    }

    /**
     * {@inheritdoc}
     */
    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        $products = $this->coreService->getList($numbers, $context);
        $mediaID = $this->config['mediaID'];

        if (!$mediaID) {
            return $products;
        }

        /** @var Struct\Media $noPicture */
        $noPicture = $this->mediaService->get($mediaID, $context);
        if (!$noPicture instanceof Struct\Media) {
            return $products;
        }

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
