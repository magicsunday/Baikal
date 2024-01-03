<?php

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://formal.codr.fr
#
#  This script is part of the Formal project. The Formal
#  project is free software; you can redistribute it
#  and/or modify it under the terms of the GNU General Public
#  License as published by the Free Software Foundation; either
#  version 2 of the License, or (at your option) any later version.
#
#  The GNU General Public License can be found at
#  http://www.gnu.org/copyleft/gpl.html.
#
#  This script is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  This copyright notice MUST APPEAR in all copies of the script!
#################################################################

namespace Formal;

use Exception;
use Flake\Core\Model;
use Flake\Util\Tools;
use Formal\Core\Message;
use Formal\Form\Morphology;
use LogicException;
use ReflectionException;
use RuntimeException;

use function array_key_exists;
use function call_user_func;
use function is_array;

/**
 *
 */
class Form
{
    protected string $sModelClass = '';
    protected array $aOptions = [
        'action'          => '',
        'close'           => true,
        'closeurl'        => '',
        'hook.validation' => false,
        'hook.morphology' => false,
    ];
    protected ?Model $oModelInstance = null;
    protected ElementCollection $oElements;
    protected array $aErrors = [];
    protected ?bool $bPersisted = null;        # TRUE when form has persisted; available only after execute

    protected string $sDisplayTitle = '';        # Displayed form title; generated in setModelInstance()
    protected string $sDisplayMessage = '';    # Displayed confirm message; generated in execute()

    protected ?Morphology $oMorpho = null;

    /**
     * @param       $sModelClass
     * @param array $aOptions
     */
    public function __construct($sModelClass, array $aOptions = [])
    {
        $this->sModelClass = $sModelClass;
        $this->aOptions = array_merge($this->aOptions, $aOptions);
        $this->oElements = new ElementCollection();
    }

    /**
     * @param $sName
     *
     * @return mixed
     */
    public function option($sName)
    {
        if (array_key_exists($sName, $this->aOptions)) {
            return $this->aOptions[$sName];
        }

        throw new RuntimeException("\Formal\Form->option(): Option '" . htmlspecialchars($sName) . "' not found.");
    }

    /**
     * @param string $sName
     * @param string $sValue
     *
     * @return $this
     */
    public function setOption(string $sName, string $sValue): Form
    {
        $this->aOptions[$sName] = $sValue;

        return $this;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->aOptions;
    }

    /**
     * @return Morphology|null
     */
    public function getMorpho(): ?Morphology
    {
        if ($this->oMorpho !== null) {
            return $this->oMorpho;
        }

        $this->oMorpho = $this->modelInstance()->formMorphologyForThisModelInstance();

        # Calling validation hook if defined
        if (($aHook = $this->option('hook.morphology')) !== false) {
            call_user_func($aHook, $this, $this->oMorpho);
        }

        return $this->oMorpho;
    }

    /**
     * @param Model $oModelInstance
     *
     * @return $this
     * @throws ReflectionException
     */
    public function setModelInstance(Model $oModelInstance): Form
    {
        if (!Tools::is_a($oModelInstance, $this->sModelClass)) {
            throw new RuntimeException(
                "\Formal\Core->setModelInstance(): Given instance is not of class '" . $this->sModelClass . "'"
            );
        }

        $this->oModelInstance = $oModelInstance;

        $this->oElements->reset();
        foreach ($this->oElements as $oElement) {
            $oElement->setValue(
                $this->modelInstance()->get(
                    $oElement->option('prop')
                )
            );
        }

        # Displayed form title is generated depending on modelInstance floatingness

        if ($this->floatingModelInstance()) {
            $this->sDisplayTitle = 'Creating new<i class=' . $this->modelInstance()->mediumicon(
                ) . '></i><strong>' . $this->modelInstance()->humanName() . '</strong>';
        } else {
            # This is changed if form is persisted, after persistance, to reflect possible change in model instance label
            $this->sDisplayTitle = 'Editing ' . $this->modelInstance()->humanName(
                ) . '<i class=' . $this->modelInstance()->mediumicon() . '></i><strong>' . $this->modelInstance(
                )->label() . '</strong>';
        }

        return $this;
    }

    /**
     * @return Model|null
     */
    public function modelInstance(): ?Model
    {
        return $this->oModelInstance;
    }

    /**
     * @return bool
     */
    public function floatingModelInstance(): bool
    {
        return $this->modelInstance()->floating();
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        # Obtaining morphology from model object
        $oMorpho = $this->getMorpho();

        $this->aErrors = [];
        $oMorpho->elements()->reset();
        foreach ($oMorpho->elements() as $oElement) {
            # If element is readonly, skip process
            if ($oElement->option('readonly')) {
                continue;
            }

            $sPropName = $oElement->option('prop');

            # posted value is fetched, then passes to element before persistance
            if ($oElement->posted()) {
                $sPostValue = $this->postValue($sPropName);
                $oElement->setValue($sPostValue);

                $sValue = $oElement->value();

                $this->modelInstance()->set(
                    $sPropName,
                    $sValue
                );
            } else {
                $oElement->setValue(
                    $this->modelInstance()->get(
                        $sPropName
                    )
                );
            }
        }

        $oMorpho->elements()->reset();
        foreach ($oMorpho->elements() as $oElement) {
            $aValidation = $oElement->optionArray('validation');
            if (empty($aValidation)) {
                continue;
            }

            $sValue = $oElement->value();

            foreach ($aValidation as $sValidation) {
                # If element is readonly, skip process
                if ($oElement->option('readonly')) {
                    continue;
                }

                $sParam = false;
                if (str_contains($sValidation, ':')) {
                    $sValidation = strtok($sValidation, ':');
                    $sParam = strtok(':');
                }

                $sMethod = 'validate' . ucfirst(strtolower($sValidation));
                if (!method_exists($this, $sMethod)) {
                    throw new RuntimeException(
                        "\Formal\Form::execute(): no validation method for '" . htmlspecialchars($sValidation) . "'"
                    );
                }

                if ($sParam !== false) {
                    $mValid = $this->$sMethod($sValue, $oMorpho, $oElement, $sParam);
                } else {
                    $mValid = $this->$sMethod($sValue, $oMorpho, $oElement);
                }

                if ($mValid !== true) {
                    $this->declareError($oElement, $mValid);
                    break;    # one error per element per submit
                }
            }
        }

        # Calling validation hook if defined
        if (($aHook = $this->option('hook.validation')) !== false) {
            call_user_func($aHook, $this, $oMorpho);
        }

        if (!$this->refreshed() && empty($this->aErrors)) {
            # Model object is persisted
            # Last chance to generate a confirm message corresponding to what *was* submitted ("Creating", instead of "Editing")

            if ($this->floatingModelInstance()) {
                $this->sDisplayMessage = Message::notice(
                    $this->modelInstance()->humanName() . " <i class='" . $this->modelInstance()->icon(
                    ) . "'></i> <strong>" . $this->modelInstance()->label() . '</strong> has been created.',
                    '',
                    false
                );
                $bWasFloating = true;
            } else {
                $bWasFloating = false;
                $this->sDisplayMessage = Message::notice(
                    "Changes on <i class='" . $this->modelInstance()->icon() . "'></i> <strong>" . $this->modelInstance(
                    )->label() . '</strong> have been saved.',
                    false,    # No title
                    false    # No close button
                );
            }

            $this->modelInstance()->persist();
            if ($bWasFloating === false) {
                # Title is generated now, as submitted data might have changed the model instance label
                $this->sDisplayTitle = 'Editing ' . $this->modelInstance()->humanName(
                    ) . '<i class=' . $this->modelInstance()->mediumicon() . '></i><strong>' . $this->modelInstance(
                    )->label() . '</strong>';
            }
            $this->bPersisted = true;
        } else {
            $this->bPersisted = false;
        }
    }

    # public, as it may be called from a hook

    /**
     * @param Element $oElement
     * @param string  $sMessage
     *
     * @return void
     */
    public function declareError(Element $oElement, string $sMessage = ''): void
    {
        $this->aErrors[] = [
            'element' => $oElement,
            'message' => $sMessage,
        ];

        $oElement->setOption('error', true);
    }

    /**
     * @return bool|null
     */
    public function persisted(): ?bool
    {
        if ($this->submitted()) {
            if ($this->bPersisted === null) {
                throw new RuntimeException(
                    "\Formal\Form->persisted(): information is not available yet. This method may only be called after execute()"
                );
            }

            return $this->bPersisted;
        }

        return false;
    }

    /**
     * @param            $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     *
     * @return true|string
     */
    public function validateRequired($sValue, Morphology $oMorpho, Element $oElement): true|string
    {
        if (trim($sValue) !== '') {
            return true;
        }

        return '<strong>' . $oElement->option('label') . '</strong> is required.';
    }

    /**
     * @param            $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     *
     * @return true|string
     */
    public function validateEmail($sValue, Morphology $oMorpho, Element $oElement): true|string
    {
        if (Tools::validEmail($sValue)) {
            return true;
        }

        return '<strong>' . $oElement->option('label') . '</strong> should be an email.';
    }

    /**
     * @param            $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     * @param            $sReferencePropName
     *
     * @return true|string
     */
    public function validateSameas($sValue, Morphology $oMorpho, Element $oElement, $sReferencePropName): true|string
    {
        $sReferenceValue = $oMorpho->element($sReferencePropName)->value();
        if ($sValue === $sReferenceValue) {
            return true;
        }

        return '<strong>' . $oElement->option('label') . '</strong> does not match ' . $oMorpho->element(
                $sReferencePropName
            )->option(
                'label'
            ) . '.';
    }

    /**
     * @param string     $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     *
     * @return true|string
     * @throws Exception
     */
    public function validateUnique(string $sValue, Morphology $oMorpho, Element $oElement): true|string
    {
        $oModelInstance = $this->modelInstance();

        $oRequest = $oModelInstance
            ->getBaseRequester()
            ->addClauseEquals(
                $oElement->option('prop'),
                $sValue
            );

        if (!$oModelInstance->floating()) {
            # checking id only if model instance is not floating
            $oRequest->addClauseNotEquals(
                $oModelInstance::PRIMARYKEY,
                $oModelInstance->get(
                    $oModelInstance::PRIMARYKEY
                )
            );
        }

        $oColl = $oRequest->execute();

        if ($oColl->count() > 0) {
            return '<strong>' . $oElement->option(
                    'label'
                ) . '</strong> has to be unique. Given value is not available.';
        }

        return true;
    }

    /**
     * @param            $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     *
     * @return true|string
     */
    public function validateTokenid($sValue, Morphology $oMorpho, Element $oElement): true|string
    {
        if (!preg_match("/^[a-z0-9\-_]+$/", $sValue)) {
            return '<strong>' . $oElement->option(
                    'label'
                ) . '</strong> is not valid. Allowed characters are digits, lowercase letters, the dash and underscore symbol.';
        }

        return true;
    }

    /**
     * @param            $sValue
     * @param Morphology $oMorpho
     * @param Element    $oElement
     *
     * @return true|string
     */
    public function validateColor($sValue, Morphology $oMorpho, Element $oElement): true|string
    {
        if (!empty($sValue) && !preg_match('/^#[a-fA-F0-9]{6}([a-fA-F0-9]{2})?$/', $sValue)) {
            return '<strong>' . $oElement->option(
                    'label'
                ) . "</strong> is not a valid color with format '#RRGGBB' or '#RRGGBBAA' in hexadecimal values.";
        }

        return true;
    }

    /**
     * @param $sPropName
     *
     * @return mixed|string
     */
    public function postValue($sPropName): mixed
    {
        $aData = Tools::POST('data');

        if (is_array($aData) && array_key_exists($sPropName, $aData)) {
            return $aData[$sPropName];
        }

        return '';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $aHtml = [];

        $oMorpho = $this->getMorpho();

        $oMorpho->elements()->reset();
        foreach ($oMorpho->elements() as $oElement) {
            # Setting current prop value for element
            # Set on empty (just created) FormMorphology
            # And obtained from Model instance

            $oElement->setValue(
                $this->modelInstance()->get(
                    $oElement->option('prop')
                )
            );

            $aHtml[] = $oElement->render();
        }

        $elements = implode("\n", $aHtml);
        $sModelClass = $this->sModelClass;

        ######################################################
        # Displaying messages
        ######################################################

        if ($this->submitted()) {
            # There were errors detected during execute()
            # Error messages are displayed

            if (!empty($this->aErrors)) {
                $this->sDisplayMessage = '';
                $aMessages = [];
                reset($this->aErrors);
                foreach ($this->aErrors as $aError) {
                    if (trim($aError['message']) === '') {
                        continue;
                    }

                    $aMessages[] = $aError['message'];
                }

                $this->sDisplayMessage = Message::error(
                    implode('<br />', $aMessages),
                    'Validation error'
                );
            }
        }

        $sSubmittedFlagName = $this->submitSignatureName();
        if ($this->option('close') === true) {
            $sCloseUrl = $this->option('closeurl');
            $sCloseButton = '<a class="btn" href="' . $sCloseUrl . '">Close</a>';
        } else {
            $sCloseButton = '';
        }

        if (!isset($_SESSION['CSRF_TOKEN'])) {
            throw new LogicException(
                'A CSRF token must be set in the session. Try clearing your cookies and logging in again'
            );
        }
        $csrfToken = htmlspecialchars($_SESSION['CSRF_TOKEN']);

        $sActionUrl = $this->option('action');

        return <<<HTML
<form class="form-horizontal" action="{$sActionUrl}" method="post" enctype="multipart/form-data">
    <input type="hidden" name="{$sSubmittedFlagName}" value="1" />
    <input type="hidden" name="refreshed" value="0" />
    <input type="hidden" name="CSRF_TOKEN" value="{$csrfToken}" />
    <fieldset>
        <legend style="line-height: 40px;">{$this->sDisplayTitle}</legend>
        {$this->sDisplayMessage}
        {$elements}
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            {$sCloseButton}
        </div>
    </fieldset>
</form>
HTML;
    }

    /**
     * @return array|string
     */
    protected function submitSignatureName(): array|string
    {
        return str_replace('\\', '_', $this->sModelClass . '::submitted');
    }

    /**
     * @return bool
     */
    public function submitted(): bool
    {
        return (int)Tools::POST($this->submitSignatureName()) === 1;
    }

    /**
     * @return bool
     */
    public function refreshed(): bool
    {
        return (int)Tools::POST('refreshed') === 1;
    }
}
