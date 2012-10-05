<?php
namespace Sauce\Sausage;

abstract class WebDriverTestCase extends \PHPUnit_Extensions_Selenium2TestCase
{

    protected $start_url = '';
    protected $base_url = null;
    protected $is_local_test = false;

    public function setUp()
    {
        $caps = $this->getDesiredCapabilities();
        if (!isset($caps['name'])) {
            $caps['name'] = get_called_class().'::'.$this->getName();
            $this->setDesiredCapabilities($caps);
        }
        $this->setBrowserUrl($this->start_url);
    }

    public function setupSpecificBrowser($params)
    {
        // Setting 'local' gives us nice defaults of localhost:4444
        $local = (isset($params['local']) && $params['local']);
        $this->is_local_test = $local;

        if (!$local)
            SauceTestCommon::RequireSauceConfig();

        // Give some nice defaults
        if (!isset($params['seleniumServerRequestsTimeout']))
            $params['seleniumServerRequestsTimeout'] = 60;

        if (!isset($params['browserName'])) {
            $params['browserName'] = 'chrome';
            $params['desiredCapabilities'] = array(
                'version' => '',
                'platform' => 'VISTA'
            );
        }


        // Set up host

        $host = isset($params['host']) ? $params['host'] : false;
        if ($local) {
            $this->setHost($host ? $host : 'localhost');
        } else {
            $sauce_host = SAUCE_USERNAME.':'.SAUCE_ACCESS_KEY.'@ondemand.saucelabs.com';
            $this->setHost($host ? $host : $sauce_host);
        }

        // Set up port
        $port = isset($params['port']) ? $params['port'] : false;
        $this->setPort($port ? $port : ($local ? 4444 : 80));

        // Set up other params
        $this->setBrowser($params['browserName']);
        $caps = isset($params['desiredCapabilities']) ? $params['desiredCapabilities'] : array();
        $this->setDesiredCapabilities($caps);
        $this->setSeleniumServerRequestsTimeout(
            $params['seleniumServerRequestsTimeout']);

        // If we're using Sauce, make sure we don't try to share browsers
        if (!$local && !$host && isset($params['sessionStrategy'])) {
            $params['sessionStrategy'] = 'isolated';
        }

        $this->setUpSessionStrategy($params);
    }

    public function isTextPresent($text, \PHPUnit_Extensions_Selenium2TestCase_Element $element = NULL)
    {
        $element = $element ?: $this->byCssSelector('body');
        $el_text = str_replace("\n", " ", $element->text());
        return strpos($el_text, $text) !== false;
    }

    public function waitForText($text, \PHPUnit_Extensions_Selenium2TestCase_Element $element = NULL,
        $timeout = 10)
    {
        $element = $element ?: $this->byCssSelector('body');
        $test = function() use ($element, $text) {
            $el_text = str_replace("\n", " ", $element->text());
            return strpos($el_text, $text) !== false;
        };
        $this->spinWait("Text $text never appeared!", $test, array(), $timeout);
    }


    public function assertTextPresent($text, \PHPUnit_Extensions_Selenium2TestCase_Element $element = NULL)
    {
        if ($element === NULL)
            $element = $this->byCssSelector('body');

        $this->assertContains($text, $element->text());
    }

    public function assertTextNotPresent($text, \PHPUnit_Extensions_Selenium2TestCase_Element $element = NULL)
    {
        $element = $element ?: $this->byCssSelector('body');
        $this->assertNotContains($text, $element->text());
    }

    public function sendKeys(\PHPUnit_Extensions_Selenium2TestCase_Element $element, $keys)
    {
        $element->click();
        $this->keys($keys);
    }

    public function tearDown()
    {
        if (!$this->is_local_test)
            SauceTestCommon::ReportStatus($this->getSessionId(), !$this->hasFailed());
    }

    public function spinAssert($msg, $test, $args=array(), $timeout=10)
    {
        list($result, $msg) = SauceTestCommon::SpinAssert($msg, $test, $args, $timeout);
        $this->assertTrue($result, $msg);
    }

    public function spinWait($msg, $test, $args=array(), $timeout=10)
    {
        list($result, $msg) = SauceTestCommon::SpinAssert($msg, $test, $args, $timeout);
        if (!$result)
            throw new \Exception($msg);
    }

    public function url($url = null)
    {
        if ($url && $this->base_url !== null && !preg_match("/^http(s):/", $url)) {
            if ($url[0] == '/') {
                $sep = '';
            } else {
                $sep = '/';
            }
            $url = trim($this->base_url, '/').$sep.$url;
        }
        return parent::url($url);
    }

}
