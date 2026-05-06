<?php

namespace WilokeListingTools\Framework\Routing;

use WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\General;

abstract class Controller
{
	/*
	 * As the default, it always die if the payment parameter is wrong, but some cases, we simply return false;
	 */
	public    $isNotDieIfFalse;
	protected $aMiddleware = [];

	protected function isWP53()
	{
		global $wp_version;
		$status = version_compare($wp_version, '5.2', '>') &&
			(
				!function_exists('kc_admin_enable') ||
				(function_exists('kc_admin_enable') && !kc_admin_enable())
			)
			&& !class_exists('Tinymce_Advanced')
			&& !function_exists('classic_editor_addon_post_init')
			&& !class_exists('Classic_Editor')
			&& !class_exists('DisableGutenberg');

		if ($status && isset($_POST['post_ID'])) {
			$aData = get_post_meta($_POST['post_ID'], 'kc_data', true);

			$status = !is_array($aData) || $aData['mode'] !== 'kc';
		}

		return $status;
	}

	protected function isAdminQuery()
	{
        $status = false;
		if (is_admin() && !wp_doing_ajax()) {
            $status = true;
		} else {
            if (wp_doing_ajax()) {
                if (isset($_POST['action'])) {
                    $action = $_POST['action'];
                } elseif (isset($_GET['action'])) {
                    $action = $_GET['action'];
                }

                if (!isset($action) || (strpos($action, 'wilcity') === false && strpos($action, 'wil_') !== 0)) {
                    $status = true;
                }
            }

        }
		// only use filter through ajax. It helps to resolve 404 error
		if (!$status && function_exists('wilcityIsSearchPage') && wilcityIsSearchPage()) {
			$status = true;
		}

		return apply_filters('wiloke-listing-tools/filter/app/Framework/Routing/Controller/After/isAdminQuery', $status);
	}

	public function isDisableMetaBlock($aParams)
	{
		if (defined('WILCITY_SHOW_ALL_META_BLOCKS') && WILCITY_SHOW_ALL_META_BLOCKS) {
			return false;
		}

		$postType = $this->getCurrentAdminPostType();
		$oAddListingSkeleton = new AddListingFieldSkeleton($postType);

		if (empty($oAddListingSkeleton->getField($aParams['fieldKey']))) {
			return true;
		}

		if (isset($aParams['param'])) {
			return empty($oAddListingSkeleton->getFieldParam($aParams['fieldKey'], $aParams['param']));
		}

		return false;
	}

	protected function createWPQuery($aArgs)
	{
		if (class_exists('\WilcityRedis\Controllers\SearchController')) {
			$result = apply_filters(
				'wilcity/filter/get-query-values',
				null,
				$aArgs
			);

			if (!empty($result)) {
				return $result;
			}
		}

		return new \WP_Query($aArgs);
	}

	protected function checkAdminReferrer()
	{
		if (isset($_GET['meta-box-loader'])) {
			return check_admin_referer('meta-box-loader', 'meta-box-loader-nonce');
		}

		if (isset($_REQUEST['_locale']) && current_user_can('administrator')) {
			return true;
		}

		if (!isset($_REQUEST['wilcity_admin_nonce_field']) || !General::isAdmin()) {
			return false;
		}

		return check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field');
	}

	protected function isAdmin()
	{
		return (isset($_POST['action']) && $_POST['action'] == 'editpost') ||
			(isset($_GET['_locale']) && $_GET['_locale'] == 'user');
	}

	protected function isAdminEditing()
	{
		return (isset($_GET['meta-box-loader']) && check_admin_referer('meta-box-loader', 'meta-box-loader-nonce')) &&
			(isset($_POST['action']) && $_POST['action'] == 'editpost') &&
			isset($_GET['action']) && $_GET['action'] === 'edit';
	}

	protected function isSavedPostMeta()
	{
		return (isset($_GET['meta-box-loader']) && check_admin_referer('meta-box-loader', 'meta-box-loader-nonce')) &&
			(isset($_POST['action']) && $_POST['action'] == 'editpost');
	}

	protected function isPostType($postType)
	{
		if (!isset($_GET['post']) || get_post_type($_GET['post']) !== $postType) {
			return false;
		}

		return true;
	}

	protected function isGroup($group)
	{
		if (!isset($_GET['post']) || !General::isPostTypeInGroup(get_post_type($_GET['post']), $group)) {
			return false;
		}

		return true;
	}

	protected function getCurrentAdminPostType()
	{
		if (!is_admin()) {
			return null;
		}

		if (isset($_GET['post_type'])) {
			return $_GET['post_type'];
		}

		if (isset($_GET['post']) && !empty($_GET['post'])) {
			return get_post_type($_GET['post']);
		}

		return null;
	}

	protected function isCurrentAdminListingType($excludeEvents = false): bool
    {
		$postType = $this->getCurrentAdminPostType();
		if (empty($postType)) {
			return false;
		}

		$aListingTypes = General::getPostTypeKeys(false, $excludeEvents);

		return in_array($postType, $aListingTypes);
	}

	private function isBoolean($aOptions): bool
	{
		return (isset($aOptions['isBoolean']) &&
				($aOptions['isBoolean'] == 'yes' || $aOptions['isBoolean'] === true)) ||
			(isset($aOptions['isApp']) && ($aOptions['isApp'] == 'yes' || $aOptions['isApp'] === true));
	}

	public function middleware($aMiddleware, array $aOptions = [], $returnType = '')
	{
		if ((!DebugStatus::status('WILOKE_LISTING_TOOLS_CHECK_EVEN_ADMIN') && current_user_can('administrator')) ||
			DebugStatus::status('WILOKE_LISTING_TOOLS_PASSED_MIDDLEWARE')
		) {
			if ($this->isBoolean($aOptions)) {
				return true;
			}

			return [
				'status' => 'success'
			];
		}

		if (empty($returnType)) {
			if (isset($aOptions['isAjax']) || wp_doing_ajax()) {
				$returnType = 'ajax';
			} else if (isset($aOptions['isRedirect'])) {
				$returnType = 'redirect';
			} else if ($this->isBoolean($aOptions)) {
				$returnType = 'bool';
			} elseif (isset($aOptions['isTryCatch'])) {
				$returnType = 'execption';
			} else {
				$returnType = 'normal';
			}
		}

		/*
		 * All Controller must be passed this middleware first
		 */
		do_action('wiloke-listing-tools/top-middleware');

		$msg = esc_html__('You do not have permission to access this page', 'wiloke-listing-tools');
		$aOptions['userID'] = isset($aOptions['userID']) ? $aOptions['userID'] : get_current_user_id();
		$response = '';

		foreach ($aMiddleware as $middleware) {
			$middlewareClass = $this->getMiddleware($middleware);
			if (class_exists($middlewareClass)) {
				$instMiddleware = new $middlewareClass;
				$status = $instMiddleware->handle($aOptions);

				if (!$status) {
					$msg = property_exists($instMiddleware, 'msg') ? $instMiddleware->msg : $msg;

					switch ($returnType) {
						case 'ajax':
							wp_send_json_error(
								[
									'msg' => $msg
								]
							);
							break;
						case 'redirect':
							$url = property_exists($instMiddleware, 'redirectTo') ? $instMiddleware->redirectTo : null;
							Redirector::to($url);
							break;
						case 'bool':
							$response = false;
							break;
						case 'exception':
							throw new \Exception($msg);
						default:
							$response = [
								'status' => 'error',
								'msg'    => $msg
							];
							break;
					}

					return $response;
				}
			} else {
				switch ($returnType) {
					case 'ajax':
						wp_send_json_error(
							[
								'msg' => sprintf(
									esc_html__("Class %s does not exists", 'wiloke-listing-tools'),
									$middleware
								)
							]
						);
						break;
					case 'bool':
						$response = false;
						break;
					case 'exception':
						throw new \Exception(printf(
							esc_html__("Class %s does not exists", 'wiloke-listing-tools'),
							$middleware
						));
					case 'rest':
						$response = [
							'status' => 'error',
							'msg'    => sprintf(
								esc_html__("Class %s does not exists", 'wiloke-listing-tools'),
								$middleware
							)
						];
						break;
				}

				return $response;
			}
		}

		if ($this->isBoolean($aOptions)) {
			return true;
		}

		return [
			'status' => 'success'
		];
	}

	public function validate($aInput, $aRules)
	{
		foreach ($aRules as $name => $rule) {
			switch ($rule) {
				case 'required':
					if (!isset($aInput[$name]) || empty($aInput[$name])) {
						if (wp_doing_ajax()) {
							wp_send_json_error(
								[
									'msg' => sprintf(esc_html__("The %s is required", 'wiloke-listing-tools'), $name)
								]
							);
						} else {
							throw new \Exception(esc_html__(
								"The %s is required",
								'wiloke-listing-tools'
							));
						}
					}
					break;
				case 'email':
					if (!isset($aInput[$name]) || empty($aInput[$name]) || !is_email($aInput[$name])) {
						if (wp_doing_ajax()) {
							wp_send_json_error(
								[
									'msg' => sprintf(esc_html__(
										"You provided an invalid email address",
										'wiloke-listing-tools'
									), $name)
								]
							);
						} else {
							throw new \Exception(esc_html__(
								"You provided an invalid email address",
								'wiloke-listing-tools'
							));
						}
					}
					break;
				default:
					do_action('wiloke-listing-tools/app/Framework/Routing/Controller/validate', $aInput, $name, $rule);
					break;
			}
		}
	}

	public function getMiddleware($middleware)
	{
		return wilokeListingToolsRepository()->get('middleware:' . $middleware);
	}

	/**
	 * Handle Calls to missing methods on the control
	 *
	 * @param array $aParameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $aParameters)
	{
		throw new \BadMethodCallException(esc_html__("Method [{{$method}}] does not exist", 'wiloke'));
	}
}
