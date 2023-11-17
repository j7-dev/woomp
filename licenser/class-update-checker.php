<?php

/**
 * wp plugin 更新檢查
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
	$_ENV['GITHUB_REPO'],
	__FILE__,
	$_ENV['APP_SLUG']
);
// $updateChecker->setBranch('master');
$updateChecker->getVcsApi()->enableReleaseAssets();
