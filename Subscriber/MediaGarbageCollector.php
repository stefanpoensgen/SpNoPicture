<?php

namespace SpNoPicture\Subscriber;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Repository as ShopRepository;

class MediaGarbageCollector implements SubscriberInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ConfigReader
     */
    private $pluginConfig;

    /** @var ShopRepository */
    private $shopRepository;

    /**
     * MediaGarbageCollector constructor.
     *
     * @param Connection     $connection
     * @param ConfigReader   $pluginConfig
     * @param ShopRepository $shopRepository
     */
    public function __construct(Connection $connection, ConfigReader $pluginConfig, ShopRepository $shopRepository)
    {
        $this->connection = $connection;
        $this->pluginConfig = $pluginConfig;
        $this->shopRepository = $shopRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Collect_MediaPositions' => 'onCollectMediaPositions',
        ];
    }

    /**
     * @throws \Exception
     */
    public function onCollectMediaPositions()
    {
        $this->connection->exec('CREATE TEMPORARY TABLE IF NOT EXISTS s_media_used (id int auto_increment, mediaId int NOT NULL, PRIMARY KEY pkid (id), INDEX media (mediaId))');

        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->connection->createQueryBuilder();
        $shops = $query->select('id')
            ->from('s_core_shops', 'shops')
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($shops as $shopId) {
            $shop = $this->shopRepository->getActiveById($shopId);

            $shop->registerResources();

            $pluginConfig = $this->pluginConfig->getByPluginName('SpNoPicture', $shop);

            if ($pluginConfig['noPictureId'] !== null) {
                $this->connection->insert('s_media_used', ['mediaId' => $pluginConfig['noPictureId']]);
            }
        }
    }
}
