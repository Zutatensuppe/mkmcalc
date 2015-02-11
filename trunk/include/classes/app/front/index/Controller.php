<?php
namespace app\front\index;

use system\abstr\Controller as AbstractController;
use system\Auth as Auth;
use system\Session as Session;
use system\User as User;
use system\HtmlView as HtmlView;
use system\Config as Config;
use system\Database as Database;

class Controller extends AbstractController {




	public function indexAction() {

		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : false;
		switch ($action) {
			case 'login':
				$loginId = !empty($_REQUEST['loginId']) ? $_REQUEST['loginId'] : false;
				$password = !empty($_REQUEST['password']) ? $_REQUEST['password'] : false;

				if ( empty($loginId) || empty($password) ) {
					/// error ... bitte was eingebem
				} else {
					if ( Auth::authenticate($loginId, $password) ) {
						// ok!
						$user = Auth::user();
						Session::setUserId($user->getId());
					} else {
						// not ok!
					}
				}
				break;
			case 'logout':
				Session::destroy();
				Auth::reset();
				break;
			default:
				break;
		}


		$user = Auth::user();



		$header_styles = array();

		$page_template = new HtmlView('main');

		$page_content = '';
		if ( !$user ) {
			$login_template = new HtmlView('util/menu-loggedout');
			$login_template->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $login_template->render();

		} else {

			$menu_template = new HtmlView('util/menu-loggedin');
			$menu_template->assign('action_url', $this->dispatcher->getUrl());
			$menu_template->assign('username', $user->getName());

			$menu_template->assign('menuitems', array(
				array('link' => Config::get('siteurl').'/', 'linktext' => 'Wishlists')
			));

			$page_content .= $menu_template->render();

			$header_styles[] = Config::get('siteurl').'/assets/css/style.css';

			$idUser = $user->getId();


			$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : false;
			switch ( $action ) {
				case 'addList':
					$listname = !empty($_REQUEST['listname']) ? $_REQUEST['listname'] : false;
					if ( !empty($listname) ) {

						$sql = '
							INSERT INTO
								`user_List`
								(`idUser`, `name`)
							VALUES
								('.(int)$idUser.', "'.Database::instance()->escape($listname).'")
							;
						';
						Database::instance()->query($sql);
					}
					break;
				case 'deleteList':
					$idList = !empty($_REQUEST['idList']) ? (int)$_REQUEST['idList'] : false;
					if ( !empty($idList) ) {
						$sql = '
							DELETE FROM `user_List` WHERE `idUser` = '.(int)$idUser.' AND `idList` = '.(int)$idList.';
						';
						Database::instance()->query($sql);
					}
				default:
					break;
			}

			$tmpl = new HtmlView('util/list-list');
			$tmpl->assign('lists', $user->getLists());
			$tmpl->assign('headline', 'Deine Listen');
			$tmpl->assign('siteurl', Config::get('siteurl'));
			$tmpl->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $tmpl->render();


			$tmpl = new HtmlView('util/list-create-form');
			$tmpl->assign('lists', $user->getLists());
			$tmpl->assign('headline', 'Weitere Liste erstellen');
			$tmpl->assign('siteurl', Config::get('siteurl'));
			$tmpl->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $tmpl->render();
		}

		$page_template->assign('page_content', $page_content);
		$page_template->assign('header_styles', $header_styles);
		echo $page_template->render();

	}

}