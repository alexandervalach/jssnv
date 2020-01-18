<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\BreadcrumbControl;
use App\Helpers\FormHelper;
use App\Model\AlbumsRepository;
use App\Model\NoticesRepository;
use App\Model\SectionsRepository;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Controls\SubmitButton;
use Nette\Application\BadRequestException;

/**
 * Class NoticesPresenter
 * @package App\Presenters
 */
class NoticesPresenter extends BasePresenter
{
  /** @var ActiveRow */
  private $noticeRow;

  /** @var string */
  private $error = "Notice not found";

  /**
   * @var NoticesRepository
   */
  private $noticesRepository;

  /**
   * NoticesPresenter constructor.
   * @param AlbumsRepository $albumsRepository
   * @param SectionsRepository $sectionRepository
   * @param NoticesRepository $noticesRepository
   * @param BreadcrumbControl $breadcrumbControl
   */
  public function __construct(AlbumsRepository $albumsRepository,
                              SectionsRepository $sectionRepository,
                              NoticesRepository $noticesRepository,
                              BreadcrumbControl $breadcrumbControl)
  {
    parent::__construct($albumsRepository, $sectionRepository, $breadcrumbControl);
    $this->noticesRepository = $noticesRepository;
  }

  /**
   *
   */
  public function renderAll() {
    $this->template->notices = $this->noticesRepository->getAll();
  }

  /**
   * @param $id
   * @throws BadRequestException
   * @throws AbortException
   */
  public function actionEdit($id) {
    $this->guestRedirect();
    $this->noticeRow = $this->noticesRepository->findById($id);

    if (!$this->noticeRow) {
      throw new BadRequestException($this->error);
    }

    $this['editForm']->setDefaults($this->noticeRow);
  }

  /**
   * @param $id
   */
  public function renderEdit($id) {
    $this->template->notice = $this->noticeRow;
  }

  /**
   * @param $id
   * @throws BadRequestException
   * @throws AbortException
   */
  public function actionRemove(int $id): void
  {
    $this->guestRedirect();
    $this->noticeRow = $this->noticesRepository->findById($id);

    if (!$this->noticeRow) {
      throw new BadRequestException($this->error);
    }
  }

  /**
   * @param $id
   */
  public function renderRemove($id) {
    $this->template->notice = $this->noticeRow;
  }

  /**
   * @return Form
   */
  protected function createComponentAddForm() {
    $form = new Form;
    $form->addSelect('type', 'Paleta', NoticesRepository::$flag);
    $form->addText('name', 'Názov')
            ->setRequired("Názov musí byť vyplnený");
    $form->addTextArea('content', 'Text')
            ->setHtmlAttribute('id', 'ckeditor');
    $form->addSubmit('save', 'Uložiť');
    $form->addSubmit('cancel', 'Zrušiť')
          ->setHtmlAttribute('class', 'btn btn-large btn-warning')
          ->setHtmlAttribute('data-dismiss', 'modal');

    $form->onSuccess[] = [$this, 'submittedAddForm'];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  /**
   * @return Form
   */
  protected function createComponentEditForm() {
    $form = new Form;
    $form->addSelect('type', 'Paleta', NoticesRepository::$flag);
    $form->addText('name', 'Názov')
            ->setRequired("Názov musí byť vyplnený");
    $form->addTextArea('content', 'Text')
            ->setHtmlAttribute('id', 'ckeditor');
    $form->addSubmit('save', 'Uložiť')
            ->onClick[] = [$this, 'submittedEditForm'];
    $form->addSubmit('cancel', 'Zrušiť')
            ->setHtmlAttribute('class', 'btn btn-warning')
            ->onClick[] = [$this, 'formCancelled'];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  /**
   * @return Form
   */
  protected function createComponentRemoveForm() {
    $form = new Form;
    $form->addSubmit('cancel', 'Zrušiť')
          ->setHtmlAttribute('class', 'btn btn-warning')
          ->onClick[] = [$this, 'formCancelled'];
    $form->addSubmit('remove', 'Odstrániť')
          ->setHtmlAttribute('class', 'btn btn-danger')
          ->onClick[] = [$this, 'submittedRemoveForm'];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  /**
   * @param Form $form
   * @param $values
   * @throws AbortException
   */
  public function submittedAddForm(Form $form, $values): void
  {
    $this->guestRedirect();
    $this->noticesRepository->insert($values);
    $this->redirect('all#primary');
  }

  /**
   * @param SubmitButton $btn
   * @throws AbortException
   */
  public function submittedEditForm(SubmitButton $btn): void
  {
    $this->guestRedirect();
    $values = $btn->form->getValues();
    $this->noticeRow->update($values);
    $this->redirect('all');
  }

  /**
   * @throws AbortException
   */
  public function submittedRemoveForm(): void
  {
    $this->guestRedirect();
    $this->noticesRepository->softDelete((int)$this->noticeRow->id);
    $this->redirect('all');
  }

  /**
   * @throws AbortException
   */
  public function formCancelled(): void
  {
    $this->redirect('all');
  }

}
