<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\BreadcrumbControl;
use App\Forms\SearchFormFactory;
use App\Model\AlbumsRepository;
use App\Model\SectionsRepository;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Nette\Security\User;

/**
 * Base presenter for all application Presenters.
 */
abstract class BasePresenter extends Presenter
{
  const TEST_NOT_FOUND = 'Test neexistuje!';
  const ITEM_NOT_FOUND = 'Položka neexistuje!';
  const FORBIDDEN = 'Action not allowed!';
  const ITEM_ADDED = 'Položka bola pridaná.';
  const ITEM_UPDATED = 'Položka bola upravená.';
  const ITEM_REMOVED = 'Položka bola odstránená.';
  const ITEMS_ADDED = 'Položky boli nahraté.';
  const USER_ADDED = 'Používateľ bol pridaný.';
  const FILE_NOT_FOUND = 'Súbor nebol nájdený.';
  const SUCCESS = 'success';
  const ERROR = 'danger';
  const INFO = 'info';
  const IMAGE_FOLDER = 'images';
  const FILE_FOLDER = 'files';
  const UNKNOWN_ERROR = 'Something went wrong';

  /** @var AlbumsRepository */
  protected $albumsRepository;

  /** @var SectionsRepository */
  protected $sectionsRepository;

  /** @var ArrayHash */
  protected $sections;

  /**
   * @var BreadcrumbControl
   */
  private $breadcrumbControl;
  
  /**
   * @var SearchFormFactory
   */
  private $searchForm;

  /**
   * BasePresenter constructor.
   * @param AlbumsRepository $albumsRepository
   * @param SectionsRepository $sectionRepository
   * @param BreadcrumbControl $breadcrumbControl
   * @param SearchFormFactory $searchForm
   */
  public function __construct(AlbumsRepository $albumsRepository,
                              SectionsRepository $sectionRepository,
                              BreadcrumbControl $breadcrumbControl,
                              SearchFormFactory $searchForm)
  {
    parent::__construct();
    $this->albumsRepository = $albumsRepository;
    $this->sectionsRepository = $sectionRepository;
    $this->breadcrumbControl = $breadcrumbControl;
    $this->searchForm = $searchForm;
  }

  public function startup()
  {
    parent::startup();

    if (!$this->user->isLoggedIn()) {
      /*
       * NOT IMPLEMENTED PROPERLY
      if ($this->user->getLogoutReason() === User::INACTIVITY) {
        $this->flashMessage('Boli ste odhlásený z dôvodu nečinnosti.', self::INFO);
      }
      */
    }

    if (!$this->getUser()->isAllowed($this->name, $this->action)) {
      $this->redirect('Homepage:');
    }
  }

  /**
   * Loads common data for every page
   */
  public function beforeRender()
  {
    $this->template->imgFolder = self::IMAGE_FOLDER;
    $this->template->fileFolder = self::FILE_FOLDER;
  }

  /**
   * Redirects guest to homepage
   * @throws AbortException
   */
  protected function guestRedirect()
  {
    if (!$this->user->isLoggedIn()) {
      $this->redirect('Sign:in');
    }
  }

  /**
   * @param $id
   * @param $currentUserRole
   * @param $privilegedUserRole
   * @throws ForbiddenRequestException
   */
  protected function userIsAllowed($id, string $currentUserRole, string $privilegedUserRole)
  {
    if ($currentUserRole != $privilegedUserRole) {
      if ($this->user->id != $id) {
        throw new ForbiddenRequestException(self::FORBIDDEN);
      }
    }
  }

  /***
   * Generates breadcrumb control
   * @return BreadcrumbControl
   */
  protected function createComponentBreadcrumb(): BreadcrumbControl
  {
    return new BreadcrumbControl();
  }

  protected function createComponentSearchForm(): Form
  {
    return $this->searchForm->create(function (Form $form, ArrayHash $values) {
      $this->redirect('Search:', $values->expression);
    });
  }

}
