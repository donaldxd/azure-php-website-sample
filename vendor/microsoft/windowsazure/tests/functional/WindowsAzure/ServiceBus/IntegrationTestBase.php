<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   Tests\Functional\WindowsAzure\ServiceBus
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */

namespace Tests\Functional\WindowsAzure\ServiceBus;

use Tests\Framework\FiddlerFilter;
use Tests\Framework\ServiceBusRestProxyTestBase;
use WindowsAzure\Common\Internal\Utilities;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use WindowsAzure\ServiceBus\Models\QueueInfo;

class IntegrationTestBase extends ServiceBusRestProxyTestBase
{
    public function setUp()
    {
        parent::setUp();
        $fiddlerFilter = new FiddlerFilter();
        $this->restProxy = $this->restProxy->withFilter($fiddlerFilter);
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::initialize();
    }

    /**
     * @covers WindowsAzure\ServiceBus\ServiceBusRestProxy::createQueue
     * @covers WindowsAzure\ServiceBus\ServiceBusRestProxy::deleteQueue
     * @covers WindowsAzure\ServiceBus\ServiceBusRestProxy::receiveQueueMessage
     */
    public static function initialize()
    {
        $inst = new IntegrationTestBase();
        $inst->setUp();
        $restProxy = $inst->restProxy;
        $testAlphaExists = false;
        $queues = $restProxy->listQueues()->getQueueInfos();
        foreach($queues as $queue)  {
            $queueName = $queue->getTitle();
            if (Utilities::startsWith($queueName, 'Test') || Utilities::startsWith($queueName, 'test')) {
                if (strtolower($queueName) == strtolower('TestAlpha')) {
                    $testAlphaExists = true;
                    $count = $queue->getQueueDescription()->getMessageCount();
                    for ($i = 0; $i != $count; $i++) {
                        $opts = new ReceiveMessageOptions();
                        $opts->setTimeout(20);
                        try {
                            $restProxy->receiveQueueMessage($queueName, $opts);
                        } catch (\Exception $ex) {
                            error_log($ex->getMessage());
                        }
                    }
                } else {
                    try {
                        $restProxy->deleteQueue($queueName);
                    } catch (\Exception $ex) {
                        error_log($ex->getMessage());
                    }
                }
            }
        }
        foreach($restProxy->listTopics()->getTopicInfos() as $topic)  {
            $topicName = $topic->getTitle();
            if (Utilities::startsWith($topicName, 'Test') || Utilities::startsWith($topicName, 'test')) {
                try {
                    $restProxy->deleteTopic($topicName);
                } catch (\Exception $ex) {
                    error_log($ex->getMessage());
                }
            }
        }

        if (!$testAlphaExists) {
            try {
                $restProxy->createQueue(new QueueInfo('TestAlpha'));
            } catch (\Exception $ex) {
                error_log($ex->getMessage());
            }
        }
    }
}


