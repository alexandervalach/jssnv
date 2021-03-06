<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\BreadcrumbControl;
use App\Components\RemoveModalControl;
use App\Forms\ModalRemoveFormFactory;
use App\Forms\MultiUploadFormFactory;
use App\Forms\SearchFormFactory;
use App\Helpers\ImageHelper;
use App\Model\AlbumsRepository;
use App\Model\ImagesRepository;
use App\Model\SectionsRepository;
use App\Forms\AlbumFormFactory;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Nette\InvalidArgumentException;
use Nette\IOException;
use Nette\Utils\ArrayHash;

/**
 * Class AlbumPresenter
 * @package App\Presenters
 */
class AlbumsPresenter extends BasePresenter
{
  /** @var ActiveRow */
  private $albumRow;

  /** @var AlbumFormFactory */
  private $albumFormFactory;

  /**
   * @var ImagesRepository
   */
  private $imagesRepository;

  /**
   * @var MultiUploadFormFactory
   */
  private $multiUploadFormFactory;

  /**
   * @var ModalRemoveFormFactory
   */
  private $removeFormFactory;

  /**
   * AlbumsPresenter constructor.
   * @param AlbumsRepository $albumsRepository
   * @param SectionsRepository $sectionRepository
   * @param BreadcrumbControl $breadcrumbControl
   * @param SearchFormFactory $searchFormFactory
   * @param AlbumFormFactory $albumFormFactory
   * @param MultiUploadFormFactory $multiUploadFormFactory
   * @param ImagesRepository $imagesRepository
   * @param ModalRemoveFormFactory $removeFormFactory
   */
  public function __construct(AlbumsRepository $albumsRepository,
                              SectionsRepository $sectionRepository,
                              BreadcrumbControl $breadcrumbControl,
                              SearchFormFactory $searchFormFactory,
                              AlbumFormFactory $albumFormFactory,
                              MultiUploadFormFactory $multiUploadFormFactory,
                              ImagesRepository $imagesRepository,
                              ModalRemoveFormFactory $removeFormFactory)
  {
    parent::__construct($albumsRepository, $sectionRepository, $breadcrumbControl, $searchFormFactory);
    $this->imagesRepository = $imagesRepository;
    $this->albumFormFactory = $albumFormFactory;
    $this->multiUploadFormFactory = $multiUploadFormFactory;
    $this->removeFormFactory = $removeFormFactory;
  }

  /**
   * Prepares data for all template
   */
  public function actionAll(): void
  {
    $this['breadcrumb']->add('Fotogaléria');
  }

  /**
   * Passes data to all template
   */
  public function renderAll(): void
  {
    $this->template->albums = $this->albumsRepository->findAll();
  }

  /**
   * @param $id
   * @throws BadRequestException
   * @throws AbortException
   * @throws InvalidLinkException
   */
  public function actionView(int $id): void
  {
    $this->guestRedirect();
    $this->albumRow = $this->albumsRepository->findById($id);

    if (!$this->albumRow) {
      throw new BadRequestException(self::ITEM_NOT_FOUND);
    }

    $this['breadcrumb']->add('Fotogaléria', $this->link('all'));
    $this['breadcrumb']->add($this->albumRow->label);
    $this['albumForm']->setDefaults($this->albumRow);
  }

  /**
   * Passes data to view template
   * @param $id
   */
  public function renderView(int $id): void
  {
    $this->template->album = $this->albumRow;
    $this->template->images = $this->albumRow->related('images')->where('is_present', 1);
  }

  /**
   * Generates album form
   * @return Form
   */
  protected function createComponentAlbumForm(): Form
  {
    return $this->albumFormFactory->create(function (Form $form, ArrayHash $values) {
      $this->guestRedirect();
      $this->getParameter('id') ? $this->submittedEditForm($values) : $this->submittedAddForm($values);
    });
  }

  /**
   * Generates upload form
   * @return Form
   */
  protected function createComponentUploadForm(): Form
  {
    return $this->multiUploadFormFactory->create(function (Form $form, ArrayHash $values) {
      $this->guestRedirect();
      $this->submittedUploadForm($values);
    });
  }

  /**
   * Generates remove form
   * @return Form
   */
  protected function createComponentRemoveForm(): Form
  {
    return $this->removeFormFactory->create(function () {
      $this->guestRedirect();
      $this->submittedRemoveForm();
    });
  }

  /**
   * @param ArrayHash $values
   * @throws AbortException
   */
  private function submittedUploadForm(ArrayHash $values): void
  {
    if ($values->images) {
      $imageNames = [];
      try {
        $imageNames = ImageHelper::uploadImages($values->images);
      } catch (InvalidArgumentException $e) {
        $this->flashMessage($e->getMessage(), self::ERROR);
        $this->redirect('view', $this->albumRow->id);
      } catch (IOException $e) {
        $this->flashMessage($e->getMessage(), self::ERROR);
        $this->redirect('view', $this->albumRow->id);
      }

      $data = [];
      foreach ($imageNames as $imageName) {
        $data[] = [
          'name' => $imageName,
          'album_id' => $this->albumRow->id
        ];
      }

      $this->imagesRepository->insert($data);

      $this->flashMessage(self::ITEMS_ADDED, self::INFO);
    } else {
      $this->flashMessage('Neboli pridané žiadne nové obrázky', self::INFO);
    }
    $this->redirect('view', $this->albumRow->id);
  }

  /**
   * @param ArrayHash $values
   * @throws AbortException
   */
  private function submittedAddForm(ArrayHash $values): void
  {
    $album = $this->albumsRepository->insert($values);
    $this->flashMessage(self::ITEM_ADDED, self::INFO);
    $this->redirect('view', $album->id);
  }

  /**
   * @param ArrayHash $values
   * @throws AbortException
   */
  public function submittedEditForm(ArrayHash $values): void
  {
    $this->albumRow->update($values);
    $this->flashMessage(self::ITEM_UPDATED, self::INFO);
    $this->redirect('view', $this->albumRow->id);
  }

  /**
   * @throws AbortException
   */
  public function submittedRemoveForm(): void
  {
    $images = $this->imagesRepository->findForAlbum((int)$this->albumRow->id);
    foreach ($images as $image) {
      $this->imagesRepository->softDelete($image);
    }
    $this->albumsRepository->softDelete($this->albumRow);
    $this->flashMessage(self::ITEM_REMOVED, self::INFO);
    $this->redirect('all');
  }
}
