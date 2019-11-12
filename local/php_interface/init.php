<?php
@require 'classes/queryIblockElements.php';

if (!function_exists('ib_elements') && CModule::IncludeModule('iblock')) {
    /**
     *
     * @return \Geezo\queryIblockElements
     */
    function ib_elements()
    {
        return new \Geezo\queryIblockElements();
    }
}