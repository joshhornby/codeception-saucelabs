<?php
namespace Codeception\Extension;

require_once 'vendor/autoload.php';

use Sauce\Sausage\SauceAPI;

/**
 * Class SauceExtension
 *
 * @author Josh Hornby <mail@susanne-moog.de>
 * @license MIT
 */
class SauceExtension extends \Codeception\Platform\Extension
{
    static $events = array(
        'test.before'  => 'beforeTest',
        'test.fail'    => 'testFailed',
        'test.error'   => 'testFailed',
        'test.success' => 'testSuccess',
    );

    protected $sauceApi;

    public function & sauceApi()
    {
        if (!($this->sauceApi instanceof \Sauce\Sausage\SauceAPI)) {
            $this->sauceApi = new \Sauce\Sausage\SauceAPI($this->config['username'], $this->config['accesskey']);
        }
        return $this->sauceApi;
    }

    public function beforeTest(\Codeception\Event\TestEvent $e)
    {
        $s = new SauceAPI($this->config['username'], $this->config['accesskey']);
        $test = $e->getTest();
        $newestTest = $this->getFirstJob($s);
        $job = $this->getCorrespondingSaucelabsJob($e);

        try {
            $build = $this->config['build'];
        } catch (\Exception $e) {
            $build = date('d-M-Y');
        }
        $metadata = $this->gatherMetaData($test);
        $s->updateJob($newestTest['id'], array('name' => $test->getName(), 'build' => $build, 'tags' => $metadata['tags'],
        'custom-data' => $metadata['custom-data']));
    }

    protected function getCorrespondingSaucelabsJob()
    {
        $jobs = $this->sauceApi()->getJobs($from = 0, $to = null, $limit = 1, $skip = null, $username = null);
        return $jobs['jobs'][0];
    }

    public function testFailed(\Codeception\Event\FailEvent $e)
    {
        $s = new SauceAPI($this->config['username'], $this->config['accesskey']);
        $newestTest = $this->getFirstJob($s);
        $s->updateJob($newestTest['id'], array('passed' => false));
    }

    public function testSuccess(\Codeception\Event\TestEvent $e)
    {
        $s = new SauceAPI($this->config['username'], $this->config['accesskey']);
        $newestTest = $this->getFirstJob($s);
        $s->updateJob($newestTest['id'], array('passed' => true));
    }

    /**
     * Retrieve the first job from a SauceLabs jobs data set
     *
     * @param SauceAPI $sauceAPI
     * @return array
     */
    private function getFirstJob(SauceAPI $sauceAPI)
    {
        $jobs = $sauceAPI->getJobs(0);
        return $jobs['jobs'][0];
    }

    protected function gatherMetaData($test)
    {
        $metadata = array(
            // Default tags from config
            'tags'        => isset($this->config['tags']) ? explode(",", $this->config['tags']) : array(),
            // Default custom data is empty
            'custom-data' => array(),
        );

        // Tag with the current test string reference
        $metadata['tags'][] = "test:" . $test->toString();

        // Add codeception runtime options to custom-data
        $metadata['custom-data']['options'] = $this->options;

        return $metadata;
    }
}
