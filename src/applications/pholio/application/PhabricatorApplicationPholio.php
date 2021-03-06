<?php

/**
 * @group pholio
 */
final class PhabricatorApplicationPholio extends PhabricatorApplication {

  public function shouldAppearInLaunchView() {
    // TODO: See getApplicationGroup().
    return false;
  }

  public function getBaseURI() {
    return '/pholio/';
  }

  public function getShortDescription() {
    return 'Design Review';
  }

  public function getIconName() {
    return 'pholio';
  }

  public function getTitleGlyph() {
    return "\xE2\x9D\xA6";
  }

  public function getFlavorText() {
    return pht('Things before they were cool.');
  }

  public function getApplicationGroup() {
    // TODO: Move to CORE, this just keeps it out of the side menu.
    return self::GROUP_COMMUNICATION;
  }

  public function getRoutes() {
    return array(
      '/M(?P<id>[1-9]\d*)' => 'PholioMockViewController',
      '/pholio/' => array(
        '' => 'PholioMockListController',
        'view/(?P<view>\w+)/'   => 'PholioMockListController',
        'new/'                  => 'PholioMockEditController',
        'edit/(?P<id>\d+)/'     => 'PholioMockEditController',
        'comment/(?P<id>\d+)/'  => 'PholioMockCommentController',
      ),
    );
  }

}
