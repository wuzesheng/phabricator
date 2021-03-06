<?php

final class PhabricatorMacroListController
  extends PhabricatorMacroController {

  public function processRequest() {

    $request = $this->getRequest();
    $viewer = $request->getUser();

    $macro_table = new PhabricatorFileImageMacro();

    $filter = $request->getStr('name');
    if (strlen($filter)) {
      $macros = $macro_table->loadAllWhere(
        'name LIKE %~',
        $filter);

      $nodata = pht(
        'There are no macros matching the filter "%s".',
        phutil_escape_html($filter));
    } else {
      $pager = new AphrontPagerView();
      $pager->setOffset($request->getInt('page'));

      $macros = $macro_table->loadAllWhere(
        '1 = 1 ORDER BY id DESC LIMIT %d, %d',
        $pager->getOffset(),
        $pager->getPageSize());

      // Get an exact count since the size here is reasonably going to be a few
      // thousand at most in any reasonable case.
      $count = queryfx_one(
        $macro_table->establishConnection('r'),
        'SELECT COUNT(*) N FROM %T',
        $macro_table->getTableName());
      $count = $count['N'];

      $pager->setCount($count);
      $pager->setURI($request->getRequestURI(), 'page');

      $nodata = pht('There are no image macros yet.');
    }

    $file_phids = mpull($macros, 'getFilePHID');

    $files = array();
    if ($file_phids) {
      $files = id(new PhabricatorFile())->loadAllWhere(
        "phid IN (%Ls)",
        $file_phids);
      $author_phids = mpull($files, 'getAuthorPHID', 'getPHID');

      $this->loadHandles($author_phids);
    }
    $files_map = mpull($files, null, 'getPHID');

    $filter_form = id(new AphrontFormView())
      ->setMethod('GET')
      ->setUser($request->getUser())
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setName('name')
          ->setLabel('Name')
          ->setValue($filter))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue('Filter Image Macros'));

    $filter_view = new AphrontListFilterView();
    $filter_view->appendChild($filter_form);

    $has_search = strlen($filter);
    $nav = $this->buildSideNavView(
      $for_app = false,
      $has_search);
    $nav->selectFilter($has_search ? 'search' : '/');

    $nav->appendChild($filter_view);

    $pinboard = new PhabricatorPinboardView();
    $pinboard->setNoDataString($nodata);
    foreach ($macros as $macro) {
      $file_phid = $macro->getFilePHID();
      $file = idx($files_map, $file_phid);

      $item = new PhabricatorPinboardItemView();
      if ($file) {
        $item->setImageURI($file->getThumb220x165URI());
        $item->setImageSize(220, 165);
        if ($file->getAuthorPHID()) {
          $author_handle = $this->getHandle($file->getAuthorPHID());
          $item->appendChild(
            'Created by '.$author_handle->renderLink());
        }
        $datetime = phabricator_date($file->getDateCreated(), $viewer);
        $item->appendChild(
          phutil_render_tag(
            'div',
            array(),
            'Created on '.$datetime));
      }
      $item->setURI($this->getApplicationURI('/view/'.$macro->getID().'/'));
      $item->setHeader($macro->getName());

      $pinboard->addItem($item);
    }
    $nav->appendChild($pinboard);

    if (!strlen($filter)) {
      $nav->appendChild($pager);
      $name = pht('All Macros');
    } else {
      $name = pht('Search');
    }

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addCrumb(
      id(new PhabricatorCrumbView())
        ->setName($name)
        ->setHref($request->getRequestURI()));
    $nav->setCrumbs($crumbs);

    return $this->buildApplicationPage(
      $nav,
      array(
        'device' => true,
        'title' => 'Image Macros',
      ));
  }
}
