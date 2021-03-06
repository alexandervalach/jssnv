<?php

declare(strict_types=1);

namespace App\Forms;

use App\Helpers\FormHelper;
use Nette\SmartObject;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Add and edit notice form factory
 * @package App\Forms
 */
class NoticeFormFactory
{
  use SmartObject;

  /** @var FormFactory */
  private $formFactory;

  /**
   * @param FormFactory $factory
   */
  public function __construct(FormFactory $factory)
  {
    $this->formFactory = $factory;
  }

  /**
   * Creates and renders add notice form
   * @param callable $onSuccess
   * @return Form
   */
  public function create(callable $onSuccess): Form
  {
    $form = $this->formFactory->create();

    $form->addText('title', 'Názov')
        ->setRequired()
        ->addRule(Form::MAX_LENGTH, '%label môže mať maximálne %value znakov.', 255);

    $form->addTextArea('content', 'Text')
        ->setHtmlAttribute('id', 'text-editor')
        ->addRule(Form::MAX_LENGTH, '%label môže mať maximálne %value znakov.', 10000);

    $form->addCheckbox('on_homepage', ' Zobraziť na domovskej stránke');

    $form->addSubmit('save', 'Uložiť');

    $form->addSubmit('cancel', 'Zrušiť')
        ->setHtmlAttribute('class', 'btn btn-warning')
        ->setHtmlAttribute('data-dismiss', 'modal');

    FormHelper::setBootstrapFormRenderer($form);

    $form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess) {
      $onSuccess($form, $values);
    };

    return $form;
  }
}