<?php
abstract class AbstractInfo
{
    abstract static public function suggest($sugTitle, $sugYear, &$result);
    abstract static public function load($id, &$result);
}
?>