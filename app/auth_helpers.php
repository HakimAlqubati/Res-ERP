<?php

/**
 * check current user if branch manager
 */

if (!function_exists('isAttendance')) {
    function isAttendance()
    {
        return auth()->user()->isAttendance();
    }
}
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin()
    {
        return auth()->user()->isSuperAdmin();
    }
}
if (!function_exists('isBranchManager')) {
    function isBranchManager()
    {
        if (auth()->check()) {
            return auth()->user()->isBranchManager();
        }
        return false;
    }
}

if (!function_exists('isSystemManager')) {
    function isSystemManager()
    {
        return auth()?->user()?->isSystemManager();
    }
}

if (!function_exists('isStoreManager')) {
    function isStoreManager()
    {
        return auth()->user()->isStoreManager();
    }
}

if (!function_exists('isMaintenanceManager')) {
    function isMaintenanceManager()
    {
        return auth()->user()->isMaintenanceManager();
    }
}

if (!function_exists('isStuff')) {
    function isStuff()
    {
        return auth()?->user()?->isStuff();
    }
}
