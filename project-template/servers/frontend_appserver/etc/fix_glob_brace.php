<?php
/**
 * Alpine does not define GLOB_BRACE, and there is a bug in Zend Framework that
 * depends on this, so a notice is generated. This is a bug that was fixed a
 * while back, but it is present in all Magento 2 versions, and, notably, breaks
 * setup:static-content:deploy.
 *
 * This will be removed when Magento 2 updates their damned dependencies.
 */
define('GLOB_BRACE', 0);
