<?php
/*
 * Copyright (c) 2012 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\Html\Renderer\ChainableRendererInterface;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();

$rendererCacheService = InstallUtils::getOrCreateInstance("rendererCacheService", "Mouf\\Utils\\Cache\\InMemoryCache", $moufManager);
if ($moufManager->instanceExists("apcCacheService")) {
	$rendererCacheService->getProperty("chainWith")->setValue($moufManager->getInstanceDescriptor("apcCacheService"));
}

$customRenderer = InstallUtils::getOrCreateInstance("customRenderer", "Mouf\\Html\\Renderer\\FileBasedRenderer", $moufManager);
$customRenderer->getProperty("directory")->setValue("src/templates");
$customRenderer->getProperty("cacheService")->setValue($rendererCacheService);
$customRenderer->getProperty("type")->setValue(ChainableRendererInterface::TYPE_CUSTOM);
$customRenderer->getProperty("priority")->setValue(0);

$defaultRenderer = InstallUtils::getOrCreateInstance("defaultRenderer", "Mouf\\Html\\Renderer\\AutoChainRenderer", $moufManager);
$defaultRenderer->getProperty("cacheService")->setValue($rendererCacheService);

$old = umask(0);
mkdir(ROOT_PATH.'src/templates', 0775, true);
umask($old);

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>