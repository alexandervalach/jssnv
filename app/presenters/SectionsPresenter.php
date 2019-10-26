<?php

namespace App\Presenters;

use App\FormHelper;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

class SectionsPresenter extends BasePresenter {

  /** @var ActiveRow */
  private $sectionRow;

  /** @var string */
  private $error = "Section not found!";

  public function actionAll() {
    $this->userIsLogged();
  }

  public function renderAll() {
    $this->template->listOfSections = $this->sectionsRepository->findAll()->order("order DESC");
  }

  public function actionEdit($id) {
    $this->sectionRow = $this->sectionsRepository->findById($id);

    if (!$this->sectionRow) {
      throw new BadRequestException($this->error);
    }
  }

  public function renderEdit($id) {
    $this->template->mainSection = $this->sectionRow;
    $this->getComponent('editForm')->setDefaults($this->sectionRow);
  }

  public function actionAdd() {
    $this->userIsLogged();
  }

  public function renderAdd() { }

  public function actionRemove($id) {
    $this->userIsLogged();
    $this->sectionRow = $this->sectionsRepository->findById($id);

    if (!$this->sectionRow) {
      throw new BadRequestException($this->error);
    }
  }

  public function renderRemove($id) {
    $this->template->mainSection = $this->sectionRow;
    $this->getComponent('removeForm');
  }

  protected function createComponentAddForm() {
    $sections = $this->sectionsRepository->fetchAll();
    $form = new Form;
    $form->addText('name', 'Názov')
        ->setRequired('Názov musí byť vyplnený.')
        ->addRule(Form::MAX_LENGTH, 'Názov môže mať maximálne 50 znakov.', 50);
    $form->addSelect('section_id', 'Sekcie', $sections);
    $form->addText('url', 'URL adresa');

    $form->addCheckbox('homeUrl', ' URL na tejto stránke')
        ->setDefaultValue(0);

    $form->addText('order', 'Poradie')
        ->setRequired(true)
        ->setDefaultValue(50)
        ->addRule(Form::INTEGER, 'Poradie môže byť len celé číslo.');

    $form->addCheckbox('visible', ' Viditeľné v bočnom menu')
        ->setDefaultValue(1);

    $form->addCheckbox('sliding', ' Rolovacie menu');
    $form->addSubmit('save', 'Uložiť');

    $form->onSuccess[] = [$this, 'submittedAddForm'];
    FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  protected function createComponentEditForm() {
    $form = new Form;

    $form->addText('name', 'Názov')
        ->setRequired('Názov musí byť vyplnený.')
        ->addRule(Form::MAX_LENGTH, 'Názov môže mať maximálne 50 znakov.', 50);

    $form->addText('url', 'URL adresa');

    $form->addCheckbox('homeUrl', ' URL na tejto stránke');

    $form->addText('order', 'Poradie')
        ->setRequired('Poradie musí byť vyplnené')
        ->addRule(Form::INTEGER, 'Poradie môže byť len celé číslo.');

    $form->addCheckbox('visible', ' Viditeľné v bočnom menu');
    $form->addCheckbox('sliding', ' Rolovacie menu');

    $form->addSubmit('save', 'Uložiť')
        ->onClick[] = [$this, 'submittedEditForm'];

    $form->addSubmit('cancel', 'Zrušiť')
        ->setHtmlAttribute('class', 'btn btn-warning')
        ->onClick[] = [$this, 'formCancelled'];

    FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  protected function createComponentRemoveForm() {
    $form = new Form;

    $form->addSubmit('cancel', 'Zrušiť')
        ->setHtmlAttribute('class', 'btn btn-warning')
        ->onClick[] = [$this, 'formCancelled'];

    $form->addSubmit('remove', 'Odstrániť')
        ->setHtmlAttribute('class', 'btn btn-danger')
        ->onClick[] = [$this, 'submittedRemoveForm'];

    FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  public function submittedAddForm(Form $form, ArrayHash $values) {
    $this->userIsLogged();

    $values->offsetSet('section_id', $values->section_id === 0 ? null : $values->section_id);
    // dump($values);
    $section = $this->sectionsRepository->insert($values);

    if (empty($values->url)) {
      $postData = array(
        'section_id' => $section->id,
        'name' => $values['name']
      );

      $this->postsRepository->insert($postData);
      $this->flashMessage('Sekcia bola pridaná');
      $this->redirect('Posts:show#primary', $section->id);
    } else {
      $this->flashMessage('Sekcia bola pridaná');
      $this->redirect('all#primary');
    }
  }

  public function submittedEditForm(SubmitButton $btn) {
    $this->userIsLogged();

    $values = $btn->form->getValues();
    $this->sectionRow->update($values);
    $this->flashMessage('Sekcia bola upravená');
    $this->redirect('all#primary');
  }

  public function submittedRemoveForm() {
    $this->userIsLogged();

    if ($this->sectionRow->url == NULL || $this->sectionRow->url == "") {
        $post = $this->sectionRow->related('post')->fetch();
        $post->delete();
    }

    $this->sectionRow->delete();
    $this->flashMessage('Sekcia bola odstránená');
    $this->redirect('all#primary');
  }

  public function formCancelled() {
    $this->redirect('all#primary');
  }

}