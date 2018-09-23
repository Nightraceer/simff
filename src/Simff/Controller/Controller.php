<?php

namespace Simff\Controller;


use http\Env\Request;
use ReflectionMethod;
use Simff\Helpers\SmartProperties;
use Simff\Main\Simff;

class Controller
{
    use SmartProperties;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var string|null Default action
     */
    public $defaultAction;

    public function __construct($request)
    {
        $this->_request = $request;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function run($action = null, $params = [])
    {
        if (!$action) {
            $action = $this->defaultAction;
        } else {
            $action = $action . 'Action';
        }
        $this->beforeAction($action, $params);
        if (method_exists($this, $action)) {
            return $this->runAction($action, $params);
        } else {
            $class = self::class;
            throw new \Exception("There is no action {$action} in controller {$class}");
        }
    }

    public function runAction($action, $params = [])
    {
        $method = new ReflectionMethod($this, $action);
        $ps = [];
        $response = null;
        if ($method->getNumberOfParameters() > 0) {
            foreach ($method->getParameters() as $param) {
                $name = $param->getName();
                if (isset($params[$name])) {
                    if ($param->isArray()) {
                        $ps[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                    } elseif (!is_array($params[$name])) {
                        $ps[] = $params[$name];
                    } else {
                        return false;
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    $ps[] = $param->getDefaultValue();
                } else {
                    $class = self::class;
                    throw new \Exception("Param {$name} for action {$action} in controller {$class} must be defined. Please, check your routes.");
                }
            }
            $response = $method->invokeArgs($this, $ps);
        } else {
            $response = $this->{$action}();
        }

        return $response;
    }

    /**
     * @param string $template Path to template
     * @param array $params
     * @return string
     */
    public function render($template, $params = [])
    {
        return Simff::app()->template->render($template, $params);
    }

    public function redirect($url, $data = [], $status = 302)
    {
        $this->request->redirect($url, $data, $status);
    }

    public function refresh()
    {
        $this->request->refresh();
    }

    public function beforeAction($action, $params)
    {
    }

    public function error($code = 404, $message = null)
    {
        throw new \Exception($code, $message);
    }

    public function jsonResponse($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}