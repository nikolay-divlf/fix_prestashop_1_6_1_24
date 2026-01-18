<?php

class AdminController extends AdminControllerCore
{
    public function initBreadcrumbs($tab_id = null, $tabs = null)
    {
        if (is_null($tabs)) {
            $tabs = [];
        }
        parent::initBreadcrumbs($tab_id, $tabs);
    }

    public function getModulesList($filter_modules_list)
    {
        if (!$filter_modules_list) {
            $filter_modules_list = [];
        }
        return parent::getModulesList($filter_modules_list);
    }
}