<?php


class IndexController extends Controller {




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

		$page_template = new Template('main');

		$page_content = '';
		if ( !$user ) {
			$login_template = new Template('util/menu-loggedout');
			$login_template->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $login_template->render();

		} else {

			$menu_template = new Template('util/menu-loggedin');
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
								('.(int)$idUser.', "'.MCalcUtil::dbescape($listname).'")
							;
						';
						MCalcUtil::dbquery($sql);
					}
					break;
				case 'deleteList':
					$idList = !empty($_REQUEST['idList']) ? (int)$_REQUEST['idList'] : false;
					if ( !empty($idList) ) {
						$sql = '
							DELETE FROM `user_List` WHERE `idUser` = '.(int)$idUser.' AND `idList` = '.(int)$idList.';
						';
						MCalcUtil::dbquery($sql);
					}
				default:
					break;
			}

			$list_list_template = new Template('util/list-list');
			$list_list_template->assign('lists', $user->getLists());
			$list_list_template->assign('headline', 'Deine Listen');
			$list_list_template->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $list_list_template->render();


			$list_create_template = new Template('util/list-create-form');
			$list_create_template->assign('lists', $user->getLists());
			$list_create_template->assign('headline', 'Weitere Liste erstellen');
			$list_create_template->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $list_create_template->render();
		}

		$page_template->assign('page_content', $page_content);
		$page_template->assign('header_styles', $header_styles);
		echo $page_template->render();

	}

}