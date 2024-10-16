<?php

namespace TrueLayer\Connect\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class CacheType extends TagScope
{
    /**
     * Cache type code unique among all cache types
     * @var string
     */
    public const TYPE_IDENTIFIER = 'truelayer';

    /**
     * The tag name that limits the cache cleaning scope within a particular tag
     * @var string
     */
    public const CACHE_TAG = 'TRUELAYER';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}