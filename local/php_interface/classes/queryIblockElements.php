<?php

namespace Geezo;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Data\Cache as BitrixCache;
use \CIBlockElement;

class queryIblockElements
{

    /**
     * @var \CIBlockElement;
     */
    protected $ib;

    /**
     * @var bool
     */
    protected $_nav_params = false;

    /**
     * @var string
     */
    protected $_order = 'id';

    /**
     * @var string
     */
    protected $_order_direction = 'desc';

    /**
     * @var array
     */
    protected $_wheres = [];

    /**
     * @var array
     */
    protected $_fields = ['id', 'name', 'iblock_name', 'iblock_type', 'iblock_id'];

    /**
     * @var array
     */
    protected $_properties = [];

    /**
     * @var bool
     */
    protected $_with_properties = false;

    /**
     * @var array
     */
    protected static $field_names = [];

    /**
     * @var string
     */
    protected $_cache_dir = 'query_iblock_elements';

    /**
     * @var int
     */
    protected $_cache_time = 36000000;

    public function __construct()
    {
        $this->ib = new \CIBlockElement;
        self::$field_names = array_keys(ElementTable::getMap());
    }

    /**
     * @param array|string $field_names
     * @return $this
     */
    public function fields($field_names)
    {
        $field_names = array_map('strtoupper', is_array($field_names) ? $field_names : func_get_args());
        $this->_fields = array_unique($field_names + $this->_fields);

        return $this;
    }

    /**
     * @param array|string $prop_names
     * @return $this
     */
    public function properties($prop_names)
    {
        $prop_names = array_map('strtoupper', is_array($prop_names) ? $prop_names : func_get_args());
        $this->_properties = array_unique($prop_names + $this->_properties);

        return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function where($field, $value)
    {
        if (is_bool($value)) {
            $value = $value ? 'Y' : 'N';
        }

        if (!self::is_field($field)) {
            $this->_wheres[strtoupper(preg_match('/^property_/i', $field) ? $field : 'PROPERTY_' . $field)] = $value;

            return $this;
        }

        $this->_wheres[strtoupper($field)] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function is_field($name)
    {
        return in_array($name, self::$field_names);
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return $this
     */
    public function whereProperty($property, $value)
    {
        if (is_bool($value)) {
            $value = $value ? 'Y' : 'N';
        }

        $this->_wheres[strtoupper(preg_match('/^property_/i',
            $property) ? $property : 'PROPERTY_' . $property)] = $value;

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function filters($conditions)
    {
        $this->_wheres += is_array($conditions) ? $conditions : array();

        return $this;
    }

    /**
     * @return array
     */
    private function getOrder()
    {
        return [$this->_order => $this->_order_direction];
    }

    /**
     * @return array
     */
    private function getFilter()
    {
        return $this->_wheres;
    }

    /**
     * @return bool
     */
    private function getGroupBy()
    {
        return false;
    }

    /**
     * @return array|bool
     */
    private function getNavStartParams()
    {
        return $this->_nav_params;
    }

    /**
     * @return array
     */
    private function getSelectFields()
    {
        $result = array_map('strtoupper', $this->_fields);

        if (!empty($this->_properties)) {
            $result += ['IBLOCK_ID', 'IBLOCK_TYPE'];

            foreach ($this->_properties as $prop) {
                $result[] = strtoupper(preg_match('/^property_/i', $prop) ? $prop : 'PROPERTY_' . $prop);
            }
        }

        $result = array_unique($result);

        return $result;
    }

    /**
     * @param int|bool $value
     *
     * @return $this
     */
    public function limit($value = false)
    {
        $this->_nav_params = ($value === false) ? $value : ["nTopCount" => $value];

        return $this;
    }

    /**
     * @return string
     */
    protected function generateCacheHash()
    {
        $hash = serialize(
            json_encode(
                array_merge(
                    $this->getOrder(),
                    $this->getFilter(),
                    //$this->getNavStartParams(),
                    $this->getSelectFields()
                )
            )
        );

        return $hash ?: '';
    }

    /**
     * @param bool|false $fetch
     *
     * @return array
     */
    public function get($fetch = false)
    {
        $result = [];
        $cache = BitrixCache::createInstance();
        $hash = $this->generateCacheHash();
        if ($cache->initCache($this->_cache_time, $hash, $this->_cache_dir)) {
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $query =
                $this->ib->GetList(
                    $this->getOrder(),
                    $this->getFilter(),
                    $this->getGroupBy(),
                    $this->getNavStartParams(),
                    $this->getSelectFields()
                );

            while ($one = $fetch ? $query->Fetch() : $query->GetNextElement()) {

                if ($fetch) {
                    $element = $one;
                } else {
                    $element = $one->GetFields();
                }

                $result[] = $element;

                if (!$fetch && $this->_with_properties) {
                    $result[key($result)]['PROPERTIES'] = $one->GetProperties();
                }

            }
            //$cache->abortDataCache();
            $cache->endDataCache($result);
        }

        return empty($result) ? [] : $result;
    }

    /**
     * @return array
     */
    public function fetch()
    {
        return $this->get(true);
    }

    /**
     * @return array
     */
    public function fetchOne()
    {
        $res = $this->limit(1)->get(true);

        return empty($res) ? null : reset($res);
    }

    /**
     * @return null|array
     */
    public function getOne()
    {
        $res = $this->limit(1)->get();

        return empty($res) ? null : reset($res);
    }

}