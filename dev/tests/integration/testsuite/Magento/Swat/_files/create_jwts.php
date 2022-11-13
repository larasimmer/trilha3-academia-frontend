<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Swat\Model\Jwt;
use Magento\Swat\Model\SwatKeyPair;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\User\Model\User;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Base64Json $json */
$json = $objectManager->get(Base64Json::class);

/** @var ScopeConfigInterface */
$scopeConfig = $objectManager->get(ScopeConfigInterface::class);

/** @var WriterInterface $configWriter */
$configWriter = $objectManager->get(WriterInterface::class);

/** @var SwatKeyPair $swatKeyPair */
$swatKeyPair = $objectManager->get(SwatKeyPair::class);

/** @var User $user */
$adminUser = $objectManager->create(User::class);
$adminUser->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);

// Create valid JWT token
$alg = $scopeConfig->getValue(Jwt::CONFIG_JWT_ALG);
$exp = time() + $scopeConfig->getValue(Jwt::CONFIG_JWT_EXP);
$header = [
    'alg' => $alg,
    'typ' => 'JWT',
    'kid' => 1,
    'iss' => 'test',
    'exp' => $exp
];
$payload = [
    'iss' => 'test',
    'exp' => $exp,
    'sub' => $adminUser->getId()
];
$base64UrlHeader = $json->serialize($header);
$base64UrlPayload = $json->serialize($payload);
openssl_sign(
    $base64UrlHeader . '.' . $base64UrlPayload,
    $signature,
    $swatKeyPair->getPrivateKey(),
    OPENSSL_ALGO_SHA256
);
$base64UrlSignature = base64_encode($signature);
$validJwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
$configWriter->save('valid_jwt', $validJwt);

// Create expired JWT token
$exp = time() - 1; // expired
$header = [
    'alg' => $alg,
    'typ' => 'JWT',
    'kid' => 1,
    'iss' => 'test',
    'exp' => $exp
];
$payload = [
    'iss' => 'test',
    'exp' => $exp,
    'sub' => $adminUser->getId()
];
$base64UrlHeader = $json->serialize($header);
$base64UrlPayload = $json->serialize($payload);
openssl_sign(
    $base64UrlHeader . '.' . $base64UrlPayload,
    $signature,
    $swatKeyPair->getPrivateKey(),
    OPENSSL_ALGO_SHA256
);
$base64UrlSignature = base64_encode($signature);
$expiredJwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
$configWriter->save('expired_jwt', $expiredJwt);

$objectManager->get(TypeListInterface::class)->cleanType('config');
