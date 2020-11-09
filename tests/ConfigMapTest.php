<?php

namespace RenokiCo\PhpK8s\Test;

use RenokiCo\PhpK8s\Exceptions\KubernetesAPIException;
use RenokiCo\PhpK8s\Kinds\K8sConfigMap;
use RenokiCo\PhpK8s\ResourcesList;

class ConfigMapTest extends TestCase
{
    public function test_config_map_build()
    {
        $cm = $this->cluster->configmap()
            ->setName('settings')
            ->setData(['somekey' => 'somevalue'])
            ->addData('key2', 'val2')
            ->removeData('somekey');

        $this->assertEquals('v1', $cm->getApiVersion());
        $this->assertEquals('settings', $cm->getName());
        $this->assertEquals(['key2' => 'val2'], $cm->getData());
    }

    public function test_config_map_from_yaml()
    {
        $cm = $this->cluster->fromYamlFile(__DIR__.'/yaml/configmap.yaml');

        $this->assertEquals('v1', $cm->getApiVersion());
        $this->assertEquals('settings', $cm->getName());
        $this->assertEquals(['key2' => 'val2'], $cm->getData());
    }

    public function test_config_map_api_interaction()
    {
        $this->runCreationTests();
        $this->runGetAllTests();
        $this->runGetTests();
        $this->runUpdateTests();
        $this->runWatchAllTests();
        $this->runWatchTests();
        $this->runDeletionTests();
    }

    public function runCreationTests()
    {
        $cm = $this->cluster->configmap()
            ->setName('settings')
            ->setData(['somekey' => 'somevalue'])
            ->addData('key2', 'val2')
            ->removeData('somekey');

        $this->assertFalse($cm->isSynced());
        $this->assertFalse($cm->exists());

        $cm = $cm->createOrUpdate();

        $this->assertTrue($cm->isSynced());
        $this->assertTrue($cm->exists());

        $this->assertInstanceOf(K8sConfigMap::class, $cm);

        $this->assertEquals('v1', $cm->getApiVersion());
        $this->assertEquals('settings', $cm->getName());
        $this->assertEquals(['key2' => 'val2'], $cm->getData());
        $this->assertEquals('val2', $cm->getData('key2'));
    }

    public function runGetAllTests()
    {
        $configmaps = $this->cluster->getAllConfigmaps();

        $this->assertInstanceOf(ResourcesList::class, $configmaps);

        foreach ($configmaps as $cm) {
            $this->assertInstanceOf(K8sConfigMap::class, $cm);

            $this->assertNotNull($cm->getName());
        }
    }

    public function runGetTests()
    {
        $cm = $this->cluster->getConfigmapByName('settings');

        $this->assertInstanceOf(K8sConfigMap::class, $cm);

        $this->assertTrue($cm->isSynced());

        $this->assertEquals('v1', $cm->getApiVersion());
        $this->assertEquals('settings', $cm->getName());
        $this->assertEquals(['key2' => 'val2'], $cm->getData());
        $this->assertEquals('val2', $cm->getData('key2'));
    }

    public function runUpdateTests()
    {
        $cm = $this->cluster->getConfigmapByName('settings');

        $this->assertTrue($cm->isSynced());

        $cm->removeData('key2')
            ->addData('newkey', 'newval');

        $cm->createOrUpdate();

        $this->assertTrue($cm->isSynced());

        $this->assertEquals('v1', $cm->getApiVersion());
        $this->assertEquals('settings', $cm->getName());
        $this->assertEquals(['newkey' => 'newval'], $cm->getData());
        $this->assertEquals('newval', $cm->getData('newkey'));
    }

    public function runDeletionTests()
    {
        $cm = $this->cluster->getConfigmapByName('settings');

        $this->assertTrue($cm->delete());

        while ($cm->exists()) {
            sleep(1);
        }

        $this->expectException(KubernetesAPIException::class);

        $this->cluster->getConfigmapByName('settings');
    }

    public function runWatchAllTests()
    {
        $watch = $this->cluster->configmap()->watchAll(function ($type, $configmap) {
            if ($configmap->getName() === 'settings') {
                return true;
            }
        }, ['timeoutSeconds' => 10]);

        $this->assertTrue($watch);
    }

    public function runWatchTests()
    {
        $watch = $this->cluster->configmap()->watchByName('settings', function ($type, $configmap) {
            return $configmap->getName() === 'settings';
        }, ['timeoutSeconds' => 10]);

        $this->assertTrue($watch);
    }
}
