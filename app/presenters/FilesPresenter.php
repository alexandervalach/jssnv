<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\BreadcrumbControl;
use App\Forms\RemoveFormFactory;
use App\Forms\UploadFormFactory;
use App\Model\AlbumsRepository;
use App\Model\ContentsRepository;
use App\Model\SectionsRepository;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;

/**
 * Class FilesPresenter
 * @package App\Presenters
 */
class FilesPresenter extends BasePresenter
{
  /** @var ActiveRow */
  private $fileRow;

  /**
   * @var UploadFormFactory
   */
  private $uploadFormFactory;

  /**
   * @var ContentsRepository
   */
  private $filesRepository;

  /**
   * @var RemoveFormFactory
   */
  private $removeFormFactory;

  /**
   * FilesPresenter constructor.
   * @param AlbumsRepository $albumsRepository
   * @param SectionsRepository $sectionRepository
   * @param UploadFormFactory $uploadFormFactory
   * @param ContentsRepository $filesRepository
   * @param RemoveFormFactory $removeFormFactoryText
   * @param BreadcrumbControl $breadcrumbControl
   */
  public function __construct(AlbumsRepository $albumsRepository,
                              SectionsRepository $sectionRepository,
                              UploadFormFactory $uploadFormFactory,
                              ContentsRepository $filesRepository,
                              RemoveFormFactory $removeFormFactoryText,
                              BreadcrumbControl $breadcrumbControl)
  {
    parent::__construct($albumsRepository, $sectionRepository, $breadcrumbControl);
    $this->filesRepository = $filesRepository;
    $this->uploadFormFactory = $uploadFormFactory;
    $this->removeFormFactory = $removeFormFactoryText;
  }

  /**
   * @throws AbortException
   */
  public function actionAll(): void
  {
    $this->guestRedirect();
  }

  /**
   *
   */
  public function renderAll(): void
  {
    $this->template->files = $this->filesRepository->findAll()->order('name ASC');
    $this->template->fileFolder = $this->fileFolder;
  }

  /**
   * @param $id
   * @throws BadRequestException
   * @throws AbortException
   */
  public function actionRemove(int $id): void
  {
    $this->guestRedirect();
    $this->fileRow = $this->filesRepository->findById($id);

    if (!$this->fileRow) {
      throw new BadRequestException(self::FILE_NOT_FOUND);
    }

    $this->submittedFileRemoveForm();
  }

  /**
   * @return Form
   */
  protected function createComponentUploadForm (): Form
  {
    return $this->uploadFormFactory->create(function (Form $form, ArrayHash $values) {
      $this->guestRedirect();
      $this->filesRepository->softDelete((int)$this->fileRow->id);
      $this->redirect('all');
    });
  }

  /**
   * @return Form
   */
  protected function createComponentRemoveFileForm(): Form
  {
    return $this->removeFormFactory->create(function () {
      $this->guestRedirect();
      $this->filesRepository->softDelete((int)$this->fileRow->id);
      $this->flashMessage(self::ITEM_REMOVED, self::SUCCESS);
      $this->redirect('all');
    }, function () {
      $this->redirect('all');
    });
  }

  /**
   * @throws AbortException
   */
  public function formCancelled(): void
  {
    $this->redirect('Posts:view#primary', $this->fileRow->ref('posts', 'post_id'));
  }

  /**
   * @throws AbortException
   */
  public function submittedFileRemoveForm(): void
  {
    $this->guestRedirect();
    $id = $this->fileRow->ref('posts', 'post_id');
    $this->filesRepository->softDelete((int)$this->fileRow->id);
    $this->redirect('Posts:view#primary', $id);
  }

}
