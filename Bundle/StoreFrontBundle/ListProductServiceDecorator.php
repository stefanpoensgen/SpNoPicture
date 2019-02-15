<?php

namespace SpNoPicture\Bundle\StoreFrontBundle;

use Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Components\DependencyInjection\Container as DIContainer;
use Shopware\Components\Model\QueryBuilder;
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
     * @param string                      $pluginName
     * @param CachedConfigReader          $config
     */
    public function __construct(
        DIContainer $container,
        ListProductServiceInterface $coreService,
        MediaService $mediaService,
        string $pluginName,
        CachedConfigReader $config
    ) {
        $this->container = $container;
        $this->coreService = $coreService;
        $this->mediaService = $mediaService;
        $this->pluginName = $pluginName;
        $this->config = $config;
    }

    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        $config = $this->config->getByPluginName($this->pluginName, $this->container->get('shop'));

        $products = $this->coreService->getList($numbers, $context);
        $virtualPath = $config['virtualPath'];

        if (!$virtualPath) {
            return $products;
        }

        /** @var QueryBuilder $builder */
        $query = $this->container->get('dbal_connection')->createQueryBuilder();
        $query->select('id')
            ->from('s_media', 'media')
            ->where('path = :path')
            ->setParameter('path', $virtualPath);

        $mediaId = $query->execute()->fetch(\PDO::FETCH_COLUMN);
        if (!$mediaId) {
            return $products;
        }

        /** @var Struct\Media $noPicture */
        $noPicture = $this->mediaService->get($mediaId, $context);
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
