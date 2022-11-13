<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Email\Model\ResourceModel\Template as TemplateResource;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Framework\Mail\TemplateInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var TemplateResource $templateResource */
$templateResource = $objectManager->get(TemplateResource::class);
/** @var TemplateInterfaceFactory $templateFactory */
$templateFactory = $objectManager->get(TemplateInterfaceFactory::class);
/** @var TemplateInterface $template */
$template = $templateFactory->create();

$content = <<<HTML
<!--@vars {
"var message|escape|nl2br":"Invitation Message",
"var message|escape|nl2br":"Message"
} @-->

{{depend message}}
    {{var message|escape|nl2br}}
{{/depend}}

<p>{{trans "Custom invitation template"}}</p>
HTML;

$template->setTemplateCode('custom_invitation_template')
    ->setTemplateText($content)
    ->setTemplateType(TemplateInterface::TYPE_HTML);
$templateResource->save($template);
