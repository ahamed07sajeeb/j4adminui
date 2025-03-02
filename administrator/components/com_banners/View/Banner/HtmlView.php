<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Banners\Administrator\View\Banner;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Banners\Administrator\Model\BannerModel;

/**
 * View to edit a banner.
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @throws  Exception
	 */
	public function display($tpl = null): void
	{
		/** @var BannerModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @throws  Exception
	 */
	protected function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user       = Factory::getUser();
		$userId     = $user->id;
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		// Since we don't track these assets at the item level, use the category id.
		$canDo = ContentHelper::getActions('com_banners', 'category', $this->item->catid);
		
		// toolbar
		$toolbar = Toolbar::getInstance();
		
		ToolbarHelper::title($isNew ? Text::_('COM_BANNERS_MANAGER_BANNER_NEW') : Text::_('COM_BANNERS_MANAGER_BANNER_EDIT'), 'bookmark banners');

		if (!empty($this->item->id) && ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit'))
		{
			ToolbarHelper::versions('com_banners.banner', $this->item->id);
		}
		
		// help button
		$toolbar->help('JHELP_COMPONENTS_BANNERS_BANNERS_EDIT');

		// cancel / close button
		if (empty($this->item->id))
		{
			$toolbar->cancel('banner.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			$toolbar->cancel('banner.cancel', 'JTOOLBAR_CLOSE');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || count($user->getAuthorisedCategories('com_banners', 'core.create')) > 0))
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			if($isNew) {
				$saveGroup = $toolbar->dropdownButton('save-group');
				$saveGroup->configure(
					function (Toolbar $childBar) use ($canDo)
					{
						$childBar->apply('banner.apply');
						$childBar->save('banner.save');
						$childBar->save2new('banner.save2new');
					}
				);
			} else {
				$saveGroup = $toolbar->dropdownButton('save-group');
				$saveGroup->configure(
					function (Toolbar $childBar) use ($itemEditable, $canDo)
					{
						// Can't do this action if it's editable
						if ($itemEditable)
						{
							$childBar->apply('banner.apply');
							$childBar->save('banner.save');

							// We can save this record, but check the create permission to see if we can return to make a new one.
							if ($canDo->get('core.create'))
							{
								$childBar->save2new('banner.save2new');
							}
						}
					}
				);
			}
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			$saveGroup->configure(
				function (Toolbar $childBar)
				{
					$childBar->save2copy('banner.save2copy');
				}
			);	
		}

	}
}
