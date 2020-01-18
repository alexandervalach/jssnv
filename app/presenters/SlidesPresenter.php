<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\BreadcrumbControl;
use App\Forms\ModalRemoveFormFactory;
use App\Forms\SlideFormFactory;
use App\Model\AlbumsRepository;
use App\Model\SectionsRepository;
use App\Model\SlidesRepository;
use Nette\Application\AbortException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;
use Nette\IOException;
use Nette\Utils\ArrayHash;

/**
 * Class SlidesPresenter
 * @package App\Presenters
 */
class SlidesPresenter extends BasePresenter
{
  /** @var ActiveRow */
  private $slideRow;

  /**
   * @var SlidesRepository
   */
  private $slidesRepository;

  /**
    * @var SlideFormFactory
    */
  private $slideFormFactory;

  /**
   * @var ModalRemoveFormFactory
   */
  private $modalRemoveFormFactory;

  /**
   * SlidesPresenter constructor.
   * @param AlbumsRepository $albumsRepository
   * @param SectionsRepository $sectionRepository
   * @param SlidesRepository $slidesRepository
   * @param SlideFormFactory $slideFormFactory
   * @param BreadcrumbControl $breadcrumbControl
   * @param ModalRemoveFormFactory $modalRemoveFormFactory
   */
  public function __construct(AlbumsRepository $albumsRepository,
                              SectionsRepository $sectionRepository,
                              SlidesRepository $slidesRepository,
                              SlideFormFactory $slideFormFactory,
                              BreadcrumbControl $breadcrumbControl,
                              ModalRemoveFormFactory $modalRemoveFormFactory)
  {
    parent::__construct($albumsRepository, $sectionRepository, $breadcrumbControl);
    $this->slidesRepository = $slidesRepository;
    $this->slideFormFactory = $slideFormFactory;
    $this->modalRemoveFormFactory = $modalRemoveFormFactory;
  }

  public function actionAll(): void
  {
    try {
      $this->guestRedirect();
    } catch (AbortException $e) {

    }
  }

  /**
   * Prepares data for render template
   */
  public function renderAll(): void
  {
    $this->template->slides = $this->slidesRepository->findAll();
    $this['breadcrumb']->add('Kurzy', 'Slides:all');
  }

  public function actionView(int $id): void
  {
    $this->slideRow = $this->slidesRepository->findById($id);
    if (!$this->slideRow) {
      $this->error(self::ITEM_NOT_FOUND);
    }
  }

  public function renderView(int $id): void
  {
    $this->template->slide = $this->slideRow;

    try {
      $this['breadcrumb']->add('Kurzy', $this->link('all'));
    } catch (InvalidLinkException $e) {

    }

    $this['breadcrumb']->add($this->slideRow->title);
  }

  /**
   * @return Form
   */
  protected function createComponentSlideForm(): Form
  {
    return $this->slideFormFactory->create(function (Form $form, ArrayHash $values) {
      $this->guestRedirect();
      $this->getParameter('id' ) ? $this->submittedEditForm($values) : $this->submittedAddForm($values);
    });
  }

  protected function createComponentRemoveForm(): Form
  {
    return $this->modalRemoveFormFactory->create(function (Form $form, ArrayHash $values) {
      $this->guestRedirect();
    });
  }

  /**
   * Insert new slide to database
   * @param ArrayHash $values
   * @throws AbortException
   */
  public function submittedAddForm(ArrayHash $values): void
  {
    $image = $values->image;
    $name = strtolower($image->getSanitizedName());

    try {
      if (!$image->isOk() || (!$image->isImage() && $image->getContentType() !== 'image/svg')) {
        throw new InvalidArgumentException;
      }

      if (!$image->move(self::IMAGE_FOLDER . '/' . $name)) {
        throw new IOException;
      }
    } catch (IOException $e) {
      $this->flashMessage(self::UPLOAD_ERROR, self::ERROR);
      $this->redirect('all');
    } catch (InvalidArgumentException $e) {
      $this->flashMessage(self::UPLOAD_ERROR, self::ERROR);
      $this->redirect('all');
    }

    $values->offsetUnset('image');
    $values->offsetSet('img', $name);

    $slide = $this->slidesRepository->insert($values);
    $this->flashMessage(self::ITEM_ADDED, self::INFO);
    $this->redirect('view', $slide->id);
  }

  /**
   * Updates item with new values
   * @param ArrayHash $values
   * @throws AbortException
   */
  public function submittedEditForm(ArrayHash $values): void
  {
    $this->slideRow->update($values);
    $this->flashMessage(self::ITEM_UPDATED, self::INFO);
    $this->redirect('view', $this->slideRow->id);
  }

}
