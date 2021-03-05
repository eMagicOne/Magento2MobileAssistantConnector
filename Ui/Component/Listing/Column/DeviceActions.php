<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Emagicone\Mobassistantconnector\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class DeviceActions
 * @package Emagicone\Mobassistantconnector\Ui\Component\Listing\Column
 */
class DeviceActions extends Column
{
    /** Url path */
    const DEVICE_URL_PATH_CHANGE_STATUS = 'mobassistantconnector/userDevice/changeStatus';
    const DEVICE_URL_PATH_DELETE = 'mobassistantconnector/userDevice/delete';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');

                if (isset($item['id'])) {
                    $item[$name]['changeStatus'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::DEVICE_URL_PATH_CHANGE_STATUS,
                            ['id' => $item['id']]
                        ),
                        'label' => __('Enable')
                    ];

                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::DEVICE_URL_PATH_DELETE,
                            ['id' => $item['id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'message' => __('Are you sure you want to delete a record?')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
