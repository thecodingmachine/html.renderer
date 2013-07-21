<?php
/*
 * Copyright (c) 2012 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();

if (!$moufManager->instanceExists("rendererCacheService")) {
	$rendererCacheService = $moufManager->createInstance("Mouf\\Utils\\Cache\\InMemoryCache");
	$rendererCacheService->setName("rendererCacheService");
	if ($moufManager->instanceExists("apcCacheService")) {
		$rendererCacheService->getProperty("chainWith")->setValue($moufManager->getInstanceDescriptor("apcCacheService"));
	}
}

if (!$moufManager->instanceExists("customRenderer")) {
	$customRenderer = $moufManager->createInstance("Mouf\\Html\\Renderer\\FileBasedRenderer");
	$customRenderer->setName("customRenderer");
	$customRenderer->getProperty("directory")->setValue("src/templates");
	$customRenderer->getProperty("cacheService")->setValue($rendererCacheService);
}

$old = umask(0);
mkdir(ROOT_PATH.'src/templates', 0775, true);
umask($old);

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>