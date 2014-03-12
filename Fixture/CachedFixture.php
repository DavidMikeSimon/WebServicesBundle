<?php

namespace AC\WebServicesBundle\Fixture;

use \Faker;

abstract class CachedFixture
{
    private $faker;
    private $currentModel;
    private $currentObject;
    private $currentGenerateIndex;
    private $currentGenerateCount;
    private $generated;

    abstract protected function loadImpl($container);
    abstract protected function getFixtureObjectManager();
    abstract protected function getNamespaceAliases($objMan);

    abstract protected function fixture();

    public function __construct()
    {
        $this->faker = Faker\Factory::create();
    }

    public function loadInto($container)
    {
        $this->loadImpl($container);
        return $this->generated;
    }

    protected function fake()
    {
        return $this->faker;
    }

    protected function reallyNull()
    {
        return new NullStandIn;
    }

    protected function curObject()
    {
        return $this->currentObject;
    }

    protected function idx()
    {
        return $this->currentGenerateIndex;
    }

    protected function remaining()
    {
        return $this->currentGenerateCount - $this->currentGenerateIndex;
    }

    protected function fetchCorresponding($model)
    {
        return $this->retrieveFromGenerated($model, function($fixture, $objects) {
            if (count($objects) > $fixture->currentGenerateCount) {
                $curModel = $fixture->currentModel;
                throw new \Exception(
                    "You're not generating enough $curModel to associate with every $model"
                );
            }
            $idx = $fixture->idx() % count($objects);
            return $objects[$idx];
        });
    }

    protected function fetchRandom($model)
    {
        return $this->retrieveFromGenerated($model, function($fixture, $objects) {
            return $fixture->fake()->randomElement($objects);
        });
    }

    private function retrieveFromGenerated($model, $fn)
    {
        $objMan = $this->getFixtureObjectManager();
        if (!isset($this->generated[$model])) {
            throw new \Exception("You haven't generated any $model yet in this fixture");
        }

        $objects = $this->generated[$model];
        $obj = call_user_func(\Closure::bind($fn, $this), $this, $objects);

        $objMan->refresh($obj);
        return $obj;
    }

    protected function generate($n, $model, $fields)
    {
        $this->currentModel = $model;
        $objMan = $this->getFixtureObjectManager();
        $clsName = $this->removeNamespaceAlias($objMan, $model);

        $this->currentGenerateCount = $n;
        for ($i = 0; $i < $n; ++$i) {
            $this->currentObject = new $clsName;
            $this->currentGenerateIndex = $i;

            foreach ($fields as $key => $field) {
                if (!is_callable($field)) {
                    throw new \Exception(
                        "Can't use a non-function for fixture $model field $key"
                    );
                }
                mt_srand(hexdec(substr(md5("$clsName-$key-$i"), 0, 8)));
                $value = call_user_func($field, $this);
                if (is_null($value)) {
                    throw new \Exception(
                        "Got null for $model $key, maybe you forgot 'return' or 'reallyNull()'"
                    );
                } elseif ($value instanceof NullStandIn) {
                    $value = null;
                }
                call_user_func([$this->currentObject, "set" . ucfirst($key)], $value);
            }

            $objMan->persist($this->currentObject);
            $objMan->flush();

            $this->generated[$model][] = $this->currentObject;
            foreach ($this->getModelAncestors($objMan, $model) as $a) {
                $this->generated[$a][] = $this->currentObject;
            }

            $this->currentObject = null;
            $this->currentGenerateIndex = null;
        }
        $this->currentGenerateCount = null;
        $this->currentModel = null;
    }

    protected function withLoadingMessage($msg, $func)
    {
        $clsName = get_called_class();
        print "\n$clsName: " . ucfirst($msg) . ", please wait...";
        ob_flush();
        $func();
        print " OK!\n";
        ob_flush();
    }

    protected function execFixture()
    {
        $this->withLoadingMessage("building fixture template",
            function () {
                $this->currentModel = null;
                $this->currentObject = null;
                $this->currentGenerateIndex = null;
                $this->currentGenerateCount = null;
                $this->generated = [];
                $this->fixture();
            }
        );
    }

    private function removeNamespaceAlias($objMan, $cls)
    {
        if (strpos($cls, ':') !== false) {
            list($nsAlias, $shortCls) = explode(':', $cls);
            $aliases = $this->getNamespaceAliases($objMan);
            if (isset($aliases[$nsAlias])) {
                return $aliases[$nsAlias] . '\\' . $shortCls;
            } else {
                throw new \LogicException("Unknown model namespace $nsAlias");
            }
        }

        return $cls;
    }

    private function getModelAncestors($objMan, $cls)
    {
        $aliases = null;
        if (strpos($cls, ':') !== false) {
            $cls = $this->removeNamespaceAlias($objMan, $cls);
            $aliases = $this->getNamespaceAliases($objMan);
        }
        $r = [];

        while (true) {
            $cls = get_parent_class($cls);
            if ($cls === false) { break; }
            $name = $cls;
            if (!is_null($aliases)) {
                foreach ($aliases as $alias => $fullNs) {
                    $fullNs = trim($fullNs, '\\');
                    if (strpos($cls, $fullNs) === 0) {
                        $name = str_replace($fullNs . '\\', $alias . ':', $cls);
                        break;
                    }
                }
            }
            $r[] = $name;
        }

        return $r;
    }
}
