<?php

declare(strict_types=1);

namespace App\Forms;

use App\Helpers\FormHelper;
use Nette\SmartObject;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Add upload form factory
 * @package App\Forms
 */
class SearchFormFactory
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
   * Creates and renders sign in form
   * @param callable $onSuccess
   * @return Form
   */
  public function create(callable $onSuccess): Form
  {
    $form = $this->formFactory->create();

    $label = $form->addText('expression', '')
        ->setHtmlAttribute('style', 'display: inline-block; width: 80%')
        ->addRule(Form::MAX_LENGTH, 'Hľadný výraz môže mať maximálne 255 znakov.', 255);
    $button = $form->addSubmit('search', 'H')
        ->setHtmlAttribute('style', 'display: inline-block');
    FormHelper::setBootstrapFormRenderer($form);

    $label->setHtmlAttribute('class', 'form-control form-control-sm');
    $button->setHtmlAttribute('class', 'btn btn-primary btn-sm');

    $form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess) {
      $onSuccess($form, $values);
    };

    return $form;
  }
}