<?php

namespace App\Presenters;

use App\FormHelper;
use App\Model\NoticeRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;

class NoticePresenter extends BasePresenter {

    /** @var ActiveRow */
    private $noticeRow;

    public function actionAll() {
        
    }

    public function renderAll() {
        $this->template->notices = $this->noticeRepository->findAll()->order('time DESC');
    }

    public function actionAdd() {
        $this->userIsLogged();
    }

    public function renderAdd() {
        $this->getComponent('addForm');
    }

    public function actionEdit($id) {
        $this->userIsLogged();
        $this->noticeRow = $this->noticeRepository->findById($id);
    }

    public function renderEdit($id) {
        $this->template->notice = $this->noticeRow;
        $this->getComponent('editForm')->setDefaults($this->noticeRow);
    }

    protected function createComponentAddForm() {
        $form = new Form;
        $form->addSelect('type', 'Typ', NoticeRepository::$flag);
        $form->addText('name', 'Názov')
                ->setRequired("Názov musí byť vyplnený");
        $form->addTextArea('content', 'Text')
                ->setAttribute('id', 'ckeditor');
        $form->addSubmit('save', 'Zapísať');
        $form->onSuccess[] = $this->submittedAddForm;
        FormHelper::setBootstrapRenderer($form);
        return $form;
    }

    protected function createComponentEditForm() {
        $form = new Form;
        $form->addSelect('type', 'Typ', NoticeRepository::$flag);
        $form->addText('name', 'Názov')
                ->setRequired("Názov musí byť vyplnený");
        $form->addTextArea('content', 'Text')
                ->setAttribute('id', 'ckeditor');
        $form->addSubmit('save', 'Zapísať')
                ->onClick[] = $this->submittedEditForm;
        $form->addSubmit('cancel', 'Zrušiť')
                ->setAttribute('class', 'btn btn-warning')
                ->onClick[] = $this->formCancelled;
        FormHelper::setBootstrapRenderer($form);
        return $form;
    }

    public function submittedAddForm(Form $form) {
        $values = $form->getValues();
        $values['time'] = date('Y-m-d h:i:s');
        $this->noticeRepository->insert($values);
        $this->redirect('all');
    }

    public function submittedEditForm(SubmitButton $btn) {
        $values = $btn->form->getValues();
        $this->noticeRow->update($values);
        $this->redirect('all');
    }

    public function submittedDeleteForm() {
        
    }
    
    public function formCancelled() {
        $this->redirect('all#primary');
    }

}
